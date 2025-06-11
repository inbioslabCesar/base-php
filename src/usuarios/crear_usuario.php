<?php
require_once __DIR__ . '/../conexion/conexion.php';

$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$dni = $_POST['dni'] ?? '';
$sexo = $_POST['sexo'] ?? null;
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$email = $_POST['email'] ?? '';
$telefono = $_POST['telefono'] ?? null;
$direccion = $_POST['direccion'] ?? null;
$cargo = $_POST['cargo'] ?? null;
$profesion = $_POST['profesion'] ?? null;
$rol = $_POST['rol'] ?? '';
$estado = $_POST['estado'] ?? 'activo';
$password = $_POST['password'] ?? '';

if ($nombre && $apellido && $dni && $email && $rol && $password) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, dni, sexo, fecha_nacimiento, email, telefono, direccion, cargo, profesion, rol, estado, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
            mb_convert_case($apellido, MB_CASE_TITLE, "UTF-8"),
            $dni,
            $sexo,
            $fecha_nacimiento,
            $email,
            $telefono,
            $direccion,
            $cargo,
            $profesion,
            $rol,
            $estado,
            $hash
        ]);

        $_SESSION['mensaje'] = "Usuario creado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear el usuario: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Faltan datos obligatorios.";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
