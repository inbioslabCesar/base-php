<?php
// CLI/HTTP runner to emit a cotización and print status
require_once __DIR__ . '/../src/conexion/conexion.php';
require_once __DIR__ . '/../src/facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../src/facturacion/FacturacionService.php';

$id = 0;
if (PHP_SAPI === 'cli') {
    $id = isset($argv[1]) ? (int)$argv[1] : 0;
} else {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

header('Content-Type: application/json');
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debe indicar id de cotización']);
    exit(1);
}

try {
    $auth = new FacturacionAuthService();
    $svc = new FacturacionService($pdo, $auth);
    $emit = $svc->emitirComprobante($id, []);
    // Intentar refrescar estado desde el API
    $status = $svc->refreshRemoteStatus($id);
    if (!$status) { $status = $svc->getStatus($id); }
    echo json_encode([
        'success' => true,
        'cotizacion_id' => $id,
        'emit' => $emit,
        'status' => $status,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit(1);
}
