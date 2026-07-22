<?php
require_once __DIR__ . '/../src/conexion/conexion.php';
require_once __DIR__ . '/../src/cotizaciones/funciones/cotizaciones_utils.php';

$cotizacionId = isset($argv[1]) && is_numeric($argv[1]) ? (int)$argv[1] : 2356;
$porcentaje = obtenerPorcentajeResultadosCotizacion($pdo, $cotizacionId);

echo "cotizacion_id={$cotizacionId}\n";
echo "porcentaje={$porcentaje}\n";
