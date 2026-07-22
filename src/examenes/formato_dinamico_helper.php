<?php

if (!function_exists('lab_format_v2_enabled')) {
    function lab_format_v2_enabled()
    {
        if (defined('LAB_FORMAT_V2_ENABLED')) {
            return (bool) LAB_FORMAT_V2_ENABLED;
        }

        $raw = getenv('LAB_FORMAT_V2_ENABLED');
        if ($raw === false) {
            return false;
        }

        $value = strtolower(trim((string)$raw));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('lab_format_decode_definition')) {
    function lab_format_decode_definition($source)
    {
        $decoded = [];
        if (is_array($source)) {
            $decoded = $source;
        } elseif (is_string($source) && trim($source) !== '') {
            $tmp = json_decode($source, true);
            if (is_array($tmp)) {
                $decoded = $tmp;
            }
        }

        $isV2 = false;
        if (is_array($decoded)) {
            $schemaVersion = intval($decoded['schema_version'] ?? 0);
            $layout = $decoded['layout'] ?? null;
            $isV2 = ($schemaVersion >= 2) && is_array($layout) && is_array($layout['columns'] ?? null);
        }

        return [
            'raw' => $decoded,
            'is_v2' => $isV2,
            'legacy_items' => $isV2 ? [] : (is_array($decoded) ? $decoded : []),
            'layout' => $isV2 ? ($decoded['layout'] ?? []) : [],
        ];
    }
}

if (!function_exists('lab_format_v2_columns')) {
    function lab_format_v2_columns(array $format)
    {
        $cols = $format['layout']['columns'] ?? [];
        if (!is_array($cols)) {
            return [];
        }

        $cols = array_values(array_filter($cols, static function ($col) {
            return is_array($col) && trim((string)($col['id'] ?? '')) !== '';
        }));

        usort($cols, static function ($a, $b) {
            return intval($a['order'] ?? 0) <=> intval($b['order'] ?? 0);
        });

        return $cols;
    }
}

if (!function_exists('lab_format_v2_rows')) {
    function lab_format_v2_rows(array $format)
    {
        $rows = $format['layout']['rows'] ?? [];
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('lab_format_v2_cell_key')) {
    function lab_format_v2_cell_key($rowId, $colId)
    {
        $row = trim((string)$rowId);
        $col = trim((string)$colId);
        if ($row === '' || $col === '') {
            return '';
        }
        return 'v2__' . $row . '__' . $col;
    }
}

if (!function_exists('lab_format_v2_get_result_value')) {
    function lab_format_v2_get_result_value(array $resultados, $rowId, $colId, $default = '')
    {
        $key = lab_format_v2_cell_key($rowId, $colId);
        if ($key !== '' && array_key_exists($key, $resultados)) {
            return $resultados[$key];
        }

        return $default;
    }
}

if (!function_exists('lab_format_v2_col_visible')) {
    function lab_format_v2_col_visible(array $col, $target)
    {
        if ($target === 'capture') {
            return !array_key_exists('visible_capture', $col) || (bool)$col['visible_capture'];
        }
        if ($target === 'pdf') {
            return !array_key_exists('visible_pdf', $col) || (bool)$col['visible_pdf'];
        }
        return true;
    }
}

if (!function_exists('lab_format_v2_col_editable')) {
    function lab_format_v2_col_editable(array $col)
    {
        if (array_key_exists('editable', $col)) {
            return (bool)$col['editable'];
        }

        $kind = strtolower(trim((string)($col['kind'] ?? '')));
        return in_array($kind, ['result', 'formula', 'select', 'long_text', 'number'], true);
    }
}

if (!function_exists('lab_format_v2_parse_tokens')) {
    function lab_format_v2_parse_tokens($formula)
    {
        $out = [];
        if (!is_string($formula) || trim($formula) === '') {
            return $out;
        }
        if (preg_match_all('/\[([^\]]+)\]/', $formula, $m)) {
            foreach ($m[1] as $token) {
                $out[] = trim((string)$token);
            }
        }
        return $out;
    }
}

if (!function_exists('lab_format_v2_resolve_token_target')) {
    function lab_format_v2_resolve_token_target($token, $currentRowId)
    {
        $token = trim((string)$token);
        if ($token === '') {
            return [null, null];
        }
        if (strpos($token, ':') !== false) {
            [$rowId, $colId] = array_pad(explode(':', $token, 2), 2, '');
            $rowId = trim((string)$rowId);
            $colId = trim((string)$colId);
            return [$rowId !== '' ? $rowId : null, $colId !== '' ? $colId : null];
        }
        $colId = trim((string)$token);
        return [$currentRowId !== '' ? $currentRowId : null, $colId !== '' ? $colId : null];
    }
}

if (!function_exists('lab_format_v2_resolve_rows')) {
    function lab_format_v2_resolve_rows(array $columns, array $rows, array $resultados)
    {
        $resolvedRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $rowId = trim((string)($row['id'] ?? ''));
            foreach ($columns as $col) {
                $colId = trim((string)($col['id'] ?? ''));
                if ($colId === '') {
                    continue;
                }
                $defaultVal = $cells[$colId] ?? '';
                if (lab_format_v2_col_editable($col) && $rowId !== '') {
                    $cells[$colId] = lab_format_v2_get_result_value($resultados, $rowId, $colId, $defaultVal);
                } else {
                    $cells[$colId] = $defaultVal;
                }
            }
            $row['cells'] = $cells;
            $resolvedRows[] = $row;
        }

        $valueMap = [];
        foreach ($resolvedRows as $row) {
            $rowId = trim((string)($row['id'] ?? ''));
            if ($rowId === '') {
                continue;
            }
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            foreach ($cells as $colId => $val) {
                $key = $rowId . ':' . trim((string)$colId);
                $valueMap[$key] = $val;
            }
        }

        $maxIter = max(1, count($resolvedRows) + 2);
        for ($iter = 0; $iter < $maxIter; $iter++) {
            $changed = false;

            foreach ($resolvedRows as $ri => $row) {
                $rowType = strtolower(trim((string)($row['type'] ?? 'data')));
                if ($rowType !== 'data') {
                    continue;
                }
                $rowId = trim((string)($row['id'] ?? ''));
                if ($rowId === '') {
                    continue;
                }
                $formulas = is_array($row['formulas'] ?? null) ? $row['formulas'] : [];
                $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];

                foreach ($formulas as $formulaColId => $formulaExpr) {
                    $formulaColId = trim((string)$formulaColId);
                    $formulaExpr = trim((string)$formulaExpr);
                    if ($formulaColId === '' || $formulaExpr === '') {
                        continue;
                    }

                    $tokens = lab_format_v2_parse_tokens($formulaExpr);
                    if (count($tokens) === 0) {
                        continue;
                    }

                    $canEvaluate = true;
                    $expr = preg_replace_callback('/\[([^\]]+)\]/', function ($m) use (&$canEvaluate, $rowId, $valueMap) {
                        $token = trim((string)($m[1] ?? ''));
                        [$refRow, $refCol] = lab_format_v2_resolve_token_target($token, $rowId);
                        if ($refRow === null || $refCol === null) {
                            $canEvaluate = false;
                            return '0';
                        }
                        $k = $refRow . ':' . $refCol;
                        if (!array_key_exists($k, $valueMap)) {
                            $canEvaluate = false;
                            return '0';
                        }
                        $raw = $valueMap[$k];
                        if (is_string($raw)) {
                            $raw = str_replace(',', '', trim($raw));
                        }
                        if ($raw === '' || $raw === null || !is_numeric($raw)) {
                            $canEvaluate = false;
                            return '0';
                        }
                        return (string)$raw;
                    }, $formulaExpr);

                    if (!$canEvaluate) {
                        continue;
                    }

                    $expr = preg_replace('/([0-9\.]|\))\s*\(/', '$1*(', $expr);
                    $expr = preg_replace('/\)\s*([0-9\.-])/', ')*$1', $expr);
                    if (strpos($expr, '^') !== false) {
                        $expr = str_replace('^', '**', $expr);
                    }

                    try {
                        $res = eval('return ' . $expr . ';');
                    } catch (Throwable $e) {
                        $res = null;
                    }

                    if (!is_numeric($res)) {
                        continue;
                    }

                    $formatted = (floor((float)$res) == (float)$res) ? (string)intval($res) : (string)$res;
                    $current = (string)($cells[$formulaColId] ?? '');
                    if ($current !== $formatted) {
                        $cells[$formulaColId] = $formatted;
                        $valueMap[$rowId . ':' . $formulaColId] = $formatted;
                        $changed = true;
                    }
                }

                $resolvedRows[$ri]['cells'] = $cells;
            }

            if (!$changed) {
                break;
            }
        }

        return $resolvedRows;
    }
}
