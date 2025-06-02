<?php require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM clientes WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}
// Redirige de vuelta a la tabla de clientes 
header('Location: ' . BASE_URL . 'dashboard.php?vista=tabla_clientes');
exit();
