<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$codigo_cliente = $_POST['codigo_cliente'] ?? '';
$sexo = $_POST['sexo'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$direccion = $_POST['direccion'] ?? null;
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$estado = $_POST['estado'] ?? 'activo';
$descuento = $_POST['descuento'] ?? null;

if ($id && $nombre && $apellido && $email) {
    try {
        // Si hay nueva contraseña, actualiza también el password
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE clientes SET codigo_cliente=?, nombre=?, apellido=?, email=?, password=?, sexo=?, telefono=?, direccion=?, fecha_nacimiento=?, estado=?, descuento=? WHERE id=?");
            $stmt->execute([
                $codigo_cliente,
                mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
                mb_convert_case($apellido, MB_CASE_TITLE, "UTF-8"),
                $email,
                $hash,
                $sexo,
                $telefono,
                $direccion,
                $fecha_nacimiento,
                $estado,
                $descuento,
                $id
            ]);
        } else {
            // Si no se cambia la contraseña
            $stmt = $pdo->prepare("UPDATE clientes SET codigo_cliente=?, nombre=?, apellido=?, email=?, sexo=?, telefono=?, direccion=?, fecha_nacimiento=?, estado=?, descuento=? WHERE id=?");
            $stmt->execute([
                $codigo_cliente,
                mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
                mb_convert_case($apellido, MB_CASE_TITLE, "UTF-8"),
                $email,
                $sexo,
                $telefono,
                $direccion,
                $fecha_nacimiento,
                $estado,
                $descuento,
                $id
            ]);
        }
        $_SESSION['mensaje'] = "Cliente actualizado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar el cliente: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el cliente.";
}

header('Location: dashboard.php?vista=clientes');
exit;
?>
