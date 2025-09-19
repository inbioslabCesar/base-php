<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
$examenes = [];

if ($cotizacion_id) {
    // Obtener todos los exámenes y resultados asociados a la cotización
    $sql = "SELECT re.id as id_resultado, re.resultados, e.adicional, e.nombre as nombre_examen
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = :cotizacion_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cotizacion_id' => $cotizacion_id]);
    $examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Llenar Resultados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary mb-3">
        ← Volver a Cotizaciones
    </a>
    <h2 class="mb-4">Llenar o Editar Resultados de Exámenes</h2>

    <?php if (!empty($examenes)): ?>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            <?php foreach ($examenes as $examen): 
                $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];
                $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
            ?>
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><?= htmlspecialchars($examen['nombre_examen']) ?></span>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="examenes[<?= $examen['id_resultado'] ?>][imprimir_examen]" id="imprimir_examen_<?= $examen['id_resultado'] ?>" value="1"
                                <?= (!isset($resultados['imprimir_examen']) || $resultados['imprimir_examen']) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="imprimir_examen_<?= $examen['id_resultado'] ?>">Imprimir este examen</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][id_resultado]" value="<?= htmlspecialchars($examen['id_resultado']) ?>">
                        <?php foreach ($adicional as $item): ?>
                            <?php if ($item['tipo'] === 'Título' || $item['tipo'] === 'Subtítulo'): ?>
                                <<?= $item['tipo'] === 'Título' ? 'h3' : 'h5' ?> 
                                    style="background:<?= $item['color_fondo'] ?>;color:<?= $item['color_texto'] ?>;font-weight:bold;margin-bottom:0;"
                                    class="<?= $item['tipo'] === 'Título' ? 'text-center' : '' ?>"
                                >
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </<?= $item['tipo'] === 'Título' ? 'h3' : 'h5' ?>>
                            <?php elseif ($item['tipo'] === 'Campo'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?= htmlspecialchars($item['nombre']) ?></label>
                                    <input type="text"
                                        class="form-control"
                                        name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                        value="<?= htmlspecialchars($resultados[$item['nombre']] ?? '') ?>">
                                </div>
                            <?php elseif ($item['tipo'] === 'Parámetro'): ?>
                                <div class="mb-3" style="background:<?= $item['color_fondo'] ?>;color:<?= $item['color_texto'] ?>;">
                                    <label><strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                        <?php if (!empty($item['unidad'])): ?>
                                            (<?= htmlspecialchars($item['unidad']) ?>)
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!empty($item['opciones'])): ?>
                                        <select name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]" class="form-control">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($item['opciones'] as $opcion): ?>
                                                <option value="<?= htmlspecialchars($opcion) ?>"
                                                    <?= (isset($resultados[$item['nombre']]) && $resultados[$item['nombre']] == $opcion) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($opcion) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input
                                            type="text"
                                            name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                            class="form-control<?= !empty($item['formula']) ? ' campo-calculado' : '' ?>"
                                            value="<?= isset($resultados[$item['nombre']]) ? htmlspecialchars($resultados[$item['nombre']]) : '' ?>"
                                            <?= !empty($item['formula']) ? 'data-formula="' . htmlspecialchars($item['formula']) . '" readonly' : '' ?>
                                        >
                                    <?php endif; ?>
                                    <?php if (!empty($item['referencias'])): ?>
                                        <small class="form-text text-muted">
                                            Referencia:
                                            <?php foreach ($item['referencias'] as $ref): ?>
                                                <?= htmlspecialchars($ref['desc'] . ' ' . $ref['valor']) ?>
                                            <?php endforeach; ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($item['metodologia'])): ?>
                                        <small class="form-text text-info">Metodología: <?= htmlspecialchars($item['metodologia']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success">Guardar Resultados</button>
        </form>
    <?php else: ?>
        <div class="alert alert-info">No hay exámenes asociados a esta cotización.</div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.campo-calculado[data-formula]').forEach(function(calculado) {
    let formula = calculado.getAttribute('data-formula');
    let variables = formula.match(/\[([^\]]+)\]/g) || [];
    // Obtener el id_resultado del campo actual
    let nameParts = calculado.name.match(/examenes\[(\d+)\]\[resultados\]\[([^\]]+)\]/);
    let idResultado = nameParts ? nameParts[1] : null;

    function calcular() {
      let expr = formula;
      variables.forEach(function(variable) {
        let nombre = variable.replace(/[\[\]]/g, '').trim();
        // Buscar el input solo dentro del mismo examen
        let input = document.querySelector(`[name="examenes[${idResultado}][resultados][${nombre}]"]`);
        let val = input && input.value ? parseFloat(input.value) : 0;
        expr = expr.replaceAll(variable, val);
      });
      try {
        let resultado = eval(expr);
        calculado.value = (!isFinite(resultado) || isNaN(resultado)) ? '' : resultado.toFixed(1);
      } catch (e) {
        calculado.value = '';
      }
    }

    variables.forEach(function(variable) {
      let nombre = variable.replace(/[\[\]]/g, '').trim();
      let input = document.querySelector(`[name="examenes[${idResultado}][resultados][${nombre}]"]`);
      if (input) {
        input.addEventListener('input', calcular);
      }
    });

    calcular();
  });
});
</script>
</body>
</html>