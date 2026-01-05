<?php
require_once __DIR__ . '/../conexion/conexion.php';

$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$dni = $_POST['dni'] ?? '';
$sinDni = !empty($_POST['sin_dni']);
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
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE dni = ?');
    $stmt->execute([$dni]);
    return ((int)$stmt->fetchColumn()) > 0;
}

$dni = limpiarSoloDigitos((string)$dni);

if ($sinDni || $dni === '') {
    $intentos = 0;
    do {
        $dni = generarDniProvisional();
        $intentos++;
    } while (dniExiste($pdo, $dni) && $intentos < 20);
}

// Forzar email dinámico en base al DNI
if ($dni !== '') {
    $email = $dni . '@' . obtenerDominioEmpresa($pdo);
    if ($password === '') {
        $password = $dni;
    }
}

if ($nombre && $apellido && $dni && $email && $rol && $password) {
    try {
        // Verificar DNI único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE dni = ?");
        $stmt->execute([$dni]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El DNI ya está registrado.";
            header('Location: dashboard.php?vista=form_usuario');
            exit;
        }

        // Verificar email único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado.";
            header('Location: dashboard.php?vista=form_usuario');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, dni, sexo, fecha_nacimiento, email, telefono, direccion, cargo, profesion, rol, estado, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
            $hash
        ]);

        $_SESSION['mensaje'] = "Usuario creado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear el usuario: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Faltan datos obligatorios.";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
