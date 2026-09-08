<?php
if (isset($cliente['id']) && $cliente['id'] && $mostrar) {
    echo '<a href="dashboard.php?vista=form_cotizacion_recepcionista&cliente_id=' . $cliente['id'] . '" class="btn btn-success btn-sm btn-cotizar-cta-global d-inline-flex align-items-center gap-1">
        <i class="bi bi-cart-plus-fill"></i> Cotizar
    </a>';
}
?>
