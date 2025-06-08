<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/empresas_crud.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Registrar Nueva Empresa</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['errores_empresa'])) {
                        foreach ($_SESSION['errores_empresa'] as $error) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
                        }
                        unset($_SESSION['errores_empresa']);
                    }
                    ?>
                    <form action="<?= BASE_URL ?>empresas/funciones/empresas_crud.php" method="POST" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">RUC *</label>
                                <input type="text" class="form-control" name="ruc" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" class="form-control" name="razon_social" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Representante</label>
                                <input type="text" class="form-control" name="representante">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Convenio</label>
                                <input type="text" class="form-control" name="convenio">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña *</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" name="registrar_empresa" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Registrar Empresa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
