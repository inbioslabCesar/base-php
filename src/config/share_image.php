<?php
// Imágenes para compartir (Open Graph / WhatsApp): acepta cualquier forma o proporción
// (cuadrada, circular, vertical...) y la ajusta a 1200x630 sin recortar ni deformar.

if (!defined('SHARE_IMAGE_W')) {
    define('SHARE_IMAGE_W', 1200);
    define('SHARE_IMAGE_H', 630);
}

if (!function_exists('share_image_needs_compose')) {
    // Ya es apta si tiene proporción ~1.91:1 y al menos 600 px de ancho.
    function share_image_needs_compose(int $w, int $h): bool
    {
        if ($w <= 0 || $h <= 0) {
            return false;
        }
        $target = SHARE_IMAGE_W / SHARE_IMAGE_H;
        return abs(($w / $h) - $target) > 0.06 || $w < 600;
    }
}

if (!function_exists('share_image_load')) {
    function share_image_load(string $path)
    {
        $info = @getimagesize($path);
        if (!is_array($info)) {
            return null;
        }
        switch ($info[2] ?? 0) {
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_WEBP:
                $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
                break;
            case IMAGETYPE_GIF:
                $img = @imagecreatefromgif($path);
                break;
            default:
                $img = false;
        }
        if (!$img) {
            return null;
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        return $img;
    }
}

if (!function_exists('share_image_compose')) {
    // Crea un JPG 1200x630: fondo suavizado con los colores de la imagen + la imagen completa centrada.
    function share_image_compose(string $srcPath, string $destPath, int $quality = 86): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        $src = share_image_load($srcPath);
        if (!$src) {
            return false;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) {
            return false;
        }
        $W = SHARE_IMAGE_W;
        $H = SHARE_IMAGE_H;

        // 1) Fondo: recorte "cover" reducido a miniatura y ampliado (efecto desenfoque barato) + velo blanco.
        $cropW = $sw;
        $cropH = (int)round($sw * $H / $W);
        if ($cropH > $sh) {
            $cropH = $sh;
            $cropW = (int)round($sh * $W / $H);
        }
        $cropX = (int)(($sw - $cropW) / 2);
        $cropY = (int)(($sh - $cropH) / 2);

        $tiny = imagecreatetruecolor(32, 17);
        imagefill($tiny, 0, 0, imagecolorallocate($tiny, 255, 255, 255));
        imagealphablending($tiny, true);
        imagecopyresampled($tiny, $src, 0, 0, $cropX, $cropY, 32, 17, $cropW, $cropH);

        // Ampliación suave en dos pasos + desenfoque para evitar el efecto de píxeles.
        $medio = imagescale($tiny, 300, 158, IMG_BICUBIC);
        if ($medio) {
            for ($i = 0; $i < 3; $i++) {
                imagefilter($medio, IMG_FILTER_GAUSSIAN_BLUR);
            }
        } else {
            $medio = $tiny;
        }
        $canvas = imagecreatetruecolor($W, $H);
        imagecopyresampled($canvas, $medio, 0, 0, 0, 0, $W, $H, imagesx($medio), imagesy($medio));
        $fondoSuave = imagescale($medio, $W, $H, IMG_BICUBIC);
        if ($fondoSuave) {
            $canvas = $fondoSuave;
        }
        imagealphablending($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $W, $H, imagecolorallocatealpha($canvas, 255, 255, 255, 50));

        // 2) Imagen completa centrada, con margen y sin deformar (no se amplía más de 2.5x).
        $scale = min(($W * 0.88) / $sw, ($H * 0.88) / $sh, 2.5);
        $nw = max(1, (int)round($sw * $scale));
        $nh = max(1, (int)round($sh * $scale));
        $dx = (int)(($W - $nw) / 2);
        $dy = (int)(($H - $nh) / 2);
        imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);

        imageinterlace($canvas, true);
        $dir = dirname($destPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        return (bool)@imagejpeg($canvas, $destPath, $quality);
    }
}

if (!function_exists('share_image_social_variant')) {
    // Para imágenes ya guardadas (logo dinámico, institucional, rutas): devuelve la versión 1200x630
    // en caché, o null si la imagen ya es apta o no se puede procesar (se usa la original).
    function share_image_social_variant(string $srcAbs, string $projectRoot): ?array
    {
        if ($srcAbs === '' || !is_file($srcAbs)) {
            return null;
        }
        $info = @getimagesize($srcAbs);
        if (!is_array($info) || !share_image_needs_compose((int)$info[0], (int)$info[1])) {
            return null;
        }
        $key = substr(md5($srcAbs . '|' . @filemtime($srcAbs) . '|' . @filesize($srcAbs)), 0, 16);
        $rel = 'uploads/empresa/share_cache/share_' . $key . '.jpg';
        $abs = rtrim($projectRoot, '/\\') . '/' . $rel;
        if (!is_file($abs) && !share_image_compose($srcAbs, $abs)) {
            return null;
        }
        return ['rel' => $rel, 'abs' => $abs];
    }
}
