<?php
require_once __DIR__ . '/../../examenes/formato_dinamico_helper.php';

// Calcula el porcentaje de parámetros llenados para una cotización
function obtenerPorcentajeResultadosCotizacion($pdo, $idCotizacion) {
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
        $stmt = $pdo->prepare("SELECT re.resultados, COALESCE(re.adicional_snapshot, e.adicional) AS adicional
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
        $formatDef = lab_format_decode_definition($examen['adicional'] ?? []);
        $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
        $adicional = $formatDef['legacy_items'];
        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];

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
