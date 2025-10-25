 <div class="cotizaciones-filters">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="vista" value="cotizaciones">
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🔍 DNI</label>
                <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🏢 Empresa</label>
                <select name="empresa" class="form-select">
                    <option value="">Seleccionar empresa...</option>
                    <?php foreach ($empresas as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($empresaFiltro == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🤝 Convenio</label>
                <select name="convenio" class="form-select">
                    <option value="">Seleccionar convenio...</option>
                    <?php foreach ($convenios as $conv): ?>
                        <option value="<?= $conv['id'] ?>" <?= ($convenioFiltro == $conv['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($conv['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-outline-secondary flex-fill">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>