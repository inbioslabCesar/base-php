<?php
require_once __DIR__ . '/../conexion/conexion.php';

$ruc = $_POST['ruc'] ?? '';
$razon_social = $_POST['razon_social'] ?? '';
$nombre_comercial = $_POST['nombre_comercial'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$email = $_POST['email'] ?? '';
$representante = $_POST['representante'] ?? '';
$convenio = $_POST['convenio'] ?? '';
$estado = $_POST['estado'] ?? 'activo';
$descuento = $_POST['descuento'] ?? null;
$password = $_POST['password'] ?? '';

if ($ruc && $razon_social && $email && $password) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO empresas (ruc, razon_social, nombre_comercial, direccion, telefono, email, representante, password, convenio, estado, descuento, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $ruc,
            mb_convert_case($razon_social, MB_CASE_TITLE, "UTF-8"),
            mb_convert_case($nombre_comercial, MB_CASE_TITLE, "UTF-8"),
            $direccion,
            $telefono,
            $email,
            mb_convert_case($representante, MB_CASE_TITLE, "UTF-8"),
            $hash,
            $convenio,
            $estado,
            $descuento
        ]);

        $_SESSION['mensaje'] = "Empresa creada exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear la empresa: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Faltan datos obligatorios.";
}

header('Location: dashboard.php?vista=empresas');
exit;
?>
