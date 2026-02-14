<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

$maxTurnosPorDia = 2;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$montoInicial = round((float)($_POST['monto_inicial'] ?? 0), 2);
$observacion = trim((string)($_POST['observacion_apertura'] ?? ''));

if ($usuarioId <= 0) {
    $_SESSION['mensaje'] = 'No se pudo identificar el usuario para abrir caja.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($montoInicial < 0) {
    $_SESSION['mensaje'] = 'El monto inicial no puede ser negativo.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

try {
    $stmtTables = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
    $stmtTables->execute();
    $existsCajas = (int)$stmtTables->fetchColumn() > 0;

    if (!$existsCajas) {
        $_SESSION['mensaje'] = 'Falta crear tablas de caja. Ejecuta sql/agregar_tablas_caja.sql (y si ya existían, sql/actualizar_caja_robusta.sql).';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtOpen = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtOpen->execute();
    $openId = $stmtOpen->fetchColumn();

    if ($openId) {
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
            $_SESSION['mensaje'] = 'Ya se registraron ' . $maxTurnosPorDia . ' turnos para hoy. No se puede abrir otra caja.';
            header('Location: dashboard.php?vista=contabilidad');
            exit;
        }
    }

    $pdo->beginTransaction();

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

    $pdo->commit();

    $_SESSION['mensaje'] = 'Caja abierta correctamente' . ($hasTurnoColumn ? ' (Turno ' . $numeroTurno . ').' : '.');
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo abrir la caja: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
