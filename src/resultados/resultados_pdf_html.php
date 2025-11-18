<?php
// Función para armar el HTML y CSS del reporte de resultados
function armarHtmlReporte($paciente, $referencia, $empresa, $items) {
    $css = 'body, table, td, th { font-family: "Segoe UI", Arial, Helvetica, sans-serif; }'
        . '.encabezado-tabla { width: 100%; border-bottom: 2px solid #eee; margin-bottom: 0px; }'
        . '.logo { width: 110px; }'
        . '.direccion { text-align: right; font-size: 13px; color: #555; vertical-align: top; }'
        . '.datos-cliente-tabla { width: 100%; margin: 10px 0 15px 0; font-size: 13px; color: #333; }'
        . '.datos-cliente-tabla td { padding: 2px 8px; vertical-align: top; }'
        . '.titulo-reporte { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 18px; color: #1a237e; letter-spacing: 1px; width: 100%; }'
        . '.tabla-resultados { width: 100%; border-collapse: collapse; margin-top: 0.5px; font-size: 11px; }'
        . '.tabla-resultados th { background: #d7e3fcff; font-size: 11px; color: #1a237e; font-weight: bold; border: none; text-align: left; height: 32px; }'
        . '.tabla-resultados td { font-size: 11px; border: none; padding: 2px 8px; text-align: left; vertical-align: middle; }'
        . '.referencia-list { margin: 0; padding-left: 16px; font-size: 0.97em; color: #222; }'
        . '.firma-footer { text-align: right; margin-top: 45px; }'
        . '.subtitulo { background: #e3e8f5 !important; color: #1a237e !important; font-weight: bold !important; border-radius: 6px; }';

    // El título debe ir fuera de la tabla para que se centre correctamente
    $html = '<div style="height:32px;"></div>';
    $html .= '<table class="tabla-resultados"><thead>';
    $html .= '<tr><th colspan="5" style="text-align:center;" class="titulo-reporte">Reporte de Resultados</th></tr>';
    $html .= '<tr><th>Prueba</th><th>Metodología</th><th>Resultado</th><th>Unidades</th><th>Valores de Referencia</th></tr></thead><tbody>';

    $sinDecimales = ['R_GLOBULOS_BLANCOS', 'PLAQUETAS'];
    foreach ($items as $item) {
        if ($item['tipo'] === "Título") {
            $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
            $color_texto = $item['color_texto'] ?? "#1a237e";
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $html .= '<tr class="subtitulo"><td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';border-radius:6px;text-align:center;">' . htmlspecialchars($item['prueba']) . '</td></tr>';
        } elseif ($item['tipo'] === "Subtítulo") {
            $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
            $color_texto = $item['color_texto'] ?? "#1a237e";
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $html .= '<tr class="subtitulo"><td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';border-radius:6px;">' . htmlspecialchars($item['prueba']) . '</td></tr>';
        } elseif ($item['tipo'] === "Parámetro") {
            $referencias = isset($item['referencias']) && is_array($item['referencias'])
                ? $item['referencias']
                : (isset($item['referencia']) ? $item['referencia'] : []);
            $refHTML = '';
            if (is_array($referencias)) {
                $refHTML = '<ul class="referencia-list">';
                foreach ($referencias as $ref) {
                    if (is_array($ref) && isset($ref['desc'])) {
                        $refHTML .= '<li><strong>' . htmlspecialchars($ref['desc']) . '</strong> ' . htmlspecialchars($ref['valor']) . '</li>';
                    } else {
                        $refHTML .= '<li>' . htmlspecialchars(is_array($ref) && isset($ref['valor']) ? $ref['valor'] : $ref) . '</li>';
                    }
                }
                $refHTML .= '</ul>';
            } elseif ($referencias) {
                $refHTML = htmlspecialchars($referencias);
            }
            $valorFormateado = $item['valor'];
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            if (in_array($item['prueba'], $sinDecimales) && is_numeric(str_replace(',', '', $valorFormateado))) {
                $valorFormateado = number_format(floatval(str_replace(',', '', $valorFormateado)), 0, '', ',');
            } elseif ($valorFormateado !== "" && !is_null($valorFormateado) && is_numeric($valorFormateado)) {
                $valorFormateado = number_format($valorFormateado, 1, '.', '');
            }
            if (is_array($valorFormateado)) {
                foreach ($valorFormateado as $valorSel) {
                    if ($valorSel !== '' && $valorSel !== null) {
                        $html .= '<tr>';
                        $html .= '<td style="font-weight:' . $font_weight . '">' . htmlspecialchars($item['prueba']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
                        $html .= '<td>' . htmlspecialchars($valorSel) . '</td>';
                        $html .= '<td>' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
                        $html .= '<td>' . $refHTML . '</td>';
                        $html .= '</tr>';
                    }
                }
            } else {
                $html .= '<tr>';
                $html .= '<td style="font-weight:' . $font_weight . '">' . htmlspecialchars($item['prueba']) . '</td>';
                $html .= '<td>' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
                $html .= '<td>' . htmlspecialchars($valorFormateado) . '</td>';
                $html .= '<td>' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
                $html .= '<td>' . $refHTML . '</td>';
                $html .= '</tr>';
            }
        }
    }
    $html .= '</tbody></table>';
    return [ 'css' => $css, 'html' => $html ];
}
