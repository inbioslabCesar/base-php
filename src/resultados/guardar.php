<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id_resultado = $_POST['id_resultado'] ?? null;
$resultados = $_POST['resultados'] ?? [];

if ($id_resultado && is_array($resultados)) {
    $json_resultados = json_encode($resultados, JSON_UNESCAPED_UNICODE);

    // Actualiza los resultados y el estado
    $sql = "UPDATE resultados_examenes SET resultados = :resultados, estado = 'completado' WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'resultados' => $json_resultados,
        'id' => $id_resultado
    ]);

    // Obtener el id_examen para la redirección
    $sql2 = "SELECT id_examen FROM resultados_examenes WHERE id = :id";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(['id' => $id_resultado]);
    $id_examen = $stmt2->fetchColumn();

    // Redirigir al dashboard con los parámetros correctos
    header("Location: dashboard.php?vista=formulario&id_examen=$id_examen&id_resultado=$id_resultado");
    exit;
} else {
    echo "Error: No se recibieron datos válidos.";
}
