<?php
require_once __DIR__ . '/../conexion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idCliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCliente <= 0) {
    echo '<div class="alert alert-warning m-3">Paciente no válido.</div>';
    return;
}

$stmtCliente = $pdo->prepare("SELECT id, nombre, apellido, dni, sexo, edad FROM clientes WHERE id = ? LIMIT 1");
$stmtCliente->execute([$idCliente]);
$cliente = $stmtCliente->fetch(\PDO::FETCH_ASSOC);

if (!$cliente) {
    echo '<div class="alert alert-warning m-3">No se encontró el paciente solicitado.</div>';
    return;
}

$sexoPaciente = strtolower(trim((string)($cliente['sexo'] ?? '')));
$edadPaciente = null;
$edadRaw = (string)($cliente['edad'] ?? '');
if (preg_match('/-?\d+(?:[\.,]\d+)?/', $edadRaw, $mEdad)) {
    $edadPaciente = (float)str_replace(',', '.', $mEdad[0]);
}

$toNullableFloat = function ($value): ?float {
    if ($value === null) {
        return null;
    }
    $text = trim((string)$value);
    if ($text === '') {
        return null;
    }

    $text = str_replace(["\xc2\xa0", ' '], '', $text);
    $text = preg_replace('/[^0-9,\.\-]/', '', $text);
    if ($text === '' || $text === '-' || $text === '.' || $text === ',') {
        return null;
    }

    if (strpos($text, ',') !== false && strpos($text, '.') !== false) {
        if (strrpos($text, ',') > strrpos($text, '.')) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }
    } elseif (strpos($text, ',') !== false) {
        $text = str_replace(',', '.', $text);
    }

    return is_numeric($text) ? (float)$text : null;
};

$normKey = function ($value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = mb_strtolower($value, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii !== false && $ascii !== null) {
        $value = $ascii;
    }
    $value = preg_replace('/[^a-z0-9 ]/', '', $value);
    return trim($value);
};

$hasSnapshotCol = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(\PDO::FETCH_ASSOC);
    $hasSnapshotCol = !empty($col);
} catch (\Throwable $e) {
    $hasSnapshotCol = false;
}

$sqlResultados = $hasSnapshotCol
    ? "SELECT re.id, re.id_examen, re.id_cotizacion, re.resultados, re.fecha_ingreso, re.estado,
        COALESCE(re.adicional_snapshot, e.adicional) AS adicional,
        e.nombre AS nombre_examen,
        c.fecha AS fecha_cotizacion,
        c.estado_pago
    FROM resultados_examenes re
    JOIN examenes e ON e.id = re.id_examen
    LEFT JOIN cotizaciones c ON c.id = re.id_cotizacion
    WHERE re.id_cliente = ?
      AND re.estado = 'completado'
      AND (c.id IS NULL OR c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
    ORDER BY COALESCE(re.fecha_ingreso, c.fecha) ASC, re.id ASC"
    : "SELECT re.id, re.id_examen, re.id_cotizacion, re.resultados, re.fecha_ingreso, re.estado,
        e.adicional AS adicional,
        e.nombre AS nombre_examen,
        c.fecha AS fecha_cotizacion,
        c.estado_pago
    FROM resultados_examenes re
    JOIN examenes e ON e.id = re.id_examen
    LEFT JOIN cotizaciones c ON c.id = re.id_cotizacion
    WHERE re.id_cliente = ?
      AND re.estado = 'completado'
      AND (c.id IS NULL OR c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
    ORDER BY COALESCE(re.fecha_ingreso, c.fecha) ASC, re.id ASC";

$stmtResultados = $pdo->prepare($sqlResultados);
$stmtResultados->execute([$idCliente]);
$rows = $stmtResultados->fetchAll(\PDO::FETCH_ASSOC);

$alcance = strtolower(trim((string)($_GET['alcance'] ?? '90d')));
if (!in_array($alcance, ['30d', '90d', 'all'], true)) {
    $alcance = '90d';
}

if ($alcance !== 'all') {
    $dias = $alcance === '30d' ? 30 : 90;
    $cutoffTs = strtotime('-' . $dias . ' days');
    if ($cutoffTs !== false) {
        $rows = array_values(array_filter($rows, function ($row) use ($cutoffTs) {
            $fecha = trim((string)($row['fecha_ingreso'] ?? ''));
            if ($fecha === '') {
                $fecha = trim((string)($row['fecha_cotizacion'] ?? ''));
            }
            if ($fecha === '') {
                return false;
            }
            $ts = strtotime($fecha);
            return $ts !== false && $ts >= $cutoffTs;
        }));
    }
}

$seleccionarReferencia = function (array $referencias, string $sexo, ?float $edad, $toNullableFloat): ?array {
    if (empty($referencias)) {
        return null;
    }

    foreach ($referencias as $ref) {
        if (!is_array($ref)) {
            continue;
        }

        $refSexo = strtolower(trim((string)($ref['sexo'] ?? '')));
        $refEdadMin = $toNullableFloat($ref['edad_min'] ?? null);
        $refEdadMax = $toNullableFloat($ref['edad_max'] ?? null);

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

$parametrosDisponibles = [];
$seriesPorParametro = [];
$legacyToStableMap = [];

$buildStableItemKey = function (array $item, int $idExamen, string $nombreExamen) use ($normKey): string {
    $idParametro = trim((string)($item['id_parametro'] ?? ''));
    if ($idParametro !== '') {
        return 'exam_' . $idExamen . '|id_parametro_' . $idParametro;
    }

    $nombreParametro = trim((string)($item['nombre'] ?? ''));
    $nombreNorm = $normKey($nombreParametro);
    $examenNorm = $normKey($nombreExamen);
    return 'exam_' . $idExamen . '|param_' . $examenNorm . '_' . $nombreNorm;
};

foreach ($rows as $row) {
    $adicional = json_decode((string)($row['adicional'] ?? '[]'), true);
    $resultados = json_decode((string)($row['resultados'] ?? '{}'), true);

    if (!is_array($adicional) || !is_array($resultados)) {
        continue;
    }

    $resultadosNorm = [];
    foreach ($resultados as $k => $v) {
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

    $getResultado = function (string $nombre, array $item = []) use ($resultados, $resultadosNorm, $normKey, $buildStableKey) {
        $stableKey = $buildStableKey($item);
        if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
            return $resultados[$stableKey];
        }
        if (array_key_exists($nombre, $resultados)) {
            return $resultados[$nombre];
        }

        $upper = mb_strtoupper($nombre, 'UTF-8');
        if (array_key_exists($upper, $resultados)) {
            return $resultados[$upper];
        }

        $nk = $normKey($nombre);
        if ($nk !== '' && array_key_exists($nk, $resultadosNorm)) {
            return $resultadosNorm[$nk];
        }

        return '';
    };

    foreach ($adicional as $item) {
        if (($item['tipo'] ?? '') !== 'Parámetro') {
            continue;
        }

        $nombreParametro = trim((string)($item['nombre'] ?? ''));
        if ($nombreParametro === '') {
            continue;
        }

        $nombreExamen = trim((string)($row['nombre_examen'] ?? 'Examen'));
        $idExamen = (int)($row['id_examen'] ?? 0);
        $llave = $buildStableItemKey((array)$item, $idExamen, $nombreExamen);
        $legacyLlave = md5(mb_strtolower($nombreExamen . '|' . $nombreParametro, 'UTF-8'));

        if (!isset($legacyToStableMap[$legacyLlave])) {
            $legacyToStableMap[$legacyLlave] = $llave;
        }

        if (!isset($parametrosDisponibles[$llave])) {
            $parametrosDisponibles[$llave] = [
                'llave' => $llave,
                'llave_legacy' => $legacyLlave,
                'examen' => $nombreExamen,
                'parametro' => $nombreParametro,
            ];
        }

        $valorRaw = (string)$getResultado($nombreParametro, $item);
        $valorNum = $toNullableFloat($valorRaw);

        $referencia = $seleccionarReferencia((array)($item['referencias'] ?? []), $sexoPaciente, $edadPaciente, $toNullableFloat);
        $refTexto = trim((string)($referencia['valor'] ?? ''));
        $refMin = $toNullableFloat($referencia['valor_min'] ?? null);
        $refMax = $toNullableFloat($referencia['valor_max'] ?? null);

        $dentroRango = null;
        if ($valorNum !== null && ($refMin !== null || $refMax !== null)) {
            $dentroRango = true;
            if ($refMin !== null && $valorNum < $refMin) {
                $dentroRango = false;
            }
            if ($refMax !== null && $valorNum > $refMax) {
                $dentroRango = false;
            }
        }

        $fechaEvento = trim((string)($row['fecha_ingreso'] ?? ''));
        if ($fechaEvento === '') {
            $fechaEvento = trim((string)($row['fecha_cotizacion'] ?? ''));
        }

        $seriesPorParametro[$llave][] = [
            'fecha' => $fechaEvento,
            'fecha_label' => $fechaEvento !== '' ? date('d/m/Y H:i', strtotime($fechaEvento)) : '-',
            'examen' => $nombreExamen,
            'parametro' => $nombreParametro,
            'valor_raw' => $valorRaw,
            'valor_num' => $valorNum,
            'unidad' => trim((string)($item['unidad'] ?? '')),
            'metodologia' => trim((string)($item['metodologia'] ?? '')),
            'referencia' => $refTexto,
            'ref_min' => $refMin,
            'ref_max' => $refMax,
            'dentro_rango' => $dentroRango,
        ];
    }
}

usort($parametrosDisponibles, function ($a, $b) {
    $c1 = strcasecmp($a['examen'], $b['examen']);
    if ($c1 !== 0) {
        return $c1;
    }
    return strcasecmp($a['parametro'], $b['parametro']);
});

$obtenerLlaveMasReciente = function (array $seriesPorParametro): string {
    $bestKey = '';
    $bestTs = 0;
    foreach ($seriesPorParametro as $key => $serieTmp) {
        if (!is_array($serieTmp) || empty($serieTmp)) {
            continue;
        }
        foreach ($serieTmp as $rowTmp) {
            $fechaTmp = trim((string)($rowTmp['fecha'] ?? ''));
            if ($fechaTmp === '') {
                continue;
            }
            $ts = strtotime($fechaTmp);
            if ($ts !== false && $ts >= $bestTs) {
                $bestTs = (int)$ts;
                $bestKey = (string)$key;
            }
        }
    }
    return $bestKey;
};

$parametroSeleccionado = trim((string)($_GET['parametro'] ?? ''));
if ($parametroSeleccionado !== '' && isset($legacyToStableMap[$parametroSeleccionado])) {
    $parametroSeleccionado = $legacyToStableMap[$parametroSeleccionado];
}

if ($parametroSeleccionado === '' || !isset($seriesPorParametro[$parametroSeleccionado])) {
    $parametroMasReciente = $obtenerLlaveMasReciente($seriesPorParametro);
    if ($parametroMasReciente !== '') {
        $parametroSeleccionado = $parametroMasReciente;
    }
}

if ($parametroSeleccionado === '' && !empty($parametrosDisponibles[0]['llave'])) {
    $parametroSeleccionado = (string)$parametrosDisponibles[0]['llave'];
}

$serie = $seriesPorParametro[$parametroSeleccionado] ?? [];
usort($serie, function ($a, $b) {
    return strcmp($a['fecha'], $b['fecha']);
});

$ultimoNumerico = null;
$anteriorNumerico = null;
for ($i = count($serie) - 1; $i >= 0; $i--) {
    if ($serie[$i]['valor_num'] !== null) {
        if ($ultimoNumerico === null) {
            $ultimoNumerico = $serie[$i];
        } elseif ($anteriorNumerico === null) {
            $anteriorNumerico = $serie[$i];
            break;
        }
    }
}

$deltaAbs = null;
$deltaPct = null;
$tendencia = 'Sin datos';
if ($ultimoNumerico && $anteriorNumerico) {
    $deltaAbs = $ultimoNumerico['valor_num'] - $anteriorNumerico['valor_num'];
    if ((float)$anteriorNumerico['valor_num'] !== 0.0) {
        $deltaPct = ($deltaAbs / $anteriorNumerico['valor_num']) * 100;
    }
    if ($deltaAbs > 0) {
        $tendencia = 'Subiendo';
    } elseif ($deltaAbs < 0) {
        $tendencia = 'Bajando';
    } else {
        $tendencia = 'Estable';
    }
}

$unidades = [];
$metodologias = [];
foreach ($serie as $p) {
    if ($p['unidad'] !== '') {
        $unidades[$p['unidad']] = true;
    }
    if ($p['metodologia'] !== '') {
        $metodologias[$p['metodologia']] = true;
    }
}
$comparacionLimitada = count($unidades) > 1 || count($metodologias) > 1;

$labelsChart = [];
$valoresChart = [];
$minChart = [];
$maxChart = [];
foreach ($serie as $p) {
    if ($p['valor_num'] === null) {
        continue;
    }
    $labelsChart[] = !empty($p['fecha']) ? date('d/m/Y', strtotime($p['fecha'])) : '-';
    $valoresChart[] = (float)$p['valor_num'];
    $minChart[] = $p['ref_min'] !== null ? (float)$p['ref_min'] : null;
    $maxChart[] = $p['ref_max'] !== null ? (float)$p['ref_max'] : null;
}

$alertas = [];
if ($ultimoNumerico) {
    if ($ultimoNumerico['dentro_rango'] === false) {
        $alertas[] = 'El último resultado está fuera de rango de referencia.';
    }
    if ($anteriorNumerico && $anteriorNumerico['dentro_rango'] === true && $ultimoNumerico['dentro_rango'] === false) {
        $alertas[] = 'Hubo cruce de rango: pasó de dentro de rango a fuera de rango.';
    }
    if ($deltaPct !== null && abs($deltaPct) > 20) {
        $alertas[] = 'Se detecta cambio brusco mayor al 20% respecto al control anterior.';
    }
}

$export = strtolower(trim((string)($_GET['export'] ?? '')));
if (in_array($export, ['excel', 'pdf'], true)) {
    if (headers_sent()) {
        echo '<div class="alert alert-warning m-3">No se puede exportar desde esta ruta de vista porque ya se enviaron cabeceras. Usa la ruta de acción de exportación.</div>';
        return;
    }

    $selectedMeta = null;
    foreach ($parametrosDisponibles as $opt) {
        if (($opt['llave'] ?? '') === $parametroSeleccionado) {
            $selectedMeta = $opt;
            break;
        }
    }

    $nombrePacientePlano = trim((string)(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? '')));
    if ($nombrePacientePlano === '') {
        $nombrePacientePlano = 'paciente_' . (int)$idCliente;
    }
    $slugPaciente = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombrePacientePlano)));
    $slugPaciente = trim($slugPaciente, '_');
    if ($slugPaciente === '') {
        $slugPaciente = 'paciente_' . (int)$idCliente;
    }

    $tituloParametro = $selectedMeta ? (($selectedMeta['examen'] ?? 'Examen') . ' · ' . ($selectedMeta['parametro'] ?? 'Parámetro')) : 'Parámetro';

    $htmlExport = '';
    $htmlExport .= '<h2>Comparación de Resultados</h2>';
    $htmlExport .= '<p><strong>Paciente:</strong> ' . htmlspecialchars($nombrePacientePlano) . ' | <strong>DNI:</strong> ' . htmlspecialchars((string)($cliente['dni'] ?? '')) . '</p>';
    $htmlExport .= '<p><strong>Parámetro:</strong> ' . htmlspecialchars($tituloParametro) . '</p>';
    $htmlExport .= '<p><strong>Tendencia:</strong> ' . htmlspecialchars($tendencia) . '</p>';
    $htmlExport .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse; margin-bottom:12px;">';
    $htmlExport .= '<tr><th align="left">Último valor</th><th align="left">Anterior</th><th align="left">Diferencia</th><th align="left">Variación (%)</th></tr>';
    $htmlExport .= '<tr>';
    $htmlExport .= '<td>' . htmlspecialchars($ultimoNumerico ? ((string)$ultimoNumerico['valor_raw'] . ($ultimoNumerico['unidad'] !== '' ? ' ' . $ultimoNumerico['unidad'] : '')) : 'Sin dato') . '</td>';
    $htmlExport .= '<td>' . htmlspecialchars($anteriorNumerico ? ((string)$anteriorNumerico['valor_raw'] . ($anteriorNumerico['unidad'] !== '' ? ' ' . $anteriorNumerico['unidad'] : '')) : 'Sin dato') . '</td>';
    $htmlExport .= '<td>' . htmlspecialchars($deltaAbs !== null ? number_format($deltaAbs, 2) : 'Sin dato') . '</td>';
    $htmlExport .= '<td>' . htmlspecialchars($deltaPct !== null ? number_format($deltaPct, 2) . '%' : 'Sin dato') . '</td>';
    $htmlExport .= '</tr>';
    $htmlExport .= '</table>';

    if (!empty($alertas)) {
        $htmlExport .= '<p><strong>Alertas:</strong></p><ul>';
        foreach ($alertas as $a) {
            $htmlExport .= '<li>' . htmlspecialchars($a) . '</li>';
        }
        $htmlExport .= '</ul>';
    }

    $htmlExport .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;">';
    $htmlExport .= '<thead><tr><th>Fecha</th><th>Examen</th><th>Parámetro</th><th>Resultado</th><th>Unidad</th><th>Referencia</th><th>Metodología</th><th>Variación vs anterior</th><th>Estado</th></tr></thead><tbody>';
    if (empty($serie)) {
        $htmlExport .= '<tr><td colspan="9" align="center">No hay datos para el parámetro seleccionado.</td></tr>';
    } else {
        $anteriorNumTablaExport = null;
        foreach ($serie as $r) {
            $varTxt = 'Sin dato';
            if ($r['valor_num'] !== null && $anteriorNumTablaExport !== null) {
                $deltaTabla = $r['valor_num'] - $anteriorNumTablaExport;
                $varTxt = number_format($deltaTabla, 2);
            }
            if ($r['valor_num'] !== null) {
                $anteriorNumTablaExport = $r['valor_num'];
            }

            $estadoTxt = 'Sin evaluar';
            if ($r['dentro_rango'] === true) {
                $estadoTxt = 'Dentro de rango';
            } elseif ($r['dentro_rango'] === false) {
                $estadoTxt = 'Fuera de rango';
            }

            $htmlExport .= '<tr>';
            $htmlExport .= '<td>' . htmlspecialchars((string)$r['fecha_label']) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)$r['examen']) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)$r['parametro']) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)($r['valor_raw'] !== '' ? $r['valor_raw'] : '-')) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)($r['unidad'] !== '' ? $r['unidad'] : '-')) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)($r['referencia'] !== '' ? $r['referencia'] : '-')) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars((string)($r['metodologia'] !== '' ? $r['metodologia'] : '-')) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars($varTxt) . '</td>';
            $htmlExport .= '<td>' . htmlspecialchars($estadoTxt) . '</td>';
            $htmlExport .= '</tr>';
        }
    }
    $htmlExport .= '</tbody></table>';

    if ($export === 'excel') {
        $filename = 'comparacion_' . $slugPaciente . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo $htmlExport;
        exit;
    }

    if ($export === 'pdf') {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 12,
                'margin_bottom' => 12,
            ]);
            $mpdf->WriteHTML($htmlExport);
            $filename = 'comparacion_' . $slugPaciente . '_' . date('Ymd_His') . '.pdf';
            $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
            exit;
        } catch (\Throwable $e) {
            echo '<div style="padding:16px;font-family:Arial,sans-serif;">';
            echo '<h4>No se pudo generar el PDF</h4>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="dashboard.php?vista=comparar_resultados_cliente&id=' . (int)$idCliente . '&parametro=' . urlencode($parametroSeleccionado) . '">Volver</a></p>';
            echo '</div>';
            exit;
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Comparación de resultados</h3>
            <div class="text-muted">
                Paciente: <strong><?= htmlspecialchars(trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''))) ?></strong>
                | DNI: <strong><?= htmlspecialchars((string)($cliente['dni'] ?? '')) ?></strong>
            </div>
        </div>
        <a href="dashboard.php?vista=clientes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Pacientes
        </a>
    </div>

    <?php if (!empty($parametrosDisponibles)): ?>
        <div class="mb-3 d-flex flex-column flex-md-row gap-2">
            <a href="dashboard.php?action=comparar_resultados_export&id=<?= (int)$idCliente ?>&parametro=<?= urlencode($parametroSeleccionado) ?>&alcance=<?= urlencode($alcance) ?>&export=excel" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </a>
            <a href="dashboard.php?action=comparar_resultados_export&id=<?= (int)$idCliente ?>&parametro=<?= urlencode($parametroSeleccionado) ?>&alcance=<?= urlencode($alcance) ?>&export=pdf" class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </a>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="vista" value="comparar_resultados_cliente">
                <input type="hidden" name="id" value="<?= (int)$idCliente ?>">
                <div class="col-12 col-md-5">
                    <label class="form-label">Parámetro a comparar</label>
                    <select name="parametro" class="form-select" required>
                        <?php foreach ($parametrosDisponibles as $opt): ?>
                            <option value="<?= htmlspecialchars($opt['llave']) ?>" <?= $parametroSeleccionado === $opt['llave'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt['examen'] . ' · ' . $opt['parametro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Alcance</label>
                    <select name="alcance" class="form-select">
                        <option value="30d" <?= $alcance === '30d' ? 'selected' : '' ?>>Últimos 30 días</option>
                        <option value="90d" <?= $alcance === '90d' ? 'selected' : '' ?>>Últimos 90 días</option>
                        <option value="all" <?= $alcance === 'all' ? 'selected' : '' ?>>Todo histórico</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Comparar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($parametrosDisponibles)): ?>
        <div class="alert alert-info">No hay resultados completados para este paciente.</div>
    <?php else: ?>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-primary">
                    <div class="card-body py-2">
                        <small class="text-muted d-block">Último valor</small>
                        <strong>
                            <?= $ultimoNumerico ? htmlspecialchars((string)$ultimoNumerico['valor_raw']) : 'Sin dato' ?>
                            <?= $ultimoNumerico && $ultimoNumerico['unidad'] !== '' ? htmlspecialchars(' ' . $ultimoNumerico['unidad']) : '' ?>
                        </strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-secondary">
                    <div class="card-body py-2">
                        <small class="text-muted d-block">Valor anterior</small>
                        <strong>
                            <?= $anteriorNumerico ? htmlspecialchars((string)$anteriorNumerico['valor_raw']) : 'Sin dato' ?>
                            <?= $anteriorNumerico && $anteriorNumerico['unidad'] !== '' ? htmlspecialchars(' ' . $anteriorNumerico['unidad']) : '' ?>
                        </strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-warning">
                    <div class="card-body py-2">
                        <small class="text-muted d-block">Diferencia</small>
                        <strong><?= $deltaAbs !== null ? number_format($deltaAbs, 2) : 'Sin dato' ?></strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-success">
                    <div class="card-body py-2">
                        <small class="text-muted d-block">Variación (%)</small>
                        <strong><?= $deltaPct !== null ? number_format($deltaPct, 2) . '%' : 'Sin dato' ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge bg-dark">Tendencia: <?= htmlspecialchars($tendencia) ?></span>
                    <?php if ($ultimoNumerico && $ultimoNumerico['dentro_rango'] !== null): ?>
                        <span class="badge <?= $ultimoNumerico['dentro_rango'] ? 'bg-success' : 'bg-danger' ?>">
                            Último estado: <?= $ultimoNumerico['dentro_rango'] ? 'Dentro de rango' : 'Fuera de rango' ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($comparacionLimitada): ?>
                        <span class="badge bg-warning text-dark">Comparación limitada (cambios en unidad o metodología)</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($alertas)): ?>
                    <div class="alert alert-warning mb-0">
                        <strong>Alertas:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($alertas as $a): ?>
                                <li><?= htmlspecialchars($a) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><strong>Tendencia del parámetro</strong></div>
            <div class="card-body">
                <?php if (count($valoresChart) >= 1): ?>
                    <canvas id="graficoTendencia" height="110"></canvas>
                <?php else: ?>
                    <div class="text-muted">No hay suficientes datos numéricos para graficar.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <strong>Histórico comparativo</strong>
                <span class="badge bg-secondary"><?= count($serie) ?> registro(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Examen</th>
                                <th>Parámetro</th>
                                <th>Resultado</th>
                                <th>Unidad</th>
                                <th>Referencia</th>
                                <th>Metodología</th>
                                <th>Variación vs anterior</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($serie)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No hay datos para el parámetro seleccionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $anteriorNumTabla = null;
                                foreach ($serie as $r):
                                    $varTxt = 'Sin dato';
                                    if ($r['valor_num'] !== null && $anteriorNumTabla !== null) {
                                        $deltaTabla = $r['valor_num'] - $anteriorNumTabla;
                                        $varTxt = number_format($deltaTabla, 2);
                                    }
                                    if ($r['valor_num'] !== null) {
                                        $anteriorNumTabla = $r['valor_num'];
                                    }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['fecha_label']) ?></td>
                                        <td><?= htmlspecialchars($r['examen']) ?></td>
                                        <td><?= htmlspecialchars($r['parametro']) ?></td>
                                        <td><?= htmlspecialchars((string)($r['valor_raw'] !== '' ? $r['valor_raw'] : '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['unidad'] !== '' ? $r['unidad'] : '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['referencia'] !== '' ? $r['referencia'] : '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['metodologia'] !== '' ? $r['metodologia'] : '-')) ?></td>
                                        <td><?= htmlspecialchars($varTxt) ?></td>
                                        <td>
                                            <?php if ($r['dentro_rango'] === true): ?>
                                                <span class="badge bg-success">Dentro de rango</span>
                                            <?php elseif ($r['dentro_rango'] === false): ?>
                                                <span class="badge bg-danger">Fuera de rango</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin evaluar</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($valoresChart)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const ctx = document.getElementById('graficoTendencia');
    if (!ctx) return;

    const labels = <?= json_encode($labelsChart, JSON_UNESCAPED_UNICODE) ?>;
    const valores = <?= json_encode($valoresChart, JSON_UNESCAPED_UNICODE) ?>;
    const minimos = <?= json_encode($minChart, JSON_UNESCAPED_UNICODE) ?>;
    const maximos = <?= json_encode($maxChart, JSON_UNESCAPED_UNICODE) ?>;

    new window.Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Resultado',
                    data: valores,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.15)',
                    fill: false,
                    tension: 0.2,
                    pointRadius: 4
                },
                {
                    label: 'Ref. mínima',
                    data: minimos,
                    borderColor: '#16a34a',
                    borderDash: [6, 4],
                    fill: false,
                    tension: 0,
                    pointRadius: 0
                },
                {
                    label: 'Ref. máxima',
                    data: maximos,
                    borderColor: '#dc2626',
                    borderDash: [6, 4],
                    fill: false,
                    tension: 0,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
