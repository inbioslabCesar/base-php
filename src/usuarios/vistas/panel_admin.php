<?php
require_once __DIR__ . '/../../config/config.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-5">
    <h2>Panel de Administrador</h2>
    <div class="row mt-4">
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>empresas/vistas/panel_empresa.php" class="btn btn-primary w-100">
                <i class="bi bi-building"></i>Vista Empresa
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>dashboard.php?vista=precio_empresa" class="btn btn-success w-100">
                <i class="bi bi-cash-coin"></i> Vista Usuario
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>clientes/vistas/panel_cliente.php" class="btn btn-info w-100">
                <i class="bi bi-people"></i>Vista Cliente
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= BASE_URL ?>examenes/tabla_examenes.php" class="btn btn-warning w-100">
                <i class="bi bi-file-earmark-medical"></i> Vista Convenio
            </a>
        </div>
        <!-- Agrega aquí más botones para otras funcionalidades administrativas -->
         <a href="<?= BASE_URL ?>dashboard.php?vista=seleccionar_empresa" class="btn btn-outline-primary">Precios por Empresa</a>

    </div>
</div>
