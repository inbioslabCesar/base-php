<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Consulta básica, ajústala según tus necesidades de JOIN y filtros
$sql = "SELECT re.id, e.nombre AS examen, c.nombre AS cliente, re.estado, re.fecha_ingreso
        FROM resultados_examenes re
        JOIN examenes e ON re.id_examen = e.id
        JOIN clientes c ON re.id_cliente = c.id
        ORDER BY re.fecha_ingreso DESC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['acciones'] = '<a href="formulario.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary">Editar</a>';
    $data[] = $row;
}

echo json_encode(['data' => $data]);
?>
