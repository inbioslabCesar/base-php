<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$nombre = isset($_POST['nombre']) ? mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, "UTF-8") : '';
$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$especialidad = isset($_POST['especialidad']) ? mb_convert_case(trim($_POST['especialidad']), MB_CASE_TITLE, "UTF-8") : '';
$descuento = isset($_POST['descuento']) ? trim($_POST['descuento']) : null;
$descripcion = isset($_POST['descripcion']) ? mb_convert_case(trim($_POST['descripcion']), MB_CASE_TITLE, "UTF-8") : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$id || !$nombre || !$dni || !$email) {
    $_SESSION['mensaje'] = "Todos los campos requeridos deben completarse.";
    header('Location: dashboard.php?vista=form_convenio&id=' . $id);
    exit;
}

try {
    // Verificar email único (excepto para el propio registro)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El email ya está registrado en otro convenio.";
        header('Location: dashboard.php?vista=form_convenio&id=' . $id);
        exit;
    }

    // Actualizar datos básicos
    $sql = "UPDATE convenios SET nombre = ?, dni = ?, especialidad = ?, descuento = ?, descripcion = ?, email = ?";
    $params = [
        $nombre,
        $dni,
        $especialidad,
        $descuento !== '' ? $descuento : null,
        $descripcion,
        $email
    ];

    // Si se ingresó una nueva contraseña, actualizarla
    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['mensaje'] = "Convenio actualizado exitosamente.";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al actualizar: " . $e->getMessage();
}
header('Location: dashboard.php?vista=convenios');
exit;
