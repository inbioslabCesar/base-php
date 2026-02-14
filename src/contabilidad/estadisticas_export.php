<?php
require_once __DIR__ . '/../conexion/conexion.php';

$month = isset($_GET['month']) ? trim((string)$_GET['month']) : '';
$format = isset($_GET['format']) ? strtolower(trim((string)$_GET['format'])) : 'xls';
$top10 = isset($_GET['top10']) ? (int)$_GET['top10'] : 0;

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo 'Parámetro month inválido. Formato esperado: YYYY-MM';
    exit;
}

[$year, $monthNum] = array_map('intval', explode('-', $month));
if ($year < 2000 || $year > 2100 || $monthNum < 1 || $monthNum > 12) {
    http_response_code(400);
    echo 'Parámetro month fuera de rango.';
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

    $data = [];
    $totalCantidad = 0;
    $totalMonto = 0.0;

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

    if ($top10 > 0 && count($data) > 10) {
        $data = array_slice($data, 0, 10);
    }

    $filenameBase = 'estadisticas_' . $month . ($top10 > 0 ? '_top10' : '');

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Mes', $month]);
        fputcsv($out, []);
        fputcsv($out, ['Examen', 'Cantidad', 'Monto']);
        foreach ($data as $row) {
            fputcsv($out, [$row['examen'], $row['cantidad'], number_format((float)$row['monto'], 2, '.', '')]);
        }
        fputcsv($out, []);
        fputcsv($out, ['TOTAL', $totalCantidad, number_format((float)$totalMonto, 2, '.', '')]);
        fclose($out);
        exit;
    }

    if ($format === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<h3>Estadística de Exámenes</h3>";
        echo "<div><strong>Mes:</strong> " . htmlspecialchars($month) . "</div>";
        if ($top10 > 0) {
            echo "<div><strong>Filtro:</strong> Top 10</div>";
        }
        echo "<br>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
        echo "<thead><tr style='background:#d9edf7; font-weight:bold;'><th>Examen</th><th>Cantidad</th><th>Monto (S/)</th></tr></thead><tbody>";
        foreach ($data as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['examen']) . "</td>";
            echo "<td align='right'>" . number_format((float)$row['cantidad'], 0, '.', ',') . "</td>";
            echo "<td align='right'>" . number_format((float)$row['monto'], 2, '.', ',') . "</td>";
            echo "</tr>";
        }
        echo "<tr style='font-weight:bold;'>";
        echo "<td>TOTAL</td>";
        echo "<td align='right'>" . number_format((float)$totalCantidad, 0, '.', ',') . "</td>";
        echo "<td align='right'>" . number_format((float)$totalMonto, 2, '.', ',') . "</td>";
        echo "</tr>";
        echo "</tbody></table>";
        echo "</body></html>";
        exit;
    }

    if ($format === 'pdf') {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $html = "<style>
            body { font-family: sans-serif; font-size: 10pt; }
            h3 { margin: 0 0 6px 0; }
            .meta { margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 6px; }
            th { background: #e9ecef; }
            .right { text-align: right; }
        </style>";

        $html .= "<h3>Estadística de Exámenes</h3>";
        $html .= "<div class='meta'><strong>Mes:</strong> " . htmlspecialchars($month) . "";
        if ($top10 > 0) {
            $html .= " &nbsp; <strong>Filtro:</strong> Top 10";
        }
        $html .= "</div>";

        $html .= "<table><thead><tr><th>Examen</th><th class='right'>Cantidad</th><th class='right'>Monto (S/)</th></tr></thead><tbody>";
        foreach ($data as $row) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($row['examen']) . "</td>";
            $html .= "<td class='right'>" . number_format((float)$row['cantidad'], 0, '.', ',') . "</td>";
            $html .= "<td class='right'>" . number_format((float)$row['monto'], 2, '.', ',') . "</td>";
            $html .= "</tr>";
        }
        $html .= "<tr>";
        $html .= "<td><strong>TOTAL</strong></td>";
        $html .= "<td class='right'><strong>" . number_format((float)$totalCantidad, 0, '.', ',') . "</strong></td>";
        $html .= "<td class='right'><strong>" . number_format((float)$totalMonto, 2, '.', ',') . "</strong></td>";
        $html .= "</tr>";
        $html .= "</tbody></table>";

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output($filenameBase . '.pdf', 'D');
        exit;
    }

    http_response_code(400);
    echo 'Formato inválido. Use csv, xls o pdf.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error al exportar estadísticas: ' . $e->getMessage();
}
