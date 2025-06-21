<?php
require_once __DIR__ . '/../conexion/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$q = '%' . trim($_GET['q']) . '%';

$stmt = $pdo->prepare("SELECT id, nombre, precio_publico FROM examenes WHERE vigente = 1 AND nombre LIKE ? ORDER BY nombre ASC LIMIT 10");
$stmt->execute([$q]);
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($examenes);
exit;
?>
