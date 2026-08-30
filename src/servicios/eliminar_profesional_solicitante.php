<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['mensaje'] = 'Profesional solicitante no válido.';
    header('Location: dashboard.php?vista=profesionales_solicitantes');
    exit;
}

try {
    servicios_asegurar_tabla($pdo);
    $stmtRel = $pdo->prepare("DELETE FROM servicio_profesional WHERE profesional_id = ?");
    $stmtRel->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM profesionales_solicitantes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensaje'] = 'Profesional solicitante eliminado correctamente.';
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo eliminar el profesional solicitante.';
}

header('Location: dashboard.php?vista=profesionales_solicitantes');
exit;
