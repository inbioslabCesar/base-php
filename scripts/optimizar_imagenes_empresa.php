<?php
/**
 * Optimiza las imagenes de carrusel e institucionales YA subidas (crea copias livianas, NO borra los originales).
 *
 * Uso (desde la raiz del proyecto):
 *   php scripts/optimizar_imagenes_empresa.php --empresa=medditech            (simulacro: solo informa)
 *   php scripts/optimizar_imagenes_empresa.php --empresa=medditech --aplicar  (crea copias y actualiza config_empresa)
 *
 * Con --aplicar guarda antes un respaldo de los JSON originales en tmp/ y deja los archivos originales en uploads/.
 * Para revertir: restaurar los JSON del respaldo. Es idempotente (omite las copias ya optimizadas).
 */
if (PHP_SAPI !== 'cli') {
    exit('Solo CLI.');
}

$empresa = '';
$aplicar = false;
foreach (array_slice($argv, 1) as $a) {
    if (strpos($a, '--empresa=') === 0) {
        $empresa = trim(substr($a, 10));
    } elseif ($a === '--aplicar') {
        $aplicar = true;
    }
}
if ($empresa !== '') {
    putenv('EMPRESA=' . $empresa);
}

require_once __DIR__ . '/../src/conexion/conexion.php';
require_once __DIR__ . '/../src/config/imagen_optimizar.php';

$raiz = realpath(__DIR__ . '/..');
$umbralBytes = 300 * 1024;
$grupos = ['imagenes_carrusel' => 1600, 'imagenes_institucionales' => 1200];

$resolver = static function (string $ruta) use ($raiz): ?string {
    $r = str_replace('\\', '/', trim($ruta));
    if ($r === '' || preg_match('#^(https?:)?//#i', $r) || strpos($r, 'data:') === 0) {
        return null;
    }
    $r = ltrim(preg_replace('#^(\.\./|\./)+#', '', $r), '/');
    if (strpos($r, 'src/images/empresa/') === 0) {
        $r = 'uploads/empresa/' . substr($r, strlen('src/images/empresa/'));
    } elseif (strpos($r, 'uploads/') !== 0) {
        $r = 'uploads/empresa/' . $r;
    }
    $abs = $raiz . '/' . $r;
    return is_file($abs) ? $abs : null;
};

$filas = $pdo->query('SELECT id, nombre, imagenes_carrusel, imagenes_institucionales FROM config_empresa')->fetchAll(PDO::FETCH_ASSOC);
$respaldo = [];
$totalAntes = 0;
$totalDespues = 0;

echo 'Empresa: ' . ($empresa !== '' ? $empresa : '(por dominio/entorno)') . ' | BD: ' . DB_NAME . ' | ' . ($aplicar ? 'APLICAR' : 'SIMULACRO') . PHP_EOL;

foreach ($filas as $fila) {
    $cambios = [];
    foreach ($grupos as $col => $maxLado) {
        $lista = json_decode((string)($fila[$col] ?? ''), true);
        if (!is_array($lista) || !$lista) {
            continue;
        }
        $nueva = $lista;
        foreach ($lista as $i => $ruta) {
            $abs = $resolver((string)$ruta);
            if ($abs === null) {
                echo "  [omitida] no se encuentra: {$ruta}" . PHP_EOL;
                continue;
            }
            $nombre = pathinfo($abs, PATHINFO_FILENAME);
            if (substr($nombre, -4) === '_opt') {
                continue;
            }
            $dim = @getimagesize($abs);
            $peso = filesize($abs);
            if (!$dim || ($peso <= $umbralBytes && max($dim[0], $dim[1]) <= $maxLado)) {
                continue;
            }
            $destinoSinExt = dirname($abs) . DIRECTORY_SEPARATOR . $nombre . '_opt';
            if ($aplicar) {
                $nuevoAbs = img_optimizar_archivo($abs, $destinoSinExt, $maxLado);
                if ($nuevoAbs === null) {
                    echo "  [sin mejora] {$ruta}" . PHP_EOL;
                    continue;
                }
                $pesoNuevo = filesize($nuevoAbs);
            } else {
                $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'opt_' . uniqid();
                $nuevoAbs = img_optimizar_archivo($abs, $tmp, $maxLado);
                if ($nuevoAbs === null) {
                    echo "  [sin mejora] {$ruta}" . PHP_EOL;
                    continue;
                }
                $pesoNuevo = filesize($nuevoAbs);
                unlink($nuevoAbs);
            }
            $totalAntes += $peso;
            $totalDespues += $pesoNuevo;
            printf("  %s: %dx%d %d KB -> %d KB%s\n", basename($abs), $dim[0], $dim[1], $peso / 1024, $pesoNuevo / 1024, $aplicar ? ' (' . basename($nuevoAbs) . ')' : '');
            if ($aplicar) {
                $nueva[$i] = preg_replace('#[^/\\\\]+$#', basename($nuevoAbs), (string)$ruta);
            }
        }
        if ($aplicar && $nueva !== $lista) {
            $cambios[$col] = $nueva;
            $respaldo[(int)$fila['id']][$col] = $lista;
        }
    }
    if ($aplicar && $cambios) {
        if (!is_dir($raiz . '/tmp')) {
            mkdir($raiz . '/tmp', 0777, true);
        }
        $archivoRespaldo = $raiz . '/tmp/respaldo_imagenes_empresa_' . date('Ymd_His') . '.json';
        file_put_contents($archivoRespaldo, json_encode($respaldo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        foreach ($cambios as $col => $lista) {
            $pdo->prepare("UPDATE config_empresa SET {$col} = ? WHERE id = ?")->execute([json_encode($lista, JSON_UNESCAPED_UNICODE), (int)$fila['id']]);
        }
        echo '  Respaldo de JSON: ' . $archivoRespaldo . PHP_EOL;
    }
}

printf("Total: %d KB -> %d KB (ahorro %d KB)\n", $totalAntes / 1024, $totalDespues / 1024, ($totalAntes - $totalDespues) / 1024);
if (!$aplicar) {
    echo 'Simulacro: nada se modifico. Use --aplicar para crear las copias y actualizar la BD.' . PHP_EOL;
}
