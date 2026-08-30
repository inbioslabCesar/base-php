<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';

$empresaCfg = ui_theme_fetch_company_config($pdo);
$config = [
    'nombre' => (string)($empresaCfg['nombre'] ?? ''),
    'ruc' => (string)($empresaCfg['ruc'] ?? ''),
    'direccion' => (string)($empresaCfg['direccion'] ?? ''),
    'celular' => (string)($empresaCfg['celular'] ?? ''),
    'telefono' => (string)($empresaCfg['telefono'] ?? ''),
    'logo' => (string)($empresaCfg['logo'] ?? ''),
    'dominio' => (string)($empresaCfg['dominio'] ?? ''),
];

// Si no hay datos, usa valores por defecto
if (!$config) {
    $config = [
        'nombre' => 'EMPRESA',
        'ruc' => '',
        'direccion' => '',
        'celular' => '',
        'telefono' => '',
        'logo' => '../uploads/empresa/logo_empresa.png',
        'dominio' => ''
    ];
}

// Si el logo es relativo y no comienza con '/' ni 'http'
if (!empty($config['logo']) && strpos($config['logo'], '/') !== 0 && strpos($config['logo'], 'http') !== 0) {
    $config['logo'] = $config['logo'];
} elseif (empty($config['logo'])) {
    $config['logo'] = '../uploads/empresa/logo_empresa.png';
}

?>
