<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Opcional: eliminar la imagen asociada si existe
    $stmt = $pdo->prepare("SELECT imagen FROM promociones WHERE id=?");
    $stmt->execute([$id]);
    $promocion = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($promocion && $promocion['imagen']) {
        $rutaImagen = __DIR__ . '/assets/' . $promocion['imagen'];
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        } else {
            echo "No se encontró la imagen a eliminar.";
        }
    }

    // Eliminar la promoción de la base de datos
    $stmt = $pdo->prepare("DELETE FROM promociones WHERE id=?");
    $stmt->execute([$id]);

    $_SESSION['mensaje'] = "Promoción eliminada correctamente.";
} else {
    $_SESSION['error'] = "ID de promoción no válido.";
}
header('Location: ' . BASE_URL . 'dashboard.php?vista=promociones');
exit;
