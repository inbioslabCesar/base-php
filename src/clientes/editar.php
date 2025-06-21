<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['msg'] = 'ID de cliente no proporcionado.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}

// Campos requeridos
$codigo_cliente = trim($_POST['codigo_cliente'] ?? '');
$nombre         = trim($_POST['nombre'] ?? '');
$apellido       = trim($_POST['apellido'] ?? '');
$dni            = trim($_POST['dni'] ?? '');
$edad           = trim($_POST['edad'] ?? '');
$email          = trim($_POST['email'] ?? '');

// Campos opcionales
$password       = $_POST['password'] ?? '';
$telefono       = trim($_POST['telefono'] ?? '');
$direccion      = trim($_POST['direccion'] ?? '');
$sexo           = $_POST['sexo'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$estado         = $_POST['estado'] ?? 'activo';
$descuento      = $_POST['descuento'] ?? null;

// Validación de requeridos
if (!$codigo_cliente || !$nombre || !$apellido || !$dni || !$edad || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
    exit;
}

// Capitaliza nombre y apellido
function capitalize($string) {
    return mb_convert_case(strtolower(trim($string)), MB_CASE_TITLE, "UTF-8");
}

try {
    if ($password) {
        $sql = "UPDATE clientes SET 
            codigo_cliente=?, nombre=?, apellido=?, dni=?, edad=?, email=?, password=?, telefono=?, direccion=?, sexo=?, fecha_nacimiento=?, estado=?, descuento=?
            WHERE id=?";
        $params = [
            $codigo_cliente,
            capitalize($nombre),
            capitalize($apellido),
            $dni,
            $edad,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $telefono ?: null,
            $direccion ?: null,
            $sexo ?: null,
            $fecha_nacimiento ?: null,
            $estado,
            $descuento !== '' ? $descuento : null,
            $id
        ];
    } else {
        $sql = "UPDATE clientes SET 
            codigo_cliente=?, nombre=?, apellido=?, dni=?, edad=?, email=?, telefono=?, direccion=?, sexo=?, fecha_nacimiento=?, estado=?, descuento=?
            WHERE id=?";
        $params = [
            $codigo_cliente,
            capitalize($nombre),
            capitalize($apellido),
            $dni,
            $edad,
            $email,
            $telefono ?: null,
            $direccion ?: null,
            $sexo ?: null,
            $fecha_nacimiento ?: null,
            $estado,
            $descuento !== '' ? $descuento : null,
            $id
        ];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['msg'] = 'Cliente actualizado correctamente.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al actualizar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
    exit;
}
