<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Obtiene el ID del examen desde la URL
$id_examen = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id_examen) {
    $_SESSION['error'] = "Examen no válido.";
    header('Location: dashboard.php?vista=listar_examenes');
    exit;
}

// Obtiene los datos del examen
$stmt = $pdo->prepare("SELECT nombre, adicional FROM examenes WHERE id = ?");
$stmt->execute([$id_examen]);
$examen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
    $_SESSION['error'] = "Examen no encontrado.";
    header('Location: dashboard.php?vista=listar_examenes');
    exit;
}

// Decodifica los parámetros actuales si existen
$parametros = [];
if (!empty($examen['adicional'])) {
    $json = json_decode($examen['adicional'], true);
    if (isset($json['parametros'])) {
        $parametros = $json['parametros'];
    }
}
?>

<h2>Parámetros de: <?= htmlspecialchars($examen['nombre']) ?></h2>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['exito'])): ?>
    <div class="alert alert-success"><?= $_SESSION['exito']; unset($_SESSION['exito']); ?></div>
<?php endif; ?>

<form action="guardar_parametros.php" method="POST" id="formParametros">
    <input type="hidden" name="id_examen" value="<?= $id_examen ?>">
    <div id="parametros">
        <?php foreach ($parametros as $i => $p): ?>
            <div class="parametro">
                <input type="text" name="parametros[<?= $i ?>][nombre]" value="<?= htmlspecialchars($p['nombre']) ?>" placeholder="Nombre parámetro" required>
                <input type="text" name="parametros[<?= $i ?>][unidad]" value="<?= htmlspecialchars($p['unidad']) ?>" placeholder="Unidad" required>
                <input type="text" name="parametros[<?= $i ?>][referencia]" value="<?= htmlspecialchars($p['referencia']) ?>" placeholder="Valor de referencia" required>
                <button type="button" onclick="this.parentNode.remove()">Quitar</button>
                <br>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="agregarParametro()">Agregar parámetro</button><br><br>
    <button type="submit">Guardar parámetros</button>
</form>

<script>
let contador = <?= count($parametros) ?>;
function agregarParametro() {
    const div = document.createElement('div');
    div.className = 'parametro';
    div.innerHTML = `
      <input type="text" name="parametros[${contador}][nombre]" placeholder="Nombre parámetro" required>
      <input type="text" name="parametros[${contador}][unidad]" placeholder="Unidad" required>
      <input type="text" name="parametros[${contador}][referencia]" placeholder="Valor de referencia" required>
      <button type="button" onclick="this.parentNode.remove()">Quitar</button>
      <br>
    `;
    document.getElementById('parametros').appendChild(div);
    contador++;
}
</script>
