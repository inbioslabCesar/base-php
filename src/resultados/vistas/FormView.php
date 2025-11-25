<?php
class FormView {
    public static function render($examenes, $cotizacion_id, $referencia_personalizada) {
        ob_start();
        ?>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            <?php foreach ($examenes as $index => $examen): ?>
                <?= ExamCardView::render($examen, $index) ?>
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
