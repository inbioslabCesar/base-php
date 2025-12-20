
<?php
class ExamCardView {
    public static function render($examen, $index, $datos_paciente = []) {
        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];
        $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
        // Si el examen tiene datos de paciente propios, usarlos; si no, usar los globales
        $edad_paciente = null;
        $sexo_paciente = '';
        if (isset($examen['edad_paciente']) && $examen['edad_paciente'] !== '') {
            $edad_paciente = floatval($examen['edad_paciente']);
        } elseif (isset($datos_paciente['edad']) && $datos_paciente['edad'] !== '') {
            $edad_paciente = floatval($datos_paciente['edad']);
        }
        if (isset($examen['sexo_paciente']) && $examen['sexo_paciente'] !== '') {
            $sexo_paciente = strtolower(trim($examen['sexo_paciente']));
        } elseif (isset($datos_paciente['sexo']) && $datos_paciente['sexo'] !== '') {
            $sexo_paciente = strtolower(trim($datos_paciente['sexo']));
        }
        ob_start();
        ?>
        <div class="exam-card" style="animation-delay: <?= $index * 0.1 ?>s;">
            <div class="exam-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard-pulse me-2"></i>
                    <span><?= htmlspecialchars($examen['nombre_examen']) ?></span>
                </div>
                <div class="form-check form-switch">
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
            </div>
            <div class="exam-card-body">
                <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][id_resultado]" 
                       value="<?= htmlspecialchars($examen['id_resultado']) ?>">
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
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-pencil-square me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <input type="text"
                                class="form-control"
                                name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']"
                                value="' . htmlspecialchars($resultados[$item['nombre']] ?? '') . '"
                                placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">
                        </div>';
                    } elseif ($item['tipo'] === 'Texto Largo') {
                        $rows = isset($item['rows']) && is_numeric($item['rows']) ? intval($item['rows']) : 4;
                        echo '<div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-textarea-t me-2"></i>
                                ' . htmlspecialchars($item['nombre']) . '
                            </label>
                            <textarea class="form-control" rows="' . $rows . '" name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" placeholder="Ingrese ' . htmlspecialchars($item['nombre']) . '">' . htmlspecialchars($resultados[$item['nombre']] ?? '') . '</textarea>
                        </div>';
                    } elseif ($item['tipo'] === 'Parámetro') {
                        // Refuerza la lógica: si no hay edad o sexo, nunca aplica
                        $referencia_aplicada = null;
                        $aplicada_idx = null;
                        if (!empty($item['referencias']) && $edad_paciente !== null && $sexo_paciente !== '') {
                            foreach ($item['referencias'] as $idx => $ref) {
                                $ref_sexo = isset($ref['sexo']) ? strtolower(trim($ref['sexo'])) : '';
                                $ref_edad_min = isset($ref['edad_min']) ? floatval($ref['edad_min']) : null;
                                $ref_edad_max = isset($ref['edad_max']) ? floatval($ref['edad_max']) : null;
                                $sexo_match = ($ref_sexo === 'cualquiera' || $ref_sexo === $sexo_paciente);
                                $edad_match = ($ref_edad_min === null || $edad_paciente >= $ref_edad_min) && ($ref_edad_max === null || $edad_paciente <= $ref_edad_max);
                                if ($sexo_match && $edad_match) {
                                    $referencia_aplicada = $ref;
                                    $aplicada_idx = $idx;
                                    break;
                                }
                            }
                        }
                        $valor_resultado = isset($resultados[$item['nombre']]) ? $resultados[$item['nombre']] : '';
                        $valor_resultado_num = str_replace(',', '', $valor_resultado);
                        $fuera_rango = false;
                        if ($referencia_aplicada && is_numeric($valor_resultado_num)) {
                            $min = isset($referencia_aplicada['valor_min']) ? floatval($referencia_aplicada['valor_min']) : null;
                            $max = isset($referencia_aplicada['valor_max']) ? floatval($referencia_aplicada['valor_max']) : null;
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
                            echo '<select name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']" class="form-control">
                                    <option value="">Seleccione una opción...</option>';
                            foreach ($item['opciones'] as $opcion) {
                                echo '<option value="' . htmlspecialchars($opcion) . '"' . ((isset($resultados[$item['nombre']]) && $resultados[$item['nombre']] == $opcion) ? ' selected' : '') . '>' . htmlspecialchars($opcion) . '</option>';
                            }
                            echo '</select>';
                        } else {
                            echo '<div class="input-icon">';
                            if (!empty($item['formula'])) {
                                echo '<i class="bi bi-calculator"></i>';
                            } else {
                                echo '<i class="bi bi-123"></i>';
                            }
                            $value = $resultados[$item['nombre']] ?? '';
                            // Formatea con coma si es numérico y mayor a 999
                            if (is_numeric(str_replace(',', '', $value)) && $value !== '' && floatval(str_replace(',', '', $value)) >= 1000) {
                                $value = number_format(str_replace(',', '', $value), 0, '.', ',');
                            }
                            echo '<input type="text"
                                name="examenes[' . $examen['id_resultado'] . '][resultados][' . htmlspecialchars($item['nombre']) . ']"
                                class="form-control' . (!empty($item['formula']) ? ' campo-calculado calculated-field' : '') . ($fuera_rango ? ' is-invalid' : '') . '"
                                value="' . htmlspecialchars($value) . '"
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
