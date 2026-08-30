<div class="cotizaciones-header">
    <div class="d-flex justify-content-between align-items-md-center gap-2 flex-column flex-md-row">
        <div>
            <h4 class="mb-1">📊 Historial de Cotizaciones</h4>
            <div class="small opacity-75">Gestiona filtros, estado de pagos y acciones de seguimiento desde un solo panel.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-start justify-content-md-end">
            <?php if (!empty($mostrarBotonAnuladas)): ?>
                <a href="dashboard.php?vista=cotizaciones_anuladas" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-archive me-1"></i> Ver anuladas
                </a>
            <?php endif; ?>
            <?php if (isset($botonTexto) && isset($botonUrl) && $botonTexto && $botonUrl): ?>
                <a href="<?= $botonUrl ?>" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle me-1"></i><?= $botonTexto ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
