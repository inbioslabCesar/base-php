<?php
require_once __DIR__ . '/../conexion/conexion.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Verifica relaciones en cotizaciones_detalle
    $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM cotizaciones_detalle WHERE id_examen = ?');
    $stmt->execute([$id]);
    $cnt = (int)$stmt->fetchColumn();
    echo json_encode(['ok' => true, 'tiene_relaciones' => $cnt > 0, 'conteo' => $cnt]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>