<?php
// Exportación de la estadística SIS por código prestacional: PDF (1 página, mPDF) y Excel. Sin precios.
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/estadisticas_sis_datos.php';

$ctxSis = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [];
if (empty($ctxSis['es_sis'])) {
    http_response_code(403);
    echo 'La estadística SIS solo está disponible en modo operativo SIS.';
    exit;
}

$format = strtolower(trim((string)($_GET['format'] ?? 'xls')));
[$desde, $hasta] = sis_estadistica_rango($_GET['desde'] ?? null, $_GET['hasta'] ?? null);

try {
    $resumen = sis_estadistica_resumen($pdo, $desde, $hasta);
    if (!$resumen['disponible']) {
        http_response_code(409);
        echo 'Falta aplicar la migración sql/2026_10_18_codigos_prestacionales_sis/migracion.sql.';
        exit;
    }

    $empresa = ui_theme_fetch_company_config($pdo);
    $nombreEmpresa = trim((string)($empresa['nombre'] ?? ''));
    $periodo = date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
    $filenameBase = 'estadistica_sis_' . $desde . '_' . $hasta;
    $h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    if ($format === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo '<h3>Estadística SIS por código prestacional</h3>';
        if ($nombreEmpresa !== '') {
            echo '<div><strong>Establecimiento:</strong> ' . $h($nombreEmpresa) . '</div>';
        }
        echo '<div><strong>Periodo:</strong> ' . $h($periodo) . '</div><br>';
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
        echo "<thead><tr style='background:#d9edf7;font-weight:bold;'><th>Código</th><th>Nombre</th><th>Descripción MINSA</th><th>Atenciones</th><th>Pacientes</th><th>Exámenes</th></tr></thead><tbody>";
        foreach ($resumen['filas'] as $f) {
            echo '<tr><td>' . $h($f['codigo']) . '</td><td>' . $h($f['nombre']) . '</td><td>' . $h($f['descripcion_minsa'] ?? '') . '</td>'
                . "<td align='right'>" . (int)$f['atenciones'] . "</td><td align='right'>" . (int)$f['pacientes'] . "</td><td align='right'>" . (int)$f['examenes'] . '</td></tr>';
        }
        echo "<tr style='font-weight:bold;'><td colspan='3'>TOTAL</td><td align='right'>" . (int)$resumen['total_atenciones']
            . "</td><td align='right'>" . (int)$resumen['total_pacientes'] . " (únicos)</td><td align='right'>" . (int)$resumen['total_examenes'] . '</td></tr>';
        echo '</tbody></table></body></html>';
        exit;
    }

    if ($format === 'pdf') {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $projectRoot = realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');

        $tarjetas = '';
        foreach ($resumen['filas'] as $i => $f) {
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($f['color'] ?? '')) ? $f['color'] : '#6c757d';
            $icono = '';
            $ruta = (string)($f['icono_ruta'] ?? '');
            $abs = $ruta !== '' ? realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta)) : false;
            if ($abs && preg_match('/\.(png|webp)$/i', $abs)) {
                $icono = '<img src="' . $h(str_replace('\\', '/', $abs)) . '" width="26" height="26"> ';
            }
            $tarjetas .= '<td style="width:33%;padding:4px;vertical-align:top;">'
                . '<table style="width:100%;border:1px solid #ccd;border-top:4px solid ' . $color . ';border-collapse:collapse;">'
                . '<tr><td style="padding:6px;">' . $icono . '<b style="color:' . $color . ';font-size:13pt;">' . $h($f['codigo']) . '</b> <b>' . $h($f['nombre']) . '</b><br>'
                . '<span style="font-size:7pt;color:#555;">' . $h($f['descripcion_minsa'] ?? '') . '</span></td></tr>'
                . '<tr><td style="padding:4px 6px;font-size:9pt;">Atenciones: <b>' . (int)$f['atenciones'] . '</b> &nbsp; Pacientes: <b>' . (int)$f['pacientes'] . '</b> &nbsp; Exámenes: <b>' . (int)$f['examenes'] . '</b></td></tr>'
                . '</table></td>';
            if (($i + 1) % 3 === 0) {
                $tarjetas .= '</tr><tr>';
            }
        }

        $html = "<style>
            body { font-family: sans-serif; font-size: 9pt; }
            h2 { margin: 0 0 2px 0; }
            .meta { color: #444; margin-bottom: 8px; }
            table.res { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.res th, table.res td { border: 1px solid #000; padding: 4px; }
            table.res th { background: #e9ecef; }
            .right { text-align: right; }
        </style>";
        $html .= '<h2>Estadística SIS por código prestacional</h2>';
        $html .= '<div class="meta">' . ($nombreEmpresa !== '' ? '<b>Establecimiento:</b> ' . $h($nombreEmpresa) . ' &nbsp; ' : '')
            . '<b>Periodo:</b> ' . $h($periodo) . '</div>';
        $html .= '<table style="width:100%;border-collapse:separate;border-spacing:4px;"><tr>'
            . '<td style="border:1px solid #0d6efd;padding:6px;text-align:center;"><span style="font-size:7pt;">ATENCIONES SIS</span><br><b style="font-size:15pt;">' . (int)$resumen['total_atenciones'] . '</b></td>'
            . '<td style="border:1px solid #198754;padding:6px;text-align:center;"><span style="font-size:7pt;">PACIENTES ÚNICOS</span><br><b style="font-size:15pt;">' . (int)$resumen['total_pacientes'] . '</b></td>'
            . '<td style="border:1px solid #fd7e14;padding:6px;text-align:center;"><span style="font-size:7pt;">EXÁMENES</span><br><b style="font-size:15pt;">' . (int)$resumen['total_examenes'] . '</b></td>'
            . '</tr></table>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin-top:6px;"><tr>' . $tarjetas . '</tr></table>';

        $html .= '<table class="res"><thead><tr><th>Código</th><th>Nombre</th><th class="right">Atenciones</th><th class="right">Pacientes</th><th class="right">Exámenes</th></tr></thead><tbody>';
        foreach ($resumen['filas'] as $f) {
            $html .= '<tr><td>' . $h($f['codigo']) . '</td><td>' . $h($f['nombre']) . '</td><td class="right">' . (int)$f['atenciones']
                . '</td><td class="right">' . (int)$f['pacientes'] . '</td><td class="right">' . (int)$f['examenes'] . '</td></tr>';
        }
        $html .= '<tr><td colspan="2"><b>TOTAL</b></td><td class="right"><b>' . (int)$resumen['total_atenciones'] . '</b></td><td class="right"><b>'
            . (int)$resumen['total_pacientes'] . '*</b></td><td class="right"><b>' . (int)$resumen['total_examenes'] . '</b></td></tr></tbody></table>';
        $html .= '<div style="font-size:7pt;color:#555;margin-top:6px;">* Pacientes únicos en el periodo. Excluye cotizaciones anuladas. Generado el ' . date('d/m/Y H:i') . '.</div>';

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->SetTitle('Estadística SIS ' . $periodo);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filenameBase . '.pdf', 'D');
        exit;
    }

    http_response_code(400);
    echo 'Formato inválido. Use xls o pdf.';
} catch (Throwable $e) {
    error_log('estadisticas_sis_export: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al exportar la estadística SIS.';
}
