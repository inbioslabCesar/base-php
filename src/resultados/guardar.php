<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$examenes = $_POST['examenes'] ?? [];
$cotizacion_id = $_POST['cotizacion_id'] ?? null;
$referencia_personalizada = trim($_POST['referencia_personalizada'] ?? '');

if (!empty($examenes) && is_array($examenes)) {
    foreach ($examenes as $examen) {
        $id_resultado = $examen['id_resultado'] ?? null;
        $resultados = $examen['resultados'] ?? [];
        $imprimir_examen = isset($examen['imprimir_examen']) ? 1 : 0;
        $resultados['imprimir_examen'] = $imprimir_examen;
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
    
    // Guardar referencia personalizada si fue proporcionada
    if ($cotizacion_id && $referencia_personalizada !== '') {
        // Verificar si ya existe una referencia personalizada para esta cotización
        $sql_check = "SELECT COUNT(*) FROM cotizaciones WHERE id = :cotizacion_id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['cotizacion_id' => $cotizacion_id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            // Actualizar la cotización con la referencia personalizada
            $sql_ref = "UPDATE cotizaciones SET referencia_personalizada = :referencia WHERE id = :cotizacion_id";
            $stmt_ref = $pdo->prepare($sql_ref);
            $stmt_ref->execute([
                'referencia' => $referencia_personalizada,
                'cotizacion_id' => $cotizacion_id
            ]);
        }
    } elseif ($cotizacion_id && $referencia_personalizada === '') {
        // Si el campo está vacío, limpiar la referencia personalizada
        $sql_clear = "UPDATE cotizaciones SET referencia_personalizada = NULL WHERE id = :cotizacion_id";
        $stmt_clear = $pdo->prepare($sql_clear);
        $stmt_clear->execute(['cotizacion_id' => $cotizacion_id]);
    }
    
    // Redirige al dashboard a la vista de cotizaciones (o ajusta la ruta según prefieras)
    header("Location: dashboard.php?vista=cotizaciones&mensaje=Resultados guardados correctamente");
    exit;
} else {
    echo "Error: No se recibieron datos válidos.";
}
?>