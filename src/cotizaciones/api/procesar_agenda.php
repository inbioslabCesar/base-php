<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function agenda_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function agenda_redirect_by_role(string $baseUrl, string $rol): void
{
    if ($rol === 'cliente') {
        header('Location: ' . $baseUrl . 'dashboard.php?vista=cotizaciones_clientes');
    } elseif ($rol === 'empresa') {
        header('Location: ' . $baseUrl . 'dashboard.php?vista=cotizaciones_empresas');
    } elseif ($rol === 'convenio') {
        header('Location: ' . $baseUrl . 'dashboard.php?vista=cotizaciones_convenios');
    } else {
        header('Location: ' . $baseUrl . 'dashboard.php?vista=cotizaciones');
    }
    exit;
}

$isOfflineSync = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
$acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$expectsJson = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($expectsJson) {
        agenda_json_response(405, ['ok' => false, 'message' => 'Metodo no permitido']);
    }
    http_response_code(405);
    exit;
}

$id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
$tipo_toma = (string)($_POST['tipo_toma'] ?? '');
$fecha_toma = (string)($_POST['fecha_toma'] ?? '');
$hora_toma = (string)($_POST['hora_toma'] ?? '');
$direccion_toma = ($tipo_toma === 'domicilio') ? trim((string)($_POST['direccion_toma'] ?? '')) : null;
$operationId = trim((string)($_POST['offline_operation_id'] ?? ''));

if ($id_cotizacion <= 0 || $tipo_toma === '' || $fecha_toma === '' || $hora_toma === '') {
    if ($expectsJson) {
        agenda_json_response(422, ['ok' => false, 'message' => 'Datos incompletos para agendar cita']);
    }
    $_SESSION['mensaje'] = 'Datos incompletos para agendar cita.';
    agenda_redirect_by_role(BASE_URL, strtolower(trim((string)($_SESSION['rol'] ?? ''))));
}

if ($tipo_toma === 'domicilio' && $direccion_toma === '') {
    if ($expectsJson) {
        agenda_json_response(422, ['ok' => false, 'message' => 'Direccion requerida para toma a domicilio']);
    }
    $_SESSION['mensaje'] = 'Debes indicar la direccion para toma a domicilio.';
    agenda_redirect_by_role(BASE_URL, strtolower(trim((string)($_SESSION['rol'] ?? ''))));
}

try {
    $payloadRaw = json_encode([
        'id_cotizacion' => $id_cotizacion,
        'tipo_toma' => $tipo_toma,
        'fecha_toma' => $fecha_toma,
        'hora_toma' => $hora_toma,
        'direccion_toma' => $direccion_toma,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $pdo->beginTransaction();

    if ($operationId !== '') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS agenda_sync_operaciones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_id VARCHAR(80) NOT NULL,
            cotizacion_id INT NOT NULL,
            estado ENUM('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
            payload_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_agenda_sync_operation_id (operation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmtOp = $pdo->prepare("SELECT id, estado FROM agenda_sync_operaciones WHERE operation_id = ? LIMIT 1");
        $stmtOp->execute([$operationId]);
        $existingOp = $stmtOp->fetch(PDO::FETCH_ASSOC);

        if ($existingOp && (string)($existingOp['estado'] ?? '') === 'aplicado') {
            $pdo->commit();
            if ($expectsJson) {
                agenda_json_response(200, [
                    'ok' => true,
                    'duplicate' => true,
                    'message' => 'Operacion ya aplicada previamente',
                ]);
            }
            agenda_redirect_by_role(BASE_URL, strtolower(trim((string)($_SESSION['rol'] ?? ''))));
        }

        if (!$existingOp) {
            $stmtInsOp = $pdo->prepare("INSERT INTO agenda_sync_operaciones (operation_id, cotizacion_id, estado, payload_json) VALUES (?, ?, 'pendiente', ?)");
            $stmtInsOp->execute([$operationId, $id_cotizacion, $payloadRaw]);
        }
    }

    // Determinar si es agendada o inmediata
    $hoy = date('Y-m-d');
    $hora_actual = date('H:i');
    if ($tipo_toma === 'laboratorio') {
        if (
            ($fecha_toma > $hoy) ||
            ($fecha_toma === $hoy && $hora_toma > $hora_actual)
        ) {
            $estado_muestra = 'pendiente'; // Agendada a futuro
        } else {
            $estado_muestra = 'realizada'; // Es para ya mismo
        }
    } else if ($tipo_toma === 'domicilio') {
        $estado_muestra = 'pendiente'; // Siempre es agendada
    } else {
        $estado_muestra = 'pendiente';
    }

    $stmt = $pdo->prepare("UPDATE cotizaciones SET tipo_toma = ?, fecha_toma = ?, hora_toma = ?, direccion_toma = ?, estado_muestra = ? WHERE id = ?");
    $stmt->execute([$tipo_toma, $fecha_toma, $hora_toma, $direccion_toma, $estado_muestra, $id_cotizacion]);

    if ($operationId !== '') {
        $stmtOkOp = $pdo->prepare("UPDATE agenda_sync_operaciones SET estado = 'aplicado' WHERE operation_id = ?");
        $stmtOkOp->execute([$operationId]);
    }

    $pdo->commit();

    if ($expectsJson) {
        agenda_json_response(200, [
            'ok' => true,
            'duplicate' => false,
            'message' => 'Agenda sincronizada correctamente',
        ]);
    }

    $rol = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
    agenda_redirect_by_role(BASE_URL, $rol);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($operationId !== '') {
        try {
            $stmtErrOp = $pdo->prepare("UPDATE agenda_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErrOp->execute([$operationId]);
        } catch (Throwable $inner) {
            // Ignorar error secundario para no ocultar el original.
        }
    }

    if ($expectsJson) {
        agenda_json_response(500, [
            'ok' => false,
            'message' => 'No se pudo procesar la agenda',
            'error' => $e->getMessage(),
        ]);
    }

    $_SESSION['mensaje'] = 'No se pudo procesar la agenda: ' . $e->getMessage();
    $rol = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
    agenda_redirect_by_role(BASE_URL, $rol);
}
