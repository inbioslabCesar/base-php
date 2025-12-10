<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
if (!in_array($rol, ['admin', 'recepcionista'])) {
    $_SESSION['mensaje'] = "No tienes permiso para confirmar la toma de muestra.";
    header('Location: ' . BASE_URL . 'dashboard.php?vista=admin');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET estado_muestra = 'realizada' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Toma de muestra confirmada correctamente.";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al confirmar la toma de muestra: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Solicitud inválida para confirmar toma.";
}
header('Location: ' . BASE_URL . 'dashboard.php?vista=pendientes_toma');
exit;
