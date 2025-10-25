<?php
// Detecta la empresa por subdominio, variable, sesión, etc.
$empresa = getenv('EMPRESA') ?: 'desarrollo'; // Por defecto desarrollo, puedes usar $_SESSION['empresa'] o $_SERVER['HTTP_HOST']
require_once __DIR__ . "/empresas/{$empresa}.php";

// BASE_URL para rutas absolutas
if (!defined('BASE_URL')) {
    if ($empresa === 'desarrollo') {
        define('BASE_URL', '/base-php/src/');
    } else {
        define('BASE_URL', '/src/');
    }
}