<?php
require_once __DIR__ . '/../conexion/conexion.php';

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

if ($nombre && $apellido && $email && $password) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO clientes (codigo_cliente, nombre, apellido, email, password, sexo, telefono, direccion, fecha_nacimiento, estado, descuento, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
            $descuento
        ]);
        
        $_SESSION['cliente_id'] = $pdo->lastInsertId();

        $_SESSION['mensaje'] = "Cliente creado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear el cliente: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Faltan datos obligatorios.";
}

header('Location: dashboard.php?vista=clientes');
exit;
?>
