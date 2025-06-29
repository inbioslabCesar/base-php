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
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Parámetro</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($valores as $param => $valor) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($param) . '</td>';
            echo '<td>' . htmlspecialchars($valor) . '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
    <a href="listado.php" class="btn btn-secondary">Volver al listado</a>
</div>
