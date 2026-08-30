<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['mensaje'] = 'Servicio no valido.';
    header('Location: dashboard.php?vista=servicios');
    exit;
}

try {
    servicios_asegurar_tabla($pdo);
    $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensaje'] = 'Servicio eliminado correctamente.';
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo eliminar el servicio.';
}

header('Location: dashboard.php?vista=servicios');
exit;
