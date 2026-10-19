<?php
// Utilidades del Cotizador Express (sin escritura en BD).

require_once __DIR__ . '/../../config/ui_theme.php';
require_once __DIR__ . '/../../config/currency.php';

if (!function_exists('cotExpHasColumn')) {
    function cotExpHasColumn(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

if (!function_exists('cotExpContexto')) {
    // Solo el modo PARTICULAR muestra precios. SIS y MIXTO quedan en modo informativo.
    function cotExpContexto(PDO $pdo): array
    {
        $ctx = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [];
        $modo = strtoupper((string)($ctx['modo_operativo'] ?? 'PARTICULAR'));
        return [
            'modo' => $modo,
            'muestra_precios' => cotizadorExpressPuedeMostrarPrecio($modo),
        ];
    }
}

if (!function_exists('cotizadorExpressPuedeMostrarPrecio')) {
    // Único punto de decisión sobre mostrar precios; aquí se extenderá la lógica SIS cuando exista.
    function cotizadorExpressPuedeMostrarPrecio(string $modoOperativo): bool
    {
        return strtoupper($modoOperativo) === 'PARTICULAR';
    }
}

if (!function_exists('cotExpPrecioUnitario')) {
    function cotExpPrecioUnitario(float $publico, float $convenio, bool $usarConvenio, float $descuento): float
    {
        if ($usarConvenio && $convenio > 0) {
            return round($convenio, 2);
        }
        if ($descuento > 0) {
            return round($publico * (1 - ($descuento / 100)), 2);
        }
        return round($publico, 2);
    }
}

if (!function_exists('cotExpCatalogo')) {
    function cotExpCatalogo(PDO $pdo, bool $conPrecios): array
    {
        $cols = [
            'id',
            "COALESCE(codigo, '') AS codigo",
            "COALESCE(nombre, '') AS nombre",
            "COALESCE(area, '') AS area",
            "COALESCE(tiempo_respuesta, '') AS tiempo_respuesta",
            "COALESCE(preanalitica_cliente, '') AS preanalitica_cliente",
            cotExpHasColumn($pdo, 'examenes', 'tipo_muestra') ? "COALESCE(tipo_muestra, '') AS tipo_muestra" : "'' AS tipo_muestra",
            cotExpHasColumn($pdo, 'examenes', 'tipo_tubo') ? "COALESCE(tipo_tubo, '') AS tipo_tubo" : "'' AS tipo_tubo",
        ];
        if ($conPrecios) {
            $cols[] = 'COALESCE(precio_publico, 0) AS precio_publico';
            $cols[] = cotExpHasColumn($pdo, 'examenes', 'precio_convenio') ? 'COALESCE(precio_convenio, 0) AS precio_convenio' : '0 AS precio_convenio';
        }

        $stmt = $pdo->query('SELECT ' . implode(', ', $cols) . ' FROM examenes WHERE vigente = 1 ORDER BY nombre ASC');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            foreach (['codigo', 'nombre', 'area', 'tiempo_respuesta', 'preanalitica_cliente', 'tipo_muestra', 'tipo_tubo'] as $k) {
                $row[$k] = trim((string)$row[$k]);
            }
            if ($conPrecios) {
                $row['precio_publico'] = round((float)$row['precio_publico'], 2);
                $row['precio_convenio'] = round((float)$row['precio_convenio'], 2);
            }
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('cotExpNormalizarTelefono')) {
    // Devuelve el primer celular peruano válido (51 + 9 dígitos) o '' si no hay.
    function cotExpNormalizarTelefono(string $raw): string
    {
        $partes = preg_split('/[\/,;\-]+/', trim($raw)) ?: [];
        $partes[] = $raw;
        foreach ($partes as $parte) {
            $digits = preg_replace('/\D+/', '', $parte) ?? '';
            if (strlen($digits) === 9 && $digits[0] === '9') {
                return '51' . $digits;
            }
            if (strlen($digits) === 11 && strpos($digits, '51') === 0 && $digits[2] === '9') {
                return $digits;
            }
        }
        return '';
    }
}

if (!function_exists('cotExpPerfiles')) {
    // Convenios y empresas con su regla de tarifa. Solo se usa cuando se muestran precios.
    function cotExpPerfiles(PDO $pdo): array
    {
        $perfiles = [];

        $selUsar = cotExpHasColumn($pdo, 'convenios', 'usar_precio_convenio') ? 'usar_precio_convenio' : '0';
        $selTel = cotExpHasColumn($pdo, 'convenios', 'telefono') ? "COALESCE(telefono, '')" : "''";
        try {
            $stmt = $pdo->query("SELECT id, nombre, COALESCE(descuento, 0) AS descuento, {$selUsar} AS usar, {$selTel} AS telefono FROM convenios ORDER BY nombre");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $r) {
                $perfiles[] = [
                    'tipo' => 'convenio',
                    'id' => (int)$r['id'],
                    'nombre' => trim((string)$r['nombre']),
                    'descuento' => (float)$r['descuento'],
                    'usar' => (int)$r['usar'] === 1,
                    'telefono' => cotExpNormalizarTelefono((string)$r['telefono']),
                ];
            }
        } catch (Throwable $e) {
        }

        $selUsar = cotExpHasColumn($pdo, 'empresas', 'usar_precio_convenio') ? 'usar_precio_convenio' : '0';
        $selTel = cotExpHasColumn($pdo, 'empresas', 'celular')
            ? "COALESCE(NULLIF(TRIM(celular), ''), COALESCE(telefono, ''))"
            : (cotExpHasColumn($pdo, 'empresas', 'telefono') ? "COALESCE(telefono, '')" : "''");
        $where = cotExpHasColumn($pdo, 'empresas', 'estado') ? 'WHERE estado = 1' : '';
        try {
            $stmt = $pdo->query("SELECT id, COALESCE(NULLIF(TRIM(nombre_comercial), ''), razon_social) AS nombre, COALESCE(descuento, 0) AS descuento, {$selUsar} AS usar, {$selTel} AS telefono FROM empresas {$where} ORDER BY nombre");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $r) {
                $perfiles[] = [
                    'tipo' => 'empresa',
                    'id' => (int)$r['id'],
                    'nombre' => trim((string)$r['nombre']),
                    'descuento' => (float)$r['descuento'],
                    'usar' => (int)$r['usar'] === 1,
                    'telefono' => cotExpNormalizarTelefono((string)$r['telefono']),
                ];
            }
        } catch (Throwable $e) {
        }

        return $perfiles;
    }
}

if (!function_exists('cotExpPerfilPorId')) {
    function cotExpPerfilPorId(PDO $pdo, string $tipo, int $id): ?array
    {
        foreach (cotExpPerfiles($pdo) as $p) {
            if ($p['tipo'] === $tipo && $p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }
}

if (!function_exists('cotExpEmpresa')) {
    function cotExpEmpresa(PDO $pdo): array
    {
        $cfg = ui_theme_fetch_company_config($pdo);
        return [
            'nombre' => trim((string)($cfg['nombre'] ?? '')) ?: 'Laboratorio',
            'ruc' => trim((string)($cfg['ruc'] ?? '')),
            'direccion' => trim((string)($cfg['direccion'] ?? '')),
            'telefono' => trim((string)($cfg['telefono'] ?? '')),
            'celular' => trim((string)($cfg['celular'] ?? '')),
            'email' => trim((string)($cfg['email'] ?? '')),
            'logo' => trim((string)($cfg['logo'] ?? '')),
        ];
    }
}

if (!function_exists('cotExpLogoTicketDataUri')) {
    // Logo reducido (máx. 300 px de ancho, en escala de grises sobre blanco) para impresoras térmicas.
    function cotExpLogoTicketDataUri(string $logoConfig): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }
        $root = dirname(__DIR__, 3);
        $base = basename(str_replace('\\', '/', $logoConfig !== '' ? $logoConfig : 'logo_empresa.png'));
        foreach (['uploads/empresa/' . $base, 'src/images/empresa/' . $base, 'uploads/empresa/logo_empresa.png'] as $rel) {
            $path = $root . '/' . $rel;
            $info = is_file($path) ? @getimagesize($path) : false;
            if (!is_array($info)) {
                continue;
            }
            $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($path)
                : ($info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($path)
                : ($info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false));
            if (!$src) {
                continue;
            }
            $sw = imagesx($src);
            $sh = imagesy($src);
            $scale = min(1, 300 / max(1, $sw));
            $nw = max(1, (int)round($sw * $scale));
            $nh = max(1, (int)round($sh * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagealphablending($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
            imagefilter($dst, IMG_FILTER_GRAYSCALE);
            imagefilter($dst, IMG_FILTER_CONTRAST, -20);
            ob_start();
            imagepng($dst, null, 7);
            $png = ob_get_clean();
            return $png ? 'data:image/png;base64,' . base64_encode($png) : '';
        }
        return '';
    }
}

if (!function_exists('cotExpLogoDataUri')) {
    function cotExpLogoDataUri(string $logoConfig): string
    {
        $root = dirname(__DIR__, 3);
        $base = basename(str_replace('\\', '/', $logoConfig !== '' ? $logoConfig : 'logo_empresa.png'));
        foreach (['uploads/empresa/' . $base, 'src/images/empresa/' . $base, 'uploads/empresa/logo_empresa.png'] as $rel) {
            $path = $root . '/' . $rel;
            if (!is_file($path)) {
                continue;
            }
            $info = @getimagesize($path);
            $mime = is_array($info) ? strtolower((string)($info['mime'] ?? '')) : '';
            if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
                continue;
            }
            // Se reduce a 500 px de ancho máx.: un logo pesado superaría el límite de HTML de mPDF.
            if (function_exists('imagecreatetruecolor') && (int)$info[0] > 0) {
                $src = $mime === 'image/png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = min(1, 500 / max(1, $sw));
                    $nw = max(1, (int)round($sw * $scale));
                    $nh = max(1, (int)round($sh * $scale));
                    $dst = imagecreatetruecolor($nw, $nh);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
                    ob_start();
                    imagepng($dst, null, 7);
                    $png = ob_get_clean();
                    if ($png && strlen($png) <= 220000) {
                        return 'data:image/png;base64,' . base64_encode($png);
                    }
                }
                continue;
            }
            if (filesize($path) > 150000) {
                continue;
            }
            $raw = @file_get_contents($path);
            if ($raw !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        }
        return '';
    }
}
