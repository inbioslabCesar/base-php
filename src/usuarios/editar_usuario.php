<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
require_once __DIR__ . '/funciones/usuarios_schema.php';
require_once __DIR__ . '/funciones/usuarios_privilegios.php';

usuarios_asegurar_columnas_profesional($pdo);

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
$colegiatura_numero = normalizarRegistroProfesionalEntrada((string)($_POST['colegiatura_numero'] ?? ''));
$ctmp_numero = normalizarRegistroProfesionalEntrada((string)($_POST['ctmp_numero'] ?? ''));
$rol = $_POST['rol'] ?? '';
$estado = $_POST['estado'] ?? 'activo';
$password = $_POST['password'] ?? '';
$quitarFirma = !empty($_POST['quitar_firma']);
$privilegios_json = usuarios_privilegios_serializar((string)$rol, $_POST['privilegios'] ?? []);

function extensionImagenValidaFirma(string $mimeType, string $nombreArchivo): ?string {
    $mimeType = strtolower(trim($mimeType));
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    $permitidos = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
    ];
    if (isset($permitidos[$mimeType])) {
        return $permitidos[$mimeType];
    }
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }
    return null;
}

function guardarFirmaUsuarioSubida(array $fileData, int $usuarioId): ?string {
    if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
        return null;
    }
    $ext = extensionImagenValidaFirma((string)($fileData['type'] ?? ''), (string)($fileData['name'] ?? ''));
    if ($ext === null) {
        return null;
    }
    $baseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'usuarios' . DIRECTORY_SEPARATOR . 'firmas';
    if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
        return null;
    }
    $nombre = 'firma_usuario_' . $usuarioId . '_' . date('YmdHis') . '.' . $ext;
    $destino = $baseDir . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($fileData['tmp_name'], $destino)) {
        return null;
    }
    return '../uploads/usuarios/firmas/' . $nombre;
}

function eliminarFirmaUsuarioArchivo(string $storedPath): void {
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return;
    }
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));
    if (strpos($normalized, '../uploads/') === 0) {
        $normalized = substr($normalized, 3);
    }
    if (strpos($normalized, 'uploads/usuarios/firmas/') !== 0) {
        return;
    }
    $projectRoot = dirname(__DIR__, 2);
    $absPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    $real = realpath($absPath);
    if ($real === false || !is_file($real)) {
        return;
    }
    $baseFirmas = realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'usuarios' . DIRECTORY_SEPARATOR . 'firmas');
    if ($baseFirmas === false) {
        return;
    }
    if (strpos($real, $baseFirmas . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }
    @unlink($real);
}

function limpiarSoloDigitos(string $valor): string {
    return preg_replace('/\D+/', '', $valor) ?? '';
}

function generarDniProvisional(): string {
    return '9' . str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
}

function normalizarRegistroProfesionalEntrada(string $valor): string {
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }
    $valor = preg_replace('/^(c\.?t\.?m\.?p\.?|ctmp|colegiatura|cmp)\s*[:#-]?\s*/iu', '', $valor) ?? $valor;
    return trim($valor);
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
            $_SESSION['mensaje_tipo'] = "warning";
            header('Location: dashboard.php?vista=form_usuario&id=' . $id);
            exit;
        }

        // Verificar email único (excepto el propio)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensaje'] = "El email ya está registrado por otro usuario.";
            $_SESSION['mensaje_tipo'] = "warning";
            header('Location: dashboard.php?vista=form_usuario&id=' . $id);
            exit;
        }

        $setParts = [
            'nombre=?',
            'apellido=?',
            'dni=?',
            'sexo=?',
            'fecha_nacimiento=?',
            'email=?',
            'telefono=?',
            'direccion=?',
            'cargo=?',
            'profesion=?',
            'rol=?',
            'estado=?'
        ];
        $paramsUpdate = [
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
        ];

        if (usuarios_tiene_columna($pdo, 'colegiatura_numero')) {
            $setParts[] = 'colegiatura_numero=?';
            $paramsUpdate[] = ($colegiatura_numero !== '') ? $colegiatura_numero : null;
        }
        if (usuarios_tiene_columna($pdo, 'ctmp_numero')) {
            $setParts[] = 'ctmp_numero=?';
            $paramsUpdate[] = ($ctmp_numero !== '') ? $ctmp_numero : null;
        }
        if (usuarios_tiene_columna($pdo, 'privilegios_json')) {
            $setParts[] = 'privilegios_json=?';
            $paramsUpdate[] = $privilegios_json;
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $setParts[] = 'password=?';
            $paramsUpdate[] = $hash;
        }

        $paramsUpdate[] = $id;
        $sqlUpdate = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id=?";
        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->execute($paramsUpdate);

        if (usuarios_tiene_columna_firma($pdo) && $quitarFirma) {
            $stmtFirmaActual = $pdo->prepare("SELECT firma FROM usuarios WHERE id = ? LIMIT 1");
            $stmtFirmaActual->execute([$id]);
            $firmaActual = (string)($stmtFirmaActual->fetchColumn() ?: '');
            if ($firmaActual !== '') {
                eliminarFirmaUsuarioArchivo($firmaActual);
            }
            $stmtQuitarFirma = $pdo->prepare("UPDATE usuarios SET firma = NULL WHERE id = ?");
            $stmtQuitarFirma->execute([$id]);
        }

        if (usuarios_tiene_columna_firma($pdo)
            && !empty($_FILES['firma'])
            && (int)($_FILES['firma']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $firmaPath = guardarFirmaUsuarioSubida($_FILES['firma'], (int)$id);
            if ($firmaPath !== null) {
                $stmtFirma = $pdo->prepare("UPDATE usuarios SET firma = ? WHERE id = ?");
                $stmtFirma->execute([$firmaPath, $id]);
            }
        }

        $_SESSION['mensaje'] = "Usuario actualizado exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar el usuario: " . $e->getMessage();
        $_SESSION['mensaje_tipo'] = "error";
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el usuario.";
    $_SESSION['mensaje_tipo'] = "warning";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
