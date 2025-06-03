<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Eliminar empresa solo si existe
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = "Empresa eliminada correctamente.";
    } else {
        $mensaje = "Empresa no encontrada.";
    }
} else {
    $mensaje = "ID inválido.";
}

// Redirigir de vuelta a la tabla de empresas después de 1 segundo
header("refresh:1;url=" . BASE_URL . "dashboard.php?vista=empresas");
?>

<div style="padding:20px;">
    <h2>Eliminar Empresa</h2>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
    <p>Redirigiendo a la tabla de empresas...</p>
</div>
