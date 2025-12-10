<?php
// src/cotizaciones/pago_masivo.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/conexion.php'; // Incluye $pdo
require_once __DIR__ . '/../funciones/cotizaciones_utils.php'; // Función utilitaria

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
    foreach ($cotizaciones as $idCotizacion) {
        $saldo = obtenerSaldoCotizacion($pdo, $idCotizacion);
        if ($saldo <= 0) continue; // Ya pagado
        // Registrar pago por el saldo pendiente
        $stmtPago = $pdo->prepare('INSERT INTO pagos (id_cotizacion, monto, fecha, metodo_pago) VALUES (?, ?, NOW(), ?)');
        $stmtPago->execute([$idCotizacion, $saldo, 'masivo']);
        $pagosRegistrados++;
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'pagos' => $pagosRegistrados]);
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
