<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../funciones/cotizaciones_utils.php';
require_once __DIR__ . '/../../facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../../facturacion/FacturacionService.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    $_SESSION['mensaje'] = 'ID inválido para emitir comprobante';
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

try {
    $stmtFlag = $pdo->prepare("SELECT emitir_comprobante FROM cotizaciones WHERE id = ?");
    $stmtFlag->execute([$id]);
    $emitir = (int)($stmtFlag->fetchColumn() ?? 1);
    if ($emitir !== 1) {
        $_SESSION['mensaje'] = 'Esta cotización está configurada como “Solo Ticket”. No se emitirá boleta/factura.';
        header('Location: dashboard.php?vista=detalle_cotizacion&id=' . $id);
        exit;
    }

    $saldo = obtenerSaldoCotizacion($pdo, $id);
    if ($saldo > 0) {
        $_SESSION['mensaje'] = 'La cotización aún tiene saldo pendiente. Debe estar pagada para emitir.';
        header('Location: dashboard.php?vista=detalle_cotizacion&id=' . $id);
        exit;
    }
    $auth = new FacturacionAuthService();
    $svc = new FacturacionService($pdo, $auth);
    $res = $svc->emitirComprobante($id, []);
    if (!empty($res['already_emitted'])) {
        $_SESSION['mensaje'] = 'El comprobante ya está ACEPTADO en SUNAT. No se re-emite.';
    } elseif (!empty($res['reintento'])) {
        $_SESSION['mensaje'] = 'Se reintentó el envío a SUNAT. Estado: ' . ($res['status'] ?? 'enviado');
    } elseif (!empty($res['error'])) {
        $_SESSION['mensaje'] = 'No se pudo emitir: ' . $res['error'];
    } else {
        $_SESSION['mensaje'] = 'Comprobante enviado a emisión. Estado: ' . ($res['status'] ?? 'pendiente');
    }
    header('Location: dashboard.php?vista=detalle_cotizacion&id=' . $id);
    exit;
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'Error al emitir comprobante: ' . $e->getMessage();
    header('Location: dashboard.php?vista=detalle_cotizacion&id=' . $id);
    exit;
}
