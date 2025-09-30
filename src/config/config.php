<?php
// Detecta la empresa por subdominio, variable, sesión, etc.
$empresa = getenv('EMPRESA') ?: 'desarrollo'; // Por defecto desarrollo, puedes usar $_SESSION['empresa'] o $_SERVER['HTTP_HOST']
require_once __DIR__ . "/empresas/{$empresa}.php";