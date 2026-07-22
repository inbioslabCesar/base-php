<?php
require_once __DIR__ . '/../src/conexion/conexion.php';

$examId = 372;
$stmt = $pdo->prepare('SELECT id, codigo, nombre, area, metodologia, vigente FROM examenes WHERE id = ?');
$stmt->execute([$examId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
