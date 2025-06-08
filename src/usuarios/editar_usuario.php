<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/usuarios_crud.php';

$id = $_GET['id'] ?? null;
$usuario = $id ? obtenerUsuarioPorId($id) : null;
if (!$usuario) {
    echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Editar Usuario</h4>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>usuarios/funciones/usuarios_crud.php" method="POST" autocomplete="off">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="nombre" required value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="apellido" required value="<?= htmlspecialchars($usuario['apellido'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" name="dni" id="dni" required maxlength="15" value="<?= htmlspecialchars($usuario['dni'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="sexo" class="form-label">Sexo</label>
                                <select class="form-select" name="sexo" id="sexo" required>
                                    <option value="">Seleccionar</option>
                                    <option value="masculino" <?= ($usuario['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="femenino" <?= ($usuario['sexo'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                                    <option value="otro" <?= ($usuario['sexo'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" required value="<?= htmlspecialchars($usuario['fecha_nacimiento'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" name="email" id="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" required value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" id="direccion" value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="profesion" class="form-label">Profesión</label>
                                <input type="text" class="form-control" name="profesion" id="profesion" value="<?= htmlspecialchars($usuario['profesion'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" name="rol" id="rol" required>
                                    <option value="admin" <?= ($usuario['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="recepcionista" <?= ($usuario['rol'] ?? '') === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                    <option value="laboratorista" <?= ($usuario['rol'] ?? '') === 'laboratorista' ? 'selected' : '' ?>>Laboratorista</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" name="estado" id="estado" required>
                                    <option value="activo" <?= ($usuario['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= ($usuario['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
                                <input type="password" class="form-control" name="password" id="password" minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" minlength="6">
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" name="actualizar_usuario" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
