<?php
require_once __DIR__ . '/../conexion/conexion.php';

$ruc = $_POST['ruc'] ?? '';
$sinRuc = !empty($_POST['sin_ruc']);
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

function limpiarSoloDigitos(string $valor): string {
    return preg_replace('/\D+/', '', $valor) ?? '';
}

function generarRucProvisional(): string {
    // 11 dígitos iniciando con 9
    return '9' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
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

function rucExiste(PDO $pdo, string $ruc): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM empresas WHERE ruc = ?');
    $stmt->execute([$ruc]);
    return ((int)$stmt->fetchColumn()) > 0;
}

$ruc = limpiarSoloDigitos((string)$ruc);

if ($sinRuc || $ruc === '') {
    $intentos = 0;
    do {
        $ruc = generarRucProvisional();
        $intentos++;
    } while (rucExiste($pdo, $ruc) && $intentos < 20);
}

// Forzar email dinámico en base al RUC
if ($ruc !== '') {
    $email = $ruc . '@' . obtenerDominioEmpresa($pdo);
    if ($password === '') {
        $password = $ruc;
    }
}

if ($ruc && $razon_social && $email && $password) {
    try {
        // Verificar RUC único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE ruc = ?");
        $stmt->execute([$ruc]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El RUC ya está registrado.";
            header('Location: dashboard.php?vista=form_empresa');
            exit;
        }

        // Verificar email único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado.";
            header('Location: dashboard.php?vista=form_empresa');
            exit;
        }

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
