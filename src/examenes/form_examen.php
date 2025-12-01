<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';

function capitalizar($texto)
{
    return mb_convert_case(trim($texto), MB_CASE_TITLE, "UTF-8");
}

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

$adicional_array = [];
if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) {
        $_SESSION['mensaje'] = "Examen no encontrado";
        header('Location: dashboard.php?vista=examenes');
        exit;
    }
    $adicional = $examen['adicional'] ?? '';
    $adicional_array = $adicional ? json_decode($adicional, true) : [];
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Examen' : 'Agregar Examen' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_examen&id=' . htmlspecialchars($_GET['id']) : 'crear_examen' ?>" id="form-examen">
        <!-- Campos básicos -->
        <div class="mb-3">
            <label for="codigo" class="form-label">Código *</label>
            <input type="text" class="form-control" id="codigo" name="codigo" required
                value="<?= htmlspecialchars($examen['codigo'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required
                value="<?= htmlspecialchars(capitalizar($examen['nombre'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion"><?= htmlspecialchars($examen['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="area" class="form-label">Área *</label>
            <input type="text" class="form-control" id="area" name="area" required
                value="<?= htmlspecialchars(capitalizar($examen['area'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="metodologia" class="form-label">Metodología</label>
            <input type="text" class="form-control" id="metodologia" name="metodologia"
                value="<?= htmlspecialchars(capitalizar($examen['metodologia'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="tiempo_respuesta" class="form-label">Tiempo de Respuesta</label>
            <input type="text" class="form-control" id="tiempo_respuesta" name="tiempo_respuesta"
                value="<?= htmlspecialchars($examen['tiempo_respuesta'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="preanalitica_cliente" class="form-label">Condiciones Preanalíticas (Cliente)</label>
            <textarea class="form-control" id="preanalitica_cliente" name="preanalitica_cliente"><?= htmlspecialchars($examen['preanalitica_cliente'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="preanalitica_referencias" class="form-label">Condiciones Preanalíticas (Referencias)</label>
            <textarea class="form-control" id="preanalitica_referencias" name="preanalitica_referencias"><?= htmlspecialchars($examen['preanalitica_referencias'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="tipo_muestra" class="form-label">Tipo de Muestra</label>
            <input type="text" class="form-control" id="tipo_muestra" name="tipo_muestra"
                value="<?= htmlspecialchars(capitalizar($examen['tipo_muestra'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="tipo_tubo" class="form-label">Tipo de Tubo</label>
            <input type="text" class="form-control" id="tipo_tubo" name="tipo_tubo"
                value="<?= htmlspecialchars(capitalizar($examen['tipo_tubo'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones" name="observaciones"><?= htmlspecialchars($examen['observaciones'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="precio_publico" class="form-label">Precio Público *</label>
            <input type="number" class="form-control" id="precio_publico" name="precio_publico" min="0" step="0.01" required
                value="<?= htmlspecialchars($examen['precio_publico'] ?? '') ?>">
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="vigente" name="vigente" value="1" <?= ($examen['vigente'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="vigente">Examen Vigente</label>
        </div>

        <!-- Builder visual para parámetros adicionales -->
        <h4>Parámetros del Examen</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-editable" id="formatTable">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Metodología</th>
                        <th>Unidad</th>
                        <th>Opciones</th>
                        <th>Valor(es) Referencia</th>
                        <th>Fórmula</th>
                        <th>Negrita</th>
                        <th>Cursiva</th>
                        <th>Alineación</th>
                        <th>Color texto</th>
                        <th>Color fondo</th>
                        <th>Decimales</th>
                        <th>Orden</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
        <input type="hidden" id="adicional" name="adicional">
        <button id="addRow" class="btn btn-success mb-2" type="button">Agregar Fila</button>
        <h4>Vista Previa en Tiempo Real</h4>
        <div id="preview" class="border p-3"></div>

        <!-- Acciones -->
        <button class="btn btn-primary mt-3" type="submit" id="saveFormat"><?= $esEdicion ? 'Actualizar' : 'Agregar' ?></button>
        <a href="dashboard.php?vista=examenes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="<?= BASE_URL ?>examenes/format-builder.js"></script>
<script>
    // Cargar datos al editar
    var datosAdicionales = <?php echo json_encode($adicional_array, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof datosAdicionales !== "undefined" && Array.isArray(datosAdicionales) && datosAdicionales.length > 0) {
            datosAdicionales.forEach(function(parametro) {
                addRow(parametro);
            });
        }
    });
</script>
