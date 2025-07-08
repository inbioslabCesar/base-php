<?php
require_once __DIR__ . '/../../config/config.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">
                <i class="bi bi-person-badge-fill me-2"></i>Panel de Administrador
            </h2>
            <div class="row justify-content-center mb-4">
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=empresa" class="btn btn-primary w-100">
                        <i class="bi bi-building"></i> Vista Empresa
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=laboratorista" class="btn btn-warning w-100 text-dark">
                        <i class="bi bi-eyeglasses"></i> Vista Laboratorista
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=recepcionista" class="btn btn-success w-100">
                        <i class="bi bi-person-lines-fill"></i> Vista Recepcionista
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=cliente" class="btn btn-info w-100">
                        <i class="bi bi-people"></i> Vista Cliente
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=contabilidad" class="btn btn-danger w-100">
                        <i class="bi bi-cash-stack"></i> Contabilidad (Ingresos/Egresos)
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=convenio" class="btn btn-warning w-100">
                        <i class="bi bi-file-earmark-medical"></i> Vista Convenio
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=config_empresa_datos" class="btn btn-outline-primary w-100">
                        <i class="bi bi-gear-fill"></i> Configuración de Empresa
                    </a>
                </div>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>dashboard.php?vista=promociones" class="btn btn-warning w-100">
                        <i class="bi bi-megaphone"></i> Gestionar Promociones
                    </a>
                </div>
                <div class="col-md-3 mb-3">
            <a href="dashboard.php?vista=cotizaciones" class="btn btn-primary btn-lg">
                <i class="bi bi-file-earmark-text"></i> Mis Cotizaciones
            </a>
        </div>
                <?php endif; ?>
            </div>           
        </div>
    </div>
</div>
