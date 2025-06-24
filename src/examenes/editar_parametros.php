<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">ID de examen no especificado.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
$stmt->execute([$id]);
$examen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
    echo '<div class="alert alert-danger">Examen no encontrado.</div>';
    exit;
}

$parametros = [];
if (!empty($examen['adicional'])) {
    $parametros = json_decode($examen['adicional'], true);
}

$mensaje_error = $_SESSION['mensaje_error'] ?? '';
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Editar parámetros de: <?= htmlspecialchars($examen['nombre']) ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                    <?php endif; ?>

                    <form action="dashboard.php?action=guardar_edicion_parametros" method="POST" id="form-editar-parametros">
                        <input type="hidden" name="id" value="<?= $examen['id'] ?>">
                        <div id="parametros">
                            <?php foreach ($parametros as $i => $p): ?>
                                <div class="row parametro-row align-items-end mb-2">
                                    <div class="col-md-3">
                                        <input type="text" name="parametros[nombre][]" class="form-control" placeholder="Nombre" value="<?= htmlspecialchars($p['parametro'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="parametros[unidad][]" class="form-control" placeholder="Unidad" value="<?= htmlspecialchars($p['unidad'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="parametros[valor_referencia][]" class="form-control" placeholder="Valor de referencia" value="<?= htmlspecialchars($p['valor'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="parametros[calculado][]" class="form-select" onchange="toggleFormula(this)">
                                            <option value="0" <?= !empty($p['calculado']) ? 'selected' : '' ?>>Procesado</option>
                                            <option value="1" <?= !empty($p['calculado']) ? 'selected' : '' ?>>Calculado</option>
                                        </select>                                        

                                    </div>
                                    <div class="col-md-2 formula-group" style="<?= !empty($p['calculado']) ? '' : 'display:none;' ?>">
                                        <input type="text" name="parametros[formula][]" class="form-control" placeholder="Fórmula" value="<?= htmlspecialchars($p['formula'] ?? '') ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                        <a href="dashboard.php?vista=examenes" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleFormula(select) {
        const formulaGroup = select.closest('.parametro-row').querySelector('.formula-group');
        if (select.value === "1") {
            formulaGroup.style.display = "";
            formulaGroup.querySelector('input').required = true;
        } else {
            formulaGroup.style.display = "none";
            formulaGroup.querySelector('input').value = "";
            formulaGroup.querySelector('input').required = false;
        }
    }
</script>