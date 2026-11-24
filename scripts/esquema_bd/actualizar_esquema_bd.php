<?php
/**
 * Actualiza la estructura de una o varias BD de clientes hasta el esquema de referencia.
 * Solo CREA lo que falta (tablas, columnas, índices, claves foráneas, semillas). No elimina ni modifica nada existente.
 * Por defecto es un SIMULACRO: muestra lo que haría. Para ejecutar: --aplicar --confirmo-respaldo.
 *
 *   php scripts/esquema_bd/actualizar_esquema_bd.php --empresa=medditech
 *   php scripts/esquema_bd/actualizar_esquema_bd.php --todas
 *   php scripts/esquema_bd/actualizar_esquema_bd.php --empresa=medditech --aplicar --confirmo-respaldo
 *
 * Opciones:
 *   --empresa=slug        BD de src/config/empresas/{slug}.php   (o --todas)
 *   --aplicar             ejecuta los cambios (exige --confirmo-respaldo)
 *   --solo=a,b            limita las fases: tablas,columnas,indices,fks,semillas
 *   --nombres-exactos     crea los índices/FK por nombre aunque ya exista uno equivalente con otro nombre
 *   --sql=archivo.sql     guarda el SQL pendiente para pegarlo en phpMyAdmin
 *   --ref=archivo.json    esquema de referencia (por defecto sql/esquema_referencia/esquema_referencia.json)
 *   --host= --puerto= --bd= --usuario= --clave=   sobrescriben la conexión (túnel / acceso remoto)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo por línea de comandos.');
}
require_once __DIR__ . '/EsquemaBd.php';

$args = EsquemaBd::parsearArgumentos($argv);
$aplicar = !empty($args['aplicar']);
$fasesValidas = ['tablas', 'columnas', 'indices', 'fks', 'semillas'];
$solo = is_string($args['solo'] ?? null) ? array_filter(array_map('trim', explode(',', $args['solo']))) : $fasesValidas;
$opciones = ['nombres_exactos' => !empty($args['nombres-exactos'])];

if ($aplicar && empty($args['confirmo-respaldo'])) {
    fwrite(STDERR, 'ERROR: para --aplicar debes confirmar que hiciste un respaldo con --confirmo-respaldo.' . PHP_EOL);
    exit(1);
}
if (array_diff($solo, $fasesValidas)) {
    fwrite(STDERR, 'ERROR: --solo admite: ' . implode(',', $fasesValidas) . PHP_EOL);
    exit(1);
}

if (!empty($args['todas']) && isset($args['empresa'])) {
    fwrite(STDERR, 'ERROR: usa --empresa=slug o --todas, no ambos a la vez.' . PHP_EOL);
    exit(1);
}
if (!empty($args['todas'])) {
    $empresas = EsquemaBd::listarEmpresas();
} elseif (is_string($args['empresa'] ?? null)) {
    $empresas = array_filter(array_map('trim', explode(',', $args['empresa'])));
} else {
    fwrite(STDERR, 'Indica --empresa=slug o --todas. Empresas: ' . implode(', ', EsquemaBd::listarEmpresas()) . PHP_EOL);
    exit(1);
}

try {
    $ref = EsquemaBd::cargarReferencia(is_string($args['ref'] ?? null) ? $args['ref'] : EsquemaBd::rutaReferenciaDefecto());
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$logRuta = null;
if ($aplicar) {
    $dirTmp = EsquemaBd::raiz() . '/tmp';
    if (!is_dir($dirTmp)) {
        @mkdir($dirTmp, 0775, true);
    }
    $logRuta = $dirTmp . '/esquema_bd_' . date('Ymd_His') . '.log';
}
$salida = static function (string $linea = '') use ($logRuta): void {
    echo $linea . PHP_EOL;
    if ($logRuta !== null) {
        file_put_contents($logRuta, $linea . PHP_EOL, FILE_APPEND);
    }
};
$etiquetas = ['pendiente' => 'PENDIENTE', 'ok' => 'OK', 'error' => 'ERROR', 'omitida' => 'OMITIDA', 'info' => 'INFO'];
$sqlPendiente = [];
$codigo = 0;

$salida('Esquema de referencia: ' . $ref['origen_motor'] . ' (generado ' . $ref['generado_en'] . ', ' . count($ref['tablas']) . ' tablas)');
$salida($aplicar ? 'MODO: APLICAR' : 'MODO: SIMULACRO (no se modifica nada; usa --aplicar --confirmo-respaldo para ejecutar)');

foreach ($empresas as $empresa) {
    $salida();
    try {
        $cfg = EsquemaBd::aplicarSobrescrituras(EsquemaBd::cargarConfigEmpresa($empresa), $args);
        $pdo = EsquemaBd::conectar($cfg);
        $destino = EsquemaBd::leerEstructura($pdo);
    } catch (Throwable $e) {
        $salida("=== {$empresa} === NO SE PUDO CONECTAR: " . $e->getMessage());
        $salida('    (Las BD de hosting suelen aceptar solo conexiones locales: ejecuta este script en el servidor, o usa --host/--puerto con acceso remoto habilitado.)');
        $codigo = max($codigo, 1);
        continue;
    }

    $salida("=== {$empresa} ({$cfg['name']}) — {$destino['motor']} {$destino['version']} ===");
    $filtrar = static function (array $acciones) use ($solo): array {
        return array_values(array_filter($acciones, static function (array $a) use ($solo): bool {
            return in_array($a['fase'], $solo, true);
        }));
    };
    $acciones = $filtrar(EsquemaBd::planificar($ref, $destino, $pdo, $opciones));
    if ($aplicar) {
        $acciones = EsquemaBd::aplicar($pdo, $acciones);
    }

    foreach ($acciones as $a) {
        $detalle = $a['sql'] !== null && $a['estado'] !== 'ok' ? str_replace("\n", ' ', $a['sql']) : $a['nota'];
        if ($a['estado'] === 'ok') {
            $detalle = "{$a['fase']} {$a['tabla']}" . ($a['objeto'] !== $a['tabla'] ? ".{$a['objeto']}" : '');
        } elseif ($a['estado'] === 'error') {
            $detalle = "{$a['fase']} {$a['tabla']}.{$a['objeto']}: {$a['nota']}";
        } elseif (in_array($a['estado'], ['omitida', 'info'], true)) {
            $detalle = "{$a['fase']} {$a['tabla']}.{$a['objeto']}: {$a['nota']}";
        }
        $salida('  [' . $etiquetas[$a['estado']] . '] ' . $detalle);
        if ($a['estado'] === 'pendiente') {
            $sqlPendiente[$empresa][] = $a['sql'] . ';';
        }
        if ($a['estado'] === 'error') {
            $codigo = max($codigo, 1);
        }
    }

    $resumen = EsquemaBd::resumen($acciones);
    $partes = [];
    foreach ($resumen as $fase => $estados) {
        $partes[] = $fase . ': ' . implode(', ', array_map(static function ($e, $n) {
            return "{$n} {$e}";
        }, array_keys($estados), $estados));
    }
    $salida('  Resumen -> ' . ($partes ? implode(' | ', $partes) : 'sin diferencias: la BD ya está al día'));

    if ($aplicar) {
        $restantes = $filtrar(EsquemaBd::planificar($ref, EsquemaBd::leerEstructura($pdo), $pdo, $opciones));
        $pendientes = array_filter($restantes, static function (array $a): bool {
            return $a['estado'] === 'pendiente';
        });
        $manuales = array_filter($restantes, static function (array $a): bool {
            return $a['estado'] === 'omitida';
        });
        $salida('  Verificación: ' . (count($pendientes) === 0 ? 'nada pendiente tras aplicar.' : count($pendientes) . ' acción(es) siguen pendientes (revisar errores).')
            . (count($manuales) > 0 ? ' ' . count($manuales) . ' omitida(s) requieren revisión manual.' : ''));
        if (count($pendientes) > 0) {
            $codigo = max($codigo, 1);
        } elseif (count($manuales) > 0) {
            $codigo = max($codigo, 2);
        }
    } elseif (array_filter($acciones, static function (array $a): bool {
        return in_array($a['estado'], ['pendiente', 'omitida'], true);
    })) {
        $codigo = max($codigo, 2);
    }
}

if (is_string($args['sql'] ?? null) && $sqlPendiente) {
    $contenido = "-- SQL pendiente generado el " . date('Y-m-d H:i') . ". Ejecutar en phpMyAdmin sobre la BD indicada.\n";
    foreach ($sqlPendiente as $empresa => $lineas) {
        $contenido .= "\n-- ===== {$empresa} =====\n" . implode("\n", $lineas) . "\n";
    }
    file_put_contents($args['sql'], $contenido);
    $salida();
    $salida("SQL pendiente guardado en {$args['sql']}");
}
if ($logRuta !== null) {
    $salida("Registro: {$logRuta}");
}
exit($codigo);
