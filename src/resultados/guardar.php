<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$examenes = $_POST['examenes'] ?? [];

if (!empty($examenes) && is_array($examenes)) {
    foreach ($examenes as $examen) {
        $id_resultado = $examen['id_resultado'] ?? null;
        $resultados = $examen['resultados'] ?? [];
        if ($id_resultado && is_array($resultados)) {
            $json_resultados = json_encode($resultados, JSON_UNESCAPED_UNICODE);
            // Actualiza los resultados y el estado
            $sql = "UPDATE resultados_examenes SET resultados = :resultados, estado = 'completado' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'resultados' => $json_resultados,
                'id' => $id_resultado
            ]);
        }
    }
    // Redirige al dashboard a la vista de cotizaciones (o ajusta la ruta según prefieras)
    header("Location: dashboard.php?vista=cotizaciones&mensaje=Resultados guardados correctamente");
    exit;
} else {
    echo "Error: No se recibieron datos válidos.";
}
?>