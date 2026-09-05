<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$nombre = isset($_POST['nombre']) ? mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, "UTF-8") : '';
$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$sinDni = !empty($_POST['sin_dni']);
$especialidad = isset($_POST['especialidad']) ? mb_convert_case(trim($_POST['especialidad']), MB_CASE_TITLE, "UTF-8") : '';
$descuento = isset($_POST['descuento']) ? trim($_POST['descuento']) : null;
$usar_precio_convenio = isset($_POST['usar_precio_convenio']) ? 1 : 0;
$descripcion = isset($_POST['descripcion']) ? mb_convert_case(trim($_POST['descripcion']), MB_CASE_TITLE, "UTF-8") : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

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
    $cfg = ui_theme_fetch_company_config($pdo);
    $dominio = is_array($cfg) ? (string)($cfg['dominio'] ?? '') : '';
    $dominio = normalizarDominioEmpresa($dominio !== '' ? $dominio : (string)($_SERVER['HTTP_HOST'] ?? ''));
    return $dominio !== '' ? $dominio : 'ejemplo.com';
}

function conveniosHasColumn(PDO $pdo, string $column): bool {
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }
    $stmt = $pdo->prepare('SHOW COLUMNS FROM convenios LIKE ?');
    $stmt->execute([$column]);
    $cache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$column];
}

$dni = limpiarSoloDigitos((string)$dni);

if ($sinDni || $dni === '') {
    // Generar DNI provisional único (evitar colisión con otros registros)
    $intentos = 0;
    do {
        $dni = generarDniProvisional();
        $intentos++;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM convenios WHERE dni = ? AND id != ?');
        $stmt->execute([$dni, $id]);
        $existe = ((int)$stmt->fetchColumn()) > 0;
    } while ($existe && $intentos < 20);
}

// Si el email viene vacío, derivarlo del DNI
if ($dni !== '' && $email === '') {
    $email = $dni . '@' . obtenerDominioEmpresa($pdo);
}

if (!$id || !$nombre || !$dni || !$email) {
    $_SESSION['mensaje'] = "Todos los campos requeridos deben completarse.";
    header('Location: dashboard.php?vista=form_convenio&id=' . $id);
    exit;
}

try {
    // Verificar DNI único (excepto para el propio registro)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE dni = ? AND id != ?");
    $stmt->execute([$dni, $id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El DNI ya está registrado en otro convenio.";
        header('Location: dashboard.php?vista=form_convenio&id=' . $id);
        exit;
    }

    // Verificar email único (excepto para el propio registro)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM convenios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = "El email ya está registrado en otro convenio.";
        header('Location: dashboard.php?vista=form_convenio&id=' . $id);
        exit;
    }

    // Actualizar datos básicos
    $hasUsarPrecioConvenio = conveniosHasColumn($pdo, 'usar_precio_convenio');
    $sql = "UPDATE convenios SET nombre = ?, dni = ?, especialidad = ?, descuento = ?";
    $params = [
        $nombre,
        $dni,
        $especialidad,
        $descuento !== '' ? $descuento : null
    ];

    if ($hasUsarPrecioConvenio) {
        $sql .= ", usar_precio_convenio = ?";
        $params[] = $usar_precio_convenio;
    }

    $sql .= ", descripcion = ?, email = ?";
    $params[] = $descripcion;
    $params[] = $email;

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
