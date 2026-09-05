<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';

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
$usar_precio_convenio = isset($_POST['usar_precio_convenio']) ? 1 : 0;
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
    $cfg = ui_theme_fetch_company_config($pdo);
    $dominio = is_array($cfg) ? (string)($cfg['dominio'] ?? '') : '';
    $dominio = normalizarDominioEmpresa($dominio !== '' ? $dominio : (string)($_SERVER['HTTP_HOST'] ?? ''));
    return $dominio !== '' ? $dominio : 'ejemplo.com';
}

function empresasHasColumn(PDO $pdo, string $column): bool {
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }
    $stmt = $pdo->prepare('SHOW COLUMNS FROM empresas LIKE ?');
    $stmt->execute([$column]);
    $cache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$column];
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
            $_SESSION['mensaje_tipo'] = "warning";
            header('Location: dashboard.php?vista=form_empresa&id=' . $id);
            exit;
        }

        // Verificar email único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado en otra empresa.";
            $_SESSION['mensaje_tipo'] = "warning";
            header('Location: dashboard.php?vista=form_empresa&id=' . $id);
            exit;
        }

        $hasUsarPrecioConvenio = empresasHasColumn($pdo, 'usar_precio_convenio');

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hasUsarPrecioConvenio) {
                $stmt = $pdo->prepare("UPDATE empresas SET ruc=?, razon_social=?, nombre_comercial=?, direccion=?, telefono=?, email=?, representante=?, password=?, convenio=?, estado=?, descuento=?, usar_precio_convenio=? WHERE id=?");
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
                    $usar_precio_convenio,
                    $id
                ]);
            } else {
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
            }
        } else {
            if ($hasUsarPrecioConvenio) {
                $stmt = $pdo->prepare("UPDATE empresas SET ruc=?, razon_social=?, nombre_comercial=?, direccion=?, telefono=?, email=?, representante=?, convenio=?, estado=?, descuento=?, usar_precio_convenio=? WHERE id=?");
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
                    $usar_precio_convenio,
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
        }
        $_SESSION['mensaje'] = "Empresa actualizada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar la empresa: " . $e->getMessage();
        $_SESSION['mensaje_tipo'] = "error";
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar la empresa.";
    $_SESSION['mensaje_tipo'] = "warning";
}

header('Location: dashboard.php?vista=empresas');
exit;
?>
