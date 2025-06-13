<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['mensaje'] = "ID de convenio no válido.";
    header('Location: dashboard.php?vista=convenios');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM convenios WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensaje'] = "Convenio eliminado exitosamente.";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al eliminar: " . $e->getMessage();
}
header('Location: dashboard.php?vista=convenios');
exit;
