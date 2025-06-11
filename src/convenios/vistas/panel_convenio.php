<?php
require_once __DIR__ . '/../../config/config.php';
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title mb-3">Bienvenido al Panel de Convenios</h2>
            <p class="card-text">
                Aquí puedes gestionar todos los convenios: registrar, editar, eliminar y consultar información de médicos, clínicas u otras entidades con precios especiales.
            </p>
            <a href="<?= BASE_URL ?>dashboard.php?vista=form_convenio" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Registrar nuevo convenio
            </a>
        </div>
    </div>
</div>
