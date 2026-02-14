<?php
require_once __DIR__ . '/../conexion/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$month = isset($_GET['month']) ? trim((string)$_GET['month']) : '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode([
        'ok' => false,
        'message' => 'Parámetro month inválido. Formato esperado: YYYY-MM',
        'data' => [],
    ]);
    exit;
}

[$year, $monthNum] = array_map('intval', explode('-', $month));
if ($year < 2000 || $year > 2100 || $monthNum < 1 || $monthNum > 12) {
    echo json_encode([
        'ok' => false,
        'message' => 'Parámetro month fuera de rango.',
        'data' => [],
    ]);
    exit;
}

$desde = sprintf('%04d-%02d-01 00:00:00', $year, $monthNum);
$hastaTs = strtotime(sprintf('%04d-%02d-01', $year, $monthNum) . ' +1 month');
$hasta = date('Y-m-d 00:00:00', $hastaTs);

try {
    $sql = "SELECT
                cd.nombre_examen AS examen,
                SUM(COALESCE(cd.cantidad, 1)) AS cantidad,
                SUM(COALESCE(cd.subtotal, 0)) AS monto
            FROM cotizaciones_detalle cd
            INNER JOIN cotizaciones c ON c.id = cd.id_cotizacion
            WHERE c.fecha >= ? AND c.fecha < ?
            GROUP BY cd.nombre_examen
            ORDER BY cantidad DESC, monto DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$desde, $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCantidad = 0;
    $totalMonto = 0.0;

    $data = [];
    foreach ($rows as $r) {
        $cantidad = (int)($r['cantidad'] ?? 0);
        $monto = (float)($r['monto'] ?? 0);
        $totalCantidad += $cantidad;
        $totalMonto += $monto;

        $data[] = [
            'examen' => (string)($r['examen'] ?? ''),
            'cantidad' => $cantidad,
            'monto' => $monto,
        ];
    }

    echo json_encode([
        'ok' => true,
        'month' => $month,
        'desde' => $desde,
        'hasta' => $hasta,
        'total_cantidad' => $totalCantidad,
        'total_monto' => $totalMonto,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Error al consultar estadísticas: ' . $e->getMessage(),
        'data' => [],
    ]);
}
