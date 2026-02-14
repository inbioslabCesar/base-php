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
$tipoAjuste = trim((string)($_POST['tipo_ajuste'] ?? ''));
$montoAjuste = round((float)($_POST['monto_ajuste'] ?? 0), 2);
$descripcion = trim((string)($_POST['descripcion_ajuste'] ?? ''));

if (!in_array($tipoAjuste, ['faltante', 'sobrante'], true)) {
    $_SESSION['mensaje'] = 'Debes seleccionar el tipo de corrección.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($montoAjuste <= 0.0) {
    $_SESSION['mensaje'] = 'El monto debe ser mayor a cero.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

if ($descripcion === '') {
    $_SESSION['mensaje'] = 'Debes ingresar una descripción para el ajuste.';
    header('Location: dashboard.php?vista=contabilidad');
    exit;
}

$montoAjuste = $tipoAjuste === 'faltante' ? -abs($montoAjuste) : abs($montoAjuste);

try {
    $stmtCajas = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
    $stmtCajas->execute();
    $existsCajas = (int)$stmtCajas->fetchColumn() > 0;

    $stmtMov = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMov->execute();
    $existsMov = (int)$stmtMov->fetchColumn() > 0;

    if (!$existsCajas || !$existsMov) {
        $_SESSION['mensaje'] = 'Faltan tablas de caja. Ejecuta sql/agregar_tablas_caja.sql y sql/actualizar_caja_robusta.sql.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtCaja->execute();
    $cajaId = (int)$stmtCaja->fetchColumn();

    if ($cajaId <= 0) {
        $_SESSION['mensaje'] = 'No hay caja abierta para registrar ajustes.';
        header('Location: dashboard.php?vista=contabilidad');
        exit;
    }

    $stmtInsert = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, descripcion, usuario_id, fecha_hora) VALUES (?, 'ajuste', 'ajuste_manual', 'efectivo', ?, 1, ?, ?, NOW())");
    $stmtInsert->execute([
        $cajaId,
        $montoAjuste,
        $descripcion,
        $usuarioId > 0 ? $usuarioId : null,
    ]);

    $_SESSION['mensaje'] = 'Ajuste registrado correctamente.';
} catch (\Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo registrar el ajuste: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=contabilidad');
exit;
