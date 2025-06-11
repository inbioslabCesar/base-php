<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Empresa eliminada exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al eliminar la empresa: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID de empresa no válido.";
}

header('Location: dashboard.php?vista=empresas');
exit;
?>
