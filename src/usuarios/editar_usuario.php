<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
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

$dni = limpiarSoloDigitos((string)$dni);

if ($sinDni || $dni === '') {
    $intentos = 0;
    do {
        $dni = generarDniProvisional();
        $intentos++;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE dni = ? AND id != ?');
        $stmt->execute([$dni, $id]);
        $existe = ((int)$stmt->fetchColumn()) > 0;
    } while ($existe && $intentos < 20);
}

// Forzar email dinámico en base al DNI
if ($dni !== '') {
    $email = $dni . '@' . obtenerDominioEmpresa($pdo);
}

if ($id && $nombre && $apellido && $dni && $email && $rol) {
    try {
        // Verificar DNI único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE dni = ? AND id != ?");
        $stmt->execute([$dni, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El DNI ya está registrado por otro usuario.";
            header('Location: dashboard.php?vista=form_usuario&id=' . $id);
            exit;
        }

        // Verificar email único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado por otro usuario.";
            header('Location: dashboard.php?vista=form_usuario&id=' . $id);
            exit;
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, dni=?, sexo=?, fecha_nacimiento=?, email=?, telefono=?, direccion=?, cargo=?, profesion=?, rol=?, estado=?, password=? WHERE id=?");
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
                $hash,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, dni=?, sexo=?, fecha_nacimiento=?, email=?, telefono=?, direccion=?, cargo=?, profesion=?, rol=?, estado=? WHERE id=?");
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
                $id
            ]);
        }
        $_SESSION['mensaje'] = "Usuario actualizado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar el usuario: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el usuario.";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
