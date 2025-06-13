<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Recoger y validar datos
$nombre = isset($_POST['nombre']) ? mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, "UTF-8") : '';
$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$especialidad = isset($_POST['especialidad']) ? mb_convert_case(trim($_POST['especialidad']), MB_CASE_TITLE, "UTF-8") : '';
$descuento = isset($_POST['descuento']) ? trim($_POST['descuento']) : null;
$descripcion = isset($_POST['descripcion']) ? mb_convert_case(trim($_POST['descripcion']), MB_CASE_TITLE, "UTF-8") : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$nombre || !$dni || !$email || !$password) {
    $_SESSION['mensaje'] = "Todos los campos requeridos deben completarse.";
    header('Location: dashboard.php?vista=form_convenio');
    exit;
}

try {
    // Verificar email único
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El email ya está registrado.";
        header('Location: dashboard.php?vista=form_convenio');
        exit;
    }

    // Hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO convenios (nombre, dni, especialidad, descuento, descripcion, email, password)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $nombre,
        $dni,
        $especialidad,
        $descuento !== '' ? $descuento : null,
        $descripcion,
        $email,
        $passwordHash
    ]);
    $_SESSION['mensaje'] = "Convenio registrado exitosamente.";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al registrar: " . $e->getMessage();
}
header('Location: dashboard.php?vista=convenios');
exit;
