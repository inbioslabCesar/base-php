<?php
require_once __DIR__ . '/../src/conexion/conexion.php';
require_once __DIR__ . '/../src/facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../src/facturacion/FacturacionService.php';

$id = 0;
if (PHP_SAPI === 'cli') { $id = isset($argv[1]) ? (int)$argv[1] : 0; }
else { $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; }

header('Content-Type: application/json');
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'ID inválido']); exit(1); }

try {
    $svc = new FacturacionService($pdo, new FacturacionAuthService());
    $st = $svc->reintentarEnvio($id);
    echo json_encode(['success' => true, 'status' => $st]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit(1);
}
