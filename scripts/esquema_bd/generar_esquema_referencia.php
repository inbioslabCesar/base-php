<?php
/**
 * Genera el esquema de referencia leyendo la BD de desarrollo (solo lectura).
 * Ejecutar cada vez que cambie la estructura de desarrollo, y subir el JSON al repositorio.
 *
 *   php scripts/esquema_bd/generar_esquema_referencia.php
 *   php scripts/esquema_bd/generar_esquema_referencia.php --origen=desarrollo --salida=ruta.json
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo por línea de comandos.');
}
require_once __DIR__ . '/EsquemaBd.php';

$args = EsquemaBd::parsearArgumentos($argv);
$origen = is_string($args['origen'] ?? null) ? $args['origen'] : 'desarrollo';
$salida = is_string($args['salida'] ?? null) ? $args['salida'] : EsquemaBd::rutaReferenciaDefecto();

try {
    $cfg = EsquemaBd::aplicarSobrescrituras(EsquemaBd::cargarConfigEmpresa($origen), $args);
    $pdo = EsquemaBd::conectar($cfg);
    $ref = EsquemaBd::construirReferencia($pdo);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$dir = dirname($salida);
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "ERROR: no se pudo crear {$dir}" . PHP_EOL);
    exit(1);
}
file_put_contents($salida, json_encode($ref, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

$cols = $idx = $fks = 0;
foreach ($ref['tablas'] as $t) {
    $cols += count($t['columnas']);
    $idx += count($t['indices']);
    $fks += count($t['fks']);
}
$semillas = array_sum(array_map('count', $ref['semillas']));
echo "Esquema de referencia generado desde '{$origen}' ({$ref['origen_motor']})" . PHP_EOL;
echo 'Tablas: ' . count($ref['tablas']) . " | columnas: {$cols} | índices: {$idx} | claves foráneas: {$fks} | semillas: {$semillas}" . PHP_EOL;
echo "Archivo: {$salida}" . PHP_EOL;
