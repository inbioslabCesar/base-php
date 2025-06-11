<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
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

if ($id && $nombre && $apellido && $dni && $email && $rol) {
    try {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, dni=?, sexo=?, fecha_nacimiento=?, email=?, telefono=?, direccion=?, cargo=?, profesion=?, rol=?, estado=?, password=? WHERE id=?");
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
                $hash,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, dni=?, sexo=?, fecha_nacimiento=?, email=?, telefono=?, direccion=?, cargo=?, profesion=?, rol=?, estado=? WHERE id=?");
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
                $id
            ]);
        }
        $_SESSION['mensaje'] = "Usuario actualizado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar el usuario: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el usuario.";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
