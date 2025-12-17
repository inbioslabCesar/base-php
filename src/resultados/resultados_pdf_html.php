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
        . '.tabla-resultados td { font-size: 11px; border: none; padding: 2px 8px; text-align: left; vertical-align: top; }'
        . '.tabla-resultados th.prueba, .tabla-resultados td.prueba { width: 30%; }'
        . '.tabla-resultados th.metodologia, .tabla-resultados td.metodologia { width: 15%; }'
        . '.tabla-resultados th.resultado, .tabla-resultados td.resultado { width: 15%; }'
        . '.tabla-resultados th.unidades, .tabla-resultados td.unidades { width: 14%; }'
        . '.tabla-resultados th.referencia, .tabla-resultados td.referencia { width: 26%; }'
        . '.referencia-list { margin: 0; padding-left: 16px; font-size: 0.97em; color: #222; }'
        . '.firma-footer { text-align: right; margin-top: 45px; }'
        . '.subtitulo { background: #e3e8f5 !important; color: #1a237e !important; font-weight: bold !important; border-radius: 6px; }';

    // Generar código QR con datos clave
    $qrText = 'Laboratorio: ' . ($empresa['nombre'] ?? 'MEDDITECH')
        . ' | Resultado ID: ' . ($paciente['id'] ?? '')
        . ' | Paciente: ' . ($paciente['nombre'] ?? '')
        . ' | DNI: ' . ($paciente['dni'] ?? '')
        . ' | Fecha: ' . ($paciente['fecha'] ?? '');
    $qrBase64 = '';
    try {
        if (class_exists('Endroid\\QrCode\\QrCode')) {
            $qr = new \Endroid\QrCode\QrCode($qrText);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qr);
            $qrBase64 = base64_encode($result->getString());
        }
    } catch (\Exception $e) {}

    // Inicializar $html para el contenido principal (sin cabecera)
    $html = '';

    // El título debe ir fuera de la tabla para que se centre correctamente
    $html .= '<div style="height:32px;"></div>';
    $html .= '<table class="tabla-resultados"><thead>';
    $html .= '<tr><th colspan="5" style="text-align:center;" class="titulo-reporte">Reporte de Resultados</th></tr>';
    $html .= '<tr>';
    $html .= '<th class="prueba">Prueba</th>';
    $html .= '<th class="metodologia">Metodología</th>';
    $html .= '<th class="resultado">Resultado</th>';
    $html .= '<th class="unidades">Unidades</th>';
    $html .= '<th class="referencia">Valores de Referencia</th>';
    $html .= '</tr></thead><tbody>';

    $sinDecimales = ['R_GLOBULOS_BLANCOS', 'PLAQUETAS'];
    foreach ($items as $item) {
        if ($item['tipo'] === "Título" || $item['tipo'] === "Subtítulo") {
            $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
            $color_texto = $item['color_texto'] ?? "#1a237e";
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $font_style = !empty($item['cursiva']) ? 'italic' : 'normal';
            $text_align = isset($item['alineacion']) ? $item['alineacion'] : ($item['tipo'] === "Título" ? 'center' : 'left');
            $html .= '<tr class="subtitulo"><td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';font-style:' . $font_style . ';border-radius:6px;text-align:' . htmlspecialchars($text_align) . ';">' . htmlspecialchars($item['prueba']) . '</td></tr>';
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
            $valorOriginal = $item['valor'];
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $font_style = !empty($item['cursiva']) ? 'italic' : 'normal';
            $text_align = isset($item['alineacion']) ? $item['alineacion'] : 'left';
            // Discriminación de referencia por sexo y edad igual que en el formulario web
            $fuera_rango = false;
            $referencia_aplicada = null;
            $edad_paciente = isset($paciente['edad']) ? floatval($paciente['edad']) : null;
            $sexo_paciente = isset($paciente['sexo']) ? strtolower(trim($paciente['sexo'])) : '';
            if (isset($item['referencias']) && is_array($item['referencias']) && $valorOriginal !== "" && is_numeric(str_replace(',', '', $valorOriginal))) {
                // Seleccionar la referencia correcta
                foreach ($item['referencias'] as $ref) {
                    $ref_sexo = isset($ref['sexo']) ? strtolower(trim($ref['sexo'])) : '';
                    $ref_edad_min = isset($ref['edad_min']) ? floatval($ref['edad_min']) : null;
                    $ref_edad_max = isset($ref['edad_max']) ? floatval($ref['edad_max']) : null;
                    $sexo_match = ($ref_sexo === 'cualquiera' || $ref_sexo === $sexo_paciente);
                    $edad_match = ($ref_edad_min === null || $edad_paciente >= $ref_edad_min) && ($ref_edad_max === null || $edad_paciente <= $ref_edad_max);
                    if ($sexo_match && $edad_match) {
                        $referencia_aplicada = $ref;
                        break;
                    }
                }
                $valor_num = floatval(str_replace(',', '', $valorOriginal));
                if ($referencia_aplicada) {
                    $min = isset($referencia_aplicada['valor_min']) ? floatval($referencia_aplicada['valor_min']) : null;
                    $max = isset($referencia_aplicada['valor_max']) ? floatval($referencia_aplicada['valor_max']) : null;
                    if (($min !== null && $valor_num < $min) || ($max !== null && $valor_num > $max)) {
                        $fuera_rango = true;
                    }
                }
            }
            // Formatear valor para mostrar, respetando decimales configurados
            // y evitando añadir ".0" automáticamente cuando no corresponde.
            $valorFormateado = $valorOriginal;
            if ($valorFormateado !== "" && !is_null($valorFormateado) && is_numeric(str_replace(',', '', $valorFormateado))) {
                $numVal = floatval(str_replace(',', '', $valorFormateado));
                if (isset($item['decimales']) && $item['decimales'] !== '' && is_numeric($item['decimales'])) {
                    // Respetar decimales definidos en el parámetro
                    $valorFormateado = number_format($numVal, intval($item['decimales']), '.', '');
                } elseif (in_array($item['prueba'], $sinDecimales)) {
                    // Forzar sin decimales para pruebas específicas
                    $valorFormateado = number_format($numVal, 0, '.', '');
                } elseif (floor($numVal) == $numVal) {
                    // Si es entero y no hay decimales definidos, mostrar sin ".0"
                    $valorFormateado = (string) intval($numVal);
                } else {
                    // Mantener el valor tal como viene (formateado previamente)
                    $valorFormateado = (string) $valorFormateado;
                }
            }
            if ($fuera_rango && $valorFormateado !== "") {
                $valorFormateado = '* ' . $valorFormateado;
            }
            if (is_array($valorFormateado)) {
                foreach ($valorFormateado as $valorSel) {
                    if ($valorSel !== '' && $valorSel !== null) {
                        $html .= '<tr>';
                        $html .= '<td class="prueba" style="font-weight:' . $font_weight . ';font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['prueba']) . '</td>';
                        $html .= '<td class="metodologia" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
                        $html .= '<td class="resultado" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($valorSel) . '</td>';
                        $html .= '<td class="unidades" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
                        $html .= '<td class="referencia" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . $refHTML . '</td>';
                        $html .= '</tr>';
                    }
                }
            } else {
                $html .= '<tr>';
                $html .= '<td class="prueba" style="font-weight:' . $font_weight . ';font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['prueba']) . '</td>';
                $html .= '<td class="metodologia" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
                $html .= '<td class="resultado" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($valorFormateado) . '</td>';
                $html .= '<td class="unidades" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
                $html .= '<td class="referencia" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . $refHTML . '</td>';
                $html .= '</tr>';
            }
        }
    }
    $html .= '</tbody></table>';
    return [ 'css' => $css, 'html' => $html ];
}