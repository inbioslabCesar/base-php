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
    header('Location: /dashboard.php?vista=config_empresa_datos');
    exit;
}

// Procesamiento del logo (solo PNG, sobrescribe archivo)
$logo = $empresa['logo'] ?? 'images/empresa/logo_empresa.png';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $logoPath = 'images/empresa/logo_empresa.png';
        $destino = __DIR__ . '/../' . $logoPath;
        // Elimina el archivo viejo si existe
        if (file_exists($destino)) {
            unlink($destino);
        }
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $logo = $logoPath;
        } else {
            $_SESSION['msg'] = 'Error al subir el logo.';
            header('Location: /dashboard.php?vista=config_empresa_datos');
            exit;
        }
    } else {
        $_SESSION['msg'] = 'El logo debe ser una imagen PNG.';
        header('Location: /dashboard.php?vista=config_empresa_datos');
        exit;
    }
}

// Procesamiento de la firma (solo PNG, sobrescribe archivo)
$firma = $empresa['firma'] ?? 'images/empresa/firma.png';
if (isset($_FILES['firma']) && $_FILES['firma']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $firmaPath = 'images/empresa/firma.png';
        $destinoFirma = __DIR__ . '/../' . $firmaPath;
        // Elimina el archivo viejo si existe
        if (file_exists($destinoFirma)) {
            unlink($destinoFirma);
        }
        if (move_uploaded_file($_FILES['firma']['tmp_name'], $destinoFirma)) {
            $firma = $firmaPath;
        } else {
            $_SESSION['msg'] = 'Error al subir la firma.';
            header('Location: /dashboard.php?vista=config_empresa_datos');
            exit;
        }
    } else {
        $_SESSION['msg'] = 'La firma debe ser una imagen PNG.';
        header('Location: /dashboard.php?vista=config_empresa_datos');
        exit;
    }
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE config_empresa SET nombre=?, ruc=?, direccion=?, email=?, telefono=?, celular=?, logo=?, firma=? WHERE id=?");
        $stmt->execute([$nombre, $ruc, $direccion, $email, $telefono, $celular, $logo, $firma, $id]);
        $_SESSION['msg'] = 'Datos de la empresa actualizados correctamente.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO config_empresa (nombre, ruc, direccion, email, telefono, celular, logo, firma) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $ruc, $direccion, $email, $telefono, $celular, $logo, $firma]);
        $_SESSION['msg'] = 'Datos de la empresa registrados correctamente.';
    }
    header('Location: ../dashboard.php?vista=config_empresa_datos');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al guardar: ' . $e->getMessage();
    header('Location: /dashboard.php?vista=config_empresa_datos');
    exit;
}
