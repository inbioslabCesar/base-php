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
$tipo_documento = $_POST['tipo_documento'] ?? 'dni';
$edad_valor     = trim($_POST['edad_valor'] ?? '');
$edad_unidad    = trim($_POST['edad_unidad'] ?? '');
$edad = ($edad_valor !== '' && $edad_unidad !== '') ? (intval($edad_valor) . ' ' . $edad_unidad) : '';
$email          = trim($_POST['email'] ?? '');

// Campos opcionales
$password       = $_POST['password'] ?? '';
$telefono       = trim($_POST['telefono'] ?? '');
$direccion      = trim($_POST['direccion'] ?? '');
$procedencia     = trim($_POST['procedencia'] ?? '');
$sexo           = $_POST['sexo'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$estado         = $_POST['estado'] ?? 'activo';
$descuento      = $_POST['descuento'] ?? null;

function normalizarDominioEmpresa(string $dominio): string {
    $dominio = trim($dominio);
    if ($dominio === '') return '';

    $dominio = preg_replace('#^https?://#i', '', $dominio);
    $dominio = preg_replace('#/.*$#', '', $dominio);
    $dominio = preg_replace('#:\\d+$#', '', $dominio);
    $dominio = preg_replace('#^www\\.#i', '', $dominio);
    return strtolower(trim($dominio));
}

// Documento
if ($tipo_documento === 'sin_dni') {
    if ($dni === '') {
        $intentos = 0;
        do {
            $dni = (string)random_int(10000000, 99999999);
            $stmt = $pdo->prepare('SELECT id FROM clientes WHERE dni = ? AND id <> ? LIMIT 1');
            $stmt->execute([$dni, $id]);
            $existe = (bool)$stmt->fetchColumn();
            $intentos++;
        } while ($existe && $intentos < 20);

        if ($existe) {
            $_SESSION['msg'] = 'No se pudo generar un documento provisional único. Intente nuevamente.';
            header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
            exit;
        }
    }
} else {
    if ($dni === '') {
        $_SESSION['msg'] = 'Por favor, ingrese el documento.';
        header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
        exit;
    }
}

// Dominio empresa para email
$dominio = '';
try {
    $stmtDom = $pdo->query('SELECT dominio FROM config_empresa LIMIT 1');
    $dominio = (string)($stmtDom->fetchColumn() ?: '');
} catch (Exception $e) {
    $dominio = '';
}
$dominio = normalizarDominioEmpresa($dominio !== '' ? $dominio : (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($dominio === '') {
    $dominio = 'localhost';
}

// Siempre forzar email según documento@dominio
$email = ($dni !== '' && $dominio !== '') ? ($dni . '@' . $dominio) : $email;

// Validación de requeridos
if (!$codigo_cliente || !$nombre || !$apellido || !$dni || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
    exit;
}

// Validar DNI único (excluyendo el registro actual)
$stmt = $pdo->prepare('SELECT id FROM clientes WHERE dni = ? AND id <> ? LIMIT 1');
$stmt->execute([$dni, $id]);
if ($stmt->fetchColumn()) {
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id . '&error=dni_duplicado');
    exit;
}

// Capitaliza nombre y apellido
function capitalize($string) {
    return mb_convert_case(strtolower(trim($string)), MB_CASE_TITLE, "UTF-8");
}
try {
    if ($password) {
        $sql = "UPDATE clientes SET 
            codigo_cliente=?, nombre=?, apellido=?, dni=?, tipo_documento=?, edad=?, email=?, password=?, telefono=?, direccion=?, sexo=?, fecha_nacimiento=?, estado=?, descuento=?, procedencia=?
            WHERE id=?";
        $params = [
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
            $id
        ];
    } else {
        $sql = "UPDATE clientes SET 
            codigo_cliente=?, nombre=?, apellido=?, dni=?, tipo_documento=?, edad=?, email=?, telefono=?, direccion=?, sexo=?, fecha_nacimiento=?, estado=?, descuento=?, procedencia=?
            WHERE id=?";
        $params = [
            $codigo_cliente,
            capitalize($nombre),
            capitalize($apellido),
            $dni,
            $tipo_documento,
            $edad,
            $email,
            $telefono ?: null,
            $direccion ?: null,
            $sexo ?: null,
            $fecha_nacimiento ?: null,
            $estado,
            $descuento !== '' ? $descuento : null,
            $procedencia !== '' ? $procedencia : null,
            $id
        ];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['msg'] = 'Cliente actualizado correctamente.';

    // Redirección según rol
    if ($_SESSION['rol'] === 'empresa') {
        header('Location: ../dashboard.php?vista=clientes_empresa');
        exit;
    }
    if ($_SESSION['rol'] === 'convenio') {
        header('Location: ../dashboard.php?vista=clientes_convenio');
        exit;
    }

    header('Location: ../dashboard.php?vista=clientes');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al actualizar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
    exit;
}
