<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_examen = intval($_POST['id_examen']);
    $parametros = isset($_POST['parametros']) ? $_POST['parametros'] : [];

    if (!$id_examen || empty($parametros)) {
        $_SESSION['error'] = "Datos incompletos.";
        header('Location: dashboard.php?vista=form_examen&id=' . $id_examen);
        exit;
    }

    $adicional = json_encode(['parametros' => $parametros], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("UPDATE examenes SET adicional = ? WHERE id = ?");
    if ($stmt->execute([$adicional, $id_examen])) {
        $_SESSION['exito'] = "Parámetros actualizados correctamente.";
        header('Location: dashboard.php?vista=form_examen&id=' . $id_examen);
        exit;
    } else {
        $_SESSION['error'] = "Error al guardar.";
        header('Location: dashboard.php?vista=form_examen&id=' . $id_examen);
        exit;
    }
} else {
    header('Location: dashboard.php?vista=listar_examenes');
    exit;
}
