<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);
$convenio = [
    'nombre' => '',
    'dni' => '',
    'especialidad' => '',
    'descuento' => '',
    'descripcion' => '',
    'email' => '',
    'password' => ''
];

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM convenios WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$convenio) {
        $_SESSION['mensaje'] = "Convenio no encontrado";
        header('Location: dashboard.php?vista=convenios');
        exit;
    }
}

// Función para capitalizar
function capitalizar($texto) {
    return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Convenio' : 'Registrar Convenio' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_convenio&id=' . htmlspecialchars($_GET['id']) : 'crear_convenio' ?>">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required
                value="<?= htmlspecialchars(capitalizar($convenio['nombre'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="dni" class="form-label">DNI *</label>
            <input type="text" class="form-control" id="dni" name="dni" required
                value="<?= htmlspecialchars($convenio['dni'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="especialidad" class="form-label">Especialidad</label>
            <input type="text" class="form-control" id="especialidad" name="especialidad"
                value="<?= htmlspecialchars(capitalizar($convenio['especialidad'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="descuento" class="form-label">Descuento (%)</label>
            <input type="number" class="form-control" id="descuento" name="descuento" min="0" max="100" step="0.01"
                value="<?= htmlspecialchars($convenio['descuento'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion"><?= htmlspecialchars(capitalizar($convenio['descripcion'] ?? '')) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required
                value="<?= htmlspecialchars($convenio['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña <?= $esEdicion ? '(dejar vacío para no cambiar)' : '*' ?></label>
            <input type="password" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
        </div>
        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
        <a href="dashboard.php?vista=convenios" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
