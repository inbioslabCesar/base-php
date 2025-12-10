<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

// Validar que el usuario sea admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Redirige o muestra mensaje de acceso denegado
    header("Location: dashboard.php?vista=cotizaciones&msg=sin_permiso");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Sanitiza el ID y elimina la cotización
    $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: dashboard.php?vista=cotizaciones&msg=eliminado");
    exit;
} else {
    header("Location: dashboard.php?vista=cotizaciones&msg=error");
    exit;
}
?>
