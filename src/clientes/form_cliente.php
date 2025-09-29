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

// Variables para mostrar información del último paciente
$ultimoCodigoCliente = '';
$ultimoNombreCliente = '';
$fechaUltimoRegistro = '';

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cli) $cliente = $cli;
    else header('Location: dashboard.php?vista=clientes&msg=sin_id');
} else {
    // Si es creación, obtener información del último paciente registrado
    $stmt = $pdo->prepare("
        SELECT codigo_cliente, nombre, apellido, fecha_registro 
        FROM clientes 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $ultimoCliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoCliente) {
        $ultimoCodigoCliente = $ultimoCliente['codigo_cliente'];
        $ultimoNombreCliente = trim($ultimoCliente['nombre'] . ' ' . $ultimoCliente['apellido']);
        $fechaUltimoRegistro = $ultimoCliente['fecha_registro'];
    }
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

    <?php if (!$esEdicion && $ultimoCodigoCliente): ?>
        <div class="alert alert-info border-0 shadow-sm" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2" style="font-size: 1.2rem; color: #1976d2;"></i>
                <div>
                    <h6 class="mb-1" style="color: #1565c0;">
                        <i class="bi bi-person-check me-1"></i>
                        Último paciente registrado
                    </h6>
                    <p class="mb-0" style="color: #1976d2;">
                        <strong>Código:</strong> <span class="badge bg-primary"><?= htmlspecialchars($ultimoCodigoCliente) ?></span>
                        <strong class="ms-3">Paciente:</strong> <?= htmlspecialchars($ultimoNombreCliente) ?>
                        <strong class="ms-3">Fecha:</strong> <?= date('d/m/Y H:i', strtotime($fechaUltimoRegistro)) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php elseif (!$esEdicion && !$ultimoCodigoCliente): ?>
        <div class="alert alert-success border-0 shadow-sm" style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-star-fill me-2" style="font-size: 1.2rem; color: #388e3c;"></i>
                <div>
                    <h6 class="mb-1" style="color: #2e7d32;">
                        <i class="bi bi-trophy me-1"></i>
                        ¡Primer paciente!
                    </h6>
                    <p class="mb-0" style="color: #388e3c;">
                        Este será el primer paciente registrado en el sistema.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="clientes/<?= $esEdicion ? 'editar.php?id='.$cliente['id'] : 'crear.php' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="codigo_cliente" class="form-label">Código Paciente *</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']??'') ?>" required>
                    <button class="btn btn-secondary" type="button" onclick="generarCodigo()" title="Generar código automático">
                        <i class="bi bi-arrow-clockwise"></i>
                        Generar
                    </button>
                </div>
                <small class="text-muted">
                    <i class="bi bi-lightbulb"></i>
                    Haz clic en "Generar" para crear un código automático
                </small>
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
                <label for="edad" class="form-label">Edad</label>
                <?php
                    // Separar valor y unidad si es posible
                    $edad_valor = '';
                    $edad_unidad = '';
                    if (preg_match('/^([0-9]+)\\s*(día|días|mes|meses|año|años)$/iu', trim($cliente['edad']), $m)) {
                        $edad_valor = $m[1];
                        $edad_unidad = strtolower($m[2]);
                    } elseif (is_numeric($cliente['edad'])) {
                        $edad_valor = $cliente['edad'];
                        $edad_unidad = 'años';
                    }
                ?>
                <div class="input-group">
                    <input type="text" class="form-control" name="edad_valor" id="edad_valor" value="<?= htmlspecialchars($edad_valor) ?>" pattern="[0-9]+">
                    <select class="form-select" name="edad_unidad" id="edad_unidad">
                        <option value="días" <?= ($edad_unidad==="día"||$edad_unidad==="días")?'selected':'' ?>>Días</option>
                        <option value="meses" <?= ($edad_unidad==="mes"||$edad_unidad==="meses")?'selected':'' ?>>Meses</option>
                        <option value="años" <?= ($edad_unidad==="año"||$edad_unidad==="años"||$edad_unidad==="")?'selected':'' ?>>Años</option>
                    </select>
                </div>
                <small class="form-text text-muted">Ejemplo: 15 días, 2 meses, 1 año</small>
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
                    <optgroup label="👤 Humanos">
                        <option value="masculino" <?= ($cliente['sexo'] === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                        <option value="femenino" <?= ($cliente['sexo'] === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    </optgroup>
                    <optgroup label="🐾 Animales">
                        <option value="macho" <?= ($cliente['sexo'] === 'macho') ? 'selected' : '' ?>>Macho</option>
                        <option value="hembra" <?= ($cliente['sexo'] === 'hembra') ? 'selected' : '' ?>>Hembra</option>
                    </optgroup>
                    <option value="otro" <?= ($cliente['sexo'] === 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Selecciona según el tipo de paciente: humano o animal
                </small>
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
