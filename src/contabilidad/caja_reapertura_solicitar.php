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
$motivo = trim((string)($_POST['motivo_reapertura'] ?? ''));

if (!in_array($rol, ['admin', 'recepcionista'], true)) {
    $_SESSION['mensaje'] = 'No tienes permisos para solicitar reapertura de caja.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($usuarioId <= 0) {
    $_SESSION['mensaje'] = 'No se pudo identificar el usuario solicitante.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($motivo === '') {
    $_SESSION['mensaje'] = 'Debes indicar el motivo de reapertura.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

try {
    $stmtCajas = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmtCajas->execute(['cajas']);
    if (!$stmtCajas->fetchColumn()) {
        $_SESSION['mensaje'] = 'No existe la tabla de cajas. Ejecuta las migraciones de caja.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

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

    $stmtOpen = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtOpen->execute();
    if ($stmtOpen->fetchColumn()) {
        $_SESSION['mensaje'] = 'Ya hay una caja abierta. No se requiere reapertura.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtPend = $pdo->prepare("SELECT id FROM caja_reaperturas WHERE fecha_operacion = CURDATE() AND estado = 'pendiente' ORDER BY id DESC LIMIT 1");
    $stmtPend->execute();
    if ($stmtPend->fetchColumn()) {
        $_SESSION['mensaje'] = 'Ya existe una solicitud pendiente de reapertura para hoy.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtTurnoCol = $pdo->prepare("SHOW COLUMNS FROM cajas LIKE 'numero_turno'");
    $stmtTurnoCol->execute();
    $hasTurnoColumn = (bool)$stmtTurnoCol->fetch(\PDO::FETCH_ASSOC);

    $sqlUltimoCierre = $hasTurnoColumn
        ? "SELECT id, COALESCE(numero_turno, 1) AS numero_turno FROM cajas WHERE fecha_operacion = CURDATE() AND estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT 1"
        : "SELECT id, 1 AS numero_turno FROM cajas WHERE fecha_operacion = CURDATE() AND estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT 1";
    $stmtUltimoCierre = $pdo->prepare($sqlUltimoCierre);
    $stmtUltimoCierre->execute();
    $ultimoCierre = $stmtUltimoCierre->fetch(\PDO::FETCH_ASSOC) ?: null;

    $cajaOrigenId = (int)($ultimoCierre['id'] ?? 0);
    $turnoResponsable = (int)($ultimoCierre['numero_turno'] ?? 1);

    $stmtIns = $pdo->prepare("INSERT INTO caja_reaperturas (fecha_operacion, estado, caja_origen_id, turno_responsable, motivo_solicitud, solicitado_por_id, fecha_solicitud) VALUES (CURDATE(), 'pendiente', ?, ?, ?, ?, NOW())");
    $stmtIns->execute([
        $cajaOrigenId > 0 ? $cajaOrigenId : null,
        $turnoResponsable > 0 ? $turnoResponsable : 1,
        $motivo,
        $usuarioId,
    ]);

    $_SESSION['mensaje'] = 'Solicitud de reapertura registrada. Espera aprobación de un administrador.';
} catch (\Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo registrar la solicitud de reapertura: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
