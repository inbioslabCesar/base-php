<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio_promocional = floatval($_POST['precio_promocional'] ?? 0);
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;
$activo = isset($_POST['activo']) ? 1 : 0;

// Manejo de imagen
$imagen = null;
if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = uniqid('promo_') . '_' . basename($_FILES['imagen']['name']);
    $rutaDestino = __DIR__ . '/assets/' . $nombreArchivo;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
        $imagen = $nombreArchivo;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO promociones 
        (titulo, descripcion, imagen, precio_promocional, fecha_inicio, fecha_fin, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $titulo,
        $descripcion,
        $imagen,
        $precio_promocional,
        $fecha_inicio,
        $fecha_fin,
        $activo
    ]);
    $_SESSION['mensaje'] = "Promoción creada correctamente.";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al crear promoción: " . $e->getMessage();
}

header('Location: dashboard.php?vista=promociones');
exit;
