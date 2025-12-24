<style>
.panel-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    box-shadow: 0 4px 24px #764ba233;
}
.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    transition: box-shadow 0.2s, transform 0.2s;
}
.btn-gradient:hover {
    box-shadow: 0 4px 16px #764ba2aa;
    transform: translateY(-2px) scale(1.04);
}
.panel-card {
    border-radius: 18px;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: 0 2px 8px #667eea22;
}
.panel-card:hover {
    box-shadow: 0 8px 32px #667eea33;
    transform: translateY(-4px) scale(1.03);
}
</style>
<?php
require_once __DIR__ . '/../../config/config.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container-fluid mt-4">
    <!-- Formulario de búsqueda de paciente -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h4 class="mb-3"><i class="bi bi-search me-2"></i>Buscar Paciente</h4>
            <form method="get" action="dashboard.php" class="row g-3 align-items-end">
                <input type="hidden" name="vista" value="buscar_paciente">
                <div class="col-md-6">
                    <input type="text" name="busqueda_paciente" class="form-control form-control-lg" placeholder="DNI, código, nombre o apellido" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">
                <i class="bi bi-person-badge-fill me-2"></i>Panel de Administrador
            </h2>

            <div class="container-fluid mt-4">
                <!-- Encabezado destacado -->
                <!-- Opciones en cards -->
                <div class="row g-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-building display-4 text-primary mb-2"></i>
                                <h5 class="card-title mb-2">Vista Empresa</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=empresa" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-eyeglasses display-4 text-warning mb-2"></i>
                                <h5 class="card-title mb-2">Vista Laboratorista</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=laboratorista" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-person-lines-fill display-4 text-success mb-2"></i>
                                <h5 class="card-title mb-2">Vista Recepcionista</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=recepcionista" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-people display-4 text-info mb-2"></i>
                                <h5 class="card-title mb-2">Vista Cliente</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=cliente" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-cash-stack display-4 text-danger mb-2"></i>
                                <h5 class="card-title mb-2">Contabilidad</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=contabilidad" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-medical display-4 text-warning mb-2"></i>
                                <h5 class="card-title mb-2">Vista Convenio</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=convenio" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card panel-card h-100 shadow-lg border-0">
                            <div class="card-body text-center">
                                <i class="bi bi-gear-fill display-4 text-primary mb-2"></i>
                                <h5 class="card-title mb-2">Configuración Empresa</h5>
                                <a href="<?= BASE_URL ?>dashboard.php?vista=config_empresa_datos" class="btn btn-gradient w-100">Ir</a>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="card panel-card h-100 shadow-lg border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-megaphone display-4 text-warning mb-2"></i>
                                    <h5 class="card-title mb-2">Promociones</h5>
                                    <a href="<?= BASE_URL ?>dashboard.php?vista=promociones" class="btn btn-gradient w-100">Ir</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card panel-card h-100 shadow-lg border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-clock-history display-4 text-warning mb-2"></i>
                                    <h5 class="card-title mb-2">Pendientes de Toma</h5>
                                    <a href="<?= BASE_URL ?>dashboard.php?vista=pendientes_toma" class="btn btn-gradient w-100">Ir</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card panel-card h-100 shadow-lg border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-file-earmark-text display-4 text-primary mb-2"></i>
                                    <h5 class="card-title mb-2">Mis Cotizaciones</h5>
                                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-gradient w-100">Ir</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>