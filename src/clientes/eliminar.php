<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['msg'] = 'ID de cliente no proporcionado.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}

try {
    // Eliminar relaciones en tablas intermedias
    $stmt1 = $pdo->prepare("DELETE FROM convenio_cliente WHERE cliente_id = ?");
    $stmt1->execute([$id]);

    $stmt2 = $pdo->prepare("DELETE FROM empresa_cliente WHERE cliente_id = ?");
    $stmt2->execute([$id]);

    // Eliminar el cliente
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['msg'] = 'Cliente eliminado correctamente.';

    // Redirección según rol
   if ($_SESSION['rol'] === 'empresa') {
    header('Location: ../src/dashboard.php?vista=clientes_empresa');
    exit;
}
if ($_SESSION['rol'] === 'convenio') {
    header('Location: ../dashboard.php?vista=clientes_convenio');
    exit;
}
header('Location: ../dashboard.php?vista=clientes');
exit;

} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al eliminar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}
