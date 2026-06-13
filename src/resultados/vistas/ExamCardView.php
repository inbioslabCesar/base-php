
<?php
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
                $normalized = str_replace(',', '', $trimmed);
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

        $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
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
        foreach ($adicional as $idx => $it) {
            $tipo = $it['tipo'] ?? '';
            $nombre = $it['nombre'] ?? '';
            if (($tipo === 'Título' || $tipo === 'Subtítulo') && $nombre !== '') {
                $before = '__END__';
                if ($idx === 0) {
                    $before = '__FIRST__';
                }
                // Detectar posición actual: "antes de" el siguiente parámetro/campo/texto
                for ($j = $idx + 1; $j < count($adicional); $j++) {
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
                </div>
                <div class="d-flex align-items-center gap-2">
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

                <?php foreach ($adicional as $item) {
                    if ($item['tipo'] === 'Título') {
                        echo '<div class="title-section" style="background: ' . (isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--primary-gradient)') . '; color: ' . (isset($item['color_texto']) ? $item['color_texto'] : 'white') . ';">
                            <i class="bi bi-bookmark-star me-2"></i>
                            ' . htmlspecialchars($item['nombre']) . '
                        </div>';
                    } elseif ($item['tipo'] === 'Subtítulo') {
                        echo '<div class="subtitle-section" style="background: ' . (isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--success-gradient)') . '; color: ' . (isset($item['color_texto']) ? $item['color_texto'] : 'white') . ';">
                            <i class="bi bi-bookmark me-2"></i>
                            ' . htmlspecialchars($item['nombre']) . '
                        </div>';
                    } elseif ($item['tipo'] === 'Campo') {
                        $valorCampo = $getResultado($item['nombre'], '', $item);
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-pencil-square me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <input type="text"
                                class="form-control"
                                name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']"
                                value="' . htmlspecialchars($valorCampo) . '"
                                data-initial-value="' . htmlspecialchars((string)$valorCampo) . '"
                                placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">
                        </div>';
                    } elseif ($item['tipo'] === 'Texto Largo') {
                        $rows = isset($item['rows']) && is_numeric($item['rows']) ? intval($item['rows']) : 4;
                        $valorTexto = $getResultado($item['nombre'], '', $item);
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-textarea-t me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <textarea class="form-control" rows="' . $rows . '" name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" data-initial-value="' . htmlspecialchars((string)$valorTexto) . '" placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">' . htmlspecialchars($valorTexto) . '</textarea>
                        </div>';
                    } elseif ($item['tipo'] === 'Parámetro') {
                        // Refuerza la lógica: si no hay edad o sexo, nunca aplica
                        $referencia_aplicada = null;
                        $aplicada_idx = null;
                        if (!empty($item['referencias']) && $edad_paciente !== null && $sexo_paciente !== '') {
                            foreach ($item['referencias'] as $idx => $ref) {
                                $ref_sexo = isset($ref['sexo']) ? strtolower(trim($ref['sexo'])) : '';
                                $ref_edad_min = isset($ref['edad_min']) ? $toNullableFloat($ref['edad_min']) : null;
                                $ref_edad_max = isset($ref['edad_max']) ? $toNullableFloat($ref['edad_max']) : null;
                                $sexo_match = ($ref_sexo === 'cualquiera' || $ref_sexo === $sexo_paciente);
                                $edad_match = ($ref_edad_min === null || $edad_paciente >= $ref_edad_min) && ($ref_edad_max === null || $edad_paciente <= $ref_edad_max);
                                if ($sexo_match && $edad_match) {
                                    $referencia_aplicada = $ref;
                                    $aplicada_idx = $idx;
                                    break;
                                }
                            }
                        }
                        $valor_resultado = $getResultado($item['nombre'], '', $item);
                        $valor_resultado_num = str_replace(',', '', $valor_resultado);
                        $fuera_rango = false;
                        if ($referencia_aplicada && is_numeric($valor_resultado_num)) {
                            $min = isset($referencia_aplicada['valor_min']) ? $toNullableFloat($referencia_aplicada['valor_min']) : null;
                            $max = isset($referencia_aplicada['valor_max']) ? $toNullableFloat($referencia_aplicada['valor_max']) : null;
                            $valor_num = floatval($valor_resultado_num);
                            if (($min !== null && $valor_num < $min) || ($max !== null && $valor_num > $max)) {
                                $fuera_rango = true;
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
                                echo '<select name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" class="form-control" data-initial-value="' . htmlspecialchars((string)$valorSelect) . '">
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
            echo '</div></div>';
            return ob_get_clean();
    }
}
