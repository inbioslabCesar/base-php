<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Cliente eliminado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al eliminar el cliente: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID de cliente no válido.";
}

header('Location: dashboard.php?vista=clientes');
exit;
?>
