<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo los campos obligatorios

$codigo_cliente    = trim($_POST['codigo_cliente'] ?? '');
$nombre            = trim($_POST['nombre'] ?? '');
$apellido          = trim($_POST['apellido'] ?? '');
$dni               = trim($_POST['dni'] ?? '');
$tipo_documento    = $_POST['tipo_documento'] ?? 'dni';
$edad_valor        = trim($_POST['edad_valor'] ?? '');
$edad_unidad       = trim($_POST['edad_unidad'] ?? '');
$edad = ($edad_valor !== '' && $edad_unidad !== '') ? (intval($edad_valor) . ' ' . $edad_unidad) : '';
$email             = trim($_POST['email'] ?? '');
$password          = $_POST['password'] ?? '';
$telefono          = trim($_POST['telefono'] ?? '');
$direccion         = trim($_POST['direccion'] ?? '');
$sexo              = $_POST['sexo'] ?? '';
$fecha_nacimiento  = $_POST['fecha_nacimiento'] ?? null;
$estado            = $_POST['estado'] ?? 'activo';
$descuento         = $_POST['descuento'] ?? null;
$procedencia       = trim($_POST['procedencia'] ?? '');
$rol_creador       = $_SESSION['rol'] ?? 'desconocido';

// Nuevos campos para empresa/convenio y tipo_registro
$empresa_nombre   = $_SESSION['empresa_nombre'] ?? null;
$convenio_nombre = $_SESSION['convenio_nombre'] ?? null;
$tipo_registro   = 'cliente'; // Valor por defecto

if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_nombre'])) {
    $empresa_nombre = $_SESSION['empresa_nombre'];
    $tipo_registro = 'empresa';
}
if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_nombre'])) {
    $convenio_nombre = $_SESSION['convenio_nombre'];
    $tipo_registro = 'convenio';
}

// Validación de requeridos

// Si el DNI está vacío, generar uno provisional de 8 dígitos

if (!$dni) {
    $dni = str_pad(strval(mt_rand(0, 99999999)), 8, '0', STR_PAD_LEFT);
}

// Si el email está vacío, generar uno provisional usando el DNI

if (!$email) {
    $email = $dni . '@medditech.com';
}

// Si la contraseña está vacía, asignar el DNI como contraseña
if (!$password) {
    $password = $dni;
}

if (!$codigo_cliente || !$nombre || !$apellido || !$email || !$password) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=form_cliente');
    exit;
}

// Validar DNI único
$stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ?");
$stmt->execute([$dni]);
if ($stmt->fetch()) {
    header('Location: ../dashboard.php?vista=form_cliente&error=dni_duplicado');
    exit;
}

// Capitaliza nombre y apellido
function capitalize($string) {
    return mb_convert_case(strtolower(trim($string)), MB_CASE_TITLE, "UTF-8");
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO clientes 
        (codigo_cliente, nombre, apellido, dni, tipo_documento, edad, email, password, telefono, direccion, sexo, fecha_nacimiento, estado, descuento, procedencia, rol_creador, empresa_nombre, convenio_nombre, tipo_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $codigo_cliente,
        capitalize($nombre),
        capitalize($apellido),
        $dni,
        $tipo_documento,
        $edad,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $telefono ?: null,
        $direccion ?: null,
        $sexo ?: null,
        $fecha_nacimiento ?: null,
        $estado,
        $descuento !== '' ? $descuento : null,
        $procedencia !== '' ? $procedencia : null,
        $rol_creador,
        $empresa_nombre,
        $convenio_nombre,
        $tipo_registro
    ]);

    $id_cliente_nuevo = $pdo->lastInsertId();

    // Asociación automática y redirección según rol
    if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_id'])) {
        $stmt = $pdo->prepare("INSERT INTO empresa_cliente (empresa_id, cliente_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['empresa_id'], $id_cliente_nuevo]);
        $_SESSION['msg'] = 'Cliente registrado y asociado correctamente.';
        header('Location: ../dashboard.php?vista=clientes_empresa');
        exit;
    }
    if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_id'])) {
        $stmt = $pdo->prepare("INSERT INTO convenio_cliente (convenio_id, cliente_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['convenio_id'], $id_cliente_nuevo]);
        $_SESSION['msg'] = 'Cliente registrado y asociado correctamente.';
        header('Location: ../dashboard.php?vista=clientes_convenio');
        exit;
    }

    $_SESSION['msg'] = 'Cliente registrado correctamente.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al registrar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=form_cliente');
    exit;
}
