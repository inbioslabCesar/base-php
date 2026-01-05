<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../../facturacion/FacturacionService.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'ID inválido']); exit; }
try {
    $svc = new FacturacionService($pdo, new FacturacionAuthService());
    // Siempre intentamos refrescar el estado desde el API si hay remote_id
    $st = $svc->refreshRemoteStatus($id);
    if (!$st) { $st = $svc->getStatus($id); }
    echo json_encode(['success' => true, 'id' => $id, 'status' => $st ?: ['status' => 'sin_estado']]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
