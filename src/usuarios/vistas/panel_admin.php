<?php
require_once __DIR__ . '/../../config/config.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-5">
    <h2>Panel de Administrador</h2>
    <div class="row mt-4">
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=empresa" class="btn btn-primary w-100">
                <i class="bi bi-building"></i>Vista Empresa
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=laboratorista" class="btn btn-success w-100">
                <i class="bi bi-cash-coin"></i> Vista laboratorista
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=recepcionista" class="btn btn-success w-100">
                <i class="bi bi-cash-coin"></i> Vista recepcionista
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=cliente" class="btn btn-info w-100">
                <i class="bi bi-people"></i>Vista Cliente
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=convenio" class="btn btn-warning w-100">
                <i class="bi bi-file-earmark-medical"></i> Vista Convenio
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="dashboard.php?vista=config_empresa_datos" class="btn btn-outline-primary mb-3">
                <i class="bi bi-gear-fill"></i> Configuración de Empresa
            </a>

        </div>
        <div class="col-md-3 mb-3">
            <a href="dashboard.php?vista=listado" class="btn btn-primary mb-3">
                Gestionar resultados
            </a>

        </div>
        <!-- Agrega aquí más botones para otras funcionalidades administrativas -->
        <a href="<?= BASE_URL ?>dashboard.php?vista=constructor" class="btn btn-outline-primary">Crear pruebas y perfiles</a>
        <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="dashboard.php?vista=promociones" class="btn btn-warning mb-3">
                <i class="bi bi-megaphone"></i> Gestionar Promociones
            </a>
        <?php endif; ?>


    </div>
</div>