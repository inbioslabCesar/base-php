<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$examId = isset($argv[1]) && is_numeric($argv[1]) ? (int)$argv[1] : 373;

function tableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $out = [];
    foreach ($rows as $r) {
        if (!empty($r['Field'])) {
            $out[(string)$r['Field']] = $r;
        }
    }
    return $out;
}

function fallbackValueForColumn(array $colDef)
{
    $type = strtolower((string)($colDef['Type'] ?? ''));

    if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) {
        return 0;
    }
    if (preg_match('/^(decimal|float|double|real)/', $type)) {
        return 0;
    }
    if (strpos($type, 'date') === 0 && strpos($type, 'datetime') === false && strpos($type, 'timestamp') === false) {
        return date('Y-m-d');
    }
    if (strpos($type, 'datetime') === 0 || strpos($type, 'timestamp') === 0) {
        return date('Y-m-d H:i:s');
    }
    if (preg_match('/^time(\(|$)/', $type)) {
        return date('H:i:s');
    }
    if (strpos($type, 'enum(') === 0) {
        if (preg_match("/^enum\\((.+)\\)$/i", $type, $m)) {
            $vals = str_getcsv($m[1], ',', "'");
            if (!empty($vals[0])) {
                return $vals[0];
            }
        }
        return '';
    }

    return '';
}

function buildInsertPayload(PDO $pdo, string $table, array $preferred): array
{
    $defs = tableColumns($pdo, $table);
    $payload = [];

    foreach ($defs as $name => $def) {
        $extra = strtolower((string)($def['Extra'] ?? ''));
        $nullable = strtoupper((string)($def['Null'] ?? 'YES')) === 'YES';
        $default = $def['Default'] ?? null;

        if (strpos($extra, 'auto_increment') !== false) {
            continue;
        }

        if (array_key_exists($name, $preferred)) {
            $payload[$name] = $preferred[$name];
            continue;
        }

        // Si no es obligatorio, omitir para que use default/NULL.
        if ($nullable || $default !== null) {
            continue;
        }

        // Obligatorio sin default: generar fallback seguro por tipo.
        $payload[$name] = fallbackValueForColumn($def);
    }

    return $payload;
}

function insertRow(PDO $pdo, string $table, array $data): int
{
    if (empty($data)) {
        throw new RuntimeException("No hay datos para insertar en {$table}");
    }
    $cols = array_keys($data);
    $marks = array_map(static fn($c) => ':' . $c, $cols);
    $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (" . implode(',', $marks) . ")";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
    return (int)$pdo->lastInsertId();
}

$stExam = $pdo->prepare('SELECT id, nombre, precio_publico, adicional FROM examenes WHERE id = ? LIMIT 1');
$stExam->execute([$examId]);
$exam = $stExam->fetch(PDO::FETCH_ASSOC);
if (!$exam) {
    fwrite(STDERR, "No existe examen con id={$examId}\n");
    exit(1);
}

$clienteId = null;
$stCliente = $pdo->query('SELECT id FROM clientes ORDER BY id DESC LIMIT 1');
if ($stCliente) {
    $clienteId = (int)$stCliente->fetchColumn();
}
if (!$clienteId) {
    fwrite(STDERR, "No hay clientes disponibles para crear cotizacion de prueba.\n");
    exit(1);
}

$usuarioId = 1;
try {
    $stUser = $pdo->query('SELECT id FROM usuarios ORDER BY id ASC LIMIT 1');
    $u = $stUser ? (int)$stUser->fetchColumn() : 0;
    if ($u > 0) {
        $usuarioId = $u;
    }
} catch (Throwable $e) {
}

$codigo = 'COT-TEST-V2-' . date('YmdHis');
$precio = (float)($exam['precio_publico'] ?? 0);
if ($precio <= 0) {
    $precio = 1.0;
}

$cotPref = [
    'codigo' => $codigo,
    'id_cliente' => $clienteId,
    'id_empresa' => null,
    'id_convenio' => null,
    'tipo_usuario' => 'cliente',
    'fecha' => date('Y-m-d H:i:s'),
    'total' => $precio,
    'total_bruto' => $precio,
    'estado_pago' => 'pendiente',
    'estado_muestra' => 'pendiente',
    'emitir_comprobante' => 0,
    'creado_por' => $usuarioId,
    'rol_creador' => 'admin',
    'tipo_toma' => null,
    'fecha_toma' => null,
    'hora_toma' => null,
    'direccion_toma' => null,
    'descuento_aplicado' => 0,
    'comprobante_tipo' => 'boleta',
];

$cotData = buildInsertPayload($pdo, 'cotizaciones', $cotPref);
$cotizacionId = insertRow($pdo, 'cotizaciones', $cotData);

$detPref = [
    'id_cotizacion' => $cotizacionId,
    'id_examen' => (int)$exam['id'],
    'nombre_examen' => (string)$exam['nombre'],
    'precio_unitario' => $precio,
    'cantidad' => 1,
    'subtotal' => $precio,
    'es_referenciado' => 0,
    'laboratorio_referenciado_nombre' => null,
    'costo_laboratorio_referenciado' => 0,
    'costo_logistica_extra' => 0,
    'estado_liquidacion' => 'liquidado',
];

$detData = buildInsertPayload($pdo, 'cotizaciones_detalle', $detPref);
$detalleId = insertRow($pdo, 'cotizaciones_detalle', $detData);

$resPref = [
    'id_examen' => (int)$exam['id'],
    'id_cliente' => $clienteId,
    'id_cotizacion' => $cotizacionId,
    'resultados' => '{}',
    'adicional_snapshot' => (string)($exam['adicional'] ?? ''),
    'estado' => 'pendiente',
    'orden_impresion' => 1,
    'fecha_ingreso' => date('Y-m-d H:i:s'),
];

$resData = buildInsertPayload($pdo, 'resultados_examenes', $resPref);
$resultadoId = insertRow($pdo, 'resultados_examenes', $resData);

echo "OK\n";
echo "id_examen={$examId}\n";
echo "id_cliente={$clienteId}\n";
echo "id_cotizacion={$cotizacionId}\n";
echo "id_cotizacion_detalle={$detalleId}\n";
echo "id_resultado={$resultadoId}\n";
echo "url_formulario_resultados=http://localhost/base-php/src/dashboard.php?vista=formulario&cotizacion_id={$cotizacionId}\n";
echo "url_ver_resultado=http://localhost/base-php/src/dashboard.php?vista=ver&id_resultado={$resultadoId}&id_examen={$examId}\n";
