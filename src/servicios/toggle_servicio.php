<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
$estado = strtolower(trim((string)($_GET['estado'] ?? '')));
$estado = in_array($estado, ['activo', 'inactivo'], true) ? $estado : '';

if ($id <= 0 || $estado === '') {
    $_SESSION['mensaje'] = 'Parametros no validos para cambiar estado del servicio.';
    header('Location: dashboard.php?vista=servicios');
    exit;
}

try {
    servicios_asegurar_tabla($pdo);
    $stmt = $pdo->prepare("UPDATE servicios SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);
    $_SESSION['mensaje'] = 'Estado del servicio actualizado.';
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo actualizar el estado del servicio.';
}

header('Location: dashboard.php?vista=servicios');
exit;
