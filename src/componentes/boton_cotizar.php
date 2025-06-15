<?php
if (isset($cliente['id']) && $cliente['id'] && $mostrar) {
    echo '<a href="dashboard.php?vista=form_cotizacion_recepcionista&cliente_id=' . $cliente['id'] . '" class="btn btn-success btn-sm">
        <i class="fas fa-file-invoice-dollar"></i> Cotizar
    </a>';
}
?>
