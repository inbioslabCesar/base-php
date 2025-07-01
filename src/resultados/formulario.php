<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id_resultado = $_GET['id_resultado'] ?? null;

// Obtener datos del resultado y del examen
$sql = "SELECT re.resultados, e.adicional
        FROM resultados_examenes re
        JOIN examenes e ON re.id_examen = e.id
        WHERE re.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id_resultado]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$resultados = $row && $row['resultados'] ? json_decode($row['resultados'], true) : [];
$adicional = $row && $row['adicional'] ? json_decode($row['adicional'], true) : [];
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
    <h2>Llenar Resultados</h2>
   <form method="post" action="dashboard.php?action=guardar">
    <input type="hidden" name="id_resultado" value="<?= htmlspecialchars($id_resultado) ?>">
    <?php foreach ($adicional as $item): ?>
        <?php if ($item['tipo'] === 'Título'): ?>
            <h3 style="background:<?= $item['color_fondo'] ?>;color:<?= $item['color_texto'] ?>;font-weight:bold;">
                <?= htmlspecialchars($item['nombre']) ?>
            </h3>
        <?php elseif ($item['tipo'] === 'Subtítulo'): ?>
            <h5 style="background:<?= $item['color_fondo'] ?>;color:<?= $item['color_texto'] ?>;">
                <?= htmlspecialchars($item['nombre']) ?>
            </h5>
        <?php elseif ($item['tipo'] === 'Parámetro'): ?>
            <div class="mb-3" style="background:<?= $item['color_fondo'] ?>;color:<?= $item['color_texto'] ?>;">
                <label><strong><?= htmlspecialchars($item['nombre']) ?></strong>
                    <?php if (!empty($item['unidad'])): ?>
                        (<?= htmlspecialchars($item['unidad']) ?>)
                    <?php endif; ?>
                </label>
                <?php if (!empty($item['opciones'])): ?>
                    <select name="resultados[<?= htmlspecialchars($item['nombre']) ?>]" class="form-control">
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
                        name="resultados[<?= htmlspecialchars($item['nombre']) ?>]"
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
    <button type="submit" class="btn btn-success">Guardar Resultados</button>
</form>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.campo-calculado[data-formula]').forEach(function(calculado) {
    let formula = calculado.getAttribute('data-formula');
    let variables = formula.match(/\[([^\]]+)\]/g) || [];

    function calcular() {
      let expr = formula;
      variables.forEach(function(variable) {
        let nombre = variable.replace(/[\[\]]/g, '').trim();
        let input = document.querySelector(`[name="resultados[${nombre}]"]`);
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
      let input = document.querySelector(`[name="resultados[${nombre}]"]`);
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
