<?php
require_once __DIR__ . '/../conexion/conexion.php';

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

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$examen) {
        $_SESSION['mensaje'] = "Examen no encontrado";
        header('Location: dashboard.php?vista=examenes');
        exit;
    }
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Examen' : 'Agregar Examen' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_examen&id=' . htmlspecialchars($_GET['id']) : 'crear_examen' ?>">
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
        <table class="table table-bordered" id="parametros-section">
            <thead>
                <tr>
                    <th>Nombre del parámetro</th>
                    <th>Valor de referencia</th>
                    <th>Unidades</th>
                    <th>Tipo</th>
                    <th>Fórmula</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $parametros = [];
                if (!empty($examen['adicional'])) {
                    $parametros = json_decode($examen['adicional'], true);
                }
                if (!empty($parametros)) {
                    foreach ($parametros as $p) {
                        ?>
                        <tr class="parametro-item">
                            <td>
                                <input type="text" class="form-control parametro-nombre" name="parametro_nombre[]" value="<?= htmlspecialchars($p['parametro'] ?? '') ?>" required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="parametro_valor[]" value="<?= htmlspecialchars($p['valor'] ?? '') ?>" required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="parametro_unidad[]" value="<?= htmlspecialchars($p['unidad'] ?? '') ?>">
                            </td>
                            <td>
                                <select class="form-select parametro-tipo" name="parametro_tipo[]">
                                    <option value="Procesado" <?= (isset($p['calculado']) && $p['calculado'] === 'Procesado') ? 'selected' : '' ?>>Procesado</option>
                                    <option value="Calculado" <?= (isset($p['calculado']) && $p['calculado'] === 'Calculado') ? 'selected' : '' ?>>Calculado</option>
                                </select>
                            </td>
                            <td>
                                <div class="formula-wrapper" style="<?= (isset($p['calculado']) && $p['calculado'] === 'Calculado') ? '' : 'display:none;' ?>">
                                    <input type="text" class="form-control parametro-formula" name="parametro_formula[]" value="<?= htmlspecialchars($p['formula'] ?? '') ?>">
                                    <div class="mt-2 formula-botones"></div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); actualizarBotonesFormula();">Eliminar</button>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr class="parametro-item">
                        <td>
                            <input type="text" class="form-control parametro-nombre" name="parametro_nombre[]" required>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="parametro_valor[]" required>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="parametro_unidad[]">
                        </td>
                        <td>
                            <select class="form-select parametro-tipo" name="parametro_tipo[]">
                                <option value="Procesado">Procesado</option>
                                <option value="Calculado">Calculado</option>
                            </select>
                        </td>
                        <td>
                            <div class="formula-wrapper" style="display:none;">
                                <input type="text" class="form-control parametro-formula" name="parametro_formula[]">
                                <div class="mt-2 formula-botones"></div>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); actualizarBotonesFormula();">Eliminar</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-success" onclick="agregarParametro()">Agregar otro parámetro</button>        
        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Actualizar' : 'Agregar' ?></button>
        <a href="dashboard.php?vista=examenes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
function agregarParametro() {
    const html = `
    <tr class="parametro-item">
      <td>
        <input type="text" class="form-control parametro-nombre" name="parametro_nombre[]" required>
      </td>
      <td>
        <input type="text" class="form-control" name="parametro_valor[]" required>
      </td>
      <td>
        <input type="text" class="form-control" name="parametro_unidad[]">
      </td>
      <td>
        <select class="form-select parametro-tipo" name="parametro_tipo[]">
          <option value="Procesado">Procesado</option>
          <option value="Calculado">Calculado</option>
        </select>
      </td>
      <td>
        <div class="formula-wrapper" style="display:none;">
          <input type="text" class="form-control parametro-formula" name="parametro_formula[]">
          <div class="mt-2 formula-botones"></div>
        </div>
      </td>
      <td>
        <button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); actualizarBotonesFormula();">Eliminar</button>
      </td>
    </tr>`;
    document.querySelector('#parametros-section tbody').insertAdjacentHTML('beforeend', html);
    actualizarBotonesFormula();
}

function actualizarBotonesFormula() {
    // Obtén todos los nombres de los parámetros
    const nombres = Array.from(document.querySelectorAll('.parametro-nombre')).map(input => input.value.trim()).filter(Boolean);

    // Para cada parámetro, coloca los botones de los demás parámetros
    document.querySelectorAll('.parametro-item').forEach((item, idx) => {
        const tipo = item.querySelector('.parametro-tipo')?.value;
        const formulaWrapper = item.querySelector('.formula-wrapper');
        if (formulaWrapper) {
            formulaWrapper.style.display = (tipo === 'Calculado') ? '' : 'none';
        }
        const formulaDiv = item.querySelector('.formula-botones');
        if (!formulaDiv) return;
        formulaDiv.innerHTML = '';
        if (tipo === 'Calculado') {
            nombres.forEach((nombre, i) => {
                if (i !== idx) { // No agregues botón para sí mismo
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-primary btn-sm me-1 mb-1';
                    btn.textContent = nombre;
                    btn.onclick = function() {
                        const formulaInput = item.querySelector('.parametro-formula');
                        if (formulaInput) {
                            formulaInput.value += (formulaInput.value ? ' ' : '') + nombre;
                        }
                    };
                    formulaDiv.appendChild(btn);
                }
            });
        }
    });
}

// Cambia visibilidad de fórmula y actualiza botones cuando cambia el tipo
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('parametro-tipo')) {
        actualizarBotonesFormula();
    }
});

// También actualiza cuando cambia el nombre
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('parametro-nombre')) {
        actualizarBotonesFormula();
    }
});

// Llama al cargar la página
document.addEventListener('DOMContentLoaded', actualizarBotonesFormula);
</script>
