<?php
/**
 * Transforma un formato dinamico v2 con una sola columna de resultado
 * a un modelo con columnas separadas hora_1 y hora_2.
 *
 * Uso:
 *   php scripts/transformar_espermatograma_horas_v2.php --examen=179
 *   php scripts/transformar_espermatograma_horas_v2.php --examen=179 --apply
 *   php scripts/transformar_espermatograma_horas_v2.php --examen=179 --apply --sync-snapshots
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se puede ejecutar por CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$options = getopt('', ['examen::', 'apply', 'sync-snapshots']);
$examId = isset($options['examen']) ? (int)$options['examen'] : 179;
$apply = array_key_exists('apply', $options);
$syncSnapshots = array_key_exists('sync-snapshots', $options);

function out($msg)
{
    fwrite(STDOUT, $msg . PHP_EOL);
}

function normalize_text($text)
{
    $text = mb_strtolower((string)$text, 'UTF-8');
    $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

function strip_hour_suffix($text)
{
    $text = (string)$text;
    // Quita sufijos/prefijos comunes de hora para poder agrupar filas gemelas.
    $patterns = [
        '/\b(1\s*h(?:ora)?|1ra\s*h(?:ora)?|una\s*h(?:ora)?)\b/iu',
        '/\b(2\s*h(?:ora)?|2da\s*h(?:ora)?|dos\s*h(?:ora)?)\b/iu',
        '/\(\s*(1\s*h(?:ora)?|2\s*h(?:ora)?)\s*\)/iu',
    ];

    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    $text = preg_replace('/\s+[-:\/]\s+$/u', '', (string)$text);
    $text = preg_replace('/\s{2,}/u', ' ', (string)$text);
    return trim((string)$text);
}

function detect_hour_bucket($text)
{
    $normalized = normalize_text($text);

    if (preg_match('/\b(1\s*h|1\s*hora|1ra\s*h|una\s*hora)\b/u', $normalized)) {
        return 'hora_1';
    }
    if (preg_match('/\b(2\s*h|2\s*hora|2da\s*h|dos\s*hora)\b/u', $normalized)) {
        return 'hora_2';
    }

    return '';
}

function get_column_by_kind(array $columns, $kind)
{
    foreach ($columns as $col) {
        if (!is_array($col)) {
            continue;
        }
        $k = strtolower(trim((string)($col['kind'] ?? '')));
        if ($k === $kind) {
            return $col;
        }
    }
    return null;
}

function get_first_text_col_id(array $columns)
{
    foreach ($columns as $col) {
        if (!is_array($col)) {
            continue;
        }
        $kind = strtolower(trim((string)($col['kind'] ?? '')));
        if ($kind === 'text') {
            $id = trim((string)($col['id'] ?? ''));
            if ($id !== '') {
                return $id;
            }
        }
    }

    return '';
}

function build_new_columns(array $oldColumns)
{
    $resultCol = get_column_by_kind($oldColumns, 'result');
    $resultOrder = (int)($resultCol['order'] ?? 3);

    $newColumns = [];
    foreach ($oldColumns as $col) {
        if (!is_array($col)) {
            continue;
        }

        $kind = strtolower(trim((string)($col['kind'] ?? '')));
        if ($kind === 'result') {
            continue;
        }

        $newColumns[] = $col;
    }

    $base = is_array($resultCol) ? $resultCol : [
        'kind' => 'result',
        'editable' => true,
        'visible_capture' => true,
        'visible_pdf' => true,
        'width' => '',
        'order' => 3,
    ];

    $colHora1 = $base;
    $colHora1['id'] = 'hora_1';
    $colHora1['label'] = '1 hora';
    $colHora1['order'] = $resultOrder;

    $colHora2 = $base;
    $colHora2['id'] = 'hora_2';
    $colHora2['label'] = '2 horas';
    $colHora2['order'] = $resultOrder + 1;

    $inserted = false;
    $finalColumns = [];
    foreach ($newColumns as $col) {
        $order = (int)($col['order'] ?? 0);
        if (!$inserted && $order >= $resultOrder) {
            $finalColumns[] = $colHora1;
            $finalColumns[] = $colHora2;
            $inserted = true;
        }
        $finalColumns[] = $col;
    }

    if (!$inserted) {
        $finalColumns[] = $colHora1;
        $finalColumns[] = $colHora2;
    }

    usort($finalColumns, static function ($a, $b) {
        return (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0);
    });

    $idx = 1;
    foreach ($finalColumns as &$col) {
        $col['order'] = $idx++;
    }
    unset($col);

    return $finalColumns;
}

function transform_rows(array $rows, array $oldColumns)
{
    $resultCol = get_column_by_kind($oldColumns, 'result');
    $resultColId = trim((string)($resultCol['id'] ?? 'resultado'));
    $textColId = get_first_text_col_id($oldColumns);

    $merged = [];
    $createdByGroup = [];

    foreach ($rows as $rowIndex => $row) {
        if (!is_array($row)) {
            continue;
        }

        $type = strtolower(trim((string)($row['type'] ?? 'data')));
        if (!in_array($type, ['data', 'formula'], true)) {
            $merged[] = $row;
            continue;
        }

        $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
        $rowId = trim((string)($row['id'] ?? ('row_' . $rowIndex)));
        $label = trim((string)($row['label'] ?? ''));
        $textCell = $textColId !== '' ? trim((string)($cells[$textColId] ?? '')) : '';
        $probe = $textCell !== '' ? $textCell : $label;

        $bucket = detect_hour_bucket($probe);
        $groupBase = normalize_text(strip_hour_suffix($probe));

        $groupKey = $groupBase;
        if ($groupKey === '') {
            $groupKey = normalize_text($rowId);
        }

        if (!isset($createdByGroup[$groupKey])) {
            $newRow = $row;
            $newCells = $cells;

            if ($textColId !== '' && $probe !== '') {
                $newCells[$textColId] = strip_hour_suffix($probe);
            }

            $oldResult = (string)($cells[$resultColId] ?? '');
            unset($newCells[$resultColId]);

            $newCells['hora_1'] = '';
            $newCells['hora_2'] = '';

            if ($bucket === 'hora_1') {
                $newCells['hora_1'] = $oldResult;
            } elseif ($bucket === 'hora_2') {
                $newCells['hora_2'] = $oldResult;
            } else {
                // Si no trae sufijo de hora, preserva el valor en 1 hora para no perder data.
                $newCells['hora_1'] = $oldResult;
            }

            $newRow['cells'] = $newCells;
            $merged[] = $newRow;
            $createdByGroup[$groupKey] = count($merged) - 1;
            continue;
        }

        $targetIdx = $createdByGroup[$groupKey];
        if (!isset($merged[$targetIdx]) || !is_array($merged[$targetIdx])) {
            continue;
        }

        $oldResult = (string)($cells[$resultColId] ?? '');
        if ($bucket === 'hora_2') {
            $merged[$targetIdx]['cells']['hora_2'] = $oldResult;
        } elseif ($bucket === 'hora_1') {
            if ((string)($merged[$targetIdx]['cells']['hora_1'] ?? '') === '') {
                $merged[$targetIdx]['cells']['hora_1'] = $oldResult;
            }
        }
    }

    return $merged;
}

try {
    $stmt = $pdo->prepare('SELECT id, nombre, adicional FROM examenes WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new RuntimeException('No se encontro el examen ' . $examId);
    }

    $payload = json_decode((string)$exam['adicional'], true);
    if (!is_array($payload)) {
        throw new RuntimeException('adicional no es JSON valido en examen ' . $examId);
    }

    $schemaVersion = (int)($payload['schema_version'] ?? 0);
    $layout = $payload['layout'] ?? null;
    if ($schemaVersion < 2 || !is_array($layout)) {
        throw new RuntimeException('El examen no tiene layout v2 activo.');
    }

    $oldColumns = is_array($layout['columns'] ?? null) ? $layout['columns'] : [];
    $oldRows = is_array($layout['rows'] ?? null) ? $layout['rows'] : [];

    if (!$oldColumns || !$oldRows) {
        throw new RuntimeException('El layout v2 no tiene columnas o filas.');
    }

    $newColumns = build_new_columns($oldColumns);
    $newRows = transform_rows($oldRows, $oldColumns);

    $payload['schema_version'] = 2;
    $payload['layout']['columns'] = $newColumns;
    $payload['layout']['rows'] = $newRows;

    $newJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (!is_string($newJson) || $newJson === '') {
        throw new RuntimeException('No se pudo serializar el nuevo JSON.');
    }

    out('Examen: ' . $exam['id'] . ' - ' . $exam['nombre']);
    out('Columnas antes: ' . count($oldColumns));
    out('Columnas despues: ' . count($newColumns));
    out('Filas antes: ' . count($oldRows));
    out('Filas despues: ' . count($newRows));

    if (!$apply) {
        out('Modo simulacion: sin cambios en BD.');
        out('Ejecuta con --apply para guardar cambios.');
        exit(0);
    }

    $pdo->beginTransaction();

    $up = $pdo->prepare('UPDATE examenes SET adicional = :adicional WHERE id = :id');
    $up->execute([
        ':adicional' => $newJson,
        ':id' => $examId,
    ]);

    out('Examen actualizado en BD.');

    if ($syncSnapshots) {
        $sync = $pdo->prepare('UPDATE resultados_examenes SET adicional_snapshot = :snap WHERE id_examen = :id_examen');
        $sync->execute([
            ':snap' => $newJson,
            ':id_examen' => $examId,
        ]);
        out('Snapshots sincronizados: ' . (int)$sync->rowCount());
    } else {
        out('Snapshots no sincronizados (usa --sync-snapshots si lo deseas).');
    }

    $pdo->commit();
    out('Proceso completado.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
