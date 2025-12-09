<div class="cotizaciones-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="mb-2 mb-md-0">📊 Historial de Cotizaciones</h4>
        <?php if (isset($botonTexto) && isset($botonUrl) && $botonTexto && $botonUrl): ?>
            <a href="<?= $botonUrl ?>" class="btn btn-light">
                <i class="bi bi-plus-circle me-2"></i><?= $botonTexto ?>
            </a>
        <?php endif; ?>
    </div>
</div>
