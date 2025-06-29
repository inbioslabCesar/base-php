<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $resultados = isset($_POST['resultados']) ? $_POST['resultados'] : [];

    // Validar que los resultados sean un array
    if (!is_array($resultados)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos de resultados inválidos']);
        exit;
    }

    // Codificar los resultados como JSON
    $json_resultados = json_encode($resultados, JSON_UNESCAPED_UNICODE);

    // Actualizar en la base de datos
    $sql = "UPDATE resultados_examenes SET resultados = ?, estado = 'completado' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $json_resultados, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo guardar']);
    }
}
?>
