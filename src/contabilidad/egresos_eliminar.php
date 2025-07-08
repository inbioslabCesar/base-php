<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM egresos WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: dashboard.php?vista=egresos&eliminado=1");
exit;
?>
