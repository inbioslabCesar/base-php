<?php
// src/cotizaciones/pago_masivo.php
header('Content-Type: application/json');
require_once '../conexion/conexion.php'; // Incluye $pdo

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
        // Obtener saldo pendiente de la cotización
    $stmt = $pdo->prepare('SELECT total, (SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = c.id) AS pagado FROM cotizaciones c WHERE c.id = ?');
    $stmt->execute([$idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;
    $saldo = floatval($row['total']) - floatval($row['pagado']);
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
