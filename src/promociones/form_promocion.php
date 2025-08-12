<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$esEdicion = isset($_SESSION['promocion_editar']);
if ($esEdicion) {
    $p = $_SESSION['promocion_editar'];
    $id = $p['id'];
    $titulo = $p['titulo'];
    $descripcion = $p['descripcion'];
    $precio_promocional = $p['precio_promocional'];
    $descuento = $p['descuento'];
    $fecha_inicio = $p['fecha_inicio'];
    $fecha_fin = $p['fecha_fin'];
    $imagen = $p['imagen'];
    $activo = $p['activo'];
    $vigente = $p['vigente'];
    $tipo_publico = $p['tipo_publico'] ?? 'todos';
    unset($_SESSION['promocion_editar']);
} else {
    $id = '';
    $titulo = $descripcion = $precio_promocional = $descuento = $fecha_inicio = $fecha_fin = $imagen = '';
    $activo = $vigente = 0;
    $tipo_publico = 'todos';
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?= $esEdicion ? "Editar" : "Nueva" ?> Promoción</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_URL ?>dashboard.php?action=<?= $esEdicion ? 'editar_promocion&id=' . htmlspecialchars($id) : 'crear_promocion' ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($titulo) ?>" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="3" class="form-control"><?= htmlspecialchars($descripcion) ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="precio_promocional" class="form-label">Precio Promocional (S/)</label>
                                <input type="number" name="precio_promocional" id="precio_promocional" class="form-control" value="<?= htmlspecialchars($precio_promocional) ?>" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="descuento" class="form-label">Descuento (%)</label>
                                <input type="number" name="descuento" id="descuento" class="form-control" value="<?= htmlspecialchars($descuento) ?>" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha de inicio <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label">Fecha de fin <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen (opcional)</label>
                            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                            <?php if ($esEdicion && $imagen): ?>
                                <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($imagen) ?>">
                                <div class="mt-2">
                                    <span class="d-block mb-1">Imagen actual:</span>
                                    <img src="<?= BASE_URL ?>promociones/assets/<?= htmlspecialchars($imagen) ?>" width="120" class="img-thumbnail rounded">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" <?= (!empty($activo)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="vigente" id="vigente" value="1" <?= (!empty($vigente)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="vigente">Vigente</label>
                        </div>
                        <div class="mb-3">
                            <label for="tipo_publico" class="form-label">Visible para</label>
                            <select name="tipo_publico" id="tipo_publico" class="form-select" required>
                                <option value="todos" <?= ($tipo_publico === 'todos') ? 'selected' : '' ?>>Todos</option>
                                <option value="convenios" <?= ($tipo_publico === 'convenios') ? 'selected' : '' ?>>Convenios</option>
                                <option value="clientes" <?= ($tipo_publico === 'clientes') ? 'selected' : '' ?>>Clientes</option>
                                <option value="empresas" <?= ($tipo_publico === 'empresas') ? 'selected' : '' ?>>Empresas</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> <?= $esEdicion ? "Actualizar" : "Crear" ?> Promoción
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>