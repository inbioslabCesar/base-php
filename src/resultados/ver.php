<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../examenes/formato_dinamico_helper.php';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mensaje']) . '</div>';
    unset($_SESSION['mensaje']);
}

// Obtener IDs de la URL
$id_resultado = $_GET['id_resultado'] ?? null;
$id_examen = $_GET['id_examen'] ?? null;

if ($id_resultado && $id_examen) {
    // Obtener resultados guardados
    $sql = "SELECT resultados FROM resultados_examenes WHERE id = ? AND id_examen = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_resultado, $id_examen]);
    $resultados_json = $stmt->fetchColumn();

    // Obtener parámetros del examen (preferir snapshot histórico si existe)
    $hasSnapshotCol = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
        $hasSnapshotCol = !empty($col);
    } catch (Exception $e) {
        $hasSnapshotCol = false;
    }
    if ($hasSnapshotCol) {
        $sql2 = "SELECT COALESCE(re.adicional_snapshot, e.adicional) AS adicional
                 FROM resultados_examenes re
                 JOIN examenes e ON e.id = re.id_examen
                 WHERE re.id = ? AND re.id_examen = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$id_resultado, $id_examen]);
        $adicional_json = $stmt2->fetchColumn();
    } else {
        $sql2 = "SELECT adicional FROM examenes WHERE id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$id_examen]);
        $adicional_json = $stmt2->fetchColumn();
    }

    $formatDef = lab_format_decode_definition($adicional_json);
    $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
    $parametros = $formatDef['legacy_items'];
    $resultados = $resultados_json ? json_decode($resultados_json, true) : [];

    $normKey = function ($s) {
        $s = (string) $s;
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = mb_strtolower($s, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($ascii !== false && $ascii !== null) {
            $s = $ascii;
        }
        $s = preg_replace('/[^a-z0-9 ._-]/', '', $s);
        return $s;
    };
    $resultadosNorm = [];
    if (is_array($resultados)) {
        foreach ($resultados as $k => $v) {
            if ($k === 'imprimir_examen') {
                continue;
            }
            $nk = $normKey($k);
            if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) {
                $resultadosNorm[$nk] = $v;
            }
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

    $getResultado = function ($nombre, $item = null, $default = '-') use ($resultados, $resultadosNorm, $normKey, $buildStableKey) {
        if (!is_array($resultados)) {
            return $default;
        }
        $stableKey = $buildStableKey($item);
        if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
            return $resultados[$stableKey];
        }
        if (array_key_exists($nombre, $resultados)) {
            return $resultados[$nombre];
        }
        $upper = mb_strtoupper((string) $nombre, 'UTF-8');
        if (array_key_exists($upper, $resultados)) {
            return $resultados[$upper];
        }
        $nk = $normKey($nombre);
        if ($nk !== '' && array_key_exists($nk, $resultadosNorm)) {
            return $resultadosNorm[$nk];
        }
        return $default;
    };

    echo "<h4>Resultados guardados</h4>";
    if ($isFormatV2) {
        $allCols = lab_format_v2_columns($formatDef);
        $resolvedRows = lab_format_v2_resolve_rows($allCols, lab_format_v2_rows($formatDef), is_array($resultados) ? $resultados : []);
        $cols = array_values(array_filter($allCols, static function ($col) {
            return lab_format_v2_col_visible($col, 'capture');
        }));
        $rowsV2 = $resolvedRows;

        echo "<table class='table table-bordered'>";
        echo "<thead><tr>";
        foreach ($cols as $col) {
            echo "<th>" . htmlspecialchars((string)($col['label'] ?? $col['id'] ?? '')) . "</th>";
        }
        echo "</tr></thead><tbody>";

        foreach ($rowsV2 as $rowV2) {
            if (!is_array($rowV2)) {
                continue;
            }
            $rowId = trim((string)($rowV2['id'] ?? ''));
            $rowType = strtolower(trim((string)($rowV2['type'] ?? 'data')));
            $rowCells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];

            if ($rowType === 'title' || $rowType === 'subtitle') {
                echo "<tr><td colspan='" . max(1, count($cols)) . "'><strong>" . htmlspecialchars((string)($rowV2['label'] ?? '')) . "</strong></td></tr>";
                continue;
            }

            if ($rowType === 'long_text') {
                $label = trim((string)($rowV2['label'] ?? ''));
                $text = (string)($rowV2['template_text'] ?? '');
                $align = strtolower(trim((string)($rowV2['template_align'] ?? 'left')));
                $textColor = trim((string)($rowV2['color_texto'] ?? ''));
                $bgColor = trim((string)($rowV2['color_fondo'] ?? ''));
                $isBold = !empty($rowV2['negrita']);
                $isItalic = !empty($rowV2['cursiva']);
                if (!in_array($align, ['left', 'center', 'right'], true)) {
                    $align = 'left';
                }
                if ($textColor === '') {
                    $textColor = '#1f2d5c';
                }
                if ($bgColor === '') {
                    $bgColor = '#fafcff';
                }
                $styleLabel = 'text-align:' . htmlspecialchars($align) . '; color:' . htmlspecialchars($textColor) . '; font-weight:' . ($isBold ? '700' : '400') . '; font-style:' . ($isItalic ? 'italic' : 'normal') . ';';
                $styleBody = 'text-align:' . htmlspecialchars($align) . '; color:' . htmlspecialchars($textColor) . '; font-weight:400; font-style:normal;';
                echo "<tr><td colspan='" . max(1, count($cols)) . "'>";
                if ($label !== '') {
                    echo "<div style='" . $styleLabel . "'>" . htmlspecialchars($label) . "</div>";
                }
                echo "<div style='white-space:pre-wrap; background:" . htmlspecialchars($bgColor) . "; " . $styleBody . "'>" . nl2br(htmlspecialchars($text)) . "</div>";
                echo "</td></tr>";
                continue;
            }

            echo "<tr>";
            foreach ($cols as $col) {
                $colId = trim((string)($col['id'] ?? ''));
                $val = $rowCells[$colId] ?? '';
                echo "<td>" . nl2br(htmlspecialchars((string)$val)) . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Parámetro</th><th>Valor</th><th>Unidad</th><th>Referencia</th></tr></thead><tbody>";
        foreach ($parametros as $param) {
            if ($param['tipo'] === 'Parámetro') {
                $nombre_raw = $param['nombre'] ?? '';
                $unidad = htmlspecialchars($param['unidad'] ?? '');
                $referencia = isset($param['referencias'][0]['valor']) ? htmlspecialchars($param['referencias'][0]['valor']) : '';
                $valor_raw = $getResultado($nombre_raw, $param, '-');
                $nombre = htmlspecialchars($nombre_raw);
                $valor = htmlspecialchars($valor_raw);
                echo "<tr><td>$nombre</td><td>$valor</td><td>$unidad</td><td>$referencia</td></tr>";
            }
        }
        echo "</tbody></table>";
    }
} else {
    echo '<div class="alert alert-warning">No se encontraron resultados para mostrar.</div>';
}
?>
