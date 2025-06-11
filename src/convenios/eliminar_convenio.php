<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM convenios WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Convenio eliminado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al eliminar el convenio: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID de convenio no válido.";
}

header('Location: dashboard.php?vista=convenios');
exit;
?>
