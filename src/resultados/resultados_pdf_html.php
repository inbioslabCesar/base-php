<?php
// Función para armar el HTML y CSS del reporte de resultados
function armarHtmlReporte($paciente, $referencia, $empresa, $items) {
    // Regla unificada del sistema: coma como miles y punto como decimal.
    $toNullableFloat = function ($value) {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $s = trim($value);
            if ($s === '') {
                return null;
            }

            // Quitar espacios y mantener separadores numericos validos.
            $s = str_replace([' ', '\u00A0'], '', $s);

            $s = str_replace(',', '', $s);
            if (!preg_match('/^[-+]?\d+(?:\.\d+)?$/', $s)) {
                return null;
            }
            return is_numeric($s) ? floatval($s) : null;
        }
        return is_numeric($value) ? floatval($value) : null;
    };

    $parseNumericExpression = function ($raw) {
        $src = trim((string)$raw);
        if ($src === '') {
            return null;
        }
        if (!preg_match('/^(<=|>=|<|>|=)?\s*([-+]?\d[\d,]*(?:\.\d+)?)$/', $src, $m)) {
            return null;
        }

        $op = isset($m[1]) && $m[1] !== '' ? $m[1] : '=';
        $numRaw = str_replace(',', '', (string)($m[2] ?? ''));
        if (!preg_match('/^[-+]?\d+(?:\.\d+)?$/', $numRaw)) {
            return null;
        }
        if (!is_numeric($numRaw)) {
            return null;
        }

        return [
            'op' => $op,
            'num' => floatval($numRaw),
        ];
    };

    $evaluateNumericExpressionAgainstRange = function ($raw, $min, $max) use ($parseNumericExpression) {
        $parsed = $parseNumericExpression($raw);
        if ($parsed === null) {
            return null;
        }

        $op = (string)($parsed['op'] ?? '=');
        $num = isset($parsed['num']) ? floatval($parsed['num']) : null;
        if ($num === null) {
            return null;
        }

        $hasMin = ($min !== null && is_numeric($min));
        $hasMax = ($max !== null && is_numeric($max));
        if (!$hasMin && !$hasMax) {
            return 'in';
        }

        if ($op === '=') {
            if ($hasMin && $num < $min) return 'out';
            if ($hasMax && $num > $max) return 'out';
            return 'in';
        }

        if ($op === '>') {
            if ($hasMax && $num >= $max) return 'out';
            if ($hasMin && !$hasMax && $num >= $min) return 'in';
            return 'indeterminate';
        }

        if ($op === '>=') {
            if ($hasMax && $num > $max) return 'out';
            if ($hasMin && !$hasMax && $num >= $min) return 'in';
            return 'indeterminate';
        }

        if ($op === '<') {
            if ($hasMin && $num <= $min) return 'out';
            if ($hasMax && !$hasMin && $num <= $max) return 'in';
            return 'indeterminate';
        }

        if ($op === '<=') {
            if ($hasMin && $num < $min) return 'out';
            if ($hasMax && !$hasMin && $num <= $max) return 'in';
            return 'indeterminate';
        }

        return null;
    };

    $css = 'body, table, td, th { font-family: "DejaVu Sans Mono", "Consolas", "Lucida Console", "Courier New", monospace; }'
        . '.encabezado-tabla { width: 100%; border-bottom: 1.2px solid #b7c7de; margin-bottom: 0px; }'
        . '.logo { width: 110px; }'
        . '.direccion { text-align: right; font-size: 12px; color: #3a4a63; vertical-align: top; }'
        . '.datos-cliente-tabla { width: 100%; margin: 10px 0 15px 0; font-size: 13px; color: #333; }'
        . '.datos-cliente-tabla td { padding: 2px 8px; vertical-align: top; }'
        . '.titulo-reporte { font-size: 18px; font-weight: bold; text-align: center; margin: 0; color: #1a237e; letter-spacing: 1px; width: 100%; }'
        . '.tabla-resultados { width: 100%; border-collapse: collapse; margin-top: 0.5px; font-size: 11px; border: 0.8px solid #b7c7de; }'
        . '.tabla-resultados th { background: #dce8f8; font-size: 11px; color: #112a55; font-weight: 700; border: 0.8px solid #b7c7de; text-align: left; height: 28px; padding: 3px 7px; }'
        . '.tabla-resultados td { font-size: 11px; border: 0.7px solid #c8d4e8; padding: 2px 7px; text-align: left; vertical-align: top; }'
        . '.tabla-resultados th.prueba, .tabla-resultados td.prueba { width: 30%; }'
        . '.tabla-resultados th.metodologia, .tabla-resultados td.metodologia { width: 15%; }'
        . '.tabla-resultados th.resultado, .tabla-resultados td.resultado { width: 15%; }'
        . '.tabla-resultados th.unidades, .tabla-resultados td.unidades { width: 14%; }'
        . '.tabla-resultados th.referencia, .tabla-resultados td.referencia { width: 26%; }'
        . '.referencia-list { margin: 0; padding-left: 16px; font-size: 0.97em; color: #1f2f49; }'
        . '.firma-footer { text-align: right; margin-top: 45px; }'
        . '.subtitulo { background: #e9f0fc !important; color: #1a237e !important; font-weight: bold !important; border-radius: 6px; }';

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

    // El titulo principal se imprime en el header repetido de mPDF para asegurar
    // visibilidad en todas las paginas (incluida la segunda en adelante).

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

    $normalizeText = static function ($value): string {
        $txt = trim((string)$value);
        if ($txt === '') {
            return '';
        }
        $txt = mb_strtolower($txt, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
        if ($ascii !== false && $ascii !== null) {
            $txt = $ascii;
        }
        $txt = preg_replace('/\s+/u', ' ', $txt);
        return trim((string)$txt);
    };

    $splitExpectedTextValues = static function ($raw) use ($normalizeText): array {
        $src = trim((string)$raw);
        if ($src === '') {
            return [];
        }
        $parts = preg_split('/[|,;\/]+/u', $src);
        $out = [];
        foreach ((array)$parts as $part) {
            $token = $normalizeText($part);
            if ($token !== '') {
                $out[] = $token;
            }
        }
        return array_values(array_unique($out));
    };

    $resolveQualitativeStatus = static function ($token) use ($normalizeText): string {
        $t = $normalizeText($token);
        if ($t === '') {
            return '';
        }

        // Priorizar frases negativas compuestas para evitar colisiones con "reactivo".
        if (preg_match('/\bno\s+reactiv[oa]?\b/u', $t)) {
            return 'no_reactivo';
        }
        if (preg_match('/\bno\s+detectad[oa]\b/u', $t)) {
            return 'no_detectado';
        }
        if (preg_match('/\bincompatibl[ea]s?\b/u', $t)) {
            return 'incompatible';
        }
        if (preg_match('/\bindeterminad[oa]\b/u', $t)) {
            return 'indeterminado';
        }
        if (preg_match('/\breactiv[oa]?\b/u', $t)) {
            return 'reactivo';
        }
        if (preg_match('/\bpositiv[oa]?\b/u', $t)) {
            return 'positivo';
        }
        if (preg_match('/\bnegativ[oa]?\b/u', $t)) {
            return 'negativo';
        }
        if (preg_match('/\bcompatible\b/u', $t)) {
            return 'compatible';
        }
        return '';
    };

    $matchesExpectedText = static function (string $actualToken, array $expectedTokens) use ($resolveQualitativeStatus): bool {
        if ($actualToken === '' || empty($expectedTokens)) {
            return false;
        }
        if (in_array($actualToken, $expectedTokens, true)) {
            return true;
        }

        $actualStatus = $resolveQualitativeStatus($actualToken);
        if ($actualStatus === '') {
            return false;
        }

        foreach ($expectedTokens as $expectedToken) {
            if ($resolveQualitativeStatus((string)$expectedToken) === $actualStatus) {
                return true;
            }
        }
        return false;
    };

    $splitAlertTextValues = static function ($raw) use ($normalizeText): array {
        $src = trim((string)$raw);
        if ($src === '') {
            return [];
        }
        $parts = preg_split('/[|,;\/]+/u', $src);
        $out = [];
        foreach ((array)$parts as $part) {
            $token = $normalizeText($part);
            if ($token !== '') {
                $out[] = $token;
            }
        }
        return array_values(array_unique($out));
    };

    $parseAlertTextColorMap = static function ($raw) use ($normalizeText): array {
        $src = trim((string)$raw);
        if ($src === '') {
            return [];
        }
        $parts = preg_split('/[;,]+/u', $src);
        $out = [];
        foreach ((array)$parts as $part) {
            $entry = trim((string)$part);
            if ($entry === '') {
                continue;
            }
            $posEq = strpos($entry, '=');
            $posColon = strpos($entry, ':');
            $sepPos = ($posEq !== false) ? $posEq : $posColon;
            if ($sepPos === false || $sepPos <= 0) {
                continue;
            }
            $label = $normalizeText(substr($entry, 0, $sepPos));
            $color = trim((string)substr($entry, $sepPos + 1));
            if ($label !== '' && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
                $out[$label] = $color;
            }
        }
        return $out;
    };

    $evaluateTextRule = static function ($resultadoRaw, array $referencia) use ($toNullableFloat, $splitExpectedTextValues, $normalizeText, $matchesExpectedText): ?bool {
        $min = array_key_exists('valor_min', $referencia) ? $toNullableFloat($referencia['valor_min']) : null;
        $max = array_key_exists('valor_max', $referencia) ? $toNullableFloat($referencia['valor_max']) : null;
        if ($min !== null || $max !== null) {
            return null;
        }

        $expectedRaw = trim((string)($referencia['valor'] ?? ''));
        if ($expectedRaw === '') {
            return null;
        }

        $expectedTokens = $splitExpectedTextValues($expectedRaw);
        if (empty($expectedTokens)) {
            return null;
        }

        $actualToken = $normalizeText($resultadoRaw);
        if ($actualToken === '') {
            return null;
        }

        return $matchesExpectedText($actualToken, $expectedTokens);
    };

    $evaluateExplicitAlertTextRule = static function ($resultadoRaw, array $referencia) use ($splitAlertTextValues, $normalizeText): ?bool {
        $alertRaw = $referencia['alerta_textos'] ?? ($referencia['alert_text_values'] ?? ($referencia['alerta_valores'] ?? ''));
        $tokens = $splitAlertTextValues($alertRaw);
        if (empty($tokens)) {
            return null;
        }
        $actualToken = $normalizeText($resultadoRaw);
        if ($actualToken === '') {
            return null;
        }
        return in_array($actualToken, $tokens, true);
    };

    $resolveExplicitAlertColorRule = static function ($resultadoRaw, array $referencia) use ($parseAlertTextColorMap, $normalizeText): ?string {
        $rawMap = $referencia['alerta_colores_texto'] ?? ($referencia['alert_text_colors'] ?? ($referencia['alerta_colores'] ?? ''));
        $map = $parseAlertTextColorMap($rawMap);
        if (empty($map)) {
            return null;
        }
        $token = $normalizeText($resultadoRaw);
        if ($token === '') {
            return null;
        }
        return isset($map[$token]) ? (string)$map[$token] : null;
    };

    $hasExplicitAlertRules = static function (array $referencia) use ($splitAlertTextValues, $parseAlertTextColorMap): bool {
        $alertRaw = $referencia['alerta_textos'] ?? ($referencia['alert_text_values'] ?? ($referencia['alerta_valores'] ?? ''));
        $rawMap = $referencia['alerta_colores_texto'] ?? ($referencia['alert_text_colors'] ?? ($referencia['alerta_colores'] ?? ''));
        return !empty($splitAlertTextValues($alertRaw)) || !empty($parseAlertTextColorMap($rawMap));
    };

    $normalizeAlertMode = static function ($mode): string {
        $raw = strtolower(trim((string)$mode));
        return in_array($raw, ['none', 'asterisk', 'color', 'both'], true) ? $raw : 'both';
    };

    $normalizeAlertColor = static function ($color): string {
        $raw = trim((string)$color);
        return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $raw) ? $raw : '#c62828';
    };

    $resolveAlertConfig = static function (array $referencia) use ($normalizeAlertMode, $normalizeAlertColor): array {
        $mode = $referencia['alerta_modo'] ?? ($referencia['alert_mode'] ?? 'both');
        $color = $referencia['alerta_color'] ?? ($referencia['alert_color'] ?? '#c62828');
        return [
            'mode' => $normalizeAlertMode($mode),
            'color' => $normalizeAlertColor($color),
        ];
    };

    $decorateOutOfRangeHtml = static function (string $cellHtml, array $alertCfg): string {
        $mode = $alertCfg['mode'] ?? 'both';
        $color = $alertCfg['color'] ?? '#c62828';
        $showColor = in_array($mode, ['color', 'both'], true);
        $showAsterisk = in_array($mode, ['asterisk', 'both'], true);
        if (!$showColor && !$showAsterisk) {
            return $cellHtml;
        }
        $style = 'font-weight:700;';
        if ($showColor) {
            $style .= ' color:' . htmlspecialchars($color) . ';';
        }
        $prefix = $showAsterisk ? '* ' : '';
        return '<span style="' . $style . '">' . $prefix . $cellHtml . '</span>';
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

            $html .= '<table style="width:100%; table-layout:fixed; border-collapse:collapse; font-size:10px; margin-top:4px; margin-bottom:8px; border:0.8px solid #b7c7de;">';
            $html .= '<thead><tr>';
            foreach ($cols as $col) {
                $w = trim((string)($col['width'] ?? ''));
                $style = 'padding:4px 6px; background:#dce8f8; color:#112a55; font-weight:700; border:0.8px solid #b7c7de; text-align:left; vertical-align:middle; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;';
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
                    $style = 'padding:4px 6px; font-weight:' . $fontWeight . '; font-style:' . $fontStyle . '; text-align:' . htmlspecialchars($textAlign) . '; border:0.8px solid #b7c7de;';
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

                if ($rowType === 'long_text') {
                    $visibleInPdf = !array_key_exists('template_visible_pdf', $rowV2) || (bool)$rowV2['template_visible_pdf'];
                    if (!$visibleInPdf) {
                        continue;
                    }
                    $blockLabel = trim((string)($rowV2['label'] ?? ''));
                    $blockText = (string)($rowV2['template_text'] ?? '');
                    $blockAlign = strtolower(trim((string)($rowV2['template_align'] ?? 'left')));
                    $blockTextColor = trim((string)($rowV2['color_texto'] ?? ''));
                    $blockBgColor = trim((string)($rowV2['color_fondo'] ?? ''));
                    $blockBold = !empty($rowV2['negrita']);
                    $blockItalic = !empty($rowV2['cursiva']);
                    if (!in_array($blockAlign, ['left', 'center', 'right'], true)) {
                        $blockAlign = 'left';
                    }
                    if ($blockTextColor === '') {
                        $blockTextColor = '#1f2d5c';
                    }
                    if ($blockBgColor === '') {
                        $blockBgColor = '#fafcff';
                    }
                    $blockStyle = 'text-align:' . htmlspecialchars($blockAlign) . '; color:' . htmlspecialchars($blockTextColor) . ';';
                    $blockHtml = '';
                    if ($blockLabel !== '') {
                        $labelText = htmlspecialchars($blockLabel);
                        if ($blockBold) {
                            $labelText = '<strong>' . $labelText . '</strong>';
                        }
                        if ($blockItalic) {
                            $labelText = '<em>' . $labelText . '</em>';
                        }
                        $blockHtml .= '<div style="margin-bottom:4px;">' . $labelText . '</div>';
                    }
                    $blockHtml .= '<div style="white-space:pre-wrap; font-weight:400; font-style:normal;">' . nl2br(htmlspecialchars($blockText)) . '</div>';
                    $html .= '<tr><td colspan="' . $colCount . '" style="padding:6px 8px; border:0.8px solid #c8d4e8; background:' . htmlspecialchars($blockBgColor) . '; ' . $blockStyle . '">' . $blockHtml . '</td></tr>';
                    continue;
                }

                $cells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];
                $rowRanges = is_array($rowV2['reference_ranges'] ?? null) ? $rowV2['reference_ranges'] : [];
                $html .= '<tr>';
                foreach ($cols as $col) {
                    $colId = (string)($col['id'] ?? '');
                    $val = $cells[$colId] ?? '';
                    $kind = strtolower(trim((string)($col['kind'] ?? 'text')));
                    $w = trim((string)($col['width'] ?? ''));
                    $style = 'padding:4px 6px; vertical-align:top; border:0.7px solid #c8d4e8; overflow:hidden; word-wrap:break-word; word-break:break-word;';
                    if ($w !== '') {
                        $style .= ' width:' . htmlspecialchars($w) . ';';
                    }
                    $cellHtml = nl2br(htmlspecialchars((string)$val));
                    if ($kind === 'reference') {
                        $cellHtml = $renderReferenciaV2($val);
                    } elseif (in_array($kind, ['result', 'select'], true) && $rowType === 'data') {
                        $currentReferences = [];
                        if (isset($rowRanges[$colId]) && is_array($rowRanges[$colId])) {
                            $currentReferences = $rowRanges[$colId];
                        }

                        if (empty($currentReferences)) {
                            foreach ($cols as $tmpCol) {
                                $tmpKind = strtolower(trim((string)($tmpCol['kind'] ?? 'text')));
                                if ($tmpKind !== 'reference') {
                                    continue;
                                }
                                $tmpColId = (string)($tmpCol['id'] ?? '');
                                if ($tmpColId !== '' && !empty($rowRanges[$tmpColId]) && is_array($rowRanges[$tmpColId])) {
                                    $currentReferences = $rowRanges[$tmpColId];
                                    break;
                                }
                            }
                        }

                        if (empty($currentReferences)) {
                            foreach ($rowRanges as $tmpRanges) {
                                if (is_array($tmpRanges) && !empty($tmpRanges)) {
                                    $currentReferences = $tmpRanges;
                                    break;
                                }
                            }
                        }

                        $valorNum = $toNullableFloat($val);
                        $edadPaciente = isset($paciente['edad']) ? $toNullableFloat($paciente['edad']) : null;
                        $sexoPaciente = isset($paciente['sexo']) ? strtolower(trim((string)$paciente['sexo'])) : '';
                        $fueraRango = false;
                        $alertCfg = ['mode' => 'both', 'color' => '#c62828'];
                        if (!empty($currentReferences)) {
                            $refAplicada = $seleccionarReferencia($currentReferences, $sexoPaciente, $edadPaciente);
                            if (is_array($refAplicada)) {
                                $alertCfg = $resolveAlertConfig($refAplicada);
                                $min = array_key_exists('valor_min', $refAplicada) ? $toNullableFloat($refAplicada['valor_min']) : null;
                                $max = array_key_exists('valor_max', $refAplicada) ? $toNullableFloat($refAplicada['valor_max']) : null;
                                $hasNumericRule = ($min !== null || $max !== null);
                                if ($hasNumericRule) {
                                    $numericStatus = $evaluateNumericExpressionAgainstRange((string)$val, $min, $max);
                                    if ($numericStatus === 'out') {
                                        $fueraRango = true;
                                    } elseif ($numericStatus === null && $valorNum !== null) {
                                        if (($min !== null && $valorNum < $min) || ($max !== null && $valorNum > $max)) {
                                            $fueraRango = true;
                                        }
                                    }
                                } else {
                                    $colorOverride = $resolveExplicitAlertColorRule((string)$val, $refAplicada);
                                    if ($colorOverride !== null) {
                                        $fueraRango = true;
                                        $alertCfg['color'] = $normalizeAlertColor($colorOverride);
                                    } else {
                                    $explicitAlert = $evaluateExplicitAlertTextRule((string)$val, $refAplicada);
                                    if ($explicitAlert === true) {
                                        $fueraRango = true;
                                    } elseif ($hasExplicitAlertRules($refAplicada)) {
                                        // Con reglas explícitas configuradas, evitar mismatch general.
                                        $fueraRango = false;
                                    } elseif ($explicitAlert === null) {
                                        $textMatch = $evaluateTextRule((string)$val, $refAplicada);
                                        if ($textMatch === false) {
                                            $fueraRango = true;
                                        }
                                    }
                                    }
                                }
                            }
                        }

                        if ($fueraRango) {
                            $cellHtml = $decorateOutOfRangeHtml($cellHtml, $alertCfg);
                        }
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
            $alertCfg = ['mode' => 'both', 'color' => '#c62828'];
            $edad_paciente = isset($paciente['edad']) ? floatval($paciente['edad']) : null;
            $sexo_paciente = isset($paciente['sexo']) ? strtolower(trim($paciente['sexo'])) : '';
            if (isset($item['referencias']) && is_array($item['referencias']) && $valorOriginal !== "") {
                $referencia_aplicada = $seleccionarReferencia((array)$item['referencias'], $sexo_paciente, $edad_paciente);
                $valor_num = $toNullableFloat($valorOriginal);
                if ($referencia_aplicada) {
                    $alertCfg = $resolveAlertConfig((array)$referencia_aplicada);
                    $min = isset($referencia_aplicada['valor_min']) ? $toNullableFloat($referencia_aplicada['valor_min']) : null;
                    $max = isset($referencia_aplicada['valor_max']) ? $toNullableFloat($referencia_aplicada['valor_max']) : null;
                    $hasNumericRule = ($min !== null || $max !== null);
                    if ($hasNumericRule) {
                        $numericStatus = $evaluateNumericExpressionAgainstRange((string)$valorOriginal, $min, $max);
                        if ($numericStatus === 'out') {
                            $fuera_rango = true;
                        } elseif ($numericStatus === null && $valor_num !== null) {
                            if (($min !== null && $valor_num < $min) || ($max !== null && $valor_num > $max)) {
                                $fuera_rango = true;
                            }
                        }
                    } else {
                        $colorOverride = $resolveExplicitAlertColorRule($valorOriginal, (array)$referencia_aplicada);
                        if ($colorOverride !== null) {
                            $fuera_rango = true;
                            $alertCfg['color'] = $normalizeAlertColor($colorOverride);
                        } else {
                            $explicitAlert = $evaluateExplicitAlertTextRule($valorOriginal, (array)$referencia_aplicada);
                            if ($explicitAlert === true) {
                                $fuera_rango = true;
                            } elseif ($hasExplicitAlertRules((array)$referencia_aplicada)) {
                                // Con reglas explícitas configuradas, evitar mismatch general.
                                $fuera_rango = false;
                            } elseif ($explicitAlert === null) {
                            $textMatch = $evaluateTextRule($valorOriginal, (array)$referencia_aplicada);
                            if ($textMatch === false) {
                                $fuera_rango = true;
                            }
                            }
                        }
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
                $valorFormateado = $decorateOutOfRangeHtml(htmlspecialchars((string)$valorFormateado), $alertCfg);
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
                $resultadoHtml = $fuera_rango
                    ? (string)$valorFormateado
                    : htmlspecialchars((string)$valorFormateado);
                $html .= '<td class="resultado" style="font-style:' . $font_style . ';text-align:' . htmlspecialchars($text_align) . '">' . $resultadoHtml . '</td>';
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