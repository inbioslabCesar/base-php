<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conexion/conexion.php';

$titulo = trim($_POST['titulo']);
$descripcion = trim($_POST['descripcion']);
$precio_promocional = floatval($_POST['precio_promocional']);
$descuento = floatval($_POST['descuento']);
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$activo = isset($_POST['activo']) ? 1 : 0;
$vigente = isset($_POST['vigente']) ? 1 : 0;
$tipo_publico = $_POST['tipo_publico'] ?? 'todos';
$imagen = '';

if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = uniqid('promo_') . '_' . basename($_FILES['imagen']['name']);
    $rutaDestino = __DIR__ . '/assets/' . $nombreArchivo;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
        $imagen = $nombreArchivo;
    }
}

$stmt = $pdo->prepare("INSERT INTO promociones (titulo, descripcion, imagen, precio_promocional, descuento, fecha_inicio, fecha_fin, activo, vigente, tipo_publico)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$titulo, $descripcion, $imagen, $precio_promocional, $descuento, $fecha_inicio, $fecha_fin, $activo, $vigente, $tipo_publico]);

$_SESSION['mensaje'] = "Promoción creada correctamente.";
header('Location: ' . BASE_URL . 'dashboard.php?vista=promociones');
exit;
