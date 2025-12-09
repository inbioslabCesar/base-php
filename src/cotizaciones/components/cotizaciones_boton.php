<?php
// Lógica para definir el texto y la URL del botón según el rol
$rol = $_SESSION['rol'] ?? '';
$botonTexto = '';
$botonUrl   = '';

if ($rol === 'recepcionista' || $rol === 'admin') {
    $botonTexto = 'Nueva Cotización';
    $botonUrl   = 'dashboard.php?vista=clientes';
} elseif ($rol === 'laboratorista') {
    $botonTexto = 'Panel de Laboratorio';
    $botonUrl   = 'dashboard.php?vista=laboratorista';
}
