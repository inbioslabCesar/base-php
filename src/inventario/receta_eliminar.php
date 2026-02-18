<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['mensaje'] = 'Receta inválida.';
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM inventario_examen_recetas WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);

    $_SESSION['mensaje'] = 'Receta eliminada correctamente.';
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo eliminar la receta: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario_interno');
exit;
