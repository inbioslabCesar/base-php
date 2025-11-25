<?php
class FormView {
    public static function render($examenes, $cotizacion_id, $referencia_personalizada, $datos_paciente = []) {
        ob_start();
        ?>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad'] ?? '') ?>">
            <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo'] ?? '') ?>">
            <?php foreach ($examenes as $index => $examen): ?>
                <?= ExamCardView::render($examen, $index, $datos_paciente) ?>
            <?php endforeach; ?>
            <?= PdfConfigView::render($referencia_personalizada) ?>
            <div class="text-center">
                <button type="submit" class="save-btn">
                    <i class="bi bi-save me-2"></i>
                    Guardar Resultados
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
}
