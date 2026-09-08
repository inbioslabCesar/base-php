<?php
require_once __DIR__ . '/../examenes/formato_dinamico_helper.php';
require_once __DIR__ . '/../config/ui_theme.php';
require_once __DIR__ . '/servicios/EdadPacienteService.php';

// Funciones para obtener datos de cotización, paciente, empresa y resultados
function obtenerDatosCotizacion($pdo, $cotizacion_id) {
    $hasEsSis = false;
    try {
        $stmtCol = $pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'es_sis'");
        $hasEsSis = (bool)$stmtCol->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $hasEsSis = false;
    }

    $selectEsSis = $hasEsSis ? "c.es_sis AS es_sis" : "0 AS es_sis";
    $hasProfId = false;
    $hasProfNombre = false;
    $hasProfTipo = false;
    $hasProfRegistro = false;
    try {
        $hasProfId = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_id'")->fetch(PDO::FETCH_ASSOC);
        $hasProfNombre = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_nombre'")->fetch(PDO::FETCH_ASSOC);
        $hasProfTipo = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_tipo'")->fetch(PDO::FETCH_ASSOC);
        $hasProfRegistro = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'profesional_solicitante_registro'")->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $hasProfId = false;
        $hasProfNombre = false;
        $hasProfTipo = false;
        $hasProfRegistro = false;
    }

    $selectProfId = $hasProfId ? "c.profesional_solicitante_id AS profesional_solicitante_id" : "NULL AS profesional_solicitante_id";
    $selectProfNombre = $hasProfNombre ? "c.profesional_solicitante_nombre AS profesional_solicitante_nombre" : "NULL AS profesional_solicitante_nombre";
    $selectProfTipo = $hasProfTipo ? "c.profesional_solicitante_tipo AS profesional_solicitante_tipo" : "NULL AS profesional_solicitante_tipo";
    $selectProfRegistro = $hasProfRegistro ? "c.profesional_solicitante_registro AS profesional_solicitante_registro" : "NULL AS profesional_solicitante_registro";

    $sqlCot = "SELECT c.id_empresa, c.id_convenio, c.referencia_personalizada, $selectEsSis,
                $selectProfId, $selectProfNombre, $selectProfTipo, $selectProfRegistro,
                e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
               FROM cotizaciones c
               LEFT JOIN empresas e ON c.id_empresa = e.id
               LEFT JOIN convenios v ON c.id_convenio = v.id
               WHERE c.id = :cotizacion_id";
    $stmtCot = $pdo->prepare($sqlCot);
    $stmtCot->execute(['cotizacion_id' => $cotizacion_id]);
    return $stmtCot->fetch(PDO::FETCH_ASSOC);
}

function obtenerResultadosExamenes($pdo, $cotizacion_id) {
    static $hasOrdenImpresion = null;
    if ($hasOrdenImpresion === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'orden_impresion'")->fetch(PDO::FETCH_ASSOC);
            $hasOrdenImpresion = !empty($col);
        } catch (Throwable $e) {
            $hasOrdenImpresion = false;
        }
    }

    $orderSql = $hasOrdenImpresion
        ? " ORDER BY COALESCE(re.orden_impresion, 2147483647), re.id"
        : " ORDER BY re.id";

    $sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.fecha_nacimiento, c.sexo, c.codigo_cliente, c.dni, c.tipo_documento, c.id AS cliente_id,
                   co.fecha AS cotizacion_fecha, co.fecha_toma AS cotizacion_fecha_toma
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        LEFT JOIN cotizaciones co ON co.id = re.id_cotizacion
        WHERE re.id_cotizacion = :cotizacion_id" . $orderSql;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cotizacion_id' => $cotizacion_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerDatosEmpresa($pdo) {
    $empresa = ui_theme_fetch_company_config($pdo);
    if (!is_array($empresa)) {
        $empresa = [
            "nombre" => "",
            "ruc" => "",
            "dominio" => "",
            "direccion" => "",
            "telefono" => "",
            "celular" => "",
            "logo" => "",
            "firma" => ""
        ];
    }
    return $empresa;
}

function obtenerItemsResultados($pdo, $rows) {
    $buildReferenceSummaryPublic = static function ($ranges) {
        if (!is_array($ranges)) {
            return '';
        }
        $lines = [];
        foreach ($ranges as $r) {
            if (!is_array($r)) {
                continue;
            }
            $desc = trim((string)($r['desc'] ?? ''));
            $valor = trim((string)($r['valor'] ?? ''));
            $min = trim((string)($r['valor_min'] ?? ''));
            $max = trim((string)($r['valor_max'] ?? ''));
            $rango = ($min !== '' || $max !== '')
                ? trim(($min !== '' ? $min : '') . ' - ' . ($max !== '' ? $max : ''))
                : '';
            $visible = $valor !== '' ? $valor : $rango;

            if ($desc !== '' && $visible !== '') {
                $lines[] = $desc . ' (' . $visible . ')';
            } elseif ($desc !== '') {
                $lines[] = $desc;
            } elseif ($visible !== '') {
                $lines[] = $visible;
            }
        }
        return implode(' | ', $lines);
    };

    $sanitizeReferenceTextPublic = static function ($value) {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }
        $parts = preg_split('/\s*\|\s*|\r\n|\r|\n|\s*;\s*/u', $raw);
        $out = [];
        foreach ((array)$parts as $part) {
            $line = trim((string)$part);
            if ($line === '') {
                continue;
            }
            // Quitar metadata interna visible (edad/sexo).
            $line = preg_replace('/\s*,\s*edad\s*[^\),]*/iu', '', $line);
            $line = preg_replace('/\s*,\s*(masculino|femenino|cualquiera)\b/iu', '', $line);
            $line = preg_replace('/\(\s*,/u', '(', $line);
            $line = preg_replace('/\s{2,}/u', ' ', $line);
            $line = trim($line, " \t\n\r\0\x0B,");
            if ($line !== '') {
                $out[] = $line;
            }
        }
        return implode(' | ', $out);
    };

    $items = [];
    foreach ($rows as $row) {
        $sql2 = "SELECT nombre AS nombre_examen, adicional FROM examenes WHERE id = :id_examen";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['id_examen' => $row['id_examen']]);
        $examen = $stmt2->fetch(PDO::FETCH_ASSOC);

        $snapshot_src = $row['adicional_snapshot'] ?? null;
        $examen_src = $examen['adicional'] ?? null;

        $adicional_src = $snapshot_src;
        if ($adicional_src === null || $adicional_src === '') {
            $adicional_src = $examen_src;
        }

        $formatDef = lab_format_decode_definition($adicional_src);
        $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
        $adicional = $formatDef['legacy_items'];
        $resultados_json = $row['resultados'] ? json_decode($row['resultados'], true) : [];

        $imprimir_examen = isset($resultados_json['imprimir_examen']) ? intval($resultados_json['imprimir_examen']) : 1;
        if ($imprimir_examen !== 1) {
            continue;
        }

        $fechaProcesoSeccion = trim((string)($row['fecha_proceso_en'] ?? ''));
        if ($fechaProcesoSeccion === '') {
            $fechaProcesoSeccion = (string)($row['fecha_ingreso'] ?? '');
        }
        $fechaValidacionSeccion = trim((string)($row['fecha_validacion_en'] ?? ''));
        $estadoValidacionSeccion = strtolower(trim((string)($row['estado_validacion'] ?? 'pendiente')));
        if (!in_array($estadoValidacionSeccion, ['pendiente', 'validado'], true)) {
            $estadoValidacionSeccion = 'pendiente';
        }

        $metaSeccion = [
            'id_resultado' => (int)($row['id'] ?? 0),
            'examen' => (string)($examen['nombre_examen'] ?? ''),
            'fecha_proceso' => $fechaProcesoSeccion,
            'estado_validacion' => $estadoValidacionSeccion,
            'fecha_validacion' => $fechaValidacionSeccion,
        ];

        if ($isFormatV2) {
            $allCols = lab_format_v2_columns($formatDef);
            $resolvedRows = lab_format_v2_resolve_rows($allCols, lab_format_v2_rows($formatDef), is_array($resultados_json) ? $resultados_json : []);
            $cols = [];
            foreach ($allCols as $col) {
                if (!lab_format_v2_col_visible($col, 'pdf')) {
                    continue;
                }
                $cols[] = [
                    'id' => (string)($col['id'] ?? ''),
                    'label' => (string)($col['label'] ?? ($col['id'] ?? '')),
                    'width' => (string)($col['width'] ?? ''),
                    'editable' => lab_format_v2_col_editable($col),
                    'kind' => (string)($col['kind'] ?? 'text'),
                ];
            }

            $rowsV2 = [];
            foreach ($resolvedRows as $rowV2) {
                if (!is_array($rowV2)) {
                    continue;
                }
                $rowId = trim((string)($rowV2['id'] ?? ''));
                $rowType = strtolower(trim((string)($rowV2['type'] ?? 'data')));
                $cells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];
                $rowDecimals = is_array($rowV2['decimales'] ?? null) ? $rowV2['decimales'] : [];
                $rowReferenceRanges = is_array($rowV2['reference_ranges'] ?? null) ? $rowV2['reference_ranges'] : [];
                $cellsOut = [];
                foreach ($cols as $col) {
                    $colId = $col['id'];
                    $rawValue = $cells[$colId] ?? '';
                    $colKind = strtolower(trim((string)($col['kind'] ?? 'text')));
                    $colDec = null;
                    if (array_key_exists($colId, $rowDecimals) && $rowDecimals[$colId] !== '' && $rowDecimals[$colId] !== null && is_numeric($rowDecimals[$colId])) {
                        $tmpDec = intval($rowDecimals[$colId]);
                        if ($tmpDec >= 0 && $tmpDec <= 6) {
                            $colDec = $tmpDec;
                        }
                    }

                    if ($colKind === 'reference') {
                        $ranges = (isset($rowReferenceRanges[$colId]) && is_array($rowReferenceRanges[$colId]))
                            ? $rowReferenceRanges[$colId]
                            : [];
                        $summaryPublic = $buildReferenceSummaryPublic($ranges);
                        if ($summaryPublic !== '') {
                            $cellsOut[$colId] = $summaryPublic;
                        } else {
                            $cellsOut[$colId] = $sanitizeReferenceTextPublic($rawValue);
                        }
                    } elseif ($colDec !== null && is_numeric($rawValue)) {
                        $cellsOut[$colId] = number_format((float)$rawValue, $colDec, '.', '');
                    } else {
                        $cellsOut[$colId] = $rawValue;
                    }
                }
                $rowsV2[] = [
                    'id' => $rowId,
                    'type' => $rowType,
                    'label' => (string)($rowV2['label'] ?? ''),
                    'template_text' => (string)($rowV2['template_text'] ?? ''),
                    'template_visible_pdf' => !array_key_exists('template_visible_pdf', $rowV2) || (bool)$rowV2['template_visible_pdf'],
                    'template_align' => (string)($rowV2['template_align'] ?? 'left'),
                    'color_texto' => (string)($rowV2['color_texto'] ?? ''),
                    'color_fondo' => (string)($rowV2['color_fondo'] ?? ''),
                    'negrita' => !empty($rowV2['negrita']) ? 1 : 0,
                    'cursiva' => !empty($rowV2['cursiva']) ? 1 : 0,
                    'alineacion' => (string)($rowV2['alineacion'] ?? ''),
                    'reference_ranges' => $rowReferenceRanges,
                    'cells' => $cellsOut,
                ];
            }

            $resolvedRowsById = [];
            foreach ($resolvedRows as $rowTmp) {
                if (!is_array($rowTmp)) {
                    continue;
                }
                $rowTmpId = trim((string)($rowTmp['id'] ?? ''));
                $rowTmpType = strtolower(trim((string)($rowTmp['type'] ?? 'data')));
                if ($rowTmpId === '' || $rowTmpType !== 'data') {
                    continue;
                }
                $resolvedRowsById[$rowTmpId] = $rowTmp;
            }

            $pickRowLabel = static function (array $row, array $allCols): string {
                $label = trim((string)($row['label'] ?? ''));
                if ($label !== '') {
                    return $label;
                }
                $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
                foreach ($allCols as $colTmp) {
                    if (!is_array($colTmp)) {
                        continue;
                    }
                    $colTmpId = trim((string)($colTmp['id'] ?? ''));
                    if ($colTmpId === '') {
                        continue;
                    }
                    $kindTmp = strtolower(trim((string)($colTmp['kind'] ?? 'text')));
                    if (!in_array($kindTmp, ['text', 'reference'], true)) {
                        continue;
                    }
                    $cellVal = trim((string)($cells[$colTmpId] ?? ''));
                    if ($cellVal !== '') {
                        return $cellVal;
                    }
                }
                foreach ($cells as $cellVal) {
                    $txt = trim((string)$cellVal);
                    if ($txt !== '') {
                        return $txt;
                    }
                }
                return trim((string)($row['id'] ?? ''));
            };

            $parseMinutesFromLabel = static function ($text): ?float {
                $src = strtolower(trim((string)$text));
                if ($src === '') {
                    return null;
                }
                if (strpos($src, 'basal') !== false || strpos($src, 'ayuno') !== false || $src === '0' || $src === '0 min' || $src === '0 minutos') {
                    return 0.0;
                }
                if (!preg_match('/(\d+(?:[\.,]\d+)?)/', $src, $m)) {
                    return null;
                }
                $value = (float)str_replace(',', '.', (string)$m[1]);
                if (!is_finite($value)) {
                    return null;
                }
                if (strpos($src, 'hora') !== false || preg_match('/\bhr?s?\b/', $src)) {
                    return $value * 60.0;
                }
                return $value;
            };

            $toNullableFloat = static function ($value): ?float {
                if ($value === null) {
                    return null;
                }
                $txt = trim((string)$value);
                if ($txt === '') {
                    return null;
                }
                $norm = str_replace(',', '', $txt);
                if (!preg_match('/^[-+]?\d+(?:\.\d+)?$/', $norm)) {
                    return null;
                }
                if (!is_numeric($norm)) {
                    return null;
                }
                return (float)$norm;
            };

            $unitColIds = [];
            foreach ($allCols as $tmpCol) {
                if (!is_array($tmpCol)) {
                    continue;
                }
                $tmpId = trim((string)($tmpCol['id'] ?? ''));
                if ($tmpId === '') {
                    continue;
                }
                $tmpKind = strtolower(trim((string)($tmpCol['kind'] ?? '')));
                $tmpLabel = strtolower(trim((string)($tmpCol['label'] ?? '')));
                if ($tmpKind === 'unit' || $tmpId === 'unidad' || strpos($tmpId, 'unidad') !== false || strpos($tmpLabel, 'unidad') !== false) {
                    $unitColIds[] = $tmpId;
                }
            }

            $findPointReferences = static function (array $row, string $yColId, array $allCols): array {
                $rowRanges = is_array($row['reference_ranges'] ?? null) ? $row['reference_ranges'] : [];
                if (isset($rowRanges[$yColId]) && is_array($rowRanges[$yColId]) && !empty($rowRanges[$yColId])) {
                    return $rowRanges[$yColId];
                }
                foreach ($allCols as $tmpCol) {
                    if (!is_array($tmpCol)) {
                        continue;
                    }
                    $kind = strtolower(trim((string)($tmpCol['kind'] ?? 'text')));
                    if ($kind !== 'reference') {
                        continue;
                    }
                    $cid = trim((string)($tmpCol['id'] ?? ''));
                    if ($cid !== '' && isset($rowRanges[$cid]) && is_array($rowRanges[$cid]) && !empty($rowRanges[$cid])) {
                        return $rowRanges[$cid];
                    }
                }
                foreach ($rowRanges as $tmpRanges) {
                    if (is_array($tmpRanges) && !empty($tmpRanges)) {
                        return $tmpRanges;
                    }
                }
                return [];
            };

            $pickRangeMinMax = static function (array $ranges): array {
                foreach ($ranges as $r) {
                    if (!is_array($r)) {
                        continue;
                    }
                    $minRaw = trim((string)($r['valor_min'] ?? ''));
                    $maxRaw = trim((string)($r['valor_max'] ?? ''));
                    $min = null;
                    $max = null;
                    if ($minRaw !== '') {
                        $normMin = str_replace(',', '', $minRaw);
                        if (is_numeric($normMin)) {
                            $min = (float)$normMin;
                        }
                    }
                    if ($maxRaw !== '') {
                        $normMax = str_replace(',', '', $maxRaw);
                        if (is_numeric($normMax)) {
                            $max = (float)$normMax;
                        }
                    }
                    if ($min !== null || $max !== null) {
                        return ['min' => $min, 'max' => $max];
                    }
                }
                return ['min' => null, 'max' => null];
            };

            $curvasV2 = [];
            $layoutRaw = is_array($formatDef['layout'] ?? null) ? $formatDef['layout'] : [];
            $chartsRaw = is_array($layoutRaw['charts'] ?? null) ? $layoutRaw['charts'] : [];
            foreach ($chartsRaw as $chartIndex => $chartRaw) {
                if (!is_array($chartRaw)) {
                    continue;
                }
                $chartType = strtolower(trim((string)($chartRaw['type'] ?? 'line_curve')));
                if ($chartType !== 'line_curve') {
                    continue;
                }
                $yColId = trim((string)($chartRaw['y_col_id'] ?? ''));
                if ($yColId === '') {
                    continue;
                }
                $pointsRaw = is_array($chartRaw['points'] ?? null) ? $chartRaw['points'] : [];
                $pointsOut = [];
                foreach ($pointsRaw as $pointRaw) {
                    if (!is_array($pointRaw) || empty($pointRaw['enabled'])) {
                        continue;
                    }
                    $rowId = trim((string)($pointRaw['row_id'] ?? ''));
                    if ($rowId === '' || !isset($resolvedRowsById[$rowId])) {
                        continue;
                    }

                    $rowRef = $resolvedRowsById[$rowId];
                    $cellsRef = is_array($rowRef['cells'] ?? null) ? $rowRef['cells'] : [];
                    $labelPoint = trim((string)($pointRaw['label'] ?? ''));
                    if ($labelPoint === '') {
                        $labelPoint = $pickRowLabel($rowRef, $allCols);
                    }

                    $xRaw = trim((string)($pointRaw['x_value'] ?? ''));
                    $xValue = null;
                    if ($xRaw !== '') {
                        $normX = str_replace(',', '.', $xRaw);
                        if (is_numeric($normX)) {
                            $xValue = (float)$normX;
                        }
                    }
                    if ($xValue === null) {
                        $xValue = $parseMinutesFromLabel($labelPoint);
                    }
                    if ($xValue === null) {
                        continue;
                    }

                    $yRaw = $cellsRef[$yColId] ?? '';
                    $yValue = $toNullableFloat($yRaw);
                    $unit = '';
                    foreach ($unitColIds as $unitColId) {
                        $unitTxt = trim((string)($cellsRef[$unitColId] ?? ''));
                        if ($unitTxt !== '') {
                            $unit = $unitTxt;
                            break;
                        }
                    }

                    $rangesPoint = $findPointReferences($rowRef, $yColId, $allCols);
                    $minMax = $pickRangeMinMax($rangesPoint);

                    $pointsOut[] = [
                        'row_id' => $rowId,
                        'label' => $labelPoint,
                        'x' => $xValue,
                        'y' => $yValue,
                        'y_raw' => (string)$yRaw,
                        'unidad' => $unit,
                        'ref_min' => $minMax['min'],
                        'ref_max' => $minMax['max'],
                    ];
                }

                if (count($pointsOut) === 0) {
                    continue;
                }

                usort($pointsOut, static function ($a, $b) {
                    return (($a['x'] ?? 0) <=> ($b['x'] ?? 0));
                });

                $curvasV2[] = [
                    'id' => trim((string)($chartRaw['id'] ?? 'curva_' . ($chartIndex + 1))),
                    'label' => trim((string)($chartRaw['label'] ?? 'Curva ' . ($chartIndex + 1))),
                    'y_col_id' => $yColId,
                    'min_points_required' => isset($chartRaw['min_points_required']) && is_numeric($chartRaw['min_points_required'])
                        ? max(2, min(20, (int)$chartRaw['min_points_required']))
                        : 3,
                    'connect_gaps' => !empty($chartRaw['connect_gaps']),
                    'strict_units' => !array_key_exists('strict_units', $chartRaw) || !empty($chartRaw['strict_units']),
                    'points' => $pointsOut,
                ];
            }

            $items[] = [
                'tipo' => 'TablaV2',
                'titulo' => (string)($examen['nombre_examen'] ?? ''),
                'columnas' => $cols,
                'filas' => $rowsV2,
                'curvas_v2' => $curvasV2,
                'seccion_id' => (int)($row['id'] ?? 0),
                'seccion_meta' => $metaSeccion,
            ];
            continue;
        }

        // Normaliza valores numéricos (quita comas)
        foreach ($resultados_json as $k => $v) {
            if (is_string($v) && preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $v)) {
                $resultados_json[$k] = str_replace(',', '', $v);
            }
        }

        usort($adicional, function ($a, $b) {
            return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
        });

        $normKey = function ($name) {
            $s = is_string($name) ? $name : '';
            $s = trim($s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return mb_strtolower($s, 'UTF-8');
        };

        $formatValor = function ($valor, $item) {
            if ($valor === '' || $valor === null) {
                return '';
            }
            if (!is_numeric($valor)) {
                return (string) $valor;
            }
            $num = floatval($valor);
            $dec = (isset($item['decimales']) && $item['decimales'] !== '') ? intval($item['decimales']) : null;
            if ($dec !== null) {
                return number_format($num, $dec, '.', '');
            }
            if (floor($num) == $num) {
                return (string) intval($num);
            }
            return (string) $valor;
        };

        $extractVars = function ($formula) {
            $vars = [];
            if (!is_string($formula) || trim($formula) === '') {
                return $vars;
            }
            if (preg_match_all('/\[(.*?)\]/', $formula, $m)) {
                foreach ($m[1] as $v) {
                    $vars[] = trim($v);
                }
            }
            return $vars;
        };

        $evalFormula = function ($formula, $valoresNorm) use ($extractVars, $normKey) {
            $vars = $extractVars($formula);
            foreach ($vars as $varName) {
                $k = $normKey($varName);
                if (!array_key_exists($k, $valoresNorm)) {
                    return null;
                }
                $raw = $valoresNorm[$k];
                if ($raw === '' || $raw === null || !is_numeric($raw)) {
                    return null;
                }
            }
            $expr = preg_replace_callback('/\[(.*?)\]/', function ($matches) use ($valoresNorm, $normKey) {
                $param = trim($matches[1]);
                $k = $normKey($param);
                $v = $valoresNorm[$k] ?? null;
                return (is_numeric($v)) ? $v : '0';
            }, $formula);

            // Soportar multiplicación implícita: 2(3+4) o (2+3)4
            $expr = preg_replace('/([0-9\.]|\))\s*\(/', '$1*(', $expr);
            $expr = preg_replace('/\)\s*([0-9\.-])/', ')*$1', $expr);

            if (strpos($expr, '^') !== false) {
                $expr = str_replace('^', '**', $expr);
            }
            try {
                $res = eval('return ' . $expr . ';');
                return is_numeric($res) ? floatval($res) : null;
            } catch (Throwable $e) {
                return null;
            }
        };

        // Índice normalizado para compatibilidad cuando cambia el nombre solo en mayúsculas/minúsculas o signos.
        $resultadosNorm = [];
        foreach ($resultados_json as $k => $v) {
            if ($k === 'imprimir_examen') {
                continue;
            }
            $nk = $normKey($k);
            if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) {
                $resultadosNorm[$nk] = $v;
            }
        }
        $buildStableKey = function ($item) {
            if (!is_array($item)) {
                return '';
            }
            $idParametro = trim((string)($item['id_parametro'] ?? ''));
            if ($idParametro === '') {
                return '';
            }
            return 'id_parametro_' . $idParametro;
        };

        $getResultado = function ($nombre, $item = null, $default = '') use ($resultados_json, $resultadosNorm, $normKey, $buildStableKey) {
            $stableKey = $buildStableKey($item);
            if ($stableKey !== '' && array_key_exists($stableKey, $resultados_json)) {
                return $resultados_json[$stableKey];
            }
            if (isset($resultados_json[$nombre])) {
                return $resultados_json[$nombre];
            }
            $upper = mb_strtoupper((string) $nombre, 'UTF-8');
            if (isset($resultados_json[$upper])) {
                return $resultados_json[$upper];
            }
            $nk = $normKey($nombre);
            if ($nk !== '' && array_key_exists($nk, $resultadosNorm)) {
                return $resultadosNorm[$nk];
            }
            return $default;
        };

        $valores = [];
        $valoresNorm = [];
        $ordered = [];
        $formulaItems = [];

        foreach ($adicional as $item) {
            if (!is_array($item)) {
                continue;
            }

            $tipoItem = (string)($item['tipo'] ?? '');
            $nombre = (string)($item['nombre'] ?? '');
            if ($tipoItem === '' || $nombre === '') {
                continue;
            }

            if (!in_array($tipoItem, ['Parámetro', 'Título', 'Subtítulo', 'Texto Largo'], true)) {
                continue;
            }

            if ($tipoItem === 'Parámetro') {
                $valor = $getResultado($nombre, $item, '');
                $valores[$nombre] = $valor;
                $valoresNorm[$normKey($nombre)] = $valor;
                $ordered[] = ['kind' => 'param', 'item' => $item, 'nombre' => $nombre];
                if (!empty($item['formula'])) {
                    $formulaItems[] = ['nombre' => $nombre, 'item' => $item];
                } else {
                    $valores[$nombre] = $formatValor($valor, $item);
                    $valoresNorm[$normKey($nombre)] = $valores[$nombre];
                }
            } elseif ($tipoItem === 'Texto Largo') {
                $ordered[] = ['kind' => 'texto', 'item' => $item, 'nombre' => $nombre];
            } else {
                $ordered[] = ['kind' => 'otro', 'item' => $item, 'nombre' => $nombre];
            }
        }

        // Resolver fórmulas en cadena (A depende de B) con iteraciones.
        $maxIter = max(1, count($formulaItems) + 3);
        for ($i = 0; $i < $maxIter; $i++) {
            $changed = false;
            foreach ($formulaItems as $fi) {
                $nombre = $fi['nombre'];
                $item = $fi['item'];
                $res = $evalFormula($item['formula'], $valoresNorm);
                if ($res === null) {
                    continue;
                }
                $formatted = $formatValor($res, $item);
                if (($valores[$nombre] ?? '') !== $formatted) {
                    $valores[$nombre] = $formatted;
                    $valoresNorm[$normKey($nombre)] = $formatted;
                    $changed = true;
                }
            }
            if (!$changed) {
                break;
            }
        }

        $examen_items = [];
        foreach ($ordered as $entry) {
            $item = $entry['item'];
            $nombre = $entry['nombre'];

            if ($entry['kind'] === 'param') {
                $valor = $valores[$nombre] ?? '';
                // Si no es fórmula, asegurar formato final.
                if (empty($item['formula'])) {
                    $valor = $formatValor($valor, $item);
                } else {
                    // Si no se pudo recalcular (dependencias faltantes), usar el valor guardado si existe.
                    $raw = $getResultado($nombre, $item, '');
                    if (($valor === '' || $valor === null) && $raw !== '') {
                        $valor = $formatValor($raw, $item);
                    }
                }
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'valor' => $valor,
                    'tipo' => 'Parámetro',
                    'seccion_id' => (int)($row['id'] ?? 0),
                    'seccion_meta' => $metaSeccion,
                ]);
            } elseif ($entry['kind'] === 'texto') {
                $valor = $getResultado($nombre, $item, '');
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'valor' => $valor,
                    'tipo' => 'Texto Largo',
                    'seccion_id' => (int)($row['id'] ?? 0),
                    'seccion_meta' => $metaSeccion,
                ]);
            } else {
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'seccion_id' => (int)($row['id'] ?? 0),
                    'seccion_meta' => $metaSeccion,
                ]);
            }
        }

        $items = array_merge($items, $examen_items);
    }
    return $items;
}
