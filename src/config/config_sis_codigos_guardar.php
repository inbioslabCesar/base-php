<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
    exit;
}

const SIS_ICONO_MAX_BYTES = 1048576; // 1 MB: los íconos deben ser livianos
const SIS_ICONO_MAX_LADO = 256;
const SIS_ICONO_DIR_REL = 'uploads/sis_codigos';

$empresaCfgId = (int)($_POST['empresa_cfg_id'] ?? 0);
$redirect = BASE_URL . 'dashboard.php?vista=config_operacion_engineer' . ($empresaCfgId > 0 ? '&empresa_cfg_id=' . $empresaCfgId : '');

$terminar = static function (string $mensaje) use ($redirect): void {
    $_SESSION['msg'] = $mensaje;
    header('Location: ' . $redirect);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $terminar('Solicitud inválida.');
}
if (!app_database_has_table($pdo, 'sis_codigos_prestacionales')) {
    $terminar('Falta la tabla sis_codigos_prestacionales. Ejecuta sql/2026_10_18_codigos_prestacionales_sis/migracion.sql.');
}

$existentes = [];
foreach ($pdo->query('SELECT * FROM sis_codigos_prestacionales')->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $existentes[(int)$fila['id']] = $fila;
}

$postCodigos = is_array($_POST['cod'] ?? null) ? $_POST['cod'] : [];
$cambios = [];
foreach ($existentes as $id => $actual) {
    $in = is_array($postCodigos[$id] ?? null) ? $postCodigos[$id] : [];
    $nombre = trim((string)($in['nombre'] ?? ''));
    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $terminar("El nombre del código {$actual['codigo']} es obligatorio (máx. 80 caracteres).");
    }
    $color = trim((string)($in['color'] ?? ''));
    $cambio = [
        'nombre' => $nombre,
        'descripcion_minsa' => mb_substr(trim((string)($in['descripcion_minsa'] ?? '')), 0, 255) ?: null,
        'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $actual['color'],
        'activo' => !empty($in['activo']) ? 1 : 0,
        'edad_min_meses' => $actual['edad_min_meses'] !== null ? (int)$actual['edad_min_meses'] : null,
        'edad_max_meses' => $actual['edad_max_meses'] !== null ? (int)$actual['edad_max_meses'] : null,
    ];

    // Los rangos se capturan en años cumplidos: "hasta 11" equivale a 11 años, 11 meses, 29 días.
    if ($actual['tipo_regla'] === 'EDAD') {
        $desde = trim((string)($in['desde'] ?? ''));
        $hasta = trim((string)($in['hasta'] ?? ''));
        if (!ctype_digit($desde) || ($hasta !== '' && !ctype_digit($hasta))) {
            $terminar("Los años del código {$actual['codigo']} deben ser números enteros (el 'hasta' puede quedar vacío).");
        }
        $cambio['edad_min_meses'] = (int)$desde * 12;
        $cambio['edad_max_meses'] = $hasta === '' ? null : (((int)$hasta + 1) * 12) - 1;
        if ($cambio['edad_max_meses'] !== null && $cambio['edad_max_meses'] < $cambio['edad_min_meses']) {
            $terminar("En el código {$actual['codigo']} el 'hasta' no puede ser menor que el 'desde'.");
        }
    }
    $cambios[$id] = $cambio;
}

// Los rangos de edad activos deben empezar en 0 y ser continuos (sin solapes ni huecos).
$activosEdad = [];
foreach ($cambios as $id => $cambio) {
    if ($existentes[$id]['tipo_regla'] === 'EDAD' && $cambio['activo'] === 1) {
        $activosEdad[] = $cambio + ['codigo' => $existentes[$id]['codigo']];
    }
}
usort($activosEdad, static fn($a, $b) => $a['edad_min_meses'] <=> $b['edad_min_meses']);
foreach ($activosEdad as $i => $rango) {
    $anterior = $activosEdad[$i - 1] ?? null;
    $esperado = $anterior === null ? 0 : ($anterior['edad_max_meses'] === null ? null : $anterior['edad_max_meses'] + 1);
    if ($esperado === null || $rango['edad_min_meses'] !== $esperado) {
        $terminar("Los rangos de edad activos deben ser continuos, sin solapes ni huecos, desde 0 años (revisa el código {$rango['codigo']}).");
    }
}

$projectRoot = realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');

// Valida y guarda el ícono; devuelve [ruta relativa|null, error|null]. PNG/WEBP se reducen y optimizan a WEBP.
$guardarIcono = static function (array $file, string $codigo) use ($projectRoot): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, 'La subida del ícono falló o excede el tamaño permitido.'];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!is_uploaded_file($tmp) || !in_array($ext, ['png', 'webp', 'pdf'], true)) {
        return [null, 'El ícono debe ser PNG, WEBP o PDF.'];
    }
    if ((int)($file['size'] ?? 0) > SIS_ICONO_MAX_BYTES) {
        return [null, 'El ícono supera 1 MB; usa un archivo más liviano.'];
    }

    $mime = '';
    if (function_exists('finfo_open') && ($finfo = finfo_open(FILEINFO_MIME_TYPE))) {
        $mime = (string)finfo_file($finfo, $tmp);
        finfo_close($finfo);
    }

    $destDir = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, SIS_ICONO_DIR_REL);
    if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        return [null, 'No se pudo crear la carpeta de íconos.'];
    }
    $base = 'cod_' . $codigo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

    if ($ext === 'pdf') {
        $cabecera = (string)file_get_contents($tmp, false, null, 0, 5);
        if ($mime !== 'application/pdf' || $cabecera !== '%PDF-') {
            return [null, 'El archivo no es un PDF válido.'];
        }
        $rel = SIS_ICONO_DIR_REL . '/' . $base . '.pdf';
        return move_uploaded_file($tmp, $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel))
            ? [$rel, null]
            : [null, 'No se pudo guardar el PDF.'];
    }

    $dim = @getimagesize($tmp);
    $mimesOk = ['image/png', 'image/webp'];
    if (!is_array($dim) || !in_array($dim['mime'] ?? '', $mimesOk, true) || ($mime !== '' && !in_array($mime, $mimesOk, true))) {
        return [null, 'La imagen no es un PNG/WEBP válido.'];
    }
    if ($dim[0] < 1 || $dim[1] < 1 || $dim[0] > 4000 || $dim[1] > 4000) {
        return [null, 'Dimensiones de imagen no permitidas (máx. 4000 px).'];
    }

    // Sin GD/WEBP se conserva el archivo original ya validado.
    if (!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) {
        $rel = SIS_ICONO_DIR_REL . '/' . $base . '.' . $ext;
        return move_uploaded_file($tmp, $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel))
            ? [$rel, null]
            : [null, 'No se pudo guardar la imagen.'];
    }

    $src = $dim['mime'] === 'image/png' ? @imagecreatefrompng($tmp) : @imagecreatefromwebp($tmp);
    if (!$src) {
        return [null, 'No se pudo procesar la imagen.'];
    }
    $escala = min(1, SIS_ICONO_MAX_LADO / max($dim[0], $dim[1]));
    $nw = max(1, (int)round($dim[0] * $escala));
    $nh = max(1, (int)round($dim[1] * $escala));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $dim[0], $dim[1]);

    $rel = SIS_ICONO_DIR_REL . '/' . $base . '.webp';
    $ok = imagewebp($dst, $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel), 85);

    return $ok ? [$rel, null] : [null, 'No se pudo optimizar la imagen.'];
};

$borrarIconoAnterior = static function (?string $ruta) use ($projectRoot): void {
    $ruta = str_replace('\\', '/', trim((string)$ruta));
    if ($ruta === '' || strpos($ruta, SIS_ICONO_DIR_REL . '/') !== 0 || strpos($ruta, '..') !== false) {
        return;
    }
    $abs = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta);
    if (is_file($abs)) {
        @unlink($abs);
    }
};

$avisos = [];
$upd = $pdo->prepare('UPDATE sis_codigos_prestacionales SET nombre = ?, descripcion_minsa = ?, color = ?, activo = ?, edad_min_meses = ?, edad_max_meses = ?, icono_ruta = ? WHERE id = ?');
try {
    foreach ($cambios as $id => $cambio) {
        $actual = $existentes[$id];
        $icono = $actual['icono_ruta'];
        $anterior = null;

        $quitar = !empty($_POST['quitar_icono'][$id]);
        $archivo = $_FILES['icono_' . $id] ?? null;
        if ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$nuevo, $error] = $guardarIcono($archivo, (string)$actual['codigo']);
            if ($nuevo !== null) {
                $anterior = $icono;
                $icono = $nuevo;
            } else {
                $avisos[] = "Código {$actual['codigo']}: {$error}";
            }
        } elseif ($quitar) {
            $anterior = $icono;
            $icono = null;
        }

        $upd->execute([
            $cambio['nombre'],
            $cambio['descripcion_minsa'],
            $cambio['color'],
            $cambio['activo'],
            $cambio['edad_min_meses'],
            $cambio['edad_max_meses'],
            $icono,
            $id,
        ]);
        if ($anterior !== null && $anterior !== $icono) {
            $borrarIconoAnterior($anterior);
        }
    }
} catch (Throwable $e) {
    error_log('config_sis_codigos_guardar: ' . $e->getMessage());
    $terminar('No se pudieron guardar los códigos prestacionales.');
}

$terminar('Códigos prestacionales actualizados.' . ($avisos ? ' Avisos: ' . implode(' ', $avisos) : ''));
