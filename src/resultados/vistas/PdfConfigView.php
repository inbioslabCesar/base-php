<?php
class PdfConfigView {
    public static function render($referencia_personalizada) {
        ob_start();
        ?>
        <div class="pdf-config-card shadow-sm rounded-3 mb-4">
            <div class="pdf-config-header d-flex align-items-center" style="background: var(--warning-gradient); color: white; padding: 1.2rem 1.5rem; border-radius: 15px 15px 0 0;">
                <h5 class="mb-0 d-flex align-items-center" style="font-size: 1.25rem; font-weight: 700;">
                    <i class="bi bi-file-earmark-pdf me-2"></i>
                    Configuración para Impresión PDF
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="referencia_personalizada" class="form-label">
                        <i class="bi bi-tag me-2"></i>
                        <strong>Referencia Personalizada para PDF</strong>
                    </label>
                    <div class="input-icon">
                        <i class="bi bi-pencil-square"></i>
                        <input type="text" 
                               id="referencia_personalizada" 
                               name="referencia_personalizada" 
                               class="form-control"
                               placeholder="Ej: Particular, Examen Médico, Empresa ABC..."
                               value="<?= htmlspecialchars($referencia_personalizada) ?>"
                               maxlength="100">
                    </div>
                    <div class="form-text-custom mt-2">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>¿Para qué sirve este campo?</strong><br>
                        Permite cambiar la referencia que aparece en el PDF de resultados. En lugar de mostrar 
                        el nombre real de la empresa o convenio, aparecerá el texto que escribas aquí. 
                        <strong>Déjalo vacío</strong> si quieres que aparezca la referencia original.
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
