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
$rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));
$solicitudId = (int)($_POST['reapertura_id'] ?? 0);
$observacion = trim((string)($_POST['observacion_aprobacion'] ?? ''));
$montoInicial = round((float)($_POST['monto_inicial_reapertura'] ?? 0), 2);

if ($rol !== 'admin') {
    $_SESSION['mensaje'] = 'Solo un administrador puede aprobar reaperturas de caja.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($usuarioId <= 0) {
    $_SESSION['mensaje'] = 'No se pudo identificar el usuario aprobador.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($solicitudId <= 0) {
    $_SESSION['mensaje'] = 'Solicitud de reapertura inválida.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($montoInicial < 0) {
    $_SESSION['mensaje'] = 'El monto inicial no puede ser negativo.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

try {
    $stmtRep = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmtRep->execute(['caja_reaperturas']);
    if (!$stmtRep->fetchColumn()) {
        $_SESSION['mensaje'] = 'No existe la tabla de reaperturas. Ejecuta sql/agregar_tabla_caja_reaperturas.sql.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtColCajaOrigen = $pdo->prepare("SHOW COLUMNS FROM caja_reaperturas LIKE 'caja_origen_id'");
    $stmtColCajaOrigen->execute();
    $hasCajaOrigenCol = (bool)$stmtColCajaOrigen->fetch(\PDO::FETCH_ASSOC);

    $stmtColTurnoResp = $pdo->prepare("SHOW COLUMNS FROM caja_reaperturas LIKE 'turno_responsable'");
    $stmtColTurnoResp->execute();
    $hasTurnoRespCol = (bool)$stmtColTurnoResp->fetch(\PDO::FETCH_ASSOC);

    if (!$hasCajaOrigenCol || !$hasTurnoRespCol) {
        $_SESSION['mensaje'] = 'Faltan columnas de reapertura por turno. Ejecuta sql/actualizar_tabla_caja_reaperturas_turno.sql.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtCajas = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmtCajas->execute(['cajas']);
    if (!$stmtCajas->fetchColumn()) {
        $_SESSION['mensaje'] = 'No existe la tabla de cajas.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtMovTable = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmtMovTable->execute(['caja_movimientos']);
    $existsMov = (bool)$stmtMovTable->fetchColumn();

    $stmtTurnoCol = $pdo->prepare("SHOW COLUMNS FROM cajas LIKE 'numero_turno'");
    $stmtTurnoCol->execute();
    $hasTurnoColumn = (bool)$stmtTurnoCol->fetch(\PDO::FETCH_ASSOC);

    $stmtOpen = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtOpen->execute();
    if ($stmtOpen->fetchColumn()) {
        $_SESSION['mensaje'] = 'Ya existe una caja abierta. No se puede reaperturar otra.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $pdo->beginTransaction();

    $stmtSel = $pdo->prepare("SELECT id, fecha_operacion, estado, motivo_solicitud, caja_origen_id, turno_responsable FROM caja_reaperturas WHERE id = ? FOR UPDATE");
    $stmtSel->execute([$solicitudId]);
    $solicitud = $stmtSel->fetch(\PDO::FETCH_ASSOC);

    if (!$solicitud) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'La solicitud ya no existe.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    if ((string)($solicitud['estado'] ?? '') !== 'pendiente') {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'La solicitud ya fue atendida.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $cajaOrigenId = (int)($solicitud['caja_origen_id'] ?? 0);
    $turnoResponsable = (int)($solicitud['turno_responsable'] ?? 0);

    if ($cajaOrigenId <= 0 || $turnoResponsable <= 0) {
        $sqlUltimoCierre = $hasTurnoColumn
            ? "SELECT id, COALESCE(numero_turno, 1) AS numero_turno FROM cajas WHERE fecha_operacion = CURDATE() AND estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT 1"
            : "SELECT id, 1 AS numero_turno FROM cajas WHERE fecha_operacion = CURDATE() AND estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT 1";
        $stmtUltimoCierre = $pdo->prepare($sqlUltimoCierre);
        $stmtUltimoCierre->execute();
        $ultimoCierre = $stmtUltimoCierre->fetch(\PDO::FETCH_ASSOC) ?: [];
        if ($cajaOrigenId <= 0) {
            $cajaOrigenId = (int)($ultimoCierre['id'] ?? 0);
        }
        if ($turnoResponsable <= 0) {
            $turnoResponsable = (int)($ultimoCierre['numero_turno'] ?? 1);
        }
    }

    $numeroTurno = 1;
    if ($hasTurnoColumn) {
        $stmtNextTurno = $pdo->prepare("SELECT IFNULL(MAX(COALESCE(numero_turno, 0)), 0) + 1 FROM cajas WHERE fecha_operacion = CURDATE()");
        $stmtNextTurno->execute();
        $numeroTurno = max(1, (int)$stmtNextTurno->fetchColumn());
    }

    $obsApertura = 'Reapertura extraordinaria aprobada (solicitud #' . (int)$solicitud['id'] . ')';
    if ($observacion !== '') {
        $obsApertura .= ' | ' . $observacion;
    }

    if ($hasTurnoColumn) {
        $stmtInsCaja = $pdo->prepare("INSERT INTO cajas (fecha_operacion, numero_turno, estado, usuario_apertura_id, fecha_hora_apertura, monto_inicial, observacion_apertura) VALUES (CURDATE(), ?, 'abierta', ?, NOW(), ?, ?)");
        $stmtInsCaja->execute([$numeroTurno, $usuarioId, $montoInicial, $obsApertura]);
    } else {
        $stmtInsCaja = $pdo->prepare("INSERT INTO cajas (fecha_operacion, estado, usuario_apertura_id, fecha_hora_apertura, monto_inicial, observacion_apertura) VALUES (CURDATE(), 'abierta', ?, NOW(), ?, ?)");
        $stmtInsCaja->execute([$usuarioId, $montoInicial, $obsApertura]);
    }

    $cajaId = (int)$pdo->lastInsertId();

    if ($existsMov) {
        $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, descripcion, usuario_id, fecha_hora) VALUES (?, 'ajuste', 'apertura', 'efectivo', ?, 1, ?, ?, NOW())");
        $stmtMov->execute([$cajaId, $montoInicial, 'Apertura por reapertura extraordinaria', $usuarioId]);
    }

    $stmtUpd = $pdo->prepare("UPDATE caja_reaperturas SET estado = 'aprobada', caja_origen_id = ?, turno_responsable = ?, aprobado_por_id = ?, fecha_aprobacion = NOW(), caja_reabierta_id = ?, observacion_aprobacion = ? WHERE id = ?");
    $stmtUpd->execute([$cajaOrigenId > 0 ? $cajaOrigenId : null, $turnoResponsable > 0 ? $turnoResponsable : 1, $usuarioId, $cajaId, $observacion !== '' ? $observacion : null, $solicitudId]);

    $pdo->commit();

    $_SESSION['mensaje'] = 'Reapertura aprobada y caja abierta correctamente.';
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo aprobar la reapertura: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
