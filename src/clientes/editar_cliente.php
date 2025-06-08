<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/clientes_crud.php';

$id = $_GET['id'] ?? null;
$cliente = $id ? obtenerClientePorId($id) : null;
if (!$cliente) {
    echo "<div class='alert alert-danger'>Cliente no encontrado.</div>";
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Editar Cliente</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['errores_cliente'])) {
                        foreach ($_SESSION['errores_cliente'] as $error) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
                        }
                        unset($_SESSION['errores_cliente']);
                    }
                    ?>
                    <form action="<?= BASE_URL ?>clientes/funciones/clientes_crud.php" method="POST" autocomplete="off">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($cliente['id'] ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" required value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido *</label>
                                <input type="text" class="form-control" name="apellido" required value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">DNI *</label>
                                <input type="text" class="form-control" name="dni" required maxlength="15" value="<?= htmlspecialchars($cliente['dni'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sexo *</label>
                                <select class="form-select" name="sexo" required>
                                    <option value="">Seleccionar</option>
                                    <option value="masculino" <?= ($cliente['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="femenino" <?= ($cliente['sexo'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                                    <option value="otro" <?= ($cliente['sexo'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Edad *</label>
                                <input type="number" class="form-control" name="edad" min="0" max="120" required value="<?= htmlspecialchars($cliente['edad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico *</label>
                                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" value="<?= htmlspecialchars($cliente['fecha_nacimiento'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" name="referencia" value="<?= htmlspecialchars($cliente['referencia'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Procedencia</label>
                                <input type="text" class="form-control" name="procedencia" value="<?= htmlspecialchars($cliente['procedencia'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Promociones</label>
                                <input type="text" class="form-control" name="promociones" value="<?= htmlspecialchars($cliente['promociones'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado">
                                    <option value="activo" <?= ($cliente['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= ($cliente['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nueva Contraseña (opcional)</label>
                                <input type="password" class="form-control" name="password" minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código Cliente</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" readonly value="<?= htmlspecialchars($cliente['codigo_cliente'] ?? '') ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generarCodigoCliente()">Generar código</button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>dashboard.php?vista=clientes" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" name="actualizar_cliente" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Actualizar Cliente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function generarCodigoCliente() {
    let codigo = 'lab-' + Math.floor(Math.random() * 90000 + 10000);
    document.getElementById('codigo_cliente').value = codigo;
}
</script>
