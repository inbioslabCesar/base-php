<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $precio_promocional = floatval($_POST['precio_promocional']);
    $descuento = floatval($_POST['descuento']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $vigente = isset($_POST['vigente']) ? 1 : 0;
    $tipo_publico = $_POST['tipo_publico'] ?? 'todos';
    $imagen = $_POST['imagen_actual'] ?? '';

    if (!empty($_FILES['imagen']['name'])) {
        $nombreArchivo = uniqid('promo_') . '_' . basename($_FILES['imagen']['name']);
        $rutaDestino = __DIR__ . '/assets/' . $nombreArchivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagen = $nombreArchivo;
        }
    }

    $stmt = $pdo->prepare("UPDATE promociones SET titulo=?, descripcion=?, imagen=?, precio_promocional=?, descuento=?, fecha_inicio=?, fecha_fin=?, activo=?, vigente=?, tipo_publico=? WHERE id=?");
    $stmt->execute([
        $titulo, $descripcion, $imagen, $precio_promocional, $descuento,
        $fecha_inicio, $fecha_fin, $activo, $vigente, $tipo_publico, $id
    ]);

    $_SESSION['mensaje'] = "Promoción actualizada correctamente.";
    header('Location: ' . BASE_URL . 'dashboard.php?vista=promociones');
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM promociones WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $promocion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promocion) {
        $_SESSION['promocion_editar'] = $promocion;
        header('Location: ' . BASE_URL . 'dashboard.php?vista=form_promocion');
        exit;
    } else {
        $_SESSION['error'] = 'Promoción no encontrada.';
        header('Location: ' . BASE_URL . 'dashboard.php?vista=promociones');
        exit;
    }
}
