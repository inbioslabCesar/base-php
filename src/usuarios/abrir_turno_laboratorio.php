<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/laboratorio_turnos.php';

$rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($rol, ['laboratorista', 'admin'], true) || $usuarioId <= 0) {
    $_SESSION['mensaje'] = 'No tienes permiso para abrir turno.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: dashboard.php?vista=laboratorista');
    exit;
}

$obs = trim((string)($_POST['observacion_apertura'] ?? ''));
$res = laboratorio_turno_abrir($pdo, $usuarioId, $obs);

$_SESSION['mensaje'] = (string)($res['msg'] ?? 'Operación ejecutada.');
$_SESSION['mensaje_tipo'] = !empty($res['ok']) ? 'success' : 'error';

header('Location: dashboard.php?vista=laboratorista');
exit;
