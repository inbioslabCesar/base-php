<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
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

$ruc = limpiarSoloDigitos((string)$ruc);

if ($sinRuc || $ruc === '') {
    // Generar RUC provisional único (evitar colisión con otros registros)
    $intentos = 0;
    do {
        $ruc = generarRucProvisional();
        $intentos++;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM empresas WHERE ruc = ? AND id != ?');
        $stmt->execute([$ruc, $id]);
        $existe = ((int)$stmt->fetchColumn()) > 0;
    } while ($existe && $intentos < 20);
}

// Forzar email dinámico en base al RUC
if ($ruc !== '') {
    $email = $ruc . '@' . obtenerDominioEmpresa($pdo);
}

if ($id && $ruc && $razon_social && $email) {
    try {
        // Verificar RUC único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE ruc = ? AND id != ?");
        $stmt->execute([$ruc, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El RUC ya está registrado en otra empresa.";
            header('Location: dashboard.php?vista=form_empresa&id=' . $id);
            exit;
        }

        // Verificar email único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado en otra empresa.";
            header('Location: dashboard.php?vista=form_empresa&id=' . $id);
            exit;
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE empresas SET ruc=?, razon_social=?, nombre_comercial=?, direccion=?, telefono=?, email=?, representante=?, password=?, convenio=?, estado=?, descuento=? WHERE id=?");
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
                $descuento,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE empresas SET ruc=?, razon_social=?, nombre_comercial=?, direccion=?, telefono=?, email=?, representante=?, convenio=?, estado=?, descuento=? WHERE id=?");
            $stmt->execute([
                $ruc,
                mb_convert_case($razon_social, MB_CASE_TITLE, "UTF-8"),
                mb_convert_case($nombre_comercial, MB_CASE_TITLE, "UTF-8"),
                $direccion,
                $telefono,
                $email,
                mb_convert_case($representante, MB_CASE_TITLE, "UTF-8"),
                $convenio,
                $estado,
                $descuento,
                $id
            ]);
        }
        $_SESSION['mensaje'] = "Empresa actualizada exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar la empresa: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar la empresa.";
}

header('Location: dashboard.php?vista=empresas');
exit;
?>
