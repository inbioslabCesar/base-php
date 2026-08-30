<?php
require_once __DIR__ . '/../../examenes/formato_dinamico_helper.php';

// Calcula el porcentaje de parámetros llenados para una cotización
// Si $soloImprimir = true, solo cuenta exámenes con imprimir_examen = 1
function obtenerPorcentajeResultadosCotizacion($pdo, $idCotizacion, $soloImprimir = false) {
    static $hasSnapshotCol = null;
    if ($hasSnapshotCol === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $hasSnapshotCol = !empty($col);
        } catch (Exception $e) {
            $hasSnapshotCol = false;
        }
    }

    if ($hasSnapshotCol) {
        $stmt = $pdo->prepare("SELECT re.resultados, re.adicional_snapshot, e.adicional AS adicional_examen
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    } else {
        $stmt = $pdo->prepare("SELECT re.resultados, e.adicional AS adicional
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    }
    $stmt->execute([$idCotizacion]);
    $examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_parametros = 0;
    $parametros_llenados = 0;

    $valorLleno = function ($valor) {
        if ($valor === 0 || $valor === '0') {
            return true;
        }
        if ($valor === null) {
            return false;
        }
        if (is_string($valor)) {
            return trim($valor) !== '';
        }
        if (is_array($valor)) {
            return count($valor) > 0;
        }
        return $valor !== '';
    };

    foreach ($examenes as $examen) {
        $adicionalRaw = $examen['adicional'] ?? [];
        if ($hasSnapshotCol) {
            $snapshotRaw = $examen['adicional_snapshot'] ?? null;
            $examenRaw = $examen['adicional_examen'] ?? null;
            $adicionalRaw = ($snapshotRaw !== null && $snapshotRaw !== '') ? $snapshotRaw : $examenRaw;
        }

        $formatDef = lab_format_decode_definition($adicionalRaw ?? []);
        $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
        $adicional = $formatDef['legacy_items'];
        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];

        // Si soloImprimir=true, filtrar exámenes no marcados para imprimir
        if ($soloImprimir) {
            $imprimir_examen = isset($resultados['imprimir_examen']) ? intval($resultados['imprimir_examen']) : 1;
            if ($imprimir_examen !== 1) {
                continue; // Saltar este examen
            }
        }

        if ($isFormatV2) {
            $cols = lab_format_v2_columns($formatDef);
            $rows = lab_format_v2_rows($formatDef);
            $rowsResolved = lab_format_v2_resolve_rows($cols, $rows, is_array($resultados) ? $resultados : []);

            $colIdSet = [];
            foreach ($cols as $colDef) {
                if (!is_array($colDef)) {
                    continue;
                }
                $cid = trim((string)($colDef['id'] ?? ''));
                if ($cid !== '') {
                    $colIdSet[$cid] = true;
                }
            }

            $rowIdSet = [];
            foreach ($rowsResolved as $rowDef) {
                if (!is_array($rowDef)) {
                    continue;
                }
                $rid = trim((string)($rowDef['id'] ?? ''));
                if ($rid !== '') {
                    $rowIdSet[$rid] = true;
                }
            }

            foreach ($rowsResolved as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $rowType = strtolower(trim((string)($row['type'] ?? 'data')));
                if ($rowType === 'long_text') {
                    $rowId = trim((string)($row['id'] ?? ''));
                    if ($rowId === '') {
                        continue;
                    }
                    $isEditableTemplate = !array_key_exists('template_editable', $row) || (bool)$row['template_editable'];
                    if (!$isEditableTemplate) {
                        continue;
                    }
                    $total_parametros++;
                    $defaultTemplate = (string)($row['template_text'] ?? '');
                    $valorTemplate = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, lab_format_v2_long_text_col_id(), $defaultTemplate);
                    if ($valorLleno($valorTemplate)) {
                        $parametros_llenados++;
                    }
                    continue;
                }
                if ($rowType !== 'data') {
                    continue;
                }
                $rowId = trim((string)($row['id'] ?? ''));
                if ($rowId === '') {
                    continue;
                }
                $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
                $formulas = is_array($row['formulas'] ?? null) ? $row['formulas'] : [];

                foreach ($cols as $col) {
                    if (!is_array($col)) {
                        continue;
                    }
                    if (!lab_format_v2_col_visible($col, 'capture')) {
                        continue;
                    }
                    if (!lab_format_v2_col_editable($col)) {
                        continue;
                    }

                    $colId = trim((string)($col['id'] ?? ''));
                    if ($colId === '') {
                        continue;
                    }

                    $formulaExpr = trim((string)($formulas[$colId] ?? ''));
                    if ($formulaExpr !== '') {
                        // Fórmulas incompletas o mal definidas no deben penalizar el progreso.
                        $tokens = lab_format_v2_parse_tokens($formulaExpr);
                        if (count($tokens) === 0) {
                            continue;
                        }

                        $depsDefined = true;
                        foreach ($tokens as $token) {
                            [$refRow, $refCol] = lab_format_v2_resolve_token_target($token, $rowId);
                            if ($refRow === null || $refCol === null) {
                                $depsDefined = false;
                                break;
                            }
                            if (!isset($rowIdSet[$refRow]) || !isset($colIdSet[$refCol])) {
                                $depsDefined = false;
                                break;
                            }
                        }

                        if (!$depsDefined) {
                            continue;
                        }
                    }

                    $total_parametros++;
                    $defaultVal = $cells[$colId] ?? '';
                    $valor = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, $colId, $defaultVal);
                    if ($formulaExpr !== '' && !$valorLleno($valor)) {
                        // Fallback: usar el valor ya resuelto de la fila en memoria (sin depender de guardado explícito).
                        $valor = $cells[$colId] ?? $valor;
                    }
                    if ($valorLleno($valor)) {
                        $parametros_llenados++;
                    }
                }
            }
            continue;
        }

        if (!is_array($adicional)) {
            $adicional = [];
        }
        if (!is_array($resultados)) {
            $resultados = [];
        }

        $resultadosNorm = [];
        foreach ($resultados as $k => $v) {
            if ($k === 'imprimir_examen') {
                continue;
            }
            $nk = trim((string)$k);
            if ($nk === '') {
                continue;
            }
            $nk = preg_replace('/\s+/u', ' ', $nk);
            $nk = mb_strtolower($nk, 'UTF-8');
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nk);
            if ($ascii !== false && $ascii !== null) {
                $nk = $ascii;
            }
            $nk = preg_replace('/[^a-z0-9 ._-]/', '', $nk);
            if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) {
                $resultadosNorm[$nk] = $v;
            }
        }

        foreach ($adicional as $item) {
            // Contabilizar parámetros ingresables: Parámetro, Campo y Texto Largo
            $tipo = (string)($item['tipo'] ?? '');
            if ($tipo === 'Parámetro' || $tipo === 'Campo' || $tipo === 'Texto Largo') {
                $total_parametros++;
                $nombre = (string)($item['nombre'] ?? '');
                $stableKey = '';
                if (is_array($item) && !empty($item['id_parametro'])) {
                    $stableKey = 'id_parametro_' . trim((string)$item['id_parametro']);
                }
                $valor = null;
                if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
                    $valor = $resultados[$stableKey];
                } elseif (array_key_exists($nombre, $resultados)) {
                    $valor = $resultados[$nombre];
                } else {
                    $nombreNorm = trim((string)$nombre);
                    if ($nombreNorm !== '') {
                        $nombreNorm = preg_replace('/\s+/u', ' ', $nombreNorm);
                        $nombreNorm = mb_strtolower($nombreNorm, 'UTF-8');
                        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreNorm);
                        if ($ascii !== false && $ascii !== null) {
                            $nombreNorm = $ascii;
                        }
                        $nombreNorm = preg_replace('/[^a-z0-9 ._-]/', '', $nombreNorm);
                        if ($nombreNorm !== '' && array_key_exists($nombreNorm, $resultadosNorm)) {
                            $valor = $resultadosNorm[$nombreNorm];
                        }
                    }
                }
                if (
                    ($valor !== null || $valor === 0 || $valor === '0') && (
                        $valor !== '' && $valor !== null
                        || $valor === 0
                        || $valor === '0'
                    )
                ) {
                    $parametros_llenados++;
                }
            }
        }
    }
    if ($total_parametros === 0) return 0;
    return round(($parametros_llenados / $total_parametros) * 100);
}

// Devuelve [re.id => porcentaje] para cada examen de una cotización
function obtenerPorcentajesPorExamen($pdo, $idCotizacion) {
    static $hasSnapshotColPE = null;
    if ($hasSnapshotColPE === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $hasSnapshotColPE = !empty($col);
        } catch (Exception $e) {
            $hasSnapshotColPE = false;
        }
    }

    if ($hasSnapshotColPE) {
        $stmt = $pdo->prepare("SELECT re.id, re.resultados, re.adicional_snapshot, e.adicional AS adicional_examen
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    } else {
        $stmt = $pdo->prepare("SELECT re.id, re.resultados, e.adicional AS adicional
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    }
    $stmt->execute([$idCotizacion]);
    $examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $valorLleno = function ($valor) {
        if ($valor === 0 || $valor === '0') return true;
        if ($valor === null) return false;
        if (is_string($valor)) return trim($valor) !== '';
        if (is_array($valor)) return count($valor) > 0;
        return $valor !== '';
    };

    $resultado = [];
    foreach ($examenes as $examen) {
        $reId = (int)$examen['id'];
        $adicionalRaw = $examen['adicional'] ?? [];
        if ($hasSnapshotColPE) {
            $snapshotRaw = $examen['adicional_snapshot'] ?? null;
            $examenRaw   = $examen['adicional_examen'] ?? null;
            $adicionalRaw = ($snapshotRaw !== null && $snapshotRaw !== '') ? $snapshotRaw : $examenRaw;
        }

        $formatDef   = lab_format_decode_definition($adicionalRaw ?? []);
        $isFormatV2  = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
        $adicional   = $formatDef['legacy_items'];
        $resultados  = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];

        $total  = 0;
        $llenos = 0;

        if ($isFormatV2) {
            $cols = lab_format_v2_columns($formatDef);
            $rows = lab_format_v2_rows($formatDef);
            $rowsResolved = lab_format_v2_resolve_rows($cols, $rows, is_array($resultados) ? $resultados : []);

            $colIdSet = [];
            foreach ($cols as $c) {
                $cid = trim((string)($c['id'] ?? ''));
                if ($cid !== '') $colIdSet[$cid] = true;
            }
            $rowIdSet = [];
            foreach ($rowsResolved as $r) {
                $rid = trim((string)($r['id'] ?? ''));
                if ($rid !== '') $rowIdSet[$rid] = true;
            }

            foreach ($rowsResolved as $row) {
                if (!is_array($row)) continue;
                $rowType = strtolower(trim((string)($row['type'] ?? 'data')));
                if ($rowType === 'long_text') {
                    $rowId = trim((string)($row['id'] ?? ''));
                    if ($rowId === '') continue;
                    if (array_key_exists('template_editable', $row) && !(bool)$row['template_editable']) continue;
                    $total++;
                    $defaultTemplate = (string)($row['template_text'] ?? '');
                    $val = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, lab_format_v2_long_text_col_id(), $defaultTemplate);
                    if ($valorLleno($val)) $llenos++;
                    continue;
                }
                if ($rowType !== 'data') continue;
                $rowId = trim((string)($row['id'] ?? ''));
                if ($rowId === '') continue;
                $cells   = is_array($row['cells'] ?? null)   ? $row['cells']   : [];
                $formulas = is_array($row['formulas'] ?? null) ? $row['formulas'] : [];
                foreach ($cols as $col) {
                    if (!is_array($col)) continue;
                    if (!lab_format_v2_col_visible($col, 'capture') || !lab_format_v2_col_editable($col)) continue;
                    $colId = trim((string)($col['id'] ?? ''));
                    if ($colId === '') continue;
                    $formulaExpr = trim((string)($formulas[$colId] ?? ''));
                    if ($formulaExpr !== '') {
                        $tokens = lab_format_v2_parse_tokens($formulaExpr);
                        if (count($tokens) === 0) continue;
                        $depsDefined = true;
                        foreach ($tokens as $token) {
                            [$refRow, $refCol] = lab_format_v2_resolve_token_target($token, $rowId);
                            if ($refRow === null || $refCol === null || !isset($rowIdSet[$refRow]) || !isset($colIdSet[$refCol])) {
                                $depsDefined = false; break;
                            }
                        }
                        if (!$depsDefined) continue;
                    }
                    $total++;
                    $defaultVal = $cells[$colId] ?? '';
                    $val = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, $colId, $defaultVal);
                    if ($valorLleno($val)) $llenos++;
                }
            }
        } else {
            if (!is_array($adicional)) $adicional = [];
            if (!is_array($resultados)) $resultados = [];

            $resultadosNorm = [];
            foreach ($resultados as $k => $v) {
                if ($k === 'imprimir_examen') continue;
                $nk = trim((string)$k);
                if ($nk === '') continue;
                $nk = preg_replace('/\s+/u', ' ', $nk);
                $nk = mb_strtolower($nk, 'UTF-8');
                $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nk);
                if ($ascii !== false && $ascii !== null) $nk = $ascii;
                $nk = preg_replace('/[^a-z0-9 ._-]/', '', $nk);
                if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) $resultadosNorm[$nk] = $v;
            }

            foreach ($adicional as $item) {
                $tipo = (string)($item['tipo'] ?? '');
                if (!in_array($tipo, ['Parámetro', 'Campo', 'Texto Largo'], true)) continue;
                $total++;
                $nombre = (string)($item['nombre'] ?? '');
                $stableKey = '';
                if (!empty($item['id_parametro'])) {
                    $stableKey = 'id_parametro_' . trim((string)$item['id_parametro']);
                }
                $val = null;
                if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
                    $val = $resultados[$stableKey];
                } elseif (array_key_exists($nombre, $resultados)) {
                    $val = $resultados[$nombre];
                } else {
                    $nNorm = trim($nombre);
                    if ($nNorm !== '') {
                        $nNorm = preg_replace('/\s+/u', ' ', $nNorm);
                        $nNorm = mb_strtolower($nNorm, 'UTF-8');
                        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nNorm);
                        if ($ascii !== false && $ascii !== null) $nNorm = $ascii;
                        $nNorm = preg_replace('/[^a-z0-9 ._-]/', '', $nNorm);
                        if ($nNorm !== '' && array_key_exists($nNorm, $resultadosNorm)) $val = $resultadosNorm[$nNorm];
                    }
                }
                if ($val !== null && $val !== '') $llenos++;
            }
        }

        $resultado[$reId] = ($total === 0) ? 0 : (int)round(($llenos / $total) * 100);
    }

    return $resultado;
}

// Devuelve [id_cotizacion => [re.id => porcentaje]] para un lote de cotizaciones
function obtenerPorcentajesPorExamenLote($pdo, array $cotizacionIds) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $cotizacionIds), function ($v) {
        return $v > 0;
    })));
    if (empty($ids)) {
        return [];
    }

    static $hasSnapshotColPELote = null;
    if ($hasSnapshotColPELote === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $hasSnapshotColPELote = !empty($col);
        } catch (Exception $e) {
            $hasSnapshotColPELote = false;
        }
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    if ($hasSnapshotColPELote) {
        $stmt = $pdo->prepare("SELECT re.id_cotizacion, re.id, re.resultados, re.adicional_snapshot, e.adicional AS adicional_examen
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion IN ($placeholders)");
    } else {
        $stmt = $pdo->prepare("SELECT re.id_cotizacion, re.id, re.resultados, e.adicional AS adicional
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion IN ($placeholders)");
    }
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rowsPorCotizacion = [];
    foreach ($rows as $row) {
        $cid = (int)($row['id_cotizacion'] ?? 0);
        if ($cid <= 0) {
            continue;
        }
        if (!isset($rowsPorCotizacion[$cid])) {
            $rowsPorCotizacion[$cid] = [];
        }
        $rowsPorCotizacion[$cid][] = $row;
    }

    $valorLleno = function ($valor) {
        if ($valor === 0 || $valor === '0') return true;
        if ($valor === null) return false;
        if (is_string($valor)) return trim($valor) !== '';
        if (is_array($valor)) return count($valor) > 0;
        return $valor !== '';
    };

    $salida = [];
    foreach ($rowsPorCotizacion as $cid => $examenes) {
        $porcentajes = [];
        foreach ($examenes as $examen) {
            $reId = (int)($examen['id'] ?? 0);
            if ($reId <= 0) {
                continue;
            }

            $adicionalRaw = $examen['adicional'] ?? [];
            if ($hasSnapshotColPELote) {
                $snapshotRaw = $examen['adicional_snapshot'] ?? null;
                $examenRaw = $examen['adicional_examen'] ?? null;
                $adicionalRaw = ($snapshotRaw !== null && $snapshotRaw !== '') ? $snapshotRaw : $examenRaw;
            }

            $formatDef = lab_format_decode_definition($adicionalRaw ?? []);
            $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
            $adicional = $formatDef['legacy_items'];
            $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];

            $total = 0;
            $llenos = 0;

            if ($isFormatV2) {
                $cols = lab_format_v2_columns($formatDef);
                $rowsV2 = lab_format_v2_rows($formatDef);
                $rowsResolved = lab_format_v2_resolve_rows($cols, $rowsV2, is_array($resultados) ? $resultados : []);

                $colIdSet = [];
                foreach ($cols as $c) {
                    $cidCol = trim((string)($c['id'] ?? ''));
                    if ($cidCol !== '') $colIdSet[$cidCol] = true;
                }
                $rowIdSet = [];
                foreach ($rowsResolved as $r) {
                    $rid = trim((string)($r['id'] ?? ''));
                    if ($rid !== '') $rowIdSet[$rid] = true;
                }

                foreach ($rowsResolved as $rowV2) {
                    if (!is_array($rowV2)) continue;
                    $rowType = strtolower(trim((string)($rowV2['type'] ?? 'data')));
                    if ($rowType === 'long_text') {
                        $rowId = trim((string)($rowV2['id'] ?? ''));
                        if ($rowId === '') continue;
                        if (array_key_exists('template_editable', $rowV2) && !(bool)$rowV2['template_editable']) continue;
                        $total++;
                        $defaultTemplate = (string)($rowV2['template_text'] ?? '');
                        $val = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, lab_format_v2_long_text_col_id(), $defaultTemplate);
                        if ($valorLleno($val)) $llenos++;
                        continue;
                    }
                    if ($rowType !== 'data') continue;

                    $rowId = trim((string)($rowV2['id'] ?? ''));
                    if ($rowId === '') continue;
                    $cells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];
                    $formulas = is_array($rowV2['formulas'] ?? null) ? $rowV2['formulas'] : [];

                    foreach ($cols as $col) {
                        if (!is_array($col)) continue;
                        if (!lab_format_v2_col_visible($col, 'capture') || !lab_format_v2_col_editable($col)) continue;
                        $colId = trim((string)($col['id'] ?? ''));
                        if ($colId === '') continue;

                        $formulaExpr = trim((string)($formulas[$colId] ?? ''));
                        if ($formulaExpr !== '') {
                            $tokens = lab_format_v2_parse_tokens($formulaExpr);
                            if (count($tokens) === 0) continue;
                            $depsDefined = true;
                            foreach ($tokens as $token) {
                                [$refRow, $refCol] = lab_format_v2_resolve_token_target($token, $rowId);
                                if ($refRow === null || $refCol === null || !isset($rowIdSet[$refRow]) || !isset($colIdSet[$refCol])) {
                                    $depsDefined = false;
                                    break;
                                }
                            }
                            if (!$depsDefined) continue;
                        }

                        $total++;
                        $defaultVal = $cells[$colId] ?? '';
                        $val = lab_format_v2_get_result_value(is_array($resultados) ? $resultados : [], $rowId, $colId, $defaultVal);
                        if ($valorLleno($val)) $llenos++;
                    }
                }
            } else {
                if (!is_array($adicional)) $adicional = [];
                if (!is_array($resultados)) $resultados = [];

                $resultadosNorm = [];
                foreach ($resultados as $k => $v) {
                    if ($k === 'imprimir_examen') continue;
                    $nk = trim((string)$k);
                    if ($nk === '') continue;
                    $nk = preg_replace('/\s+/u', ' ', $nk);
                    $nk = mb_strtolower($nk, 'UTF-8');
                    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nk);
                    if ($ascii !== false && $ascii !== null) $nk = $ascii;
                    $nk = preg_replace('/[^a-z0-9 ._-]/', '', $nk);
                    if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) $resultadosNorm[$nk] = $v;
                }

                foreach ($adicional as $item) {
                    $tipo = (string)($item['tipo'] ?? '');
                    if (!in_array($tipo, ['Parámetro', 'Campo', 'Texto Largo'], true)) continue;
                    $total++;
                    $nombre = (string)($item['nombre'] ?? '');
                    $stableKey = '';
                    if (!empty($item['id_parametro'])) {
                        $stableKey = 'id_parametro_' . trim((string)$item['id_parametro']);
                    }
                    $val = null;
                    if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
                        $val = $resultados[$stableKey];
                    } elseif (array_key_exists($nombre, $resultados)) {
                        $val = $resultados[$nombre];
                    } else {
                        $nNorm = trim($nombre);
                        if ($nNorm !== '') {
                            $nNorm = preg_replace('/\s+/u', ' ', $nNorm);
                            $nNorm = mb_strtolower($nNorm, 'UTF-8');
                            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nNorm);
                            if ($ascii !== false && $ascii !== null) $nNorm = $ascii;
                            $nNorm = preg_replace('/[^a-z0-9 ._-]/', '', $nNorm);
                            if ($nNorm !== '' && array_key_exists($nNorm, $resultadosNorm)) $val = $resultadosNorm[$nNorm];
                        }
                    }
                    if ($val !== null && $val !== '') $llenos++;
                }
            }

            $porcentajes[$reId] = ($total === 0) ? 0 : (int)round(($llenos / $total) * 100);
        }

        $salida[(int)$cid] = $porcentajes;
    }

    return $salida;
}

// Funciones utilitarias para cotizaciones
function obtenerSaldoCotizacion($pdo, $idCotizacion) {
    $stmt = $pdo->prepare('SELECT total, estado_pago, (SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = c.id) AS pagado FROM cotizaciones c WHERE c.id = ?');
    $stmt->execute([$idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    if (isset($row['estado_pago']) && strtolower((string)$row['estado_pago']) === 'anulada') {
        return 0;
    }
    return max(0, floatval($row['total']) - floatval($row['pagado']));
}
