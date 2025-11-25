<?php
class AlertView {
    public static function render($mensaje) {
        ob_start();
        ?>
        <div class="alert-custom text-center">
            <i class="bi bi-info-circle-fill display-4 mb-3"></i>
            <h4><?= htmlspecialchars($mensaje) ?></h4>
            <p class="mb-0">No se encontraron exámenes para esta cotización.</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
