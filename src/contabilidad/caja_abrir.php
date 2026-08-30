<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

$maxTurnosPorDia = 2;

function caja_abrir_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$isOfflineSync = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
$acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$expectsJson = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($expectsJson) {
        caja_abrir_json_response(405, ['ok' => false, 'message' => 'Metodo no permitido']);
    }
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$montoInicial = round((float)($_POST['monto_inicial'] ?? 0), 2);
$observacion = trim((string)($_POST['observacion_apertura'] ?? ''));
$operationId = trim((string)($_POST['offline_operation_id'] ?? ''));

if ($usuarioId <= 0) {
    if ($expectsJson) {
        caja_abrir_json_response(401, ['ok' => false, 'message' => 'No se pudo identificar el usuario']);
    }
    $_SESSION['mensaje'] = 'No se pudo identificar el usuario para abrir caja.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($montoInicial < 0) {
    if ($expectsJson) {
        caja_abrir_json_response(422, ['ok' => false, 'message' => 'El monto inicial no puede ser negativo']);
    }
    $_SESSION['mensaje'] = 'El monto inicial no puede ser negativo.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

try {
    $stmtTables = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
    $stmtTables->execute();
    $existsCajas = (int)$stmtTables->fetchColumn() > 0;

    if (!$existsCajas) {
        if ($expectsJson) {
            caja_abrir_json_response(500, ['ok' => false, 'message' => 'Faltan tablas de caja']);
        }
        $_SESSION['mensaje'] = 'Falta crear tablas de caja. Ejecuta sql/agregar_tablas_caja.sql (y si ya existían, sql/actualizar_caja_robusta.sql).';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $pdo->beginTransaction();

    if ($operationId !== '') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS caja_sync_operaciones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_id VARCHAR(80) NOT NULL,
            usuario_id INT NOT NULL,
            tipo_operacion VARCHAR(30) NOT NULL,
            estado ENUM('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
            payload_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_caja_sync_operation_id (operation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmtOp = $pdo->prepare("SELECT estado FROM caja_sync_operaciones WHERE operation_id = ? LIMIT 1");
        $stmtOp->execute([$operationId]);
        $existingOp = $stmtOp->fetch(PDO::FETCH_ASSOC);

        if ($existingOp && (string)($existingOp['estado'] ?? '') === 'aplicado') {
            $pdo->commit();
            if ($expectsJson) {
                caja_abrir_json_response(200, [
                    'ok' => true,
                    'duplicate' => true,
                    'message' => 'Operacion ya aplicada previamente',
                ]);
            }
            header('Location: dashboard.php?vista=contabilidad');
            exit;
        }

        if (!$existingOp) {
            $payloadRaw = json_encode([
                'monto_inicial' => $montoInicial,
                'observacion_apertura' => $observacion,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmtInsOp = $pdo->prepare("INSERT INTO caja_sync_operaciones (operation_id, usuario_id, tipo_operacion, estado, payload_json) VALUES (?, ?, 'apertura', 'pendiente', ?)");
            $stmtInsOp->execute([$operationId, $usuarioId, $payloadRaw]);
        }
    }

    $stmtOpen = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtOpen->execute();
    $openId = $stmtOpen->fetchColumn();

    if ($openId) {
        if ($operationId !== '') {
            $stmtErr = $pdo->prepare("UPDATE caja_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        }
        $pdo->commit();
        if ($expectsJson) {
            caja_abrir_json_response(409, ['ok' => false, 'message' => 'Ya existe una caja abierta']);
        }
        $_SESSION['mensaje'] = 'Ya existe una caja abierta. Debes cerrarla antes de abrir una nueva.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtTurnoCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas' AND COLUMN_NAME = 'numero_turno'");
    $stmtTurnoCol->execute();
    $hasTurnoColumn = ((int)$stmtTurnoCol->fetchColumn() > 0);

    $numeroTurno = 1;
    if ($hasTurnoColumn) {
        $stmtCountTurnos = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE fecha_operacion = CURDATE()");
        $stmtCountTurnos->execute();
        $turnosRegistradosHoy = (int)$stmtCountTurnos->fetchColumn();
        $numeroTurno = $turnosRegistradosHoy + 1;

        if ($numeroTurno > $maxTurnosPorDia) {
            if ($operationId !== '') {
                $stmtErr = $pdo->prepare("UPDATE caja_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
                $stmtErr->execute([$operationId]);
            }
            $pdo->commit();
            if ($expectsJson) {
                caja_abrir_json_response(409, ['ok' => false, 'message' => 'Se alcanzo el limite de turnos del dia']);
            }
            $_SESSION['mensaje'] = 'Ya se registraron ' . $maxTurnosPorDia . ' turnos para hoy. No se puede abrir otra caja.';
            header('Location: dashboard.php?vista=contabilidad');
            exit;
        }
    }

    if ($hasTurnoColumn) {
        $stmt = $pdo->prepare("INSERT INTO cajas (fecha_operacion, numero_turno, estado, usuario_apertura_id, fecha_hora_apertura, monto_inicial, observacion_apertura) VALUES (CURDATE(), ?, 'abierta', ?, NOW(), ?, ?)");
        $stmt->execute([$numeroTurno, $usuarioId, $montoInicial, $observacion !== '' ? $observacion : null]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cajas (fecha_operacion, estado, usuario_apertura_id, fecha_hora_apertura, monto_inicial, observacion_apertura) VALUES (CURDATE(), 'abierta', ?, NOW(), ?, ?)");
        $stmt->execute([$usuarioId, $montoInicial, $observacion !== '' ? $observacion : null]);
    }

    $cajaId = (int)$pdo->lastInsertId();

    $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMovTable->execute();
    $existsMov = (int)$stmtMovTable->fetchColumn() > 0;

    if ($existsMov) {
        $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, descripcion, usuario_id, fecha_hora) VALUES (?, 'ajuste', 'apertura', 'efectivo', ?, 1, ?, ?, NOW())");
        $stmtMov->execute([$cajaId, $montoInicial, 'Apertura de caja', $usuarioId]);
    }

    if ($operationId !== '') {
        $stmtOk = $pdo->prepare("UPDATE caja_sync_operaciones SET estado = 'aplicado' WHERE operation_id = ?");
        $stmtOk->execute([$operationId]);
    }

    $pdo->commit();

    if ($expectsJson) {
        caja_abrir_json_response(200, [
            'ok' => true,
            'duplicate' => false,
            'message' => 'Caja abierta correctamente',
        ]);
    }

    $_SESSION['mensaje'] = 'Caja abierta correctamente' . ($hasTurnoColumn ? ' (Turno ' . $numeroTurno . ').' : '.');
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($operationId !== '') {
        try {
            $stmtErr = $pdo->prepare("UPDATE caja_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        } catch (\Throwable $inner) {
            // Ignorar error secundario.
        }
    }

    if ($expectsJson) {
        caja_abrir_json_response(500, [
            'ok' => false,
            'message' => 'No se pudo abrir la caja',
            'error' => $e->getMessage(),
        ]);
    }

    $_SESSION['mensaje'] = 'No se pudo abrir la caja: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
