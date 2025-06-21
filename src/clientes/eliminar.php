<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['msg'] = 'ID de cliente no proporcionado.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = 'Cliente eliminado correctamente.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al eliminar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}
