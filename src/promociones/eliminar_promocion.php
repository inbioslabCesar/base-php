<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    try {
        // Opcional: eliminar imagen física si existe
        $stmt = $pdo->prepare("SELECT imagen FROM promociones WHERE id = ?");
        $stmt->execute([$id]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($promo && !empty($promo['imagen'])) {
            $rutaImagen = __DIR__ . '/assets/' . $promo['imagen'];
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }

        // Elimina la promoción
        $stmt = $pdo->prepare("DELETE FROM promociones WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Promoción eliminada correctamente.";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al eliminar promoción: " . $e->getMessage();
    }
}
header('Location: dashboard.php?vista=promociones');
exit;
