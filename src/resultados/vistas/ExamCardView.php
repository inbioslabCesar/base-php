
<?php
require_once __DIR__ . '/../../examenes/formato_dinamico_helper.php';

class ExamCardView {
    public static function render($examen, $index, $datos_paciente = [], $areas_disponibles = []) {
        $toNullableFloat = function ($value) {
            if ($value === null) {
                return null;
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    return null;
                }
                $normalized = preg_replace('/\s+/', '', $trimmed);
                if ($normalized === null) {
                    $normalized = $trimmed;
                }

                // Regla unificada del sistema: coma solo miles, punto solo decimales.
                $normalized = str_replace(',', '', $normalized);
                if (!preg_match('/^[-+]?\d+(?:\.\d+)?$/', $normalized)) {
                    return null;
                }
                return is_numeric($normalized) ? floatval($normalized) : null;
            }
            return is_numeric($value) ? floatval($value) : null;
        };

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

        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];

        $parseDiasDesdeTexto = function ($texto) {
            $texto = strtolower(trim((string)$texto));
            if ($texto === '') {
                return null;
            }

            if (!preg_match('/(\d+(?:[\.,]\d+)?)/', $texto, $m)) {
                return null;
            }

            $valor = (float)str_replace(',', '.', $m[1]);
            if ($valor <= 0) {
                return null;
            }

            if (strpos($texto, 'hora') !== false || preg_match('/\bhr?s?\b|\bh\b/', $texto)) {
                return max(1, (int)ceil($valor / 24));
            }

            if (strpos($texto, 'dia') !== false || strpos($texto, 'días') !== false || strpos($texto, 'dias') !== false || preg_match('/\bd\b/', $texto)) {
                return max(1, (int)ceil($valor));
            }

            return null;
        };

        // Índice normalizado para compatibilidad cuando cambian mayúsculas/minúsculas o signos
        // (ej. "PROLACTINA" vs "Prolactina").
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
            $idParametro = (string)($item['id_parametro'] ?? '');
            $idParametro = trim($idParametro);
            if ($idParametro === '') {
                return '';
            }
            return 'id_parametro_' . $idParametro;
        };

        $getResultado = function ($nombre, $default = '', $item = null) use ($resultados, $resultadosNorm, $normKey, $buildStableKey) {
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

            $keysNoPrint = [];
            foreach ($resultados as $k => $v) {
                if ($k === 'imprimir_examen') {
                    continue;
                }
                $keysNoPrint[] = $k;
            }
            if (count($keysNoPrint) === 1) {
                return $resultados[$keysNoPrint[0]];
            }
            return $default;
        };

        $formatDef = lab_format_decode_definition($examen['adicional'] ?? []);
        $isFormatV2 = lab_format_v2_enabled() && !empty($formatDef['is_v2']);
        $formatColumns = $isFormatV2 ? lab_format_v2_columns($formatDef) : [];
        $formatRows = $isFormatV2 ? lab_format_v2_rows($formatDef) : [];
        $formatRowsResolved = $isFormatV2 ? lab_format_v2_resolve_rows($formatColumns, $formatRows, is_array($resultados) ? $resultados : []) : [];

        $v2RowAnchor = function ($row) use ($formatColumns) {
            if (!is_array($row)) {
                return '';
            }
            $rowType = strtolower(trim((string)($row['type'] ?? 'data')));
            $label = trim((string)($row['label'] ?? ''));
            if ($rowType === 'title' || $rowType === 'subtitle') {
                return $label;
            }

            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            foreach ($formatColumns as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $colId = trim((string)($col['id'] ?? ''));
                if ($colId === '') {
                    continue;
                }
                $kind = strtolower(trim((string)($col['kind'] ?? 'text')));
                if (!in_array($kind, ['text', 'reference'], true)) {
                    continue;
                }
                $cellValue = trim((string)($cells[$colId] ?? ''));
                if ($cellValue !== '') {
                    return $cellValue;
                }
            }

            foreach ($cells as $cellValue) {
                $cellText = trim((string)$cellValue);
                if ($cellText !== '') {
                    return $cellText;
                }
            }

            return $label;
        };

        $adicional = $formatDef['legacy_items'];
        if (!is_array($adicional)) {
            $adicional = [];
        }

        $hasReceta = ((int)($examen['has_receta'] ?? 0) === 1);
        $alarmaActiva = ((int)($examen['alarma_activa'] ?? 0) === 1);
        $alarmaDiasGuardados = isset($examen['alarma_dias']) && $examen['alarma_dias'] !== null ? (int)$examen['alarma_dias'] : null;
        $alarmaDiasSugeridos = $parseDiasDesdeTexto($examen['tiempo_respuesta'] ?? '');
        $alarmaDiasValor = $alarmaDiasGuardados !== null && $alarmaDiasGuardados > 0
            ? $alarmaDiasGuardados
            : ($alarmaDiasSugeridos ?? '');
        $teniaResultadoPrevio = false;
        if (is_array($resultados)) {
            foreach ($resultados as $k => $v) {
                if ($k === 'imprimir_examen') {
                    continue;
                }
                if ($v === 0 || $v === '0') {
                    $teniaResultadoPrevio = true;
                    break;
                }
                if (is_string($v) && trim($v) !== '') {
                    $teniaResultadoPrevio = true;
                    break;
                }
                if (!is_string($v) && $v !== null && $v !== '') {
                    $teniaResultadoPrevio = true;
                    break;
                }
            }
        }

        $cabecerasExistentes = [];
        $posiciones = [];
        if ($isFormatV2) {
            foreach ($formatRows as $idx => $it) {
                if (!is_array($it)) {
                    continue;
                }
                $tipo = strtolower(trim((string)($it['type'] ?? 'data')));
                $nombre = trim((string)($it['label'] ?? ''));
                if (($tipo === 'title' || $tipo === 'subtitle') && $nombre !== '') {
                    $before = '__END__';
                    if ($idx === 0) {
                        $before = '__FIRST__';
                    }
                    for ($j = $idx + 1; $j < count($formatRows); $j++) {
                        $t2 = strtolower(trim((string)($formatRows[$j]['type'] ?? 'data')));
                        if (in_array($t2, ['data', 'long_text'], true)) {
                            $n2 = trim((string)$v2RowAnchor($formatRows[$j]));
                            if ($n2 !== '') {
                                $before = $n2;
                            }
                            break;
                        }
                    }
                    $cabecerasExistentes[] = [
                        'idx' => $idx,
                        'tipo' => $tipo === 'subtitle' ? 'Subtítulo' : 'Título',
                        'nombre' => $nombre,
                        'color_texto' => $it['color_texto'] ?? '#dc2626',
                        'before' => $before,
                    ];
                }
                if (in_array($tipo, ['data', 'long_text'], true)) {
                    $anchor = trim((string)$v2RowAnchor($it));
                    if ($anchor !== '') {
                        $posiciones[] = $anchor;
                    }
                }
            }
            if (!empty($posiciones)) {
                $posiciones = array_values(array_unique($posiciones));
            }
        } else {
            foreach ($adicional as $idx => $it) {
                if (!is_array($it)) {
                    continue;
                }
                $tipo = $it['tipo'] ?? '';
                $nombre = $it['nombre'] ?? '';
                if (($tipo === 'Título' || $tipo === 'Subtítulo') && $nombre !== '') {
                    $before = '__END__';
                    if ($idx === 0) {
                        $before = '__FIRST__';
                    }
                    // Detectar posición actual: "antes de" el siguiente parámetro/campo/texto
                    for ($j = $idx + 1; $j < count($adicional); $j++) {
                        if (!is_array($adicional[$j] ?? null)) {
                            continue;
                        }
                        $t2 = $adicional[$j]['tipo'] ?? '';
                        if (in_array($t2, ['Parámetro', 'Campo', 'Texto Largo'], true)) {
                            $n2 = $adicional[$j]['nombre'] ?? '';
                            if ($n2 !== '') {
                                $before = $n2;
                            }
                            break;
                        }
                    }
                    $cabecerasExistentes[] = [
                        'idx' => $idx,
                        'tipo' => $tipo,
                        'nombre' => $nombre,
                        'color_texto' => $it['color_texto'] ?? '#dc2626',
                        'before' => $before,
                    ];
                }
                if (in_array($tipo, ['Parámetro', 'Campo', 'Texto Largo'], true) && $nombre !== '') {
                    $posiciones[] = $nombre;
                }
            }
        }
        // Si el examen tiene datos de paciente propios, usarlos; si no, usar los globales
        $edad_paciente = null;
        $sexo_paciente = '';
        if (isset($examen['edad_paciente']) && $examen['edad_paciente'] !== '') {
            $edad_paciente = $toNullableFloat($examen['edad_paciente']);
        } elseif (isset($datos_paciente['edad']) && $datos_paciente['edad'] !== '') {
            $edad_paciente = $toNullableFloat($datos_paciente['edad']);
        }
        if (isset($examen['sexo_paciente']) && $examen['sexo_paciente'] !== '') {
            $sexo_paciente = strtolower(trim($examen['sexo_paciente']));
        } elseif (isset($datos_paciente['sexo']) && $datos_paciente['sexo'] !== '') {
            $sexo_paciente = strtolower(trim($datos_paciente['sexo']));
        }
        ob_start();
        ?>
           <div class="exam-card"
               data-id-resultado="<?= htmlspecialchars((string)$examen['id_resultado']) ?>"
               data-has-receta="<?= $hasReceta ? '1' : '0' ?>"
               data-tenia-previo="<?= $teniaResultadoPrevio ? '1' : '0' ?>"
               data-examen-nombre="<?= htmlspecialchars((string)$examen['nombre_examen']) ?>"
               style="animation-delay: <?= $index * 0.1 ?>s;">
            <div class="exam-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard-pulse me-2"></i>
                    <span><?= htmlspecialchars($examen['nombre_examen']) ?></span>
                    <span class="badge bg-danger ms-2 js-exam-progress-badge">0%</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-light js-update-snapshot-exam"
                        data-cotizacion-id="<?= htmlspecialchars((string)($examen['id_cotizacion'] ?? $_GET['cotizacion_id'] ?? '')) ?>"
                        data-id-resultado="<?= htmlspecialchars((string)$examen['id_resultado']) ?>"
                        title="Actualizar solo este examen al formato actual (conservar cabeceras personalizadas)">
                        <i class="bi bi-arrow-repeat me-1"></i>Actualizar formato
                    </button>
                    <div class="exam-order-controls d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-light js-exam-drag-handle" title="Arrastrar para reordenar">
                            <i class="bi bi-grip-vertical"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light js-exam-move-up" title="Subir">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light js-exam-move-down" title="Bajar">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" 
                               name="examenes[<?= $examen['id_resultado'] ?>][imprimir_examen]" 
                               id="imprimir_examen_<?= $examen['id_resultado'] ?>" 
                               value="1"
                               <?= (!isset($resultados['imprimir_examen']) || $resultados['imprimir_examen']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="imprimir_examen_<?= $examen['id_resultado'] ?>">
                            <i class="bi bi-printer me-1"></i>
                            Imprimir
                        </label>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input js-alarma-switch" type="checkbox"
                               name="examenes[<?= $examen['id_resultado'] ?>][alarma_activa]"
                               id="alarma_activa_<?= $examen['id_resultado'] ?>"
                               value="1"
                               <?= $alarmaActiva ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="alarma_activa_<?= $examen['id_resultado'] ?>">
                            <i class="bi bi-bell me-1"></i>
                            Alarma
                        </label>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <label class="text-white-50 small mb-0" for="alarma_dias_<?= $examen['id_resultado'] ?>">días</label>
                        <input type="number"
                               min="1"
                               step="1"
                               class="form-control form-control-sm js-alarma-dias"
                               style="width: 78px;"
                               name="examenes[<?= $examen['id_resultado'] ?>][alarma_dias]"
                               id="alarma_dias_<?= $examen['id_resultado'] ?>"
                               value="<?= htmlspecialchars((string)$alarmaDiasValor) ?>"
                               <?= $alarmaActiva ? '' : 'disabled' ?>>
                    </div>
                </div>
            </div>
            <div class="exam-card-body">
                <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][id_resultado]" 
                       value="<?= htmlspecialchars($examen['id_resultado']) ?>">
                  <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][repeticion_confirmada]" class="js-repeticion-confirmada" value="0">
                  <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][motivo_repeticion]" class="js-repeticion-motivo" value="">

                <div class="header-builder" data-exam-id="<?= htmlspecialchars($examen['id_resultado']) ?>">
                    <div class="header-builder-title">
                        <i class="bi bi-layout-text-window-reverse me-2"></i>
                        Cabeceras del reporte (solo para este paciente)
                    </div>

                    <?php if (!empty($cabecerasExistentes)): ?>
                        <div class="header-existing">
                            <?php foreach ($cabecerasExistentes as $h): ?>
                                <div class="header-existing-row">
                                     <input type="text" class="form-control form-control-sm" 
                                         style="color: <?= htmlspecialchars($h['color_texto'] ?: '#0923E1') ?>; font-weight: 600;"
                                           name="examenes[<?= $examen['id_resultado'] ?>][cabeceras_editar][<?= $h['idx'] ?>][nombre]"
                                           value="<?= htmlspecialchars($h['nombre']) ?>"
                                           placeholder="Título">
                                    <input type="color" class="form-control form-control-sm header-color" 
                                           name="examenes[<?= $examen['id_resultado'] ?>][cabeceras_editar][<?= $h['idx'] ?>][color]"
                                         value="<?= htmlspecialchars($h['color_texto'] ?: '#0923E1') ?>">
                                    <select class="form-select form-select-sm" name="examenes[<?= $examen['id_resultado'] ?>][cabeceras_editar][<?= $h['idx'] ?>][before]">
                                        <option value="__FIRST__" <?= ($h['before'] === '__FIRST__') ? 'selected' : '' ?>>Al inicio</option>
                                        <?php foreach ($posiciones as $p): ?>
                                            <option value="<?= htmlspecialchars($p) ?>" <?= ($h['before'] === $p) ? 'selected' : '' ?>>Antes de: <?= htmlspecialchars($p) ?></option>
                                        <?php endforeach; ?>
                                        <option value="__END__" <?= ($h['before'] === '__END__') ? 'selected' : '' ?>>Al final</option>
                                    </select>
                                    <label class="header-remove">
                                        <input type="checkbox" value="1" 
                                               name="examenes[<?= $examen['id_resultado'] ?>][cabeceras_editar][<?= $h['idx'] ?>][eliminar]">
                                        Quitar
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="header-add">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Nombre</label>
                                <select class="form-select form-select-sm header-title-select">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach (($areas_disponibles ?? []) as $a): ?>
                                        <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__">Personalizado...</option>
                                </select>
                                <input type="text" class="form-control form-control-sm header-title-custom d-none" placeholder="Escribe la cabecera">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">Color</label>
                                <input type="color" class="form-control form-control-sm header-color header-color-new" value="#0923E1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Ubicación</label>
                                <select class="form-select form-select-sm header-insert-before">
                                    <option value="__FIRST__">Al inicio del examen</option>
                                    <?php foreach ($posiciones as $p): ?>
                                        <option value="<?= htmlspecialchars($p) ?>">Antes de: <?= htmlspecialchars($p) ?></option>
                                    <?php endforeach; ?>
                                    <option value="__END__">Al final</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-primary add-header-btn">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <div class="header-preview-list mt-2"></div>
                        <div class="headers-hidden" data-next-index="0"></div>
                    </div>
                </div>

                <?php if ($isFormatV2): ?>
                    <div class="alert alert-primary py-2 mb-3">
                        Formato dinamico activo para este examen.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr>
                                    <?php foreach ($formatColumns as $col): ?>
                                        <?php if (!lab_format_v2_col_visible($col, 'capture')) continue; ?>
                                        <th style="<?= !empty($col['width']) ? 'width:' . htmlspecialchars((string)$col['width']) . ';' : '' ?>">
                                            <?= htmlspecialchars((string)($col['label'] ?? $col['id'])) ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formatRowsResolved as $rowV2): ?>
                                    <?php
                                        if (!is_array($rowV2)) {
                                            continue;
                                        }
                                        $rowType = strtolower(trim((string)($rowV2['type'] ?? 'data')));
                                        $rowId = trim((string)($rowV2['id'] ?? ''));
                                        $rowCells = is_array($rowV2['cells'] ?? null) ? $rowV2['cells'] : [];
                                        $rowFormulas = is_array($rowV2['formulas'] ?? null) ? $rowV2['formulas'] : [];
                                        $visibleCount = 0;
                                        foreach ($formatColumns as $tmpCol) {
                                            if (lab_format_v2_col_visible($tmpCol, 'capture')) {
                                                $visibleCount++;
                                            }
                                        }
                                        if ($visibleCount <= 0) {
                                            $visibleCount = 1;
                                        }
                                    ?>
                                    <?php if ($rowType === 'title' || $rowType === 'subtitle'): ?>
                                        <?php
                                            $rowBg = trim((string)($rowV2['color_fondo'] ?? ''));
                                            $rowText = trim((string)($rowV2['color_texto'] ?? ''));
                                            $rowBold = !array_key_exists('negrita', $rowV2) || !empty($rowV2['negrita']);
                                            $rowItalic = !empty($rowV2['cursiva']);
                                            $rowAlign = strtolower(trim((string)($rowV2['alineacion'] ?? '')));
                                            if (!in_array($rowAlign, ['left', 'center', 'right'], true)) {
                                                $rowAlign = $rowType === 'title' ? 'center' : 'left';
                                            }
                                            if ($rowBg === '') {
                                                $rowBg = $rowType === 'title' ? '#eef4ff' : '#f4f7fb';
                                            }
                                            if ($rowText === '') {
                                                $rowText = '#1f2d5c';
                                            }
                                        ?>
                                        <tr>
                                            <td colspan="<?= $visibleCount ?>" style="background: <?= htmlspecialchars($rowBg) ?>; color: <?= htmlspecialchars($rowText) ?>; border-radius: 6px; padding: 6px 8px; font-weight: <?= $rowBold ? '700' : '400' ?>; font-style: <?= $rowItalic ? 'italic' : 'normal' ?>; text-align: <?= htmlspecialchars($rowAlign) ?>;">
                                                <?= htmlspecialchars((string)($rowV2['label'] ?? '')) ?>
                                            </td>
                                        </tr>
                                    <?php elseif ($rowType === 'long_text'): ?>
                                        <?php
                                            $templateEditable = !array_key_exists('template_editable', $rowV2) || (bool)$rowV2['template_editable'];
                                            $templateText = (string)($rowV2['template_text'] ?? '');
                                            $templateLabel = trim((string)($rowV2['label'] ?? ''));
                                            $templateKey = ($rowId !== '') ? lab_format_v2_cell_key($rowId, lab_format_v2_long_text_col_id()) : '';
                                            $templateAlign = strtolower(trim((string)($rowV2['template_align'] ?? 'left')));
                                            $templateTextColor = trim((string)($rowV2['color_texto'] ?? ''));
                                            $templateBgColor = trim((string)($rowV2['color_fondo'] ?? ''));
                                            $templateBold = !empty($rowV2['negrita']);
                                            $templateItalic = !empty($rowV2['cursiva']);
                                            if (!in_array($templateAlign, ['left', 'center', 'right'], true)) {
                                                $templateAlign = 'left';
                                            }
                                            if ($templateTextColor === '') {
                                                $templateTextColor = '#1f2d5c';
                                            }
                                            if ($templateBgColor === '') {
                                                $templateBgColor = '#fafcff';
                                            }
                                        ?>
                                        <tr>
                                            <td colspan="<?= $visibleCount ?>" style="padding: 8px; background: <?= htmlspecialchars($templateBgColor) ?>; color: <?= htmlspecialchars($templateTextColor) ?>;">
                                                <?php if ($templateLabel !== ''): ?>
                                                    <div class="mb-1" style="text-align: <?= htmlspecialchars($templateAlign) ?>; font-weight: <?= $templateBold ? '700' : '400' ?>; font-style: <?= $templateItalic ? 'italic' : 'normal' ?>;"><?= htmlspecialchars($templateLabel) ?></div>
                                                <?php endif; ?>
                                                <?php if ($templateEditable && $templateKey !== ''): ?>
                                                    <textarea class="form-control form-control-sm"
                                                        rows="5"
                                                        name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($templateKey) ?>]"
                                                        data-progress-track="1"
                                                        data-v2-row-id="<?= htmlspecialchars($rowId) ?>"
                                                        data-v2-col-id="<?= htmlspecialchars(lab_format_v2_long_text_col_id()) ?>"
                                                        data-initial-value="<?= htmlspecialchars($templateText) ?>"
                                                        style="text-align: <?= htmlspecialchars($templateAlign) ?>; color: <?= htmlspecialchars($templateTextColor) ?>; background: <?= htmlspecialchars($templateBgColor) ?>; font-weight: 400; font-style: normal;"><?= htmlspecialchars($templateText) ?></textarea>
                                                <?php else: ?>
                                                    <div style="white-space: pre-wrap; text-align: <?= htmlspecialchars($templateAlign) ?>; color: <?= htmlspecialchars($templateTextColor) ?>; font-weight: 400; font-style: normal;"><?= nl2br(htmlspecialchars($templateText)) ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <?php $rowSelectOptions = (isset($rowV2['select_options']) && is_array($rowV2['select_options'])) ? $rowV2['select_options'] : []; ?>
                                            <?php $rowReferenceRanges = (isset($rowV2['reference_ranges']) && is_array($rowV2['reference_ranges'])) ? $rowV2['reference_ranges'] : []; ?>
                                            <?php $rowDecimals = (isset($rowV2['decimales']) && is_array($rowV2['decimales'])) ? $rowV2['decimales'] : []; ?>
                                            <?php foreach ($formatColumns as $col): ?>
                                                <?php if (!lab_format_v2_col_visible($col, 'capture')) continue; ?>
                                                <?php
                                                    $colId = trim((string)($col['id'] ?? ''));
                                                    $defaultCell = $rowCells[$colId] ?? '';
                                                    $formulaExpr = isset($rowFormulas[$colId]) ? trim((string)$rowFormulas[$colId]) : '';
                                                    $isFormulaCell = ($formulaExpr !== '');
                                                    $editable = lab_format_v2_col_editable($col) && $rowId !== '';
                                                    $value = $editable
                                                        ? lab_format_v2_get_result_value($resultados, $rowId, $colId, $defaultCell)
                                                        : $defaultCell;
                                                    if ($isFormulaCell && ($value === '' || $value === null)) {
                                                        $value = $defaultCell;
                                                    }
                                                    $cellOptions = [];
                                                    if (isset($rowSelectOptions[$colId])) {
                                                        $rawOptions = $rowSelectOptions[$colId];
                                                        if (is_array($rawOptions)) {
                                                            foreach ($rawOptions as $optionValue) {
                                                                $opt = trim((string)$optionValue);
                                                                if ($opt !== '') {
                                                                    $cellOptions[] = $opt;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    if ($editable && !$isFormulaCell && empty($value) && !empty($cellOptions)) {
                                                        $value = $cellOptions[0];
                                                    }

                                                    $currentReferences = [];
                                                    if (isset($rowReferenceRanges[$colId]) && is_array($rowReferenceRanges[$colId])) {
                                                        $currentReferences = $rowReferenceRanges[$colId];
                                                    }

                                                    // Para columnas de resultado sin rango propio, priorizar la
                                                    // primera columna tipo "reference" de la fila (paridad con v2).
                                                    if (empty($currentReferences)) {
                                                        $referenceColIds = [];
                                                        foreach ($formatColumns as $tmpColDef) {
                                                            if (!is_array($tmpColDef)) {
                                                                continue;
                                                            }
                                                            $tmpKind = strtolower(trim((string)($tmpColDef['kind'] ?? 'text')));
                                                            if ($tmpKind !== 'reference') {
                                                                continue;
                                                            }
                                                            $tmpColId = trim((string)($tmpColDef['id'] ?? ''));
                                                            if ($tmpColId !== '') {
                                                                $referenceColIds[] = $tmpColId;
                                                            }
                                                        }

                                                        foreach ($referenceColIds as $refColId) {
                                                            if (!empty($rowReferenceRanges[$refColId]) && is_array($rowReferenceRanges[$refColId])) {
                                                                $currentReferences = $rowReferenceRanges[$refColId];
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    // Fallback final para compatibilidad con estructuras antiguas.
                                                    if (empty($currentReferences)) {
                                                        foreach ($rowReferenceRanges as $tmpRanges) {
                                                            if (is_array($tmpRanges) && !empty($tmpRanges)) {
                                                                $currentReferences = $tmpRanges;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    $currentDecimals = '';
                                                    if (array_key_exists($colId, $rowDecimals) && $rowDecimals[$colId] !== '' && $rowDecimals[$colId] !== null) {
                                                        $decRaw = intval($rowDecimals[$colId]);
                                                        if ($decRaw >= 0 && $decRaw <= 6) {
                                                            $currentDecimals = (string)$decRaw;
                                                        }
                                                    }
                                                    $kind = strtolower(trim((string)($col['kind'] ?? 'text')));
                                                    $referencesAttr = htmlspecialchars(json_encode($currentReferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <td>
                                                    <?php if ($editable): ?>
                                                        <?php
                                                            $fieldKey = lab_format_v2_cell_key($rowId, $colId);
                                                        ?>
                                                        <?php if ($kind === 'long_text'): ?>
                                                            <textarea class="form-control form-control-sm"
                                                                rows="3"
                                                                name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($fieldKey) ?>]"
                                                                data-progress-track="1"
                                                                data-v2-row-id="<?= htmlspecialchars($rowId) ?>"
                                                                data-v2-col-id="<?= htmlspecialchars($colId) ?>"
                                                                data-referencias='<?= $referencesAttr ?>'
                                                                data-edad="<?= htmlspecialchars((string)($edad_paciente ?? '')) ?>"
                                                                data-decimales="<?= htmlspecialchars($currentDecimals) ?>"
                                                                data-sexo="<?= htmlspecialchars((string)($sexo_paciente ?? '')) ?>"
                                                                data-initial-value="<?= htmlspecialchars((string)$value) ?>"><?= htmlspecialchars((string)$value) ?></textarea>
                                                        <?php elseif (!$isFormulaCell && !empty($cellOptions)): ?>
                                                            <select class="form-select form-select-sm"
                                                                name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($fieldKey) ?>]"
                                                                data-progress-track="1"
                                                                data-v2-row-id="<?= htmlspecialchars($rowId) ?>"
                                                                data-v2-col-id="<?= htmlspecialchars($colId) ?>"
                                                                data-referencias='<?= $referencesAttr ?>'
                                                                data-edad="<?= htmlspecialchars((string)($edad_paciente ?? '')) ?>"
                                                                data-decimales="<?= htmlspecialchars($currentDecimals) ?>"
                                                                data-sexo="<?= htmlspecialchars((string)($sexo_paciente ?? '')) ?>"
                                                                data-initial-value="<?= htmlspecialchars((string)$value) ?>">
                                                                <?php foreach ($cellOptions as $optionValue): ?>
                                                                    <option value="<?= htmlspecialchars($optionValue) ?>" <?= ((string)$value === (string)$optionValue) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($optionValue) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else: ?>
                                                            <input type="text"
                                                                class="form-control form-control-sm<?= $isFormulaCell ? ' campo-calculado calculated-field' : '' ?>"
                                                                name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($fieldKey) ?>]"
                                                                data-progress-track="1"
                                                                value="<?= htmlspecialchars((string)$value) ?>"
                                                                data-initial-value="<?= htmlspecialchars((string)$value) ?>"
                                                                data-v2-row-id="<?= htmlspecialchars($rowId) ?>"
                                                                data-v2-col-id="<?= htmlspecialchars($colId) ?>"
                                                                data-referencias='<?= $referencesAttr ?>'
                                                                data-edad="<?= htmlspecialchars((string)($edad_paciente ?? '')) ?>"
                                                                data-sexo="<?= htmlspecialchars((string)$sexo_paciente ?? '') ?>"
                                                                data-decimales="<?= htmlspecialchars($currentDecimals) ?>"
                                                                <?= $isFormulaCell ? 'data-formula-v2="' . htmlspecialchars($formulaExpr) . '" readonly' : '' ?>>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php
                                                            $displayValue = (string)$value;
                                                            if ($kind === 'reference' && !empty($currentReferences) && is_array($currentReferences)) {
                                                                $refLines = [];
                                                                foreach ($currentReferences as $refItem) {
                                                                    if (!is_array($refItem)) {
                                                                        continue;
                                                                    }
                                                                    $descRef = trim((string)($refItem['desc'] ?? ''));
                                                                    $valorRef = trim((string)($refItem['valor'] ?? ''));
                                                                    $minRef = trim((string)($refItem['valor_min'] ?? ''));
                                                                    $maxRef = trim((string)($refItem['valor_max'] ?? ''));
                                                                    $rangoRef = ($minRef !== '' || $maxRef !== '') ? trim(($minRef !== '' ? $minRef : '') . ' - ' . ($maxRef !== '' ? $maxRef : '')) : '';
                                                                    $visibleRef = $valorRef !== '' ? $valorRef : $rangoRef;
                                                                    if ($descRef !== '' && $visibleRef !== '') {
                                                                        $refLines[] = $descRef . ' (' . $visibleRef . ')';
                                                                    } elseif ($descRef !== '') {
                                                                        $refLines[] = $descRef;
                                                                    } elseif ($visibleRef !== '') {
                                                                        $refLines[] = $visibleRef;
                                                                    }
                                                                }
                                                                if (!empty($refLines)) {
                                                                    $displayValue = implode(' | ', $refLines);
                                                                }
                                                            }
                                                        ?>
                                                        <?= nl2br(htmlspecialchars((string)$displayValue)) ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                <?php foreach ($adicional as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $tipoItem = (string)($item['tipo'] ?? '');
                    if ($tipoItem === '') {
                        continue;
                    }
                    if ($tipoItem === 'Título') {
                        echo '<div class="title-section" style="background: ' . (isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--primary-gradient)') . '; color: ' . (isset($item['color_texto']) ? $item['color_texto'] : 'white') . ';">
                            <i class="bi bi-bookmark-star me-2"></i>
                            ' . htmlspecialchars($item['nombre']) . '
                        </div>';
                    } elseif ($tipoItem === 'Subtítulo') {
                        echo '<div class="subtitle-section" style="background: ' . (isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--success-gradient)') . '; color: ' . (isset($item['color_texto']) ? $item['color_texto'] : 'white') . ';">
                            <i class="bi bi-bookmark me-2"></i>
                            ' . htmlspecialchars($item['nombre']) . '
                        </div>';
                    } elseif ($tipoItem === 'Campo') {
                        $valorCampo = $getResultado($item['nombre'], '', $item);
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-pencil-square me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <input type="text"
                                class="form-control"
                                name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']"
                                data-progress-track="1"
                                value="' . htmlspecialchars($valorCampo) . '"
                                data-initial-value="' . htmlspecialchars((string)$valorCampo) . '"
                                placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">
                        </div>';
                    } elseif ($tipoItem === 'Texto Largo') {
                        $rows = isset($item['rows']) && is_numeric($item['rows']) ? intval($item['rows']) : 4;
                        $plantillaTexto = isset($item['template_text']) ? (string)$item['template_text'] : '';
                        if ($plantillaTexto === '' && isset($item['valor']) && is_string($item['valor'])) {
                            $plantillaTexto = (string)$item['valor'];
                        }
                        $valorTexto = $getResultado($item['nombre'], $plantillaTexto, $item);
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-textarea-t me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <textarea class="form-control" rows="' . $rows . '" name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" data-progress-track="1" data-initial-value="' . htmlspecialchars((string)$valorTexto) . '" placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">' . htmlspecialchars($valorTexto) . '</textarea>
                        </div>';
                    } elseif ($tipoItem === 'Parámetro') {
                        $parseNumericExpression = static function ($raw) {
                            $src = trim((string)$raw);
                            if ($src === '') {
                                return null;
                            }
                            if (!preg_match('/^(<=|>=|<|>|=)?\s*([-+]?\d[\d,]*(?:\.\d+)?)$/', $src, $m)) {
                                return null;
                            }

                            $op = isset($m[1]) && $m[1] !== '' ? $m[1] : '=';
                            $numRaw = str_replace(',', '', (string)($m[2] ?? ''));
                            if (!preg_match('/^[-+]?\d+(?:\.\d+)?$/', $numRaw) || !is_numeric($numRaw)) {
                                return null;
                            }

                            return [
                                'op' => $op,
                                'num' => floatval($numRaw),
                            ];
                        };

                        $evaluateNumericExpressionAgainstRange = static function ($raw, $min, $max) use ($parseNumericExpression) {
                            $parsedExpr = $parseNumericExpression($raw);
                            if ($parsedExpr === null) {
                                return null;
                            }

                            $op = (string)($parsedExpr['op'] ?? '=');
                            $num = isset($parsedExpr['num']) ? floatval($parsedExpr['num']) : null;
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

                        $referencia_aplicada = null;
                        $aplicada_idx = null;
                        if (!empty($item['referencias'])) {
                            $referencia_aplicada = $seleccionarReferencia((array)$item['referencias'], $sexo_paciente, $edad_paciente);
                            if ($referencia_aplicada !== null) {
                                foreach ($item['referencias'] as $idx => $ref) {
                                    if ($ref === $referencia_aplicada) {
                                        $aplicada_idx = $idx;
                                        break;
                                    }
                                }
                            }
                        }
                        $valor_resultado = $getResultado($item['nombre'], '', $item);
                        $valor_resultado_num = str_replace(',', '', (string)$valor_resultado);
                        $fuera_rango = false;
                        if ($referencia_aplicada) {
                            $min = isset($referencia_aplicada['valor_min']) ? $toNullableFloat($referencia_aplicada['valor_min']) : null;
                            $max = isset($referencia_aplicada['valor_max']) ? $toNullableFloat($referencia_aplicada['valor_max']) : null;
                            $numericStatus = $evaluateNumericExpressionAgainstRange((string)$valor_resultado, $min, $max);
                            if ($numericStatus === 'out') {
                                $fuera_rango = true;
                            } elseif ($numericStatus === null && is_numeric($valor_resultado_num)) {
                                $valor_num = floatval($valor_resultado_num);
                                if (($min !== null && $valor_num < $min) || ($max !== null && $valor_num > $max)) {
                                    $fuera_rango = true;
                                }
                            }
                        }
                        echo '<div class="parameter-section">
                            <label class="parameter-label">
                                <i class="bi bi-graph-up me-1"></i>
                                <strong>' . htmlspecialchars($item['nombre']) . '</strong>';
                        if (!empty($item['unidad'])) {
                            echo '<span class="badge bg-info ms-2">' . htmlspecialchars($item['unidad']) . '</span>';
                        }
                        echo '</label>';
                        if (!empty($item['opciones'])) {
                            $valorSelect = $getResultado($item['nombre'], '', $item);
                            if ($valorSelect === '' || $valorSelect === null) {
                                foreach ($item['opciones'] as $opcionInicial) {
                                    $opcionInicial = trim((string)$opcionInicial);
                                    if ($opcionInicial !== '') {
                                        $valorSelect = $opcionInicial;
                                        break;
                                    }
                                }
                            }
                                echo '<select name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" class="form-control" data-progress-track="1" data-initial-value="' . htmlspecialchars((string)$valorSelect) . '" data-referencias=\'' . json_encode($item['referencias'] ?? []) . '\' data-edad="' . htmlspecialchars($edad_paciente ?? '') . '" data-sexo="' . htmlspecialchars($sexo_paciente ?? '') . '">
                                    <option value="">Seleccione una opción...</option>';
                            foreach ($item['opciones'] as $opcion) {
                                echo '<option value="' . htmlspecialchars($opcion) . '"' . (($valorSelect !== '' && $valorSelect == $opcion) ? ' selected' : '') . '>' . htmlspecialchars($opcion) . '</option>';
                            }
                            echo '</select>';
                        } else {
                            echo '<div class="input-icon">';
                            if (!empty($item['formula'])) {
                                echo '<i class="bi bi-calculator"></i>';
                            } else {
                                echo '<i class="bi bi-123"></i>';
                            }
                            $value = $getResultado($item['nombre'], '', $item);
                            // Formatea con coma si es numérico y mayor a 999
                            if (is_numeric(str_replace(',', '', $value)) && $value !== '' && floatval(str_replace(',', '', $value)) >= 1000) {
                                $value = number_format(str_replace(',', '', $value), 0, '.', ',');
                            }
                            echo '<input type="text"
                                name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']"
                                class="form-control' . (!empty($item['formula']) ? ' campo-calculado calculated-field' : '') . ($fuera_rango ? ' is-invalid' : '') . '"
                                data-progress-track="1"
                                value="' . htmlspecialchars($value) . '"
                                data-initial-value="' . htmlspecialchars((string)$value) . '"
                                placeholder="' . (!empty($item['formula']) ? 'Valor calculado automáticamente' : 'Ingrese el valor') . '"' .
                                (!empty($item['formula']) ? ' data-formula="' . htmlspecialchars($item['formula']) . '" readonly' : '') .
                                ' data-referencias=\'' . json_encode($item['referencias'] ?? []) . '\'' .
                                ' data-edad="' . htmlspecialchars($edad_paciente ?? '') . '"' .
                                ' data-sexo="' . htmlspecialchars($sexo_paciente ?? '') . '"' .
                                ' data-decimales="' . htmlspecialchars(isset($item['decimales']) ? $item['decimales'] : '') . '"' .
                                '>';                      
                            // Checkbox "Sin .0" eliminado: el sistema ahora formatea de forma natural
                            echo '</div>';
                        }
                        if (!empty($item['referencias'])) {
                            echo '<div class="reference-info">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Valores de Referencia:</strong>';
                            foreach ($item['referencias'] as $i => $ref) {
                                echo '<span class="badge bg-primary ms-1">' . htmlspecialchars($ref['desc'] . ' ' . $ref['valor']) . '</span>';
                                if ($aplicada_idx !== null && $aplicada_idx === $i) {
                                    echo '<span class="badge bg-warning ms-1">Aplicado: ' . htmlspecialchars($ref['desc'] . ' ' . $ref['valor']) . '</span>';
                                }
                            }
                            echo '</div>';
                        }
                        if (!empty($item['metodologia'])) {
                            echo '<div class="methodology-info">
                                    <i class="bi bi-gear me-1"></i>
                                    <strong>Metodología:</strong> ' . htmlspecialchars($item['metodologia']) . '
                                </div>';
                        }
                        echo '</div>';
                    }
                }
                endif;
            echo '</div></div>';
            return ob_get_clean();
    }
}
