<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);
$convenio = [
    'nombre' => '',
    'dni' => '',
    'especialidad' => '',
    'descuento' => '',
    'descripcion' => ''
];

if ($esEdicion) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM convenios WHERE id = ?");
    $stmt->execute([$id]);
    $convenio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$convenio) {
        $_SESSION['mensaje'] = "Convenio no encontrado.";
        header('Location: dashboard.php?vista=convenios');
        exit;
    }
}

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Convenio' : 'Agregar Convenio' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_convenio&id=' . $_GET['id'] : 'crear_convenio' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required
                    value="<?= htmlspecialchars(capitalizar($convenio['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="dni" class="form-label">DNI</label>
                <input type="text" class="form-control" id="dni" name="dni"
                    value="<?= htmlspecialchars($convenio['dni'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="especialidad" class="form-label">Especialidad</label>
                <input type="text" class="form-control" id="especialidad" name="especialidad"
                    value="<?= htmlspecialchars($convenio['especialidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" id="descuento" name="descuento"
                    value="<?= htmlspecialchars($convenio['descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-8 mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($convenio['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Crear' ?></button>
        <a href="dashboard.php?vista=convenios" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
