<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);
$cliente = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'sexo' => '',
    'codigo_cliente' => '',
    'telefono' => '',
    'direccion' => '',
    'fecha_nacimiento' => '',
    'estado' => 'activo',
    'descuento' => ''
];

if ($esEdicion) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $_SESSION['mensaje'] = "Cliente no encontrado.";
        header('Location: dashboard.php?vista=clientes');
        exit;
    }
}

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Cliente' : 'Agregar Cliente' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_cliente&id=' . $_GET['id'] : 'crear_cliente' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="codigo_cliente" class="form-label">Código Cliente</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo_cliente" name="codigo_cliente"
                        value="<?= htmlspecialchars($cliente['codigo_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="generarCodigo()">Generar Código</button>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required
                    value="<?= htmlspecialchars(capitalizar($cliente['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" id="apellido" name="apellido" required
                    value="<?= htmlspecialchars(capitalizar($cliente['apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required
                    value="<?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="password" class="form-label"><?= $esEdicion ? 'Nueva Contraseña' : 'Contraseña *' ?></label>
                <input type="password" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
                <?php if ($esEdicion): ?>
                    <small class="text-muted">Deja en blanco si no deseas cambiar la contraseña.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="sexo" class="form-label">Sexo</label>
                <select class="form-select" id="sexo" name="sexo" required>
                    <option value="">Seleccionar</option>
                    <option value="masculino" <?= (isset($cliente['sexo']) && $cliente['sexo'] == 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= (isset($cliente['sexo']) && $cliente['sexo'] == 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    <option value="otro" <?= (isset($cliente['sexo']) && $cliente['sexo'] == 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                    value="<?= htmlspecialchars($cliente['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activo" <?= (isset($cliente['estado']) && $cliente['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= (isset($cliente['estado']) && $cliente['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" id="descuento" name="descuento"
                    value="<?= htmlspecialchars($cliente['descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Crear' ?></button>
        <a href="dashboard.php?vista=clientes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
function generarCodigo() {
    const random = Math.floor(100000 + Math.random() * 900000);
    document.getElementById('codigo_cliente').value = 'LAB-' + random;
}
</script>
