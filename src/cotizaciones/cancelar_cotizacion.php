<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Solo admin o recepcionista pueden cancelar
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'recepcionista'])) {
    header("Location: dashboard.php?vista=cotizaciones&msg=sin_permiso");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // Actualiza el estado_pago a 'pagado' (puedes cambiar a 'cancelada' si lo prefieres)
    $stmt = $pdo->prepare("UPDATE cotizaciones SET estado_pago = 'pagado' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../dashboard.php?vista=cotizaciones&msg=cancelada");
    exit;
} else {
    header("Location: dashboard.php?vista=cotizaciones&msg=error");
    exit;
}
?>
