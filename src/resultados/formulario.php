<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta el resultado de examen
$sql = "SELECT re.*, e.nombre AS nombre_examen, c.nombre AS nombre_cliente
        FROM resultados_examenes re
        JOIN examenes e ON re.id_examen = e.id
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$resultado = $res->fetch_assoc();

if (!$resultado) {
    echo "<div class='alert alert-danger'>No se encontró el registro.</div>";
    exit;
}

// Decodifica los resultados (si existen)
$valores = [];
if (!empty($resultado['resultados'])) {
    $valores = json_decode($resultado['resultados'], true);
}
?>

<div class="container">
    <h3>Resultados de <?= htmlspecialchars($resultado['nombre_examen']) ?> - Cliente: <?= htmlspecialchars($resultado['nombre_cliente']) ?></h3>
    <form id="formResultados" method="post">
        <input type="hidden" name="id" value="<?= $resultado['id'] ?>">
        <?php
        // Aquí puedes generar dinámicamente los campos según los parámetros del examen
        // Por ejemplo:
        foreach ($valores as $param => $valor) {
            echo '<div class="mb-3">';
            echo '<label class="form-label">' . htmlspecialchars($param) . '</label>';
            echo '<input type="text" class="form-control" name="resultados[' . htmlspecialchars($param) . ']" value="' . htmlspecialchars($valor) . '">';
            echo '</div>';
        }
        ?>
        <button type="submit" class="btn btn-success">Guardar resultados</button>
    </form>
</div>

<script>
$('#formResultados').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'guardar.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(respuesta) {
            alert('Resultados guardados correctamente');
            window.location.href = 'listado.php';
        },
        error: function() {
            alert('Ocurrió un error al guardar');
        }
    });
});
</script>
