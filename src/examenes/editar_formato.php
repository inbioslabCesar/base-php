<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener el ID del examen
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = 'ID de examen no especificado.';
    header('Location: dashboard.php?vista=examenes');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adicional = $_POST['adicional'] ?? '';

    // Guardar para depuración (puedes quitarlo en producción)
    file_put_contents('debug_adicional.txt', $adicional);

    // Validar que el campo no esté vacío
    if (empty($adicional)) {
        $_SESSION['error'] = 'El campo adicional está vacío.';
        header('Location: dashboard.php?vista=vista_editar_formato&id=' . urlencode($id));
        exit;
    }

    // Validar que sea JSON válido
    json_decode($adicional);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error'] = 'El formato de parámetros adicionales no es válido.';
        header('Location: dashboard.php?vista=vista_editar_formato&id=' . urlencode($id));
        exit;
    }

    // Guardar en la base de datos
    $stmt = $pdo->prepare('UPDATE examenes SET adicional = ? WHERE id = ?');
    $stmt->execute([$adicional, $id]);
    $_SESSION['exito'] = 'Formato actualizado correctamente.';
    header('Location: dashboard.php?vista=examenes');
    exit;
}
?>
