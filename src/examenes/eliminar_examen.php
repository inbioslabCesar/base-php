<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM examenes WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Examen eliminado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al eliminar el examen: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID de examen no válido.";
}

header('Location: dashboard.php?vista=examenes');
exit;
?>
