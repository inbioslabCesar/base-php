<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Recoger y validar datos
$nombre = isset($_POST['nombre']) ? mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, "UTF-8") : '';
$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$sinDni = !empty($_POST['sin_dni']);
$especialidad = isset($_POST['especialidad']) ? mb_convert_case(trim($_POST['especialidad']), MB_CASE_TITLE, "UTF-8") : '';
$descuento = isset($_POST['descuento']) ? trim($_POST['descuento']) : null;
$descripcion = isset($_POST['descripcion']) ? mb_convert_case(trim($_POST['descripcion']), MB_CASE_TITLE, "UTF-8") : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

function limpiarSoloDigitos(string $valor): string {
    return preg_replace('/\D+/', '', $valor) ?? '';
}

function generarDniProvisional(): string {
    // 8 dígitos iniciando con 9
    return '9' . str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
}

function normalizarDominioEmpresa(string $dominio): string {
    $dominio = strtolower(trim($dominio));
    $dominio = preg_replace('#^https?://#', '', $dominio) ?? $dominio;
    $dominio = preg_replace('#/.*$#', '', $dominio) ?? $dominio;
    $dominio = preg_replace('#:\\d+$#', '', $dominio) ?? $dominio;
    $dominio = trim($dominio);
    $dominio = ltrim($dominio, '@');
    if (str_starts_with($dominio, 'www.')) {
        $dominio = substr($dominio, 4);
    }
    return $dominio;
}

function obtenerDominioEmpresa(PDO $pdo): string {
    $stmt = $pdo->query('SELECT dominio FROM config_empresa LIMIT 1');
    $dominio = (string)($stmt->fetchColumn() ?: '');
    $dominio = normalizarDominioEmpresa($dominio !== '' ? $dominio : (string)($_SERVER['HTTP_HOST'] ?? ''));
    return $dominio !== '' ? $dominio : 'ejemplo.com';
}

function dniExiste(PDO $pdo, string $dni): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM convenios WHERE dni = ?');
    $stmt->execute([$dni]);
    return ((int)$stmt->fetchColumn()) > 0;
}

$dni = limpiarSoloDigitos((string)$dni);

if ($sinDni || $dni === '') {
    // Generar DNI provisional único
    $intentos = 0;
    do {
        $dni = generarDniProvisional();
        $intentos++;
    } while (dniExiste($pdo, $dni) && $intentos < 20);
}

// Si vienen vacíos o se desea autogenerar, derivar credenciales desde DNI
if ($dni !== '') {
    if ($email === '') {
        $email = $dni . '@' . obtenerDominioEmpresa($pdo);
    }
    if ($password === '') {
        $password = $dni;
    }
}

if (!$nombre || !$dni || !$email || !$password) {
    $_SESSION['mensaje'] = "Todos los campos requeridos deben completarse.";
    header('Location: dashboard.php?vista=form_convenio');
    exit;
}

try {
    // Verificar DNI único
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE dni = ?");
    $stmt->execute([$dni]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El DNI ya está registrado.";
        header('Location: dashboard.php?vista=form_convenio');
        exit;
    }

    // Verificar email único
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El email ya está registrado.";
        header('Location: dashboard.php?vista=form_convenio');
        exit;
    }

    // Hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO convenios (nombre, dni, especialidad, descuento, descripcion, email, password)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $nombre,
        $dni,
        $especialidad,
        $descuento !== '' ? $descuento : null,
        $descripcion,
        $email,
        $passwordHash
    ]);
    $_SESSION['mensaje'] = "Convenio registrado exitosamente.";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al registrar: " . $e->getMessage();
}
header('Location: dashboard.php?vista=convenios');
exit;
