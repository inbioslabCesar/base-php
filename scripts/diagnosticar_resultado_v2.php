<?php
/**
 * Diagnostico de resultado v2 para detectar desalineacion entre:
 * - adicional_snapshot.layout (rows/columns)
 * - resultados (claves v2__...)
 *
 * Uso:
 *   EMPRESA=inbioslab php scripts/diagnosticar_resultado_v2.php --cotizacion=4216 --examen=179
 *   EMPRESA=inbioslab php scripts/diagnosticar_resultado_v2.php --resultado=9436
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$options = getopt('', ['cotizacion::', 'examen::', 'resultado::']);
$cotizacionId = isset($options['cotizacion']) ? (int)$options['cotizacion'] : 0;
$examenId = isset($options['examen']) ? (int)$options['examen'] : 0;
$resultadoId = isset($options['resultado']) ? (int)$options['resultado'] : 0;

if ($resultadoId <= 0 && ($cotizacionId <= 0 || $examenId <= 0)) {
    fwrite(STDERR, "Uso invalido. Debes pasar --resultado=ID o --cotizacion=ID --examen=ID\n");
    exit(1);
}

function out($label, $value)
{
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }
    if (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    echo $label . ': ' . $value . PHP_EOL;
}

function is_v2_format($arr)
{
    return is_array($arr)
        && isset($arr['schema_version'])
        && (int)($arr['schema_version'] ?? 0) >= 2
        && isset($arr['layout'])
        && is_array($arr['layout'])
        && isset($arr['layout']['columns'])
        && is_array($arr['layout']['columns'])
        && isset($arr['layout']['rows'])
        && is_array($arr['layout']['rows']);
}

function editable_cols(array $columns)
{
    $out = [];
    foreach ($columns as $c) {
        if (!is_array($c)) {
            continue;
        }
        $id = trim((string)($c['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $kind = strtolower(trim((string)($c['kind'] ?? 'text')));
        $editable = array_key_exists('editable', $c)
            ? (bool)$c['editable']
            : in_array($kind, ['result', 'formula', 'select', 'long_text', 'number'], true);
        if ($editable) {
            $out[] = $id;
        }
    }
    return $out;
}

function expected_v2_keys(array $format)
{
    $keys = [];
    $columns = array_values((array)($format['layout']['columns'] ?? []));
    usort($columns, static function ($a, $b) {
        return (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0);
    });

    $rows = array_values((array)($format['layout']['rows'] ?? []));
    $editable = editable_cols($columns);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rid = trim((string)($row['id'] ?? ''));
        if ($rid === '') {
            continue;
        }
        $type = strtolower(trim((string)($row['type'] ?? 'data')));
        if ($type === 'long_text') {
            $keys[] = 'v2__' . $rid . '____texto';
            continue;
        }
        if (!in_array($type, ['data', 'formula'], true)) {
            continue;
        }
        foreach ($editable as $cid) {
            $keys[] = 'v2__' . $rid . '__' . $cid;
        }
    }

    return $keys;
}

try {
    if ($resultadoId > 0) {
        $stmt = $pdo->prepare("SELECT re.id, re.id_examen, re.id_cotizacion, re.resultados, re.adicional_snapshot, e.adicional AS adicional_examen, e.nombre AS examen_nombre
            FROM resultados_examenes re
            JOIN examenes e ON e.id = re.id_examen
            WHERE re.id = :id
            LIMIT 1");
        $stmt->execute(['id' => $resultadoId]);
    } else {
        $stmt = $pdo->prepare("SELECT re.id, re.id_examen, re.id_cotizacion, re.resultados, re.adicional_snapshot, e.adicional AS adicional_examen, e.nombre AS examen_nombre
            FROM resultados_examenes re
            JOIN examenes e ON e.id = re.id_examen
            WHERE re.id_cotizacion = :cotizacion AND re.id_examen = :examen
            LIMIT 1");
        $stmt->execute([
            'cotizacion' => $cotizacionId,
            'examen' => $examenId,
        ]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        out('encontrado', false);
        exit(0);
    }

    $resultados = json_decode((string)($row['resultados'] ?? ''), true);
    if (!is_array($resultados)) {
        $resultados = [];
    }

    $snapshot = json_decode((string)($row['adicional_snapshot'] ?? ''), true);
    if (!is_array($snapshot)) {
        $snapshot = [];
    }

    $examen = json_decode((string)($row['adicional_examen'] ?? ''), true);
    if (!is_array($examen)) {
        $examen = [];
    }

    $snapshotV2 = is_v2_format($snapshot);
    $examenV2 = is_v2_format($examen);

    $allResultKeys = array_keys($resultados);
    $v2ResultKeys = array_values(array_filter($allResultKeys, static function ($k) {
        return strpos((string)$k, 'v2__') === 0;
    }));

    out('resultado_id', (int)$row['id']);
    out('cotizacion_id', (int)$row['id_cotizacion']);
    out('examen_id', (int)$row['id_examen']);
    out('examen_nombre', (string)$row['examen_nombre']);
    out('snapshot_v2', $snapshotV2);
    out('examen_v2', $examenV2);
    out('total_result_keys', count($allResultKeys));
    out('v2_result_keys', count($v2ResultKeys));

    if ($snapshotV2) {
        $cols = array_values((array)($snapshot['layout']['columns'] ?? []));
        $rows = array_values((array)($snapshot['layout']['rows'] ?? []));
        out('snapshot_columns', count($cols));
        out('snapshot_rows', count($rows));

        $expected = expected_v2_keys($snapshot);
        $expectedSet = [];
        foreach ($expected as $k) {
            $expectedSet[$k] = true;
        }

        $matched = [];
        $orphan = [];
        foreach ($v2ResultKeys as $k) {
            if (isset($expectedSet[$k])) {
                $matched[] = $k;
            } else {
                $orphan[] = $k;
            }
        }

        $missing = [];
        foreach ($expected as $k) {
            if (!array_key_exists($k, $resultados)) {
                $missing[] = $k;
            }
        }

        out('expected_v2_keys', count($expected));
        out('matched_v2_keys', count($matched));
        out('orphan_v2_keys', count($orphan));
        out('missing_v2_keys', count($missing));

        out('sample_orphan', array_slice($orphan, 0, 15));
        out('sample_missing', array_slice($missing, 0, 15));
    }

    if ($snapshotV2 && $examenV2) {
        $snapHash = md5((string)$row['adicional_snapshot']);
        $examHash = md5((string)$row['adicional_examen']);
        out('snapshot_equals_exam', $snapHash === $examHash);
        out('snapshot_md5', $snapHash);
        out('exam_md5', $examHash);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
