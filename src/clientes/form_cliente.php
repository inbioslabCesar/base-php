<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
$esEdicion = !empty($id);
$cliente = [
    'codigo_cliente' => '',
    'nombre' => '',
    'apellido' => '',
    'dni' => '',
    'edad' => '',
    'email' => '',
    'telefono' => '',
    'direccion' => '',
    'sexo' => '',
    'fecha_nacimiento' => '',
    'estado' => 'activo',
    'descuento' => ''
];

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cli) $cliente = $cli;
    else header('Location: dashboard.php?vista=clientes&msg=sin_id');
}

function capitalize($string) {
    return mb_convert_case(strtolower(trim((string)$string)), MB_CASE_TITLE, "UTF-8");
}
?>
<div class="container mt-4">
    <h4><?= $esEdicion ? 'Editar Paciente' : 'Nuevo Paciente' ?></h4>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'dni_duplicado'): ?>
        <div class="alert alert-danger">El DNI ingresado ya está registrado.</div>
    <?php endif; ?>

    <form method="POST" action="clientes/<?= $esEdicion ? 'editar.php?id='.$cliente['id'] : 'crear.php' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="codigo_cliente" class="form-label">Código Paciente *</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']??'') ?>" required>
                    <button class="btn btn-secondary" type="button" onclick="generarCodigo()">Generar</button>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" name="nombre" id="nombre" value="<?= capitalize($cliente['nombre']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" name="apellido" id="apellido" value="<?= capitalize($cliente['apellido']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="dni" class="form-label">DNI *</label>
                <input type="text" class="form-control" name="dni" id="dni" value="<?= htmlspecialchars($cliente['dni']??'') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="edad" class="form-label">Edad *</label>
                <input type="number" class="form-control" name="edad" id="edad" value="<?= htmlspecialchars($cliente['edad']) ?>" required min="0">
            </div>
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="password" class="form-label"><?= $esEdicion ? 'Nueva contraseña' : 'Contraseña *' ?></label>
                <input type="text" class="form-control" name="password" id="password" <?= $esEdicion ? '' : 'required' ?>>
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" id="telefono" value="<?= htmlspecialchars($cliente['telefono']??'') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" id="direccion" value="<?= htmlspecialchars($cliente['direccion']??'') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="sexo" class="form-label">Sexo</label>
                <select class="form-select" name="sexo" id="sexo">
                    <option value="">Seleccionar</option>
                    <option value="masculino" <?= ($cliente['sexo'] === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= ($cliente['sexo'] === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    <option value="otro" <?= ($cliente['sexo'] === 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento</label>
                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" value="<?= htmlspecialchars($cliente['fecha_nacimiento']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" name="estado" id="estado">
                    <option value="activo" <?= ($cliente['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($cliente['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" class="form-control" name="descuento" id="descuento" value="<?= htmlspecialchars($cliente['descuento']) ?>" min="0" max="100">
            </div>
        </div>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
        <a href="dashboard.php?vista=clientes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
function generarCodigo() {
    let fecha = new Date();
    let año = fecha.getFullYear().toString().slice(-2); // últimos 2 dígitos del año
    let mes = ('0' + (fecha.getMonth() + 1)).slice(-2); // mes con dos dígitos
    let dia = ('0' + fecha.getDate()).slice(-2); // día con dos dígitos
    let aleatorio = Math.random().toString(36).substr(2, 6).toUpperCase();
    let codigo = 'CLI-' + año + mes + dia + '-' + aleatorio;
    document.getElementById('codigo_cliente').value = codigo;
}
</script>
