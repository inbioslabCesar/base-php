<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Usuario eliminado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al eliminar el usuario: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID de usuario no válido.";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
