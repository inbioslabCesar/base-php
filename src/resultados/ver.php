<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mensaje']) . '</div>';
    unset($_SESSION['mensaje']);
}

// Obtener IDs de la URL
$id_resultado = $_GET['id_resultado'] ?? null;
$id_examen = $_GET['id_examen'] ?? null;

if ($id_resultado && $id_examen) {
    // Obtener resultados guardados
    $sql = "SELECT resultados FROM resultados_examenes WHERE id = ? AND id_examen = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_resultado, $id_examen]);
    $resultados_json = $stmt->fetchColumn();

    // Obtener parámetros del examen
    $sql2 = "SELECT adicional FROM examenes WHERE id = ?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$id_examen]);
    $adicional_json = $stmt2->fetchColumn();

    $parametros = $adicional_json ? json_decode($adicional_json, true) : [];
    $resultados = $resultados_json ? json_decode($resultados_json, true) : [];

    echo "<h4>Resultados guardados</h4>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Parámetro</th><th>Valor</th><th>Unidad</th><th>Referencia</th></tr></thead><tbody>";
    foreach ($parametros as $param) {
        if ($param['tipo'] === 'Parámetro') {
            $nombre = htmlspecialchars($param['nombre']);
            $unidad = htmlspecialchars($param['unidad']);
            $referencia = isset($param['referencias'][0]['valor']) ? htmlspecialchars($param['referencias'][0]['valor']) : '';
            $valor = isset($resultados[$nombre]) ? htmlspecialchars($resultados[$nombre]) : '-';
            echo "<tr><td>$nombre</td><td>$valor</td><td>$unidad</td><td>$referencia</td></tr>";
        }
    }
    echo "</tbody></table>";
} else {
    echo '<div class="alert alert-warning">No se encontraron resultados para mostrar.</div>';
}
?>
