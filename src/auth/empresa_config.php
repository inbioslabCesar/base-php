<?php
require_once __DIR__ . '/../conexion/conexion.php';
$stmt = $pdo->query("SELECT nombre, ruc, direccion, celular, telefono, logo FROM config_empresa LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no hay datos, usa valores por defecto
if (!$config) {
    $config = [
        'nombre' => 'EMPRESA',
        'ruc' => '',
        'direccion' => '',
        'celular' => '',
        'telefono' => '',
        'logo' => '../images/empresa/logo_empresa.png'
    ];
}

// Si el logo es relativo y no comienza con '/' ni 'http'
if (!empty($config['logo']) && strpos($config['logo'], '/') !== 0 && strpos($config['logo'], 'http') !== 0) {
    $config['logo'] = $config['logo'];
} elseif (empty($config['logo'])) {
    $config['logo'] = '../images/empresa/logo_empresa.png';
}

?>
