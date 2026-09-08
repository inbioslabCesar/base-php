<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$empresaCfgId = (int)($_POST['empresa_cfg_id'] ?? 0);
$empresa = ui_theme_fetch_company_config($pdo, $empresaCfgId > 0 ? $empresaCfgId : null);
$empresa = is_array($empresa) ? $empresa : [];
$id = !empty($empresa['id']) ? (int)$empresa['id'] : null;

// Recoge los datos del formulario
// ...existing code...
$nombre    = trim($_POST['nombre'] ?? '');
$ruc       = trim($_POST['ruc'] ?? '');
$dominio   = trim($_POST['dominio'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$celular   = trim($_POST['celular'] ?? '');
$has_modo_operativo_input = array_key_exists('modo_operativo', $_POST);
$has_portal_publico_enable_input = array_key_exists('portal_publico_enable_present', $_POST)
    || array_key_exists('portal_publico_enable', $_POST);
$has_operation_settings_input = $has_modo_operativo_input || $has_portal_publico_enable_input;

$modo_operativo = null;
if ($has_modo_operativo_input) {
    $modo_operativo = strtoupper(trim((string)($_POST['modo_operativo'] ?? 'PARTICULAR')));
    if (!in_array($modo_operativo, ['PARTICULAR', 'SIS', 'MIXTO'], true)) {
        $modo_operativo = 'PARTICULAR';
    }
}

$portal_publico_enable = null;
if ($has_portal_publico_enable_input) {
    $portal_publico_enable = isset($_POST['portal_publico_enable']) ? 1 : 0;
}

$mostrar_fecha_ingreso_pdf = isset($_POST['mostrar_fecha_ingreso_pdf']) ? 1 : 0;
$mostrar_fecha_validacion_pdf = isset($_POST['mostrar_fecha_validacion_pdf']) ? 1 : 0;

$promo_web_activa = isset($_POST['promo_web_activa']) ? 1 : 0;
$promo_web_porcentaje = (float)($_POST['promo_web_porcentaje'] ?? 0);
if ($promo_web_porcentaje < 0) {
    $promo_web_porcentaje = 0;
}
if ($promo_web_porcentaje > 100) {
    $promo_web_porcentaje = 100;
}
$promo_web_aplicar_carrito = isset($_POST['promo_web_aplicar_carrito']) ? 1 : 0;
$promo_web_mensaje = trim((string)($_POST['promo_web_mensaje'] ?? ''));
if (mb_strlen($promo_web_mensaje) > 255) {
    $promo_web_mensaje = mb_substr($promo_web_mensaje, 0, 255);
}

$normalizeDateOrNull = static function ($value): ?string {
    $txt = trim((string)$value);
    if ($txt === '') {
        return null;
    }
    $ts = strtotime($txt);
    if ($ts === false || $ts <= 0) {
        return null;
    }
    return date('Y-m-d', $ts);
};

$promo_web_fecha_inicio = $normalizeDateOrNull($_POST['promo_web_fecha_inicio'] ?? null);
$promo_web_fecha_fin = $normalizeDateOrNull($_POST['promo_web_fecha_fin'] ?? null);
if ($promo_web_fecha_inicio !== null && $promo_web_fecha_fin !== null && strcmp($promo_web_fecha_fin, $promo_web_fecha_inicio) < 0) {
    [$promo_web_fecha_inicio, $promo_web_fecha_fin] = [$promo_web_fecha_fin, $promo_web_fecha_inicio];
}

$share_preview_titulo = trim((string)($_POST['share_preview_titulo'] ?? ''));
if (mb_strlen($share_preview_titulo) > 160) {
    $share_preview_titulo = mb_substr($share_preview_titulo, 0, 160);
}
$share_preview_descripcion = trim((string)($_POST['share_preview_descripcion'] ?? ''));
if (mb_strlen($share_preview_descripcion) > 255) {
    $share_preview_descripcion = mb_substr($share_preview_descripcion, 0, 255);
}
$share_preview_imagen = trim((string)($_POST['share_preview_imagen'] ?? ''));
if (mb_strlen($share_preview_imagen) > 600) {
    $share_preview_imagen = mb_substr($share_preview_imagen, 0, 600);
}

$moneda_codigo = strtoupper(trim((string)($_POST['moneda_codigo'] ?? 'PEN')));
if ($moneda_codigo === '') {
    $moneda_codigo = 'PEN';
}
$moneda_simbolo = trim((string)($_POST['moneda_simbolo'] ?? 'S/'));
if ($moneda_simbolo === '') {
    $moneda_simbolo = 'S/';
}
$moneda_posicion = strtolower(trim((string)($_POST['moneda_posicion'] ?? 'prefix')));
if (!in_array($moneda_posicion, ['prefix', 'suffix'], true)) {
    $moneda_posicion = 'prefix';
}
$moneda_decimales = (int)($_POST['moneda_decimales'] ?? 2);
if ($moneda_decimales < 0 || $moneda_decimales > 4) {
    $moneda_decimales = 2;
}
$moneda_separador_decimal = (string)($_POST['moneda_separador_decimal'] ?? '.');
$moneda_separador_decimal = $moneda_separador_decimal !== '' ? mb_substr($moneda_separador_decimal, 0, 1) : '.';
$moneda_separador_miles = (string)($_POST['moneda_separador_miles'] ?? ',');
$moneda_separador_miles = $moneda_separador_miles !== '' ? mb_substr($moneda_separador_miles, 0, 1) : ',';
if ($moneda_separador_decimal === $moneda_separador_miles) {
    $moneda_separador_decimal = '.';
    $moneda_separador_miles = ',';
}

$normalizeMapsEmbed = static function (string $value): string {
    $v = trim($value);
    if ($v !== '' && stripos($v, '<iframe') !== false) {
        if (preg_match('/src\s*=\s*"([^"]+)"/i', $v, $m)) {
            $v = trim((string)$m[1]);
        }
    }
    return $v;
};

$normalizePhoneDigits = static function (string $value): string {
    return preg_replace('/\D+/', '', trim($value)) ?? '';
};

$extractPhonesFromItem = static function (array $item) use ($normalizePhoneDigits): array {
    $phones = [];

    if (isset($item['telefonos'])) {
        if (is_array($item['telefonos'])) {
            foreach ($item['telefonos'] as $tel) {
                $rawTel = trim((string)$tel);
                if ($rawTel === '') {
                    continue;
                }
                $norm = $normalizePhoneDigits($rawTel);
                if ($norm === '' || isset($phones[$norm])) {
                    continue;
                }
                $phones[$norm] = $rawTel;
            }
        } elseif (is_string($item['telefonos'])) {
            $chunks = array_map('trim', explode(',', $item['telefonos']));
            foreach ($chunks as $rawTel) {
                if ($rawTel === '') {
                    continue;
                }
                $norm = $normalizePhoneDigits($rawTel);
                if ($norm === '' || isset($phones[$norm])) {
                    continue;
                }
                $phones[$norm] = $rawTel;
            }
        }
    }

    $rawCel = trim((string)($item['celular'] ?? ''));
    if ($rawCel !== '') {
        $normCel = $normalizePhoneDigits($rawCel);
        if ($normCel !== '' && !isset($phones[$normCel])) {
            $phones = [$normCel => $rawCel] + $phones;
        }
    }

    return array_values($phones);
};

// Compatibilidad: si un cliente antiguo aun envia maps_embed directo, lo aceptamos como fallback.
$maps_embed_legacy = $normalizeMapsEmbed((string)($_POST['maps_embed'] ?? ''));
if ($maps_embed_legacy !== '' && !preg_match('~^https?://www\.google\.com/maps/(embed\?pb=|q=|search/)~i', $maps_embed_legacy)) {
    $_SESSION['msg'] = 'El mapa debe ser un enlace válido de Google Maps (ideal: src del iframe /maps/embed?pb=...).';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
}

$ubicacionesJsonRaw = trim((string)($_POST['ubicaciones_json'] ?? ''));
$ubicaciones = [];
if ($ubicacionesJsonRaw !== '') {
    $decoded = json_decode($ubicacionesJsonRaw, true);
    if (!is_array($decoded)) {
        $_SESSION['msg'] = 'El campo de ubicaciones debe ser JSON válido.';
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos' . ($id ? '&empresa_cfg_id=' . (int)$id : ''));
        exit;
    }

    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }
        $nombreUbicacion = trim((string)($item['nombre'] ?? ''));
        $direccionUbicacion = trim((string)($item['direccion'] ?? ''));
        $telefonosUbicacion = $extractPhonesFromItem($item);
        $celularUbicacion = !empty($telefonosUbicacion) ? (string)$telefonosUbicacion[0] : '';
        $mapaUbicacion = $normalizeMapsEmbed((string)($item['maps_embed'] ?? ''));

        if ($mapaUbicacion !== '' && !preg_match('~^https?://www\.google\.com/maps/(embed\?pb=|q=|search/)~i', $mapaUbicacion)) {
            $_SESSION['msg'] = 'Cada mapa de ubicación debe ser un enlace válido de Google Maps.';
            header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos' . ($id ? '&empresa_cfg_id=' . (int)$id : ''));
            exit;
        }

        if ($nombreUbicacion === '' && $direccionUbicacion === '' && $celularUbicacion === '' && $mapaUbicacion === '') {
            continue;
        }

        $ubicaciones[] = [
            'nombre' => $nombreUbicacion,
            'direccion' => $direccionUbicacion,
            'celular' => $celularUbicacion,
            'telefonos' => $telefonosUbicacion,
            'maps_embed' => $mapaUbicacion,
        ];
    }
}

if (empty($ubicaciones) && ($direccion !== '' || $maps_embed_legacy !== '')) {
    $telefonoFallback = trim((string)$celular);
    $telefonosFallback = [];
    if ($telefonoFallback !== '') {
        $telefonosFallback[] = $telefonoFallback;
    }
    $ubicaciones[] = [
        'nombre' => 'Sede principal',
        'direccion' => $direccion,
        'celular' => $telefonoFallback,
        'telefonos' => $telefonosFallback,
        'maps_embed' => $maps_embed_legacy,
    ];
}

// El mapa principal en config_empresa se sincroniza con la primera sede con mapa.
$maps_embed = '';
foreach ($ubicaciones as $ubItem) {
    if (!is_array($ubItem)) {
        continue;
    }
    $mapaItem = trim((string)($ubItem['maps_embed'] ?? ''));
    if ($mapaItem !== '') {
        $maps_embed = $mapaItem;
        break;
    }
}
if ($maps_embed === '') {
    $maps_embed = $maps_embed_legacy;
}

$has_maps_embed = false;
$has_currency_columns = false;
$has_operation_columns = false;
$has_ubicaciones_json = false;
$has_logo_fondo_navbar = false;
$has_marketing_columns = false;
$has_pdf_fechas_columns = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM config_empresa LIKE 'maps_embed'");
    $has_maps_embed = (bool)$chk->fetch(PDO::FETCH_ASSOC);

    $stmtCols = $pdo->query("SHOW COLUMNS FROM config_empresa");
    $colsRows = $stmtCols ? $stmtCols->fetchAll(PDO::FETCH_ASSOC) : [];
    $colsMap = [];
    foreach ($colsRows as $colRow) {
        if (!empty($colRow['Field'])) {
            $colsMap[] = (string)$colRow['Field'];
        }
    }
    $has_currency_columns = in_array('moneda_codigo', $colsMap, true)
        && in_array('moneda_simbolo', $colsMap, true)
        && in_array('moneda_posicion', $colsMap, true)
        && in_array('moneda_decimales', $colsMap, true)
        && in_array('moneda_separador_decimal', $colsMap, true)
        && in_array('moneda_separador_miles', $colsMap, true);
    $has_operation_columns = in_array('modo_operativo', $colsMap, true)
        && in_array('portal_publico_enable', $colsMap, true);
    $has_ubicaciones_json = in_array('ubicaciones_json', $colsMap, true);
    $has_logo_fondo_navbar = in_array('logo_fondo_navbar', $colsMap, true);
    $has_marketing_columns = in_array('promo_web_activa', $colsMap, true)
        && in_array('promo_web_porcentaje', $colsMap, true)
        && in_array('promo_web_aplicar_carrito', $colsMap, true)
        && in_array('promo_web_mensaje', $colsMap, true)
        && in_array('promo_web_fecha_inicio', $colsMap, true)
        && in_array('promo_web_fecha_fin', $colsMap, true)
        && in_array('share_preview_titulo', $colsMap, true)
        && in_array('share_preview_descripcion', $colsMap, true)
        && in_array('share_preview_imagen', $colsMap, true);
    $has_pdf_fechas_columns = in_array('mostrar_fecha_ingreso_pdf', $colsMap, true)
        && in_array('mostrar_fecha_validacion_pdf', $colsMap, true);

    if (!$has_ubicaciones_json) {
        try {
            $pdo->exec("ALTER TABLE config_empresa ADD COLUMN ubicaciones_json TEXT NULL AFTER maps_embed");
            $has_ubicaciones_json = true;
        } catch (Throwable $e) {
            $has_ubicaciones_json = false;
        }
    }

    if (!$has_logo_fondo_navbar) {
        try {
            $pdo->exec("ALTER TABLE config_empresa ADD COLUMN logo_fondo_navbar VARCHAR(20) NULL DEFAULT '#ffffff' AFTER color_texto");
            $has_logo_fondo_navbar = true;
        } catch (Throwable $e) {
            $has_logo_fondo_navbar = false;
        }
    }

    if (!$has_marketing_columns) {
        $alterStatements = [
            "ALTER TABLE config_empresa ADD COLUMN promo_web_activa TINYINT(1) NOT NULL DEFAULT 0 AFTER portal_publico_enable",
            "ALTER TABLE config_empresa ADD COLUMN promo_web_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER promo_web_activa",
            "ALTER TABLE config_empresa ADD COLUMN promo_web_aplicar_carrito TINYINT(1) NOT NULL DEFAULT 0 AFTER promo_web_porcentaje",
            "ALTER TABLE config_empresa ADD COLUMN promo_web_mensaje VARCHAR(255) NULL AFTER promo_web_aplicar_carrito",
            "ALTER TABLE config_empresa ADD COLUMN promo_web_fecha_inicio DATE NULL AFTER promo_web_mensaje",
            "ALTER TABLE config_empresa ADD COLUMN promo_web_fecha_fin DATE NULL AFTER promo_web_fecha_inicio",
            "ALTER TABLE config_empresa ADD COLUMN share_preview_titulo VARCHAR(160) NULL AFTER promo_web_fecha_fin",
            "ALTER TABLE config_empresa ADD COLUMN share_preview_descripcion VARCHAR(255) NULL AFTER share_preview_titulo",
            "ALTER TABLE config_empresa ADD COLUMN share_preview_imagen VARCHAR(600) NULL AFTER share_preview_descripcion",
        ];

        foreach ($alterStatements as $alterSql) {
            try {
                $pdo->exec($alterSql);
            } catch (Throwable $e) {
                // Ignorar errores por columnas existentes para mantener compatibilidad.
            }
        }

        $stmtCols2 = $pdo->query("SHOW COLUMNS FROM config_empresa");
        $colsRows2 = $stmtCols2 ? $stmtCols2->fetchAll(PDO::FETCH_ASSOC) : [];
        $colsMap2 = [];
        foreach ($colsRows2 as $colRow2) {
            if (!empty($colRow2['Field'])) {
                $colsMap2[] = (string)$colRow2['Field'];
            }
        }
        $has_marketing_columns = in_array('promo_web_activa', $colsMap2, true)
            && in_array('promo_web_porcentaje', $colsMap2, true)
            && in_array('promo_web_aplicar_carrito', $colsMap2, true)
            && in_array('promo_web_mensaje', $colsMap2, true)
            && in_array('promo_web_fecha_inicio', $colsMap2, true)
            && in_array('promo_web_fecha_fin', $colsMap2, true)
            && in_array('share_preview_titulo', $colsMap2, true)
            && in_array('share_preview_descripcion', $colsMap2, true)
            && in_array('share_preview_imagen', $colsMap2, true);
    }

    if (!$has_pdf_fechas_columns) {
        $alterPdfFechaStatements = [
            "ALTER TABLE config_empresa ADD COLUMN mostrar_fecha_ingreso_pdf TINYINT(1) NOT NULL DEFAULT 1",
            "ALTER TABLE config_empresa ADD COLUMN mostrar_fecha_validacion_pdf TINYINT(1) NOT NULL DEFAULT 1",
        ];
        foreach ($alterPdfFechaStatements as $alterPdfFechaSql) {
            try {
                $pdo->exec($alterPdfFechaSql);
            } catch (Throwable $e) {
                // Ignorar si ya existe para compatibilidad.
            }
        }

        $stmtCols3 = $pdo->query("SHOW COLUMNS FROM config_empresa");
        $colsRows3 = $stmtCols3 ? $stmtCols3->fetchAll(PDO::FETCH_ASSOC) : [];
        $colsMap3 = [];
        foreach ($colsRows3 as $colRow3) {
            if (!empty($colRow3['Field'])) {
                $colsMap3[] = (string)$colRow3['Field'];
            }
        }
        $has_pdf_fechas_columns = in_array('mostrar_fecha_ingreso_pdf', $colsMap3, true)
            && in_array('mostrar_fecha_validacion_pdf', $colsMap3, true);
    }
} catch (Exception $e) {
    $has_maps_embed = false;
    $has_currency_columns = false;
    $has_operation_columns = false;
    $has_ubicaciones_json = false;
    $has_logo_fondo_navbar = false;
    $has_marketing_columns = false;
    $has_pdf_fechas_columns = false;
}

// Validación básica
if (!$nombre || !$ruc || !$direccion || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
    exit;
}

// Procesamiento del logo (solo PNG, sobrescribe archivo)
$logo = $empresa['logo'] ?? '../uploads/empresa/logo_empresa.png';
$firma = $empresa['firma'] ?? '../uploads/empresa/firma.png';

$srcDir = realpath(__DIR__ . '/..');
if ($srcDir === false) {
    $srcDir = __DIR__ . '/..';
}

$projectRoot = realpath(__DIR__ . '/../..'); // apunta a raíz del proyecto (fuera de /src)
if ($projectRoot === false) {
    $projectRoot = __DIR__ . '/../..';
}

$baseDir = $projectRoot;
if ($baseDir === false) {
    $baseDir = __DIR__ . '/../..';
}

$resolveStoredAbsolutePath = function (string $storedPath) use ($srcDir, $projectRoot): string {
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));
    if (strpos($normalized, '../uploads/') === 0) {
        $normalized = substr($normalized, 3);
    }
    if (strpos($normalized, 'uploads/') === 0) {
        return rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }
    return rtrim($srcDir, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
};

$migrateLegacyAssetToUploads = function (string $storedPath, string $targetRelativeUpload) use ($resolveStoredAbsolutePath, $projectRoot): string {
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));
    if (strpos($normalized, '../uploads/') === 0 || strpos($normalized, 'uploads/') === 0) {
        return $storedPath;
    }
    if (strpos($normalized, 'images/empresa/') !== 0) {
        return $storedPath;
    }

    $src = $resolveStoredAbsolutePath($storedPath);
    if (!is_file($src)) {
        return $storedPath;
    }

    $targetRelativeUpload = ltrim($targetRelativeUpload, '/');
    $dest = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetRelativeUpload);
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0775, true);
    }

    if (@copy($src, $dest)) {
        return '../' . $targetRelativeUpload;
    }

    return $storedPath;
};

$logo = $migrateLegacyAssetToUploads((string)$logo, 'uploads/empresa/logo_empresa.png');
$firma = $migrateLegacyAssetToUploads((string)$firma, 'uploads/empresa/firma.png');

$quitarLogo = isset($_POST['quitar_logo']) && (string)$_POST['quitar_logo'] === '1';
$quitarFirma = isset($_POST['quitar_firma']) && (string)$_POST['quitar_firma'] === '1';

if ($quitarLogo) {
    $logoAbsPath = $resolveStoredAbsolutePath((string)$logo);
    if (is_file($logoAbsPath)) {
        @unlink($logoAbsPath);
    }
    $logo = '';
}

if ($quitarFirma) {
    $firmaAbsPath = $resolveStoredAbsolutePath((string)$firma);
    if (is_file($firmaAbsPath)) {
        @unlink($firmaAbsPath);
    }
    $firma = '';
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

$isImageUploadAllowed = function (string $tmpPath, string $originalName): bool {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
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
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                return false;
            }
        }
    }
    return true;
};

$saveSharePreviewUpload = function (string $field) use ($baseDir, $describeUploadError, $isImageUploadAllowed): ?string {
    if (!isset($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = $describeUploadError((int)$file['error']);
        $_SESSION['msg'] = "Error al subir la imagen de compartido: {$msg}";
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    $tmp = $file['tmp_name'] ?? '';
    $name = $file['name'] ?? '';
    if (!$isImageUploadAllowed($tmp, $name)) {
        $_SESSION['msg'] = 'La imagen de compartido debe ser PNG, JPG, JPEG o WEBP valida.';
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $relativeDir = 'uploads/empresa';
    $relativePath = $relativeDir . '/share_preview_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = rtrim($baseDir, '\/') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $destDir = dirname($destino);

    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            $_SESSION['msg'] = 'No se pudo crear la carpeta para la imagen de compartido.';
            header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
            exit;
        }
    }

    if (!move_uploaded_file($tmp, $destino)) {
        $_SESSION['msg'] = 'Error al guardar la imagen de compartido.';
        header('Location: ' . BASE_URL . 'dashboard.php?vista=config_empresa_datos');
        exit;
    }

    return '../' . ltrim($relativePath, '/');
};

$nuevoLogo = $saveUpload('logo', 'uploads/empresa/logo_empresa.png', 'el logo');
if ($nuevoLogo) {
    $logo = '../' . ltrim($nuevoLogo, '/');
}

// Procesamiento de la firma (solo PNG, sobrescribe archivo)

$nuevaFirma = $saveUpload('firma', 'uploads/empresa/firma.png', 'la firma');
if ($nuevaFirma) {
    $firma = '../' . ltrim($nuevaFirma, '/');
}

$nuevaSharePreviewImagen = $saveSharePreviewUpload('share_preview_imagen_file');
if ($nuevaSharePreviewImagen) {
    $share_preview_imagen = $nuevaSharePreviewImagen;
}
// Colores y textos
$color_principal  = trim($_POST['color_principal'] ?? '#1f4f82');
$color_secundario = trim($_POST['color_secundario'] ?? '#e9f3fb');
$color_footer     = trim($_POST['color_footer'] ?? '#173a60');
$color_botones    = trim($_POST['color_botones'] ?? '#2f74bd');
$color_texto      = trim($_POST['color_texto'] ?? '#1c2a3b');
$logo_fondo_navbar = strtolower(trim((string)($_POST['logo_fondo_navbar'] ?? '#ffffff')));
if (!preg_match('/^#[0-9a-f]{6}$/i', $logo_fondo_navbar)) {
    $logo_fondo_navbar = '#ffffff';
}
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
            $ruta = $resolveStoredAbsolutePath((string)$imagenes_carrusel[$idx]);
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_carrusel[$idx]);
        }
    }
    $imagenes_carrusel = array_values($imagenes_carrusel);
}
if (!empty($_FILES['imagenes_carrusel']['name'][0])) {
    $uploadDir = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'empresa' . DIRECTORY_SEPARATOR . 'carrusel' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_carrusel']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_carrusel']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('carrusel_') . '.' . $ext;
                $rutaRelativa = '../uploads/empresa/carrusel/' . $nuevoNombre;
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
            $ruta = $resolveStoredAbsolutePath((string)$imagenes_institucionales[$idx]);
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_institucionales[$idx]);
        }
    }
    $imagenes_institucionales = array_values($imagenes_institucionales);
}
if (!empty($_FILES['imagenes_institucionales']['name'][0])) {
    $uploadDir = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'empresa' . DIRECTORY_SEPARATOR . 'institucional' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_institucionales']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_institucionales']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('institucional_') . '.' . $ext;
                $rutaRelativa = '../uploads/empresa/institucional/' . $nuevoNombre;
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
        if ($has_ubicaciones_json) {
            $sql .= ", ubicaciones_json=?";
            $params[] = json_encode($ubicaciones, JSON_UNESCAPED_UNICODE);
        }

        if ($has_currency_columns) {
            $sql .= ", moneda_codigo=?, moneda_simbolo=?, moneda_posicion=?, moneda_decimales=?, moneda_separador_decimal=?, moneda_separador_miles=?";
            $params[] = $moneda_codigo;
            $params[] = $moneda_simbolo;
            $params[] = $moneda_posicion;
            $params[] = $moneda_decimales;
            $params[] = $moneda_separador_decimal;
            $params[] = $moneda_separador_miles;
        }

        if ($has_operation_columns) {
            $modoOperativoSql = $modo_operativo;
            if ($modoOperativoSql === null) {
                $modoOperativoSql = strtoupper(trim((string)($empresa['modo_operativo'] ?? 'PARTICULAR')));
                if (!in_array($modoOperativoSql, ['PARTICULAR', 'SIS', 'MIXTO'], true)) {
                    $modoOperativoSql = 'PARTICULAR';
                }
            }
            $portalPublicoSql = $portal_publico_enable;
            if ($portalPublicoSql === null) {
                $portalPublicoSql = ((int)($empresa['portal_publico_enable'] ?? 1) === 1) ? 1 : 0;
            }

            $sql .= ", modo_operativo=?, portal_publico_enable=?";
            $params[] = $modoOperativoSql;
            $params[] = $portalPublicoSql;
        }

        $sql .= ",
            color_principal=?, color_secundario=?, color_footer=?, color_botones=?, color_texto=?";

        if ($has_logo_fondo_navbar) {
            $sql .= ", logo_fondo_navbar=?";
        }

        if ($has_marketing_columns) {
            $sql .= ", promo_web_activa=?, promo_web_porcentaje=?, promo_web_aplicar_carrito=?, promo_web_mensaje=?, promo_web_fecha_inicio=?, promo_web_fecha_fin=?, share_preview_titulo=?, share_preview_descripcion=?, share_preview_imagen=?";
        }
        if ($has_pdf_fechas_columns) {
            $sql .= ", mostrar_fecha_ingreso_pdf=?, mostrar_fecha_validacion_pdf=?";
        }

        $sql .= ", tamano_letra=?,
            frase_promocion=?, oferta_mes=?,
            imagenes_carrusel=?, imagenes_institucionales=?, servicios=?, testimonios=?, redes_sociales=?,
            menu_inicio=?, menu_servicios=?, menu_testimonios=?, menu_contacto=?
            WHERE id=?";

        $params = array_merge($params, [
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto,
        ]);
        if ($has_logo_fondo_navbar) {
            $params[] = $logo_fondo_navbar;
        }
        if ($has_marketing_columns) {
            $params[] = (int)$promo_web_activa;
            $params[] = (float)$promo_web_porcentaje;
            $params[] = (int)$promo_web_aplicar_carrito;
            $params[] = $promo_web_mensaje;
            $params[] = $promo_web_fecha_inicio;
            $params[] = $promo_web_fecha_fin;
            $params[] = $share_preview_titulo;
            $params[] = $share_preview_descripcion;
            $params[] = $share_preview_imagen;
        }
        if ($has_pdf_fechas_columns) {
            $params[] = (int)$mostrar_fecha_ingreso_pdf;
            $params[] = (int)$mostrar_fecha_validacion_pdf;
        }
        $params = array_merge($params, [
            $tamano_letra,
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
        if ($has_ubicaciones_json) {
            $cols[] = 'ubicaciones_json';
            $vals[] = json_encode($ubicaciones, JSON_UNESCAPED_UNICODE);
        }
        if ($has_currency_columns) {
            $cols[] = 'moneda_codigo';
            $cols[] = 'moneda_simbolo';
            $cols[] = 'moneda_posicion';
            $cols[] = 'moneda_decimales';
            $cols[] = 'moneda_separador_decimal';
            $cols[] = 'moneda_separador_miles';
            $vals[] = $moneda_codigo;
            $vals[] = $moneda_simbolo;
            $vals[] = $moneda_posicion;
            $vals[] = $moneda_decimales;
            $vals[] = $moneda_separador_decimal;
            $vals[] = $moneda_separador_miles;
        }
        if ($has_operation_columns && $has_operation_settings_input) {
            $cols[] = 'modo_operativo';
            $cols[] = 'portal_publico_enable';
            $vals[] = $modo_operativo ?? 'PARTICULAR';
            $vals[] = ($portal_publico_enable === null) ? 1 : (int)$portal_publico_enable;
        }
        $cols = array_merge($cols, [
            'color_principal', 'color_secundario', 'color_footer', 'color_botones', 'color_texto'
        ]);
        $vals = array_merge($vals, [
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto
        ]);

        if ($has_logo_fondo_navbar) {
            $cols[] = 'logo_fondo_navbar';
            $vals[] = $logo_fondo_navbar;
        }

        if ($has_marketing_columns) {
            $cols = array_merge($cols, [
                'promo_web_activa', 'promo_web_porcentaje', 'promo_web_aplicar_carrito',
                'promo_web_mensaje', 'promo_web_fecha_inicio', 'promo_web_fecha_fin',
                'share_preview_titulo', 'share_preview_descripcion', 'share_preview_imagen'
            ]);
            $vals = array_merge($vals, [
                (int)$promo_web_activa, (float)$promo_web_porcentaje, (int)$promo_web_aplicar_carrito,
                $promo_web_mensaje, $promo_web_fecha_inicio, $promo_web_fecha_fin,
                $share_preview_titulo, $share_preview_descripcion, $share_preview_imagen
            ]);
        }
        if ($has_pdf_fechas_columns) {
            $cols[] = 'mostrar_fecha_ingreso_pdf';
            $cols[] = 'mostrar_fecha_validacion_pdf';
            $vals[] = (int)$mostrar_fecha_ingreso_pdf;
            $vals[] = (int)$mostrar_fecha_validacion_pdf;
        }

        $cols = array_merge($cols, [
            'tamano_letra',
            'frase_promocion', 'oferta_mes',
            'imagenes_carrusel', 'imagenes_institucionales', 'servicios', 'testimonios', 'redes_sociales',
            'menu_inicio', 'menu_servicios', 'menu_testimonios', 'menu_contacto'
        ]);
        $vals = array_merge($vals, [
            $tamano_letra,
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
    $redirect = BASE_URL . 'dashboard.php?vista=config_empresa_datos';
    if ($id) {
        $redirect .= '&empresa_cfg_id=' . (int)$id;
    }
    header('Location: ' . $redirect);
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al guardar: ' . $e->getMessage();
    $redirect = BASE_URL . 'dashboard.php?vista=config_empresa_datos';
    if ($id) {
        $redirect .= '&empresa_cfg_id=' . (int)$id;
    }
    header('Location: ' . $redirect);
    exit;
}
