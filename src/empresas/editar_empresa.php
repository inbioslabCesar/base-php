<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/empresas_crud.php';

$id = $_GET['id'] ?? null;
$empresa = $id ? obtenerEmpresaPorId($id) : null;
if (!$empresa) {
    echo "<div class='alert alert-danger'>Empresa no encontrada.</div>";
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Editar Empresa</h4>
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
                        <input type="hidden" name="id" value="<?= htmlspecialchars($empresa['id'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">RUC *</label>
                                <input type="text" class="form-control" name="ruc" required value="<?= htmlspecialchars($empresa['ruc'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" class="form-control" name="razon_social" required value="<?= htmlspecialchars($empresa['razon_social'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial" value="<?= htmlspecialchars($empresa['nombre_comercial'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico *</label>
                                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Representante</label>
                                <input type="text" class="form-control" name="representante" value="<?= htmlspecialchars($empresa['representante'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Convenio</label>
                                <input type="text" class="form-control" name="convenio" value="<?= htmlspecialchars($empresa['convenio'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado">
                                    <option value="activo" <?= ($empresa['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= ($empresa['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nueva Contraseña (opcional)</label>
                                <input type="password" class="form-control" name="password" minlength="6">
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" name="actualizar_empresa" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Actualizar Empresa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
