<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);

$examen = [
    'codigo' => '',
    'nombre' => '',
    'descripcion' => '',
    'area' => '',
    'metodologia' => '',
    'tiempo_respuesta' => '',
    'preanalitica_cliente' => '',
    'preanalitica_referencias' => '',
    'tipo_muestra' => '',
    'tipo_tubo' => '',
    'observaciones' => '',
    'precio_publico' => '',
    'adicional' => '',
    'vigente' => 1
];

if ($esEdicion) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
    $stmt->execute([$id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) {
        $_SESSION['mensaje'] = "Examen no encontrado.";
        header('Location: dashboard.php?vista=examenes');
        exit;
    }
}

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Examen' : 'Agregar Examen' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_examen&id=' . $_GET['id'] : 'crear_examen' ?>">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="codigo" class="form-label">Código *</label>
                <input type="text" class="form-control" id="codigo" name="codigo" required
                    value="<?= htmlspecialchars($examen['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required
                    value="<?= htmlspecialchars(capitalizar($examen['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="area" class="form-label">Área</label>
                <input type="text" class="form-control" id="area" name="area"
                    value="<?= htmlspecialchars(capitalizar($examen['area'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="metodologia" class="form-label">Metodología</label>
                <input type="text" class="form-control" id="metodologia" name="metodologia"
                    value="<?= htmlspecialchars(capitalizar($examen['metodologia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="tiempo_respuesta" class="form-label">Tiempo Respuesta</label>
                <input type="text" class="form-control" id="tiempo_respuesta" name="tiempo_respuesta"
                    value="<?= htmlspecialchars($examen['tiempo_respuesta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="preanalitica_cliente" class="form-label">Preanalítica Cliente</label>
                <input type="text" class="form-control" id="preanalitica_cliente" name="preanalitica_cliente"
                    value="<?= htmlspecialchars($examen['preanalitica_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="preanalitica_referencias" class="form-label">Preanalítica Referencias</label>
                <input type="text" class="form-control" id="preanalitica_referencias" name="preanalitica_referencias"
                    value="<?= htmlspecialchars($examen['preanalitica_referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="tipo_muestra" class="form-label">Tipo de Muestra</label>
                <input type="text" class="form-control" id="tipo_muestra" name="tipo_muestra"
                    value="<?= htmlspecialchars($examen['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="tipo_tubo" class="form-label">Tipo de Tubo</label>
                <input type="text" class="form-control" id="tipo_tubo" name="tipo_tubo"
                    value="<?= htmlspecialchars($examen['tipo_tubo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="precio_publico" class="form-label">Precio Público *</label>
                <input type="number" step="0.01" min="0" class="form-control" id="precio_publico" name="precio_publico" required
                    value="<?= htmlspecialchars($examen['precio_publico'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="vigente" class="form-label">Vigente</label>
                <select class="form-select" id="vigente" name="vigente">
                    <option value="1" <?= (isset($examen['vigente']) && $examen['vigente'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($examen['vigente']) && $examen['vigente'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="adicional" class="form-label">Adicional</label>
                <input type="text" class="form-control" id="adicional" name="adicional"
                    value="<?= htmlspecialchars($examen['adicional'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($examen['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Crear' ?></button>
        <a href="dashboard.php?vista=examenes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
