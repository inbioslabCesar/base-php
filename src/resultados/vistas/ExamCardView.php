<?php
class ExamCardView {
    public static function render($examen, $index) {
        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];
        $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
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
                <?php foreach ($adicional as $item): ?>
                    <?php if ($item['tipo'] === 'Título'): ?>
                        <div class="title-section" style="background: <?= isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--primary-gradient)' ?>; color: <?= isset($item['color_texto']) ? $item['color_texto'] : 'white' ?>;">
                            <i class="bi bi-bookmark-star me-2"></i>
                            <?= htmlspecialchars($item['nombre']) ?>
                        </div>
                    <?php elseif ($item['tipo'] === 'Subtítulo'): ?>
                        <div class="subtitle-section" style="background: <?= isset($item['color_fondo']) ? $item['color_fondo'] : 'var(--success-gradient)' ?>; color: <?= isset($item['color_texto']) ? $item['color_texto'] : 'white' ?>;">
                            <i class="bi bi-bookmark me-2"></i>
                            <?= htmlspecialchars($item['nombre']) ?>
                        </div>
                    <?php elseif ($item['tipo'] === 'Campo'): ?>
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-pencil-square me-2"></i>
                                <?= htmlspecialchars($item['nombre']) ?>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                value="<?= htmlspecialchars($resultados[$item['nombre']] ?? '') ?>"
                                placeholder="Ingrese <?= htmlspecialchars($item['nombre']) ?>">
                        </div>
                    <?php elseif ($item['tipo'] === 'Parámetro'): ?>
                        <div class="parameter-section">
                            <label class="parameter-label">
                                <i class="bi bi-graph-up me-1"></i>
                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                <?php if (!empty($item['unidad'])): ?>
                                    <span class="badge bg-info ms-2"><?= htmlspecialchars($item['unidad']) ?></span>
                                <?php endif; ?>
                            </label>
                            <?php if (!empty($item['opciones'])): ?>
                                <select name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]" class="form-control">
                                    <option value="">Seleccione una opción...</option>
                                    <?php foreach ($item['opciones'] as $opcion): ?>
                                        <option value="<?= htmlspecialchars($opcion) ?>"
                                            <?= (isset($resultados[$item['nombre']]) && $resultados[$item['nombre']] == $opcion) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opcion) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="input-icon">
                                    <?php if (!empty($item['formula'])): ?>
                                        <i class="bi bi-calculator"></i>
                                    <?php else: ?>
                                        <i class="bi bi-123"></i>
                                    <?php endif; ?>
                                    <input
                                        type="text"
                                        name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                        class="form-control<?= !empty($item['formula']) ? ' campo-calculado calculated-field' : '' ?>"
                                        value="<?= isset($resultados[$item['nombre']]) ? htmlspecialchars($resultados[$item['nombre']]) : '' ?>"
                                        placeholder="<?= !empty($item['formula']) ? 'Valor calculado automáticamente' : 'Ingrese el valor' ?>"
                                        <?= !empty($item['formula']) ? 'data-formula="' . htmlspecialchars($item['formula']) . '" readonly' : '' ?>
                                    >
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['referencias'])): ?>
                                <div class="reference-info">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Valores de Referencia:</strong>
                                    <?php foreach ($item['referencias'] as $ref): ?>
                                        <span class="badge bg-primary ms-1"><?= htmlspecialchars($ref['desc'] . ' ' . $ref['valor']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['metodologia'])): ?>
                                <div class="methodology-info">
                                    <i class="bi bi-gear me-1"></i>
                                    <strong>Metodología:</strong> <?= htmlspecialchars($item['metodologia']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
