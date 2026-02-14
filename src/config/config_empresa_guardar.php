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
// ...existing code...
$nombre    = trim($_POST['nombre'] ?? '');
$ruc       = trim($_POST['ruc'] ?? '');
$dominio   = trim($_POST['dominio'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$celular   = trim($_POST['celular'] ?? '');

// Mapa (embed/src) opcional
$maps_embed = trim($_POST['maps_embed'] ?? '');
if ($maps_embed !== '' && stripos($maps_embed, '<iframe') !== false) {
    if (preg_match('/src\s*=\s*"([^"]+)"/i', $maps_embed, $m)) {
        $maps_embed = trim($m[1]);
    }
}
if ($maps_embed !== '' && !preg_match('~^https?://www\.google\.com/maps/(embed\?pb=|q=|search/)~i', $maps_embed)) {
    $_SESSION['msg'] = 'El mapa debe ser un enlace válido de Google Maps (ideal: src del iframe /maps/embed?pb=...).';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
}

$has_maps_embed = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM config_empresa LIKE 'maps_embed'");
    $has_maps_embed = (bool)$chk->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $has_maps_embed = false;
}

// Validación básica
if (!$nombre || !$ruc || !$direccion || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
}

// Procesamiento del logo (solo PNG, sobrescribe archivo)
$logo = $empresa['logo'] ?? 'images/empresa/logo_empresa.png';

$baseDir = realpath(__DIR__ . '/..'); // apunta a /src (donde vive images/...)
if ($baseDir === false) {
    $baseDir = __DIR__ . '/..';
}

$describeUploadError = function (int $code): string {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño permitido.';
        case UPLOAD_ERR_PARTIAL:
            return 'La subida quedó incompleta.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal del servidor.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'No se pudo escribir el archivo en disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP bloqueó la subida.';
        case UPLOAD_ERR_OK:
        default:
            return 'Error desconocido al subir el archivo.';
    }
};

$isPngUpload = function (string $tmpPath, string $originalName): bool {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== 'png') {
        return false;
    }
    if (!is_file($tmpPath)) {
        return false;
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (!in_array($mime, ['image/png', 'image/x-png'], true)) {
                return false;
            }
        }
    }
    return true;
};

$saveUpload = function (string $field, string $relativePath, string $label) use ($baseDir, $describeUploadError, $isPngUpload): ?string {
    if (!isset($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = $describeUploadError((int)$file['error']);
        $_SESSION['msg'] = "Error al subir {$label}: {$msg}";
        error_log("[config_empresa_guardar] {$label} upload error={$file['error']} name=" . ($file['name'] ?? ''));
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    $tmp = $file['tmp_name'] ?? '';
    $name = $file['name'] ?? '';
    if (!$isPngUpload($tmp, $name)) {
        $_SESSION['msg'] = "{$label} debe ser una imagen PNG válida.";
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    $relativePath = ltrim($relativePath, '/');
    $destino = rtrim($baseDir, '\\/') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $destDir = dirname($destino);
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            $_SESSION['msg'] = "No se pudo crear la carpeta para {$label}.";
            error_log("[config_empresa_guardar] mkdir failed destDir={$destDir}");
            header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
            exit;
        }
    }

    if (file_exists($destino) && !is_writable($destino)) {
        $_SESSION['msg'] = "No hay permisos para reemplazar {$label}.";
        error_log("[config_empresa_guardar] not writable destino={$destino}");
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }
    if (is_dir($destino)) {
        $_SESSION['msg'] = "Ruta inválida para {$label}.";
        error_log("[config_empresa_guardar] destino is dir destino={$destino}");
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    if (!move_uploaded_file($tmp, $destino)) {
        $_SESSION['msg'] = "Error al guardar {$label}.";
        error_log("[config_empresa_guardar] move_uploaded_file failed destino={$destino}");
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    return $relativePath;
};

$nuevoLogo = $saveUpload('logo', 'images/empresa/logo_empresa.png', 'el logo');
if ($nuevoLogo) {
    $logo = $nuevoLogo;
}

// Procesamiento de la firma (solo PNG, sobrescribe archivo)
$firma = $empresa['firma'] ?? 'images/empresa/firma.png';

$nuevaFirma = $saveUpload('firma', 'images/empresa/firma.png', 'la firma');
if ($nuevaFirma) {
    $firma = $nuevaFirma;
}
// Colores y textos
$color_principal  = trim($_POST['color_principal'] ?? '#0d6efd');
$color_secundario = trim($_POST['color_secundario'] ?? '#f8f9fa');
$color_footer     = trim($_POST['color_footer'] ?? '#343a40');
$color_botones    = trim($_POST['color_botones'] ?? '#198754');
$color_texto      = trim($_POST['color_texto'] ?? '#212529');
$tamano_letra     = trim($_POST['tamano_letra'] ?? '1rem');
$frase_promocion  = trim($_POST['frase_promocion'] ?? '');
$oferta_mes       = trim($_POST['oferta_mes'] ?? '');

// --- Imágenes del carrusel ---
$imagenes_carrusel = [];
if (!empty($empresa['imagenes_carrusel'])) {
    $tmp = json_decode($empresa['imagenes_carrusel'], true);
    if (is_array($tmp)) {
        $imagenes_carrusel = $tmp;
    }
}
if (!empty($_POST['eliminar_carrusel']) && is_array($_POST['eliminar_carrusel'])) {
    foreach ($_POST['eliminar_carrusel'] as $idx) {
        $idx = (int)$idx;
        if (isset($imagenes_carrusel[$idx])) {
            $ruta = __DIR__ . '/../' . $imagenes_carrusel[$idx];
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_carrusel[$idx]);
        }
    }
    $imagenes_carrusel = array_values($imagenes_carrusel);
}
if (!empty($_FILES['imagenes_carrusel']['name'][0])) {
    $uploadDir = __DIR__ . '/../images/empresa/carrusel/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_carrusel']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_carrusel']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('carrusel_') . '.' . $ext;
                $rutaRelativa = 'images/empresa/carrusel/' . $nuevoNombre;
                if (move_uploaded_file($tmp_name, $uploadDir . $nuevoNombre)) {
                    $imagenes_carrusel[] = $rutaRelativa;
                }
            }
        }
    }
}

// --- Imágenes institucionales ---
$imagenes_institucionales = [];
if (!empty($empresa['imagenes_institucionales'])) {
    $tmp = json_decode($empresa['imagenes_institucionales'], true);
    if (is_array($tmp)) {
        $imagenes_institucionales = $tmp;
    }
}
if (!empty($_POST['eliminar_institucional']) && is_array($_POST['eliminar_institucional'])) {
    foreach ($_POST['eliminar_institucional'] as $idx) {
        $idx = (int)$idx;
        if (isset($imagenes_institucionales[$idx])) {
            $ruta = __DIR__ . '/../' . $imagenes_institucionales[$idx];
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_institucionales[$idx]);
        }
    }
    $imagenes_institucionales = array_values($imagenes_institucionales);
}
if (!empty($_FILES['imagenes_institucionales']['name'][0])) {
    $uploadDir = __DIR__ . '/../images/empresa/institucional/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_institucionales']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_institucionales']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('institucional_') . '.' . $ext;
                $rutaRelativa = 'images/empresa/institucional/' . $nuevoNombre;
                if (move_uploaded_file($tmp_name, $uploadDir . $nuevoNombre)) {
                    $imagenes_institucionales[] = $rutaRelativa;
                }
            }
        }
    }
}

// Servicios, testimonios y redes sociales (JSON)
$servicios      = json_decode($_POST['servicios'] ?? '[]', true);
if (!is_array($servicios)) $servicios = [];
$testimonios    = json_decode($_POST['testimonios'] ?? '[]', true);
if (!is_array($testimonios)) $testimonios = [];
$redes_sociales = json_decode($_POST['redes_sociales'] ?? '[]', true);
if (!is_array($redes_sociales)) $redes_sociales = [];

// Menú personalizable
$menu_inicio      = trim($_POST['menu_inicio'] ?? 'Inicio');
$menu_servicios   = trim($_POST['menu_servicios'] ?? 'Servicios');
$menu_testimonios = trim($_POST['menu_testimonios'] ?? 'Testimonios');
$menu_contacto    = trim($_POST['menu_contacto'] ?? 'Contacto');
try {
    if ($id) {
        $sql = "UPDATE config_empresa SET 
            nombre=?, ruc=?, dominio=?, direccion=?, email=?, telefono=?, celular=?, logo=?, firma=?";
        $params = [$nombre, $ruc, $dominio, $direccion, $email, $telefono, $celular, $logo, $firma];

        if ($has_maps_embed) {
            $sql .= ", maps_embed=?";
            $params[] = $maps_embed;
        }

        $sql .= ",
            color_principal=?, color_secundario=?, color_footer=?, color_botones=?, color_texto=?, tamano_letra=?,
            frase_promocion=?, oferta_mes=?,
            imagenes_carrusel=?, imagenes_institucionales=?, servicios=?, testimonios=?, redes_sociales=?,
            menu_inicio=?, menu_servicios=?, menu_testimonios=?, menu_contacto=?
            WHERE id=?";

        $params = array_merge($params, [
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto, $tamano_letra,
            $frase_promocion, $oferta_mes,
            json_encode($imagenes_carrusel, JSON_UNESCAPED_UNICODE),
            json_encode($imagenes_institucionales, JSON_UNESCAPED_UNICODE),
            json_encode($servicios, JSON_UNESCAPED_UNICODE),
            json_encode($testimonios, JSON_UNESCAPED_UNICODE),
            json_encode($redes_sociales, JSON_UNESCAPED_UNICODE),
            $menu_inicio, $menu_servicios, $menu_testimonios, $menu_contacto,
            $id
        ]);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['msg'] = 'Datos de la empresa actualizados correctamente.';
    } else {
        $cols = [
            'nombre', 'ruc', 'dominio', 'direccion', 'email', 'telefono', 'celular', 'logo', 'firma'
        ];
        $vals = [
            $nombre, $ruc, $dominio, $direccion, $email, $telefono, $celular, $logo, $firma
        ];
        if ($has_maps_embed) {
            $cols[] = 'maps_embed';
            $vals[] = $maps_embed;
        }
        $cols = array_merge($cols, [
            'color_principal', 'color_secundario', 'color_footer', 'color_botones', 'color_texto', 'tamano_letra',
            'frase_promocion', 'oferta_mes',
            'imagenes_carrusel', 'imagenes_institucionales', 'servicios', 'testimonios', 'redes_sociales',
            'menu_inicio', 'menu_servicios', 'menu_testimonios', 'menu_contacto'
        ]);
        $vals = array_merge($vals, [
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto, $tamano_letra,
            $frase_promocion, $oferta_mes,
            json_encode($imagenes_carrusel, JSON_UNESCAPED_UNICODE),
            json_encode($imagenes_institucionales, JSON_UNESCAPED_UNICODE),
            json_encode($servicios, JSON_UNESCAPED_UNICODE),
            json_encode($testimonios, JSON_UNESCAPED_UNICODE),
            json_encode($redes_sociales, JSON_UNESCAPED_UNICODE),
            $menu_inicio, $menu_servicios, $menu_testimonios, $menu_contacto
        ]);

        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $stmt = $pdo->prepare('INSERT INTO config_empresa (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')');
        $stmt->execute($vals);
        $_SESSION['msg'] = 'Datos de la empresa registrados correctamente.';
    }
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al guardar: ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
}
