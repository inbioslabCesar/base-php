<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_POST['id'] ?? null;
if ($id) {
    $monto = floatval($_POST['monto'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    if ($monto > 0 && $descripcion !== '') {
        $stmt = $pdo->prepare("UPDATE egresos SET monto = ?, descripcion = ?, fecha = ? WHERE id = ?");
        $stmt->execute([$monto, $descripcion, $fecha, $id]);
        header("Location: dashboard.php?vista=egresos&editado=1");
        exit;
    } else {
        header("Location: dashboard.php?vista=egresos_editar&id=$id&msg=error");
        exit;
    }
}
header("Location: dashboard.php?vista=egresos");
exit;
?>
