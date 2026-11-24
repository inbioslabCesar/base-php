<?php
// Optimiza imagenes de carrusel/institucionales: limita el lado mayor y recomprime (WebP, o JPEG si no hay soporte).
// Si algo falla o no hay GD, el llamador conserva el archivo original.

if (!function_exists('img_optimizar_archivo')) {
    /**
     * Genera una version liviana de $origen en "$destinoSinExt.webp|.jpg".
     * Devuelve la ruta final o null si no se pudo (o si no mejora el peso y no hizo falta redimensionar).
     */
    function img_optimizar_archivo(string $origen, string $destinoSinExt, int $maxLado = 1600, int $calidad = 82): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !is_file($origen)) {
            return null;
        }
        $info = @getimagesize($origen);
        if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg', 'image/png'], true)) {
            return null;
        }
        [$w, $h] = $info;
        if ($w < 1 || $h < 1 || ($w * $h) > 60000000) {
            return null;
        }

        $src = $info['mime'] === 'image/png' ? @imagecreatefrompng($origen) : @imagecreatefromjpeg($origen);
        if (!$src) {
            return null;
        }

        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($origen);
            $giros = [3 => 180, 6 => -90, 8 => 90];
            $orientacion = (int)($exif['Orientation'] ?? 1);
            if (isset($giros[$orientacion])) {
                $rot = @imagerotate($src, $giros[$orientacion], 0);
                if ($rot) {
                    $src = $rot;
                    [$w, $h] = [imagesx($src), imagesy($src)];
                }
            }
        }

        $escala = min(1, $maxLado / max($w, $h));
        $nw = max(1, (int)round($w * $escala));
        $nh = max(1, (int)round($h * $escala));
        $redimensiono = $escala < 1;

        $dst = imagecreatetruecolor($nw, $nh);
        $usaWebp = function_exists('imagewebp');
        if ($usaWebp) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        } else {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $destino = $destinoSinExt . ($usaWebp ? '.webp' : '.jpg');
        $ok = $usaWebp ? @imagewebp($dst, $destino, $calidad) : @imagejpeg($dst, $destino, $calidad);
        imagedestroy($dst);
        if (!$ok || !is_file($destino)) {
            return null;
        }

        // Si no hubo que reducir dimensiones y no se ahorra peso, es mejor quedarse con el original.
        if (!$redimensiono && filesize($destino) >= filesize($origen)) {
            @unlink($destino);
            return null;
        }
        return $destino;
    }
}

if (!function_exists('img_guardar_subida')) {
    /**
     * Guarda un archivo subido en $dir con nombre unico "$prefijo*". Devuelve el nombre de archivo guardado o null.
     * Intenta optimizarlo; si no se puede, lo mueve tal cual (solo png/jpg/jpeg).
     */
    function img_guardar_subida(string $tmp, string $nombreOriginal, string $dir, string $prefijo, int $maxLado = 1600): ?string
    {
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            return null;
        }
        $base = uniqid($prefijo);
        $optimizada = img_optimizar_archivo($tmp, rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $base, $maxLado);
        if ($optimizada !== null) {
            return basename($optimizada);
        }
        $nombre = $base . '.' . $ext;
        return move_uploaded_file($tmp, rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $nombre) ? $nombre : null;
    }
}
