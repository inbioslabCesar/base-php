<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$montoContado = round((float)($_POST['monto_contado_efectivo'] ?? 0), 2);
$observacion = trim((string)($_POST['observacion_cierre'] ?? ''));

if ($usuarioId <= 0) {
    $_SESSION['mensaje'] = 'No se pudo identificar el usuario para cerrar caja.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($montoContado < 0) {
    $_SESSION['mensaje'] = 'El monto contado no puede ser negativo.';
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

    $stmtTurnoCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas' AND COLUMN_NAME = 'numero_turno'");
    $stmtTurnoCol->execute();
    $hasTurnoColumn = ((int)$stmtTurnoCol->fetchColumn() > 0);

    $sqlOpen = $hasTurnoColumn
        ? "SELECT id, monto_inicial, fecha_hora_apertura, COALESCE(numero_turno, 1) AS numero_turno FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1"
        : "SELECT id, monto_inicial, fecha_hora_apertura, 1 AS numero_turno FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1";
    $stmtOpen = $pdo->prepare($sqlOpen);
    $stmtOpen->execute();
    $caja = $stmtOpen->fetch();

    if (!$caja) {
        $_SESSION['mensaje'] = 'No hay una caja abierta para cerrar.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMovTable->execute();
    $existsMov = (int)$stmtMovTable->fetchColumn() > 0;

    $montoInicial = round((float)$caja['monto_inicial'], 2);
    $ingresosEfectivo = 0.0;
    $egresosEfectivo = 0.0;
    $ajustesEfectivo = 0.0;

    if ($existsMov) {
        $stmtResumenMov = $pdo->prepare("SELECT
            IFNULL(SUM(CASE WHEN tipo = 'ingreso' AND afecta_efectivo = 1 THEN monto ELSE 0 END), 0) AS ingresos,
            IFNULL(SUM(CASE WHEN tipo = 'egreso' AND afecta_efectivo = 1 THEN monto ELSE 0 END), 0) AS egresos,
            IFNULL(SUM(CASE WHEN tipo = 'ajuste' AND afecta_efectivo = 1 AND origen NOT IN ('apertura','cierre') THEN monto ELSE 0 END), 0) AS ajustes
        FROM caja_movimientos
        WHERE caja_id = ?");
        $stmtResumenMov->execute([(int)$caja['id']]);
        $resMov = $stmtResumenMov->fetch();

        $ingresosEfectivo = round((float)($resMov['ingresos'] ?? 0), 2);
        $egresosEfectivo = round((float)($resMov['egresos'] ?? 0), 2);
        $ajustesEfectivo = round((float)($resMov['ajustes'] ?? 0), 2);
    } else {
        $inicioCaja = $caja['fecha_hora_apertura'];

        $stmtIngresos = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE fecha >= ? AND fecha <= NOW() AND LOWER(TRIM(metodo_pago)) = 'efectivo'");
        $stmtIngresos->execute([$inicioCaja]);
        $ingresosEfectivo = round((float)$stmtIngresos->fetchColumn(), 2);

        $stmtEgresos = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM egresos WHERE fecha >= ? AND fecha <= NOW()");
        $stmtEgresos->execute([$inicioCaja]);
        $egresosEfectivo = round((float)$stmtEgresos->fetchColumn(), 2);
    }

    $cajaTeorica = round($montoInicial + $ingresosEfectivo - $egresosEfectivo + $ajustesEfectivo, 2);
    $diferencia = round($montoContado - $cajaTeorica, 2);

    $pdo->beginTransaction();

    $stmtUpd = $pdo->prepare("UPDATE cajas SET estado = 'cerrada', usuario_cierre_id = ?, fecha_hora_cierre = NOW(), monto_contado_efectivo = ?, ingresos_efectivo = ?, egresos_efectivo = ?, caja_teorica_efectivo = ?, diferencia_efectivo = ?, observacion_cierre = ? WHERE id = ?");
    $stmtUpd->execute([
        $usuarioId,
        $montoContado,
        $ingresosEfectivo,
        $egresosEfectivo,
        $cajaTeorica,
        $diferencia,
        $observacion !== '' ? $observacion : null,
        (int)$caja['id'],
    ]);

    if ($existsMov && abs($diferencia) > 0.0001) {
        $descripcion = $diferencia > 0 ? 'Cierre con sobrante' : 'Cierre con faltante';
        $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, descripcion, usuario_id, fecha_hora) VALUES (?, 'ajuste', 'cierre', 'efectivo', ?, 1, ?, ?, NOW())");
        $stmtMov->execute([(int)$caja['id'], $diferencia, $descripcion, $usuarioId]);
    }

    $pdo->commit();

    $_SESSION['mensaje'] = 'Caja cerrada correctamente (Turno ' . (int)($caja['numero_turno'] ?? 1) . '). Diferencia: S/ ' . number_format($diferencia, 2);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo cerrar la caja: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
