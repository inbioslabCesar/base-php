<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/conexion/conexion.php';

$logo = '../uploads/empresa/logo_empresa.png';

try {
    $stmt = $pdo->query("SELECT logo FROM config_empresa LIMIT 1");
    $logoDb = $stmt ? $stmt->fetchColumn() : null;
    if (is_string($logoDb) && trim($logoDb) !== '') {
        $logo = trim($logoDb);
    }
} catch (Throwable $e) {
}

if (preg_match('/^data:image\//i', (string)$logo)) {
    $logo = '../uploads/empresa/logo_empresa.png';
}

$logoRel = ltrim($logo, '/');
$logoRel = preg_replace('#^\.\./+#', '', $logoRel);
$candidatos = [
    __DIR__ . '/' . $logoRel,
    __DIR__ . '/../' . $logoRel,
    __DIR__ . '/../uploads/empresa/logo_empresa.png',
];

$archivo = null;
foreach ($candidatos as $ruta) {
    if (is_file($ruta)) {
        $archivo = $ruta;
        break;
    }
}

if ($archivo === null) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
$mime = 'image/png';
if ($ext === 'ico') {
    $mime = 'image/x-icon';
} elseif ($ext === 'jpg' || $ext === 'jpeg') {
    $mime = 'image/jpeg';
} elseif ($ext === 'webp') {
    $mime = 'image/webp';
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($archivo);
