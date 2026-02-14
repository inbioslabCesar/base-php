<?php
class FormView {
    public static function render($examenes, $cotizacion_id, $referencia_personalizada, $datos_paciente = [], $areas_disponibles = []) {
        ob_start();
        ?>
        <div class="alert alert-info" style="border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.06);">
            <strong>Importante:</strong> cuando modificas un examen en el CRUD (nombre, metodología, parámetros o área),
            <strong>no se actualiza automáticamente</strong> en cotizaciones ya creadas.
            Este formulario usa un <strong>formato guardado (snapshot)</strong> para no romper históricos.
            Si deseas reflejar cambios del CRUD en esta cotización, usa <strong>Actualizar formato</strong>.
        </div>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad'] ?? '') ?>">
            <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo'] ?? '') ?>">
            <?php foreach ($examenes as $index => $examen): ?>
                <?= ExamCardView::render($examen, $index, $datos_paciente, $areas_disponibles) ?>
            <?php endforeach; ?>
            <?= PdfConfigView::render($referencia_personalizada) ?>
            <div class="text-center">
                <button type="submit" class="save-btn">
                    <i class="bi bi-save me-2"></i>
                    Guardar Resultados
                </button>
            </div>
        </form>

        <div class="text-center update-format-form">
            <form method="post" action="dashboard.php?action=actualizar_snapshot_resultados" class="d-inline" onsubmit="return confirm('Actualiza el formato con el examen actual y CONSERVA las cabeceras personalizadas del paciente (si existen). Los resultados guardados se mantienen. ¿Continuar?');">
                <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
                <input type="hidden" name="preserve_headers" value="1">
                <button type="submit" class="update-format-btn">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Actualizar formato (conservar cabeceras)
                </button>
            </form>

            <form method="post" action="dashboard.php?action=actualizar_snapshot_resultados" class="d-inline ms-2" onsubmit="return confirm('Esto REEMPLAZARÁ el formato de esta cotización con el examen actual. Podría eliminar cabeceras personalizadas. Los resultados guardados se mantienen. ¿Continuar?');">
                <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
                <input type="hidden" name="preserve_headers" value="0">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Reemplazar formato
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
