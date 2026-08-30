<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';

header('Content-Type: application/manifest+json; charset=UTF-8');

$configEmpresa = ui_theme_fetch_company_config($pdo);
$uiTheme = ui_theme_get_active($pdo);

$nombre = trim((string)($configEmpresa['nombre'] ?? 'Portal de Salud'));
if ($nombre === '') {
    $nombre = 'Portal de Salud';
}

$shortName = mb_substr($nombre, 0, 12, 'UTF-8');
$primary = $uiTheme['primary'] ?? '#0d6efd';
$secondary = $uiTheme['secondary'] ?? '#ffffff';

$basePath = rtrim((string)dirname(rtrim((string)BASE_URL, '/')), '/\\');
if ($basePath === '.' || $basePath === '') {
    $basePath = '';
}

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$scheme = $isHttps ? 'https' : 'http';
$origin = $scheme . '://' . $host;

$startUrl = ($basePath === '' ? '' : $basePath) . '/index.php?source=pwa';
$scope = ($basePath === '' ? '/' : ($basePath . '/'));
$faviconPng = ($basePath === '' ? '' : $basePath) . '/src/favicon.php';

$manifest = [
    'id' => ($basePath === '' ? '/' : ($basePath . '/')),
    'name' => $nombre,
    'short_name' => $shortName,
    'description' => 'Acceso rapido al portal para pacientes y promociones.',
    'start_url' => $startUrl,
    'scope' => $scope,
    'display' => 'standalone',
    'orientation' => 'portrait',
    'background_color' => $secondary,
    'theme_color' => $primary,
    'icons' => [
        [
            'src' => $origin . $faviconPng . '?v=192',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $origin . $faviconPng . '?v=512',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
