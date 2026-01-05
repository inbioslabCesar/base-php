<?php
// src/cotizaciones/pago_masivo.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/conexion.php'; // Incluye $pdo
require_once __DIR__ . '/../funciones/cotizaciones_utils.php'; // Función utilitaria
require_once __DIR__ . '/../../facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../../facturacion/FacturacionService.php';

// Solo aceptar POST y JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['cotizaciones']) || !is_array($input['cotizaciones'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$cotizaciones = $input['cotizaciones'];
if (empty($cotizaciones)) {
    echo json_encode(['success' => false, 'message' => 'No hay cotizaciones seleccionadas']);
    exit;
}

try {
    $pdo->beginTransaction();
    $pagosRegistrados = 0;
    // Reusar servicios (evita reinstanciar por cada cotización)
    $auth = new FacturacionAuthService();
    $svc = new FacturacionService($pdo, $auth);
    foreach ($cotizaciones as $idCotizacion) {
        $saldo = obtenerSaldoCotizacion($pdo, $idCotizacion);
        if ($saldo <= 0) continue; // Ya pagado
        // Registrar pago por el saldo pendiente
        $stmtPago = $pdo->prepare('INSERT INTO pagos (id_cotizacion, monto, fecha, metodo_pago) VALUES (?, ?, NOW(), ?)');
        $stmtPago->execute([$idCotizacion, $saldo, 'masivo']);
        $pagosRegistrados++;
        // Actualizar estado de pago a 'pagado' para la cotización liquidada
        $stmtUpd = $pdo->prepare("UPDATE cotizaciones SET estado_pago = 'pagado' WHERE id = ?");
        $stmtUpd->execute([$idCotizacion]);
        try {
            // Respeta el flag por cotización: permitir solo ticket sin emitir CPE
            $stmtFlag = $pdo->prepare("SELECT emitir_comprobante FROM cotizaciones WHERE id = ?");
            $stmtFlag->execute([(int)$idCotizacion]);
            $emitir = (int)($stmtFlag->fetchColumn() ?? 1);
            if ($emitir === 1) {
                $svc->emitirComprobante((int)$idCotizacion, []);
            }
        } catch (Throwable $e) {
            $dir = __DIR__ . '/../../tmp/facturacion/logs';
            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            file_put_contents($dir . '/hook_errors.log', json_encode(['time' => date('c'), 'cotizacion_id' => (int)$idCotizacion, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'pagos' => $pagosRegistrados]);
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
