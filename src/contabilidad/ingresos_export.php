<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';

$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'pdf';
if (!in_array($format, ['pdf', 'csv', 'xls', 'print'], true)) {
    $format = 'pdf';
}

// Por defecto exporta con detalle (exámenes y precios). Se puede desactivar con ?detalle=0
$includeDetalle = !isset($_GET['detalle']) || trim((string)$_GET['detalle']) !== '0';

function normalize_date(string $value, string $fallback): string {
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        return $value;
    }
    return $fallback;
}

function format_metodo_pago_display(?string $metodoPago): string {
    if ($metodoPago === null || trim($metodoPago) === '') {
        return 'Sin pago';
    }

    $metodos = array_map('trim', explode(',', $metodoPago));
    $metodos = array_map(static function (string $metodo): string {
        return strtolower($metodo) === 'yape' ? 'Yape/Plin' : $metodo;
    }, $metodos);

    return implode(', ', $metodos);
}

$desde = normalize_date($_GET['desde'] ?? '', date('Y-m-01'));
$hasta = normalize_date($_GET['hasta'] ?? '', date('Y-m-d'));
$tipo_paciente = $_GET['tipo_paciente'] ?? 'todos';
$filtro_convenio = $_GET['filtro_convenio'] ?? '';
$filtro_empresa = $_GET['filtro_empresa'] ?? '';
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

$orderCol = isset($_GET['orderCol']) ? (int)$_GET['orderCol'] : 1;
$orderDir = isset($_GET['orderDir']) ? strtolower((string)$_GET['orderDir']) : 'desc';
$orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';

// Orden seguro (evita inyección y columnas inexistentes)
$orderMap = [
    0 => 'c.codigo',
    1 => 'c.fecha',
    3 => 'cl.nombre',
    5 => 'referencia_orden'
];
$orderBy = $orderMap[$orderCol] ?? 'c.fecha';

$where = "WHERE DATE(c.fecha) BETWEEN ? AND ?";
$params = [$desde, $hasta];

if ($tipo_paciente === 'convenio') {
    $where .= " AND c.id_convenio IS NOT NULL";
    if ($filtro_convenio !== '') {
        $where .= " AND c.id_convenio = ?";
        $params[] = $filtro_convenio;
    }
} elseif ($tipo_paciente === 'empresa') {
    $where .= " AND c.id_empresa IS NOT NULL";
    if ($filtro_empresa !== '') {
        $where .= " AND c.id_empresa = ?";
        $params[] = $filtro_empresa;
    }
} elseif ($tipo_paciente === 'particular') {
    $where .= " AND c.id_convenio IS NULL AND c.id_empresa IS NULL";
}

if ($search !== '') {
    $where .= " AND (c.codigo LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR conv.nombre LIKE ? OR emp.nombre_comercial LIKE ?)";
    $searchLike = "%$search%";
    $params = array_merge($params, [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
}

$sql = "SELECT 
    c.id AS id_cotizacion,
    c.codigo AS codigo_cotizacion,
    c.fecha,
    (SELECT GROUP_CONCAT(DISTINCT p3.metodo_pago SEPARATOR ', ') FROM pagos p3 WHERE p3.id_cotizacion = c.id) AS metodo_pago,
    CONCAT(cl.nombre, ' ', cl.apellido) AS cliente,
    CASE 
        WHEN c.id_empresa IS NOT NULL THEN 'Empresa'
        WHEN c.id_convenio IS NOT NULL THEN 'Convenio'
        ELSE 'Particular'
    END AS tipo_paciente,
    CASE 
        WHEN c.id_empresa IS NOT NULL THEN emp.nombre_comercial
        WHEN c.id_convenio IS NOT NULL THEN conv.nombre
        ELSE 'Particular'
    END AS referencia,
    c.total AS total_cotizacion,
    (SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_cotizacion = c.id) AS adelanto,
    GREATEST(0, c.total - (SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_cotizacion = c.id)) AS deuda,
    CASE 
        WHEN c.id_empresa IS NOT NULL THEN emp.nombre_comercial
        WHEN c.id_convenio IS NOT NULL THEN conv.nombre
        ELSE 'Particular'
    END AS referencia_orden
FROM cotizaciones c
JOIN clientes cl ON c.id_cliente = cl.id
LEFT JOIN convenios conv ON c.id_convenio = conv.id
LEFT JOIN empresas emp ON c.id_empresa = emp.id
$where
ORDER BY $orderBy $orderDir";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar detalle de exámenes por cotización (para export con desglose)
$detallePorCotizacion = [];
if ($includeDetalle && $rows) {
    $cotIds = [];
    foreach ($rows as $r) {
        if (isset($r['id_cotizacion'])) {
            $cotIds[] = (int)$r['id_cotizacion'];
        }
    }
    $cotIds = array_values(array_unique(array_filter($cotIds)));

    if ($cotIds) {
        $placeholders = implode(',', array_fill(0, count($cotIds), '?'));
        $stmtDet = $pdo->prepare(
            "SELECT id_cotizacion, nombre_examen, cantidad, precio_unitario, subtotal
             FROM cotizaciones_detalle
             WHERE id_cotizacion IN ($placeholders)
             ORDER BY id_cotizacion ASC"
        );
        $stmtDet->execute($cotIds);
        $detRows = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        foreach ($detRows as $d) {
            $idCot = (int)$d['id_cotizacion'];
            if (!isset($detallePorCotizacion[$idCot])) {
                $detallePorCotizacion[$idCot] = [];
            }
            $detallePorCotizacion[$idCot][] = $d;
        }
    }
}

$filenameBase = "ingresos_{$desde}_{$hasta}";

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');

    // BOM para que Excel reconozca UTF-8 (tildes, ñ)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // En muchos Windows/Excel en español el separador por defecto es ';'
    $delimiter = ';';
    if ($includeDetalle) {
        fputcsv($out, ['Codigo Cotizacion', 'Fecha', 'Metodo Pago', 'Cliente', 'Tipo Paciente', 'Referencia', 'Examen', 'Cantidad', 'Precio Unitario', 'Subtotal Examen', 'Total Cotizacion', 'Adelanto', 'Deuda'], $delimiter);

        foreach ($rows as $r) {
            $idCot = (int)$r['id_cotizacion'];
            $metodo = format_metodo_pago_display($r['metodo_pago'] ?? null);
            $deudaText = number_format((float)$r['deuda'], 2);
            $det = $detallePorCotizacion[$idCot] ?? [];

            if (!$det) {
                fputcsv($out, [
                    $r['codigo_cotizacion'],
                    $r['fecha'],
                    $metodo,
                    $r['cliente'],
                    $r['tipo_paciente'],
                    $r['referencia'],
                    '',
                    '',
                    '',
                    '',
                    number_format((float)$r['total_cotizacion'], 2),
                    number_format((float)$r['adelanto'], 2),
                    $deudaText,
                ], $delimiter);
                continue;
            }

            foreach ($det as $d) {
                fputcsv($out, [
                    $r['codigo_cotizacion'],
                    $r['fecha'],
                    $metodo,
                    $r['cliente'],
                    $r['tipo_paciente'],
                    $r['referencia'],
                    $d['nombre_examen'],
                    (int)($d['cantidad'] ?? 1),
                    number_format((float)($d['precio_unitario'] ?? 0), 2),
                    number_format((float)($d['subtotal'] ?? 0), 2),
                    number_format((float)$r['total_cotizacion'], 2),
                    number_format((float)$r['adelanto'], 2),
                    $deudaText,
                ], $delimiter);
            }
        }
    } else {
        fputcsv($out, ['Codigo Cotizacion', 'Fecha', 'Metodo Pago', 'Cliente', 'Tipo Paciente', 'Referencia', 'Total', 'Adelanto', 'Deuda'], $delimiter);

        foreach ($rows as $r) {
            $metodo = format_metodo_pago_display($r['metodo_pago'] ?? null);
            $deudaText = ((float)$r['deuda'] > 0) ? number_format((float)$r['deuda'], 2) : '0.00';
            fputcsv($out, [
                $r['codigo_cotizacion'],
                $r['fecha'],
                $metodo,
                $r['cliente'],
                $r['tipo_paciente'],
                $r['referencia'],
                number_format((float)$r['total_cotizacion'], 2),
                number_format((float)$r['adelanto'], 2),
                $deudaText,
            ], $delimiter);
        }
    }

    fclose($out);
    exit;
}

// HTML para PDF / Print
$title = 'Reporte de Deudas y Adelantos';

$totalAdelanto = 0.0;
$totalDeuda = 0.0;
foreach ($rows as $r) {
    $totalAdelanto += (float)$r['adelanto'];
    $totalDeuda += (float)$r['deuda'];
}

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 6px 0; }
        .meta { margin: 0 0 12px 0; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #4f46e5; color: #fff; font-weight: bold; }
        .main-table { table-layout: fixed; }
        .main-table td { overflow-wrap: break-word; }
        tfoot td { font-weight: bold; background: #e0f2fe; }
        .right { text-align: right; }
        /* Detalle sin tabla anidada (mPDF encoge tablas anidadas en exportes largos) */
        .detalle-head td,
        .detalle-row td {
            border: 1px solid #eee;
            padding: 2px 4px;
            font-size: 9.5px;
            line-height: 1.15;
            vertical-align: top;
        }
        .detalle-head td {
            background: #f3f4f6;
            font-weight: bold;
        }
        .detalle-row td:nth-child(2),
        .detalle-row td:nth-child(3),
        .detalle-row td:nth-child(4),
        .detalle-head td:nth-child(2),
        .detalle-head td:nth-child(3),
        .detalle-head td:nth-child(4) {
            white-space: nowrap;
        }
        .detalle-row td:first-child { overflow-wrap: break-word; }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($title) ?></h2>
    <div class="meta">
        Periodo: <b><?= htmlspecialchars($desde) ?></b> a <b><?= htmlspecialchars($hasta) ?></b>
        <?php if ($search !== ''): ?>
            | Busqueda: <b><?= htmlspecialchars($search) ?></b>
        <?php endif; ?>
    </div>

    <table class="main-table">
        <colgroup>
            <col style="width:16%;">
            <col style="width:13%;">
            <col style="width:9%;">
            <col style="width:22%;">
            <col style="width:6%;">
            <col style="width:12%;">
            <col style="width:7%;">
            <col style="width:7%;">
            <col style="width:8%;">
        </colgroup>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Fecha</th>
                <th>Met. Pago</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Referencia</th>
                <th class="right">Total</th>
                <th class="right">Adelanto</th>
                <th class="right">Deuda</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['codigo_cotizacion']) ?></td>
                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                    <td><?= htmlspecialchars(format_metodo_pago_display($r['metodo_pago'] ?? null)) ?></td>
                    <td><?= htmlspecialchars($r['cliente']) ?></td>
                    <td><?= htmlspecialchars($r['tipo_paciente']) ?></td>
                    <td><?= htmlspecialchars($r['referencia']) ?></td>
                    <td class="right">S/ <?= number_format((float)$r['total_cotizacion'], 2) ?></td>
                    <td class="right">S/ <?= number_format((float)$r['adelanto'], 2) ?></td>
                    <td class="right"><?php if ((float)$r['deuda'] > 0): ?>S/ <?= number_format((float)$r['deuda'], 2) ?><?php else: ?>Sin deuda<?php endif; ?></td>
                </tr>

                <?php if ($includeDetalle):
                    $idCot = (int)$r['id_cotizacion'];
                    $det = $detallePorCotizacion[$idCot] ?? [];
                ?>
                    <?php if (!$det): ?>
                        <tr class="detalle-row">
                            <td colspan="9" style="padding:6px;color:#555;">Sin detalle de exámenes</td>
                        </tr>
                    <?php else: ?>
                        <tr class="detalle-head">
                            <td colspan="6">Examen</td>
                            <td class="right">Cant</td>
                            <td class="right">P.Unit</td>
                            <td class="right">Subtotal</td>
                        </tr>
                        <?php foreach ($det as $d): ?>
                            <tr class="detalle-row">
                                <td colspan="6"><?= htmlspecialchars((string)$d['nombre_examen']) ?></td>
                                <td class="right"><?= (int)($d['cantidad'] ?? 1) ?></td>
                                <td class="right"><?= number_format((float)($d['precio_unitario'] ?? 0), 2) ?></td>
                                <td class="right"><?= number_format((float)($d['subtotal'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="right">Totales del periodo:</td>
                <td class="right">S/ <?= number_format($totalAdelanto, 2) ?></td>
                <td class="right">S/ <?= number_format($totalDeuda, 2) ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

if ($format === 'xls') {
    // Excel abre HTML con tabla como hoja de cálculo real (columnas correctas)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');
    echo "\xEF\xBB\xBF";

    if ($includeDetalle) {
        ob_start();
        ?>
        <!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 6px; }
                th { background: #f3f4f6; font-weight: bold; }
                .right { text-align: right; }
            </style>
        </head>
        <body>
            <h2><?= htmlspecialchars($title) ?></h2>
            <div>Periodo: <b><?= htmlspecialchars($desde) ?></b> a <b><?= htmlspecialchars($hasta) ?></b></div>
            <br>
            <table>
                <thead>
                    <tr>
                        <th>Codigo Cotizacion</th>
                        <th>Fecha</th>
                        <th>Metodo Pago</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Referencia</th>
                        <th>Examen</th>
                        <th class="right">Cant</th>
                        <th class="right">P.Unit</th>
                        <th class="right">Subtotal</th>
                        <th class="right">Total Cotizacion</th>
                        <th class="right">Adelanto</th>
                        <th class="right">Deuda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $idCot = (int)$r['id_cotizacion'];
                        $metodo = format_metodo_pago_display($r['metodo_pago'] ?? null);
                        $det = $detallePorCotizacion[$idCot] ?? [];
                        if (!$det) {
                            $det = [[
                                'nombre_examen' => '',
                                'cantidad' => '',
                                'precio_unitario' => '',
                                'subtotal' => ''
                            ]];
                        }
                        foreach ($det as $d):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['codigo_cotizacion']) ?></td>
                            <td><?= htmlspecialchars($r['fecha']) ?></td>
                            <td><?= htmlspecialchars($metodo) ?></td>
                            <td><?= htmlspecialchars($r['cliente']) ?></td>
                            <td><?= htmlspecialchars($r['tipo_paciente']) ?></td>
                            <td><?= htmlspecialchars($r['referencia']) ?></td>
                            <td><?= htmlspecialchars((string)$d['nombre_examen']) ?></td>
                            <td class="right"><?= $d['cantidad'] === '' ? '' : (int)($d['cantidad'] ?? 1) ?></td>
                            <td class="right"><?= $d['precio_unitario'] === '' ? '' : number_format((float)($d['precio_unitario'] ?? 0), 2) ?></td>
                            <td class="right"><?= $d['subtotal'] === '' ? '' : number_format((float)($d['subtotal'] ?? 0), 2) ?></td>
                            <td class="right"><?= number_format((float)$r['total_cotizacion'], 2) ?></td>
                            <td class="right"><?= number_format((float)$r['adelanto'], 2) ?></td>
                            <td class="right"><?= number_format((float)$r['deuda'], 2) ?></td>
                        </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        echo ob_get_clean();
        exit;
    }

    echo $html;
    exit;
}

if ($format === 'print') {
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

// PDF
require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
]);

$mpdf->WriteHTML($html);
$mpdf->Output($filenameBase . '.pdf', 'D');
exit;
