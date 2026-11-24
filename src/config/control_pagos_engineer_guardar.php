<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/sistema_pagos.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
    exit;
}

$empresaId = (int)($_POST['empresa_cfg_id'] ?? 0);
$redirect = BASE_URL . 'dashboard.php?vista=control_pagos_engineer' . ($empresaId > 0 ? '&empresa_cfg_id=' . $empresaId : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !sp_csrf_ok((string)($_POST['csrf'] ?? ''))) {
    $_SESSION['msg'] = 'Solicitud invalida. Recargue la pagina e intente de nuevo.';
    header('Location: ' . $redirect);
    exit;
}
if (!sp_tablas_ok($pdo)) {
    $_SESSION['msg'] = 'Falta aplicar sql/2026_11_21_control_pagos_sistema/migracion.sql.';
    header('Location: ' . $redirect);
    exit;
}

$empresaCfg = ui_theme_fetch_company_config($pdo, $empresaId > 0 ? $empresaId : null);
$empresaId = (int)($empresaCfg['id'] ?? 0);
if ($empresaId <= 0) {
    $_SESSION['msg'] = 'No hay empresa configurada.';
    header('Location: ' . $redirect);
    exit;
}
$redirect = BASE_URL . 'dashboard.php?vista=control_pagos_engineer&empresa_cfg_id=' . $empresaId;
$accion = (string)($_POST['accion'] ?? '');

try {
    if ($accion === 'guardar_config') {
        sp_guardar_config($pdo, $empresaId, $_POST);
        $_SESSION['msg'] = 'Configuracion de cobro guardada.';
    } elseif ($accion === 'registrar_pago') {
        $sus = sp_obtener_suscripcion($pdo, $empresaId);
        if (!$sus) {
            throw new RuntimeException('Guarde primero la configuracion del cobro.');
        }
        $concepto = (string)($_POST['concepto'] ?? 'CICLO');
        if (!in_array($concepto, ['CICLO', 'IMPLEMENTACION', 'OTRO'], true)) {
            $concepto = 'CICLO';
        }
        $cantidad = sp_registrar_pago($pdo, $sus, $concepto, (int)($_POST['periodos'] ?? 1), $_POST, 'engineer');
        $_SESSION['msg'] = $cantidad > 1 ? "Se registraron $cantidad pagos." : 'Pago registrado.';
    } elseif ($accion === 'anular_pago') {
        $sus = sp_obtener_suscripcion($pdo, $empresaId);
        $ok = $sus && sp_anular_pago($pdo, (int)($_POST['pago_id'] ?? 0), (int)$sus['id'], (string)($_POST['motivo'] ?? ''));
        $_SESSION['msg'] = $ok ? 'Pago anulado. El ciclo volvio a quedar pendiente.' : 'No se pudo anular el pago.';
    } else {
        $_SESSION['msg'] = 'Accion no reconocida.';
    }
} catch (RuntimeException $e) {
    $_SESSION['msg'] = $e->getMessage();
} catch (Throwable $e) {
    error_log('control_pagos_engineer: ' . $e->getMessage());
    $_SESSION['msg'] = 'No se pudo completar la operacion.';
}

header('Location: ' . $redirect);
exit;
