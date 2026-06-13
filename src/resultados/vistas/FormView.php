<?php
class FormView {
    public static function render($examenes, $cotizacion_id, $referencia_personalizada, $datos_paciente = [], $areas_disponibles = []) {
        ob_start();
        $pdfDownloadUrl = 'resultados/descarga-pdf.php?cotizacion_id=' . urlencode((string)$cotizacion_id);
        ?>
        <div class="alert alert-info" style="border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.06);">
            <strong>Importante:</strong> los cambios del CRUD de exámenes (nombre, metodología, parámetros o área)
            se reflejan automáticamente en este formulario y en la impresión.
            Los controles manuales de actualización/reemplazo de formato están temporalmente deshabilitados para todos.
        </div>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            <input type="hidden" name="stay_on_form" value="1">
            <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad'] ?? '') ?>">
            <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo'] ?? '') ?>">
            <div id="examCardsContainer" class="exam-cards-container">
                <?php foreach ($examenes as $index => $examen): ?>
                    <?= \ExamCardView::render($examen, $index, $datos_paciente, $areas_disponibles) ?>
                <?php endforeach; ?>
            </div>
            <div id="examOrderInputs"></div>
            <?= \PdfConfigView::render($referencia_personalizada) ?>
            <div class="text-center d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                <a href="<?= htmlspecialchars($pdfDownloadUrl) ?>"
                   class="btn btn-success js-download-pdf-resultados"
                   id="btnDescargarPdfResultados"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="Descargar PDF de resultados">
                    <i class="bi bi-file-earmark-pdf me-2"></i>
                    Descargar PDF
                </a>
                <button type="submit" class="save-btn">
                    <i class="bi bi-save me-2"></i>
                    Guardar Resultados
                </button>
            </div>

            <div id="resultsActionsDock" class="results-actions-dock" aria-label="Acciones rapidas de resultados">
                <button
                    type="button"
                    id="resultsDockModeToggle"
                    class="results-actions-dock__mode-btn"
                    aria-pressed="false"
                    title="Alternar visibilidad de la barra flotante">
                    <i class="bi bi-pin-angle me-1"></i>Fijar
                </button>
                <a href="<?= htmlspecialchars($pdfDownloadUrl) ?>"
                   class="btn btn-success results-actions-dock__btn js-download-pdf-resultados"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="Descargar PDF de resultados">
                    <i class="bi bi-file-earmark-pdf me-2"></i>
                    Descargar PDF
                </a>
                <button type="submit" class="save-btn results-actions-dock__btn">
                    <i class="bi bi-save me-2"></i>
                    Guardar Resultados
                </button>
            </div>
        </form>

        <script>
        document.addEventListener('change', function (event) {
            const target = event.target;
            if (!target.classList.contains('js-alarma-switch')) {
                return;
            }

            const card = target.closest('.exam-card');
            if (!card) {
                return;
            }

            const daysInput = card.querySelector('.js-alarma-dias');
            if (!daysInput) {
                return;
            }

            daysInput.disabled = !target.checked;
            if (!target.checked) {
                daysInput.value = '';
                return;
            }

            if (!daysInput.value) {
                daysInput.value = '1';
            }
        });

        document.addEventListener('click', function (event) {
            const link = event.target.closest('.js-download-pdf-resultados');
            if (!link) {
                return;
            }

            const baseUrl = <?= json_encode($pdfDownloadUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            link.href = `${baseUrl}&_ts=${Date.now()}`;
        });
        </script>

        <?php $snapshotControlsEnabled = false; ?>
        <?php if ($snapshotControlsEnabled): ?>
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
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}
