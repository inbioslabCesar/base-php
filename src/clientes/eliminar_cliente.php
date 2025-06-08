<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/clientes_crud.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM clientes WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}
header("Location: " . BASE_URL . "dashboard.php?vista=clientes&success=3");
exit;
