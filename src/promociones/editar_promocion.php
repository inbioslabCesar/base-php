<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_promocional = floatval($_POST['precio_promocional'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Manejo de imagen (opcional)
    $imagen = $_POST['imagen_actual'] ?? '';
    if (!empty($_FILES['imagen']['name'])) {
        $nombreArchivo = uniqid('promo_') . '_' . basename($_FILES['imagen']['name']);
        $rutaDestino = __DIR__ . '/assets/' . $nombreArchivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagen = $nombreArchivo;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE promociones SET 
            titulo = ?, descripcion = ?, imagen = ?, precio_promocional = ?, fecha_inicio = ?, fecha_fin = ?, activo = ?
            WHERE id = ?");
        $stmt->execute([
            $titulo,
            $descripcion,
            $imagen,
            $precio_promocional,
            $fecha_inicio,
            $fecha_fin,
            $activo,
            $id
        ]);
        $_SESSION['mensaje'] = "Promoción actualizada correctamente.";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al actualizar promoción: " . $e->getMessage();
    }
    header('Location: dashboard.php?vista=promociones');
    exit;
}
