<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script debe ejecutarse desde CLI.\n";
    exit(1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$loadSimpleEnv = function ($path) {
    if (!is_file($path)) {
        return;
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
};

$loadSimpleEnv(__DIR__ . '/.whatsapp.env');

$webhookUrl = getenv('WHATSAPP_REMINDER_WEBHOOK_URL') ?: '';
$safeMode = (int)(getenv('WHATSAPP_REMINDER_SAFE_MODE') ?: 0) === 1;
$hoursThrottle = (int)(getenv('WHATSAPP_REMINDER_THROTTLE_HOURS') ?: 6);
if ($hoursThrottle <= 0) {
    $hoursThrottle = 6;
}

$show = function ($text) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
};

$hasAlarmColumns = function () use ($pdo) {
    $required = ['alarma_activa', 'alarma_dias', 'alarma_ultimo_aviso', 'alarma_whatsapp_destino'];
    try {
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resultados_examenes'");
        $cols = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($required as $col) {
            if (!in_array($col, $cols, true)) {
                return false;
            }
        }
        return true;
    } catch (\Exception $e) {
        return false;
    }
};

$getCompanyWhatsapp = function () use ($pdo) {
    try {
        $stmt = $pdo->query("SELECT redes_sociales FROM config_empresa ORDER BY id DESC LIMIT 1");
        $raw = $stmt->fetchColumn();
        if (!$raw) {
            return null;
        }
        $redes = json_decode((string)$raw, true);
        if (!is_array($redes)) {
            return null;
        }
        foreach ($redes as $red) {
            if (!is_array($red)) {
                continue;
            }
            $nombre = strtolower(trim((string)($red['nombre'] ?? '')));
            if ($nombre !== 'whatsapp') {
                continue;
            }
            $digits = preg_replace('/\D+/', '', (string)($red['url'] ?? ''));
            if ($digits !== '') {
                return $digits;
            }
        }
    } catch (\Exception $e) {
    }
    return null;
};

$sendWebhook = function ($url, array $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return [false, $err];
    }
    if ($status < 200 || $status >= 300) {
        return [false, 'HTTP ' . $status . ' - ' . substr((string)$resp, 0, 250)];
    }

    return [true, substr((string)$resp, 0, 250)];
};

if (!$hasAlarmColumns()) {
    $show('No se encontraron columnas de alarmas. Ejecuta sql/agregar_alarmas_resultados_examenes.sql primero.');
    exit(1);
}

$defaultWhatsapp = $getCompanyWhatsapp();
if (!$defaultWhatsapp) {
    $show('No se encontró WhatsApp en config_empresa.redes_sociales.');
    exit(1);
}

$stmtPend = $pdo->prepare("SELECT
        re.id,
        re.id_cotizacion,
        re.id_examen,
        re.id_cliente,
        re.fecha_ingreso,
        re.alarma_dias,
        re.alarma_ultimo_aviso,
        COALESCE(NULLIF(re.alarma_whatsapp_destino,''), :ws_default) AS whatsapp_destino,
        e.nombre AS examen_nombre,
        CONCAT(COALESCE(c.nombre,''), ' ', COALESCE(c.apellido,'')) AS paciente,
        CASE
            WHEN NOW() > DATE_ADD(re.fecha_ingreso, INTERVAL re.alarma_dias DAY) THEN 'vencido'
            WHEN NOW() >= DATE_ADD(re.fecha_ingreso, INTERVAL GREATEST(re.alarma_dias - 1, 0) DAY) THEN 'por_vencer'
            ELSE 'en_tiempo'
        END AS alarma_estado_calc,
        DATE_ADD(re.fecha_ingreso, INTERVAL re.alarma_dias DAY) AS fecha_objetivo
    FROM resultados_examenes re
    JOIN examenes e ON e.id = re.id_examen
    JOIN clientes c ON c.id = re.id_cliente
    WHERE (re.estado IS NULL OR re.estado <> 'completado')
      AND re.alarma_activa = 1
      AND re.alarma_dias IS NOT NULL
      AND re.alarma_dias > 0
      AND (
        re.alarma_ultimo_aviso IS NULL
        OR TIMESTAMPDIFF(HOUR, re.alarma_ultimo_aviso, NOW()) >= :throttle
      )
      AND (
        NOW() > DATE_ADD(re.fecha_ingreso, INTERVAL re.alarma_dias DAY)
        OR NOW() >= DATE_ADD(re.fecha_ingreso, INTERVAL GREATEST(re.alarma_dias - 1, 0) DAY)
      )
    ORDER BY re.fecha_ingreso ASC
    LIMIT 300");

$stmtPend->execute([
    'ws_default' => $defaultWhatsapp,
    'throttle' => $hoursThrottle,
]);

$rows = $stmtPend->fetchAll(\PDO::FETCH_ASSOC);
if (!$rows) {
    $show('Sin recordatorios para enviar.');
    exit(0);
}

$show('Recordatorios detectados: ' . count($rows));
if ($safeMode) {
    $show('MODO SEGURO ACTIVO: no se enviarán WhatsApp ni se actualizarán alarmas_ultimo_aviso.');
}
if (!$safeMode && $webhookUrl === '') {
    $show('WHATSAPP_REMINDER_WEBHOOK_URL no configurado. Se listan mensajes sugeridos (modo vista previa).');
}

$stmtUpd = $pdo->prepare("UPDATE resultados_examenes
    SET alarma_ultimo_aviso = NOW(),
        alarma_estado = :alarma_estado
    WHERE id = :id");

$sent = 0;
$failed = 0;
$preview = 0;
foreach ($rows as $row) {
    $to = preg_replace('/\D+/', '', (string)($row['whatsapp_destino'] ?? ''));
    if ($to === '') {
        $failed++;
        $show('Sin destino válido para resultado #' . (int)$row['id']);
        continue;
    }

    $msg = sprintf(
        "Recordatorio de laboratorio\nPaciente: %s\nExamen: %s\nCotización: #%d\nEstado: %s\nFecha objetivo: %s",
        trim((string)($row['paciente'] ?? 'Paciente')),
        trim((string)($row['examen_nombre'] ?? 'Examen')),
        (int)$row['id_cotizacion'],
        strtoupper((string)$row['alarma_estado_calc']),
        (string)$row['fecha_objetivo']
    );

    if ($safeMode) {
        $preview++;
        $show('SAFE PREVIEW #' . (int)$row['id'] . ' -> ' . $msg);
        continue;
    }

    if ($webhookUrl !== '') {
        [$ok, $detail] = $sendWebhook($webhookUrl, [
            'to' => $to,
            'message' => $msg,
            'template_params' => [
                trim((string)($row['paciente'] ?? 'Paciente')),
                trim((string)($row['examen_nombre'] ?? 'Examen')),
                '#' . (int)$row['id_cotizacion'],
                strtoupper((string)$row['alarma_estado_calc']),
                (string)$row['fecha_objetivo'],
            ],
            'result_id' => (int)$row['id'],
            'cotizacion_id' => (int)$row['id_cotizacion'],
            'status' => (string)$row['alarma_estado_calc'],
        ]);

        if (!$ok) {
            $failed++;
            $show('Error envío resultado #' . (int)$row['id'] . ': ' . $detail);
            continue;
        }
    } else {
        $show('Vista previa #' . (int)$row['id'] . ' -> https://wa.me/' . $to . '?text=' . rawurlencode($msg));
    }

    $stmtUpd->execute([
        'alarma_estado' => (string)$row['alarma_estado_calc'],
        'id' => (int)$row['id'],
    ]);
    $sent++;
}

$show('Finalizado. Enviados/actualizados: ' . $sent . '. Fallidos: ' . $failed . '. Previews: ' . $preview . '.');
exit(0);
