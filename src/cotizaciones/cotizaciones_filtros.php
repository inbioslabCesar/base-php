
<style>
.cotizaciones-filters {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(44,62,80,0.07);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 2rem;
}
.cotizaciones-filters .form-label {
    color: #1565c0;
    font-weight: 600;
    font-size: 1rem;
}
.cotizaciones-filters .form-control, .cotizaciones-filters .form-select {
    border-radius: 10px;
    border: 1.5px solid #90caf9;
    font-size: 1rem;
}
.cotizaciones-filters .btn-primary {
    background: linear-gradient(135deg, #1976d2 0%, #64b5f6 100%);
    border: none;
    font-weight: 600;
}
.cotizaciones-filters .btn-outline-secondary {
    border-radius: 10px;
    font-weight: 600;
}
.cotizaciones-filters .row {
    row-gap: 1.2rem;
}
</style>

<div class="cotizaciones-filters">
    <form method="get">
        <input type="hidden" name="vista" value="cotizaciones">
        <div class="row">
            <div class="col-md-2 col-sm-6">
                <label class="form-label">🔍 DNI</label>
                <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label">🏢 Empresa</label>
                <select name="empresa" class="form-select">
                    <option value="">Seleccionar empresa...</option>
                    <?php foreach ($empresas as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($empresaFiltro == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label">🤝 Convenio</label>
                <select name="convenio" class="form-select">
                    <option value="">Seleccionar convenio...</option>
                    <?php foreach ($convenios as $conv): ?>
                        <option value="<?= $conv['id'] ?>" <?= ($convenioFiltro == $conv['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($conv['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label">📅 Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control mb-1" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label">📅 Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="col-md-2 col-sm-12 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>