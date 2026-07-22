<?php
// Función para armar el HTML y CSS del reporte de resultados
function armarHtmlReporte($paciente, $referencia, $empresa, $items) {
    // Conversión robusta de números con separadores locales (coma/punto)
    $toNullableFloat = function ($value) {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $s = trim($value);
            if ($s === '') {
                return null;
            }
            // Quitar espacios no separadores
            $s = str_replace([' ', '\u00A0'], '', $s);
            $hasComma = strpos($s, ',') !== false;
            $hasDot = strpos($s, '.') !== false;
            if ($hasComma && $hasDot) {
                $posComma = strrpos($s, ',');
                $posDot = strrpos($s, '.');
                if ($posComma > $posDot) {
                    // Formato tipo "1.234,56" → '.' miles, ',' decimal
                    $s = str_replace('.', '', $s);
                    $s = str_replace(',', '.', $s);
                } else {
                    // Formato tipo "1,234.56" → ',' miles, '.' decimal
                    $s = str_replace(',', '', $s);
                }
            } elseif ($hasComma && !$hasDot) {
                // Solo coma: asumir coma decimal
                $s = str_replace(',', '.', $s);
            } else {
                // Solo punto o sin separadores: dejar como está
            }
            return is_numeric($s) ? floatval($s) : null;
        }
        return is_numeric($value) ? floatval($value) : null;
    };

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

    // El título principal va fuera de cualquier tabla para evitar duplicados visuales.
    $html .= '<div style="height:32px;"></div>';
    $html .= '<div class="titulo-reporte">Reporte de Resultados</div>';

    $legacyTableOpen = false;
    $openLegacyTable = function () use (&$html, &$legacyTableOpen) {
        if ($legacyTableOpen) {
            return;
        }
        $html .= '<table class="tabla-resultados"><thead><tr>';
        $html .= '<th class="prueba">Prueba</th>';
        $html .= '<th class="metodologia">Metodología</th>';
        $html .= '<th class="resultado">Resultado</th>';
        $html .= '<th class="unidades">Unidades</th>';
        $html .= '<th class="referencia">Valores de Referencia</th>';
        $html .= '</tr></thead><tbody>';
        $legacyTableOpen = true;
    };
    $closeLegacyTable = function () use (&$html, &$legacyTableOpen) {
        if (!$legacyTableOpen) {
            return;
        }
        $html .= '</tbody></table>';
        $legacyTableOpen = false;
    };

    $sinDecimales = ['R_GLOBULOS_BLANCOS', 'PLAQUETAS'];
    $seleccionarReferencia = static function (array $referencias, string $sexo, ?float $edad) use ($toNullableFloat): ?array {
        if (empty($referencias)) {
            return null;
        }
        foreach ($referencias as $ref) {
            if (!is_array($ref)) {
                continue;
            }
            $refSexo = strtolower(trim((string)($ref['sexo'] ?? '')));
            $refEdadMin = isset($ref['edad_min']) ? $toNullableFloat($ref['edad_min']) : null;
            $refEdadMax = isset($ref['edad_max']) ? $toNullableFloat($ref['edad_max']) : null;
            $sexoOk = ($refSexo === '' || $refSexo === 'cualquiera' || $refSexo === $sexo);
            $edadOk = true;
            if ($edad !== null) {
                $edadOk = ($refEdadMin === null || $edad >= $refEdadMin) && ($refEdadMax === null || $edad <= $refEdadMax);
            }
            if ($sexoOk && $edadOk) {
                return $ref;
            }
        }
        return is_array($referencias[0] ?? null) ? $referencias[0] : null;
    };

    $renderReferenciaV2 = static function ($value): string {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        $parts = preg_split('/\s*\|\s*|\r\n|\r|\n|\s*;\s*/u', $raw);
        $items = [];
        foreach ((array)$parts as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $items[] = $part;
            }
        }

        if (count($items) <= 1) {
            return htmlspecialchars($raw);
        }

        $htmlList = '<table style="width:100%; border-collapse:collapse; font-size:10px;">';
        foreach ($items as $itemText) {
            $htmlList .= '<tr><td style="padding:2px 0; border-bottom:0.4px solid #d9e3f5;">' . htmlspecialchars($itemText) . '</td></tr>';
        }
        $htmlList .= '</table>';
        return $htmlList;
    };

    foreach ($items as $item) {
        if (($item['tipo'] ?? '') === 'TablaV2') {
            $closeLegacyTable();

            $cols = is_array($item['columnas'] ?? null) ? $item['columnas'] : [];
            $rowsV2 = is_array($item['filas'] ?? null) ? $item['filas'] : [];
            $colCount = max(1, count($cols));

            $html .= '<table style="width:100%; table-layout:fixed; border-collapse:collapse; font-size:10px; margin-top:4px; margin-bottom:8px;">';
            $html .= '<thead><tr>';
            foreach ($cols as $col) {
                $w = trim((string)($col['width'] ?? ''));
                $style = 'padding:4px; background:#d7e3fcff; color:#1a237e; font-weight:bold; text-align:left; vertical-align:middle; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;';
                if ($w !== '') {
                    $style .= ' width:' . htmlspecialchars($w) . ';';
                }
                $html .= '<th style="' . $style . '">' . htmlspecialchars((string)($col['label'] ?? $col['id'] ?? '')) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rowsV2 as $rowV2) {
                if (!is_array($rowV2)) {
                    continue;
                }
                $rowType = strtolower(trim((string)($rowV2['type'] ?? 'data')));
                if ($rowType === 'title' || $rowType === 'subtitle') {
                    $colorFondo = trim((string)($rowV2['color_fondo'] ?? ''));
                    $colorTexto = trim((string)($rowV2['color_texto'] ?? ''));
                    $fontWeight = !empty($rowV2['negrita']) ? 'bold' : 'normal';
                    $fontStyle = !empty($rowV2['cursiva']) ? 'italic' : 'normal';
                    $textAlign = trim((string)($rowV2['alineacion'] ?? ''));
                    if ($textAlign === '') {
                        $textAlign = ($rowType === 'title') ? 'center' : 'left';
                    }
                    $style = 'padding:4px 6px; font-weight:' . $fontWeight . '; font-style:' . $fontStyle . '; text-align:' . htmlspecialchars($textAlign) . ';';
                    if ($colorFondo !== '') {
                        $style .= ' background:' . htmlspecialchars($colorFondo) . ';';
                    }
                    if ($colorTexto !== '') {
                        $style .= ' color:' . htmlspecialchars($colorTexto) . ';';
                    } else {
                        $style .= ' color:#1a237e;';
                    }
                    $html .= '<tr><td colspan="' . $colCount . '" style="' . $style . '">'
                        . htmlspecialchars((string)($rowV2['label'] ?? '')) . '</td></tr>';
                    continue;
                }

                $cells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];
                $html .= '<tr>';
                foreach ($cols as $col) {
                    $colId = (string)($col['id'] ?? '');
                    $val = $cells[$colId] ?? '';
                    $kind = strtolower(trim((string)($col['kind'] ?? 'text')));
                    $w = trim((string)($col['width'] ?? ''));
                    $style = 'padding:4px; vertical-align:top; overflow:hidden; word-wrap:break-word; word-break:break-word;';
                    if ($w !== '') {
                        $style .= ' width:' . htmlspecialchars($w) . ';';
                    }
                    $cellHtml = nl2br(htmlspecialchars((string)$val));
                    if ($kind === 'reference') {
                        $cellHtml = $renderReferenciaV2($val);
                    }
                    $html .= '<td style="' . $style . '">' . $cellHtml . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } elseif ($item['tipo'] === "Título" || $item['tipo'] === "Subtítulo") {
            $openLegacyTable();
            $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
            $color_texto = $item['color_texto'] ?? "#1a237e";
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $font_style = !empty($item['cursiva']) ? 'italic' : 'normal';
            $text_align = isset($item['alineacion']) ? $item['alineacion'] : ($item['tipo'] === "Título" ? 'center' : 'left');
            $textoCabecera = trim((string)($item['nombre'] ?? $item['prueba'] ?? ''));
            $html .= '<tr class="subtitulo"><td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';font-style:' . $font_style . ';border-radius:6px;text-align:' . htmlspecialchars($text_align) . ';">' . htmlspecialchars($textoCabecera) . '</td></tr>';
        } elseif ($item['tipo'] === "Parámetro") {
            $openLegacyTable();
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
            // Discriminación de referencia por sexo y edad con fallback al primer rango disponible
            $fuera_rango = false;
            $referencia_aplicada = null;
            $edad_paciente = isset($paciente['edad']) ? floatval($paciente['edad']) : null;
            $sexo_paciente = isset($paciente['sexo']) ? strtolower(trim($paciente['sexo'])) : '';
            if (isset($item['referencias']) && is_array($item['referencias']) && $valorOriginal !== "" && $toNullableFloat($valorOriginal) !== null) {
                $referencia_aplicada = $seleccionarReferencia((array)$item['referencias'], $sexo_paciente, $edad_paciente);
                $valor_num = $toNullableFloat($valorOriginal);
                if ($referencia_aplicada) {
                    $min = isset($referencia_aplicada['valor_min']) ? $toNullableFloat($referencia_aplicada['valor_min']) : null;
                    $max = isset($referencia_aplicada['valor_max']) ? $toNullableFloat($referencia_aplicada['valor_max']) : null;
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
                    // Respetar decimales definidos en el parámetro, con separador de miles
                    $valorFormateado = number_format($numVal, intval($item['decimales']), '.', ',');
                } elseif (in_array($item['prueba'], $sinDecimales)) {
                    // Forzar sin decimales para pruebas específicas, con separador de miles
                    $valorFormateado = number_format($numVal, 0, '.', ',');
                } elseif (floor($numVal) == $numVal) {
                    // Enteros sin decimales, con separador de miles
                    $valorFormateado = number_format($numVal, 0, '.', ',');
                } else {
                    // Mantener el valor original para decimales no configurados
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
        } elseif ($item['tipo'] === "Texto Largo") {
            $openLegacyTable();
            $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
            $font_style = !empty($item['cursiva']) ? 'italic' : 'normal';
            $text_align = isset($item['alineacion']) ? $item['alineacion'] : 'left';
            $color_fondo = $item['color_fondo'] ?? '';
            $color_texto = $item['color_texto'] ?? '';
            $contenido = isset($item['valor']) ? (string)$item['valor'] : '';
            $html .= '<tr>';
            $html .= '<td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . ';">'
                  . '<div><strong>' . htmlspecialchars($item['prueba']) . '</strong></div>'
                  . '<div>' . nl2br(htmlspecialchars($contenido)) . '</div>'
                  . '</td>';
            $html .= '</tr>';
        }
    }
    $closeLegacyTable();
    return [ 'css' => $css, 'html' => $html ];
}