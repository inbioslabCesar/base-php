<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener la fila actual de config_empresa
$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);
$id = $empresa ? $empresa['id'] : null;

// Recoge los datos del formulario
$nombre    = trim($_POST['nombre'] ?? '');
$ruc       = trim($_POST['ruc'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$celular   = trim($_POST['celular'] ?? '');

// Validación básica
if (!$nombre || !$ruc || !$direccion || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=config_empresa_datos');
    exit;
}

// Procesamiento del logo
$logo = $empresa['logo'] ?? 'images/inbioslab-logo.png';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $logoPath = 'images/empresa/logo_empresa.png';
        $destino = __DIR__ . '/../' . $logoPath;
        // Elimina el archivo viejo si existe (opcional)
        if (file_exists($destino)) {
            unlink($destino);
        }
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $logo = $logoPath;
        } else {
            $_SESSION['msg'] = 'Error al subir el logo.';
            header('Location: ../dashboard.php?vista=config_empresa_datos');
            exit;
        }
    } else {
        $_SESSION['msg'] = 'El logo debe ser una imagen PNG.';
        header('Location: ../dashboard.php?vista=config_empresa_datos');
        exit;
    }
}

// Actualizar o insertar según corresponda
try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE config_empresa SET nombre=?, ruc=?, direccion=?, email=?, telefono=?, celular=?, logo=? WHERE id=?");
        $stmt->execute([$nombre, $ruc, $direccion, $email, $telefono, $celular, $logo, $id]);
        $_SESSION['msg'] = 'Datos de la empresa actualizados correctamente.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO config_empresa (nombre, ruc, direccion, email, telefono, celular, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $ruc, $direccion, $email, $telefono, $celular, $logo]);
        $_SESSION['msg'] = 'Datos de la empresa registrados correctamente.';
    }
    header('Location: ../dashboard.php?vista=config_empresa_datos');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al guardar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=config_empresa_datos');
    exit;
}
