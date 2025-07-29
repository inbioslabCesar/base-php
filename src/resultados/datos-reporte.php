<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
if (!$cotizacion_id) {
    echo json_encode([
        "paciente" => [
            "nombre" => "",
            "codigo_cliente" => "",
            "dni" => "",
            "edad" => "",
            "sexo" => "",
            "fecha" => "",
            "id" => ""
        ],
        "items" => [],
        "empresa" => [
            "nombre" => "",
            "direccion" => "",
            "telefono" => "",
            "celular" => "",
            "logo" => "",
            "firma" => ""
        ]
    ]);
    exit;
}

// 1. Obtener todos los resultados de la cotización
$sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.id AS cliente_id
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['cotizacion_id' => $cotizacion_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows || count($rows) === 0) {
    echo json_encode([
        "paciente" => [
            "nombre" => "",
            "codigo_cliente" => "",
            "dni" => "",
            "edad" => "",
            "sexo" => "",
            "fecha" => "",
            "id" => ""
        ],
        "items" => [],
        "empresa" => [
            "nombre" => "",
            "direccion" => "",
            "telefono" => "",
            "celular" => "",
            "logo" => "",
            "firma" => ""
        ]
    ]);
    exit;
}
// Tomar datos del paciente del primer examen
$primer_row = $rows[0];
$paciente = [
    "nombre"         => trim($primer_row['nombre'] . ' ' . $primer_row['apellido']),
    "codigo_cliente" => $primer_row['codigo_cliente'] ?? "",
    "dni"            => $primer_row['dni'] ?? "",
    "edad"           => $primer_row['edad'],
    "sexo"           => $primer_row['sexo'],
    "fecha"          => $primer_row['fecha_ingreso'],
    "id"             => $primer_row['cliente_id']
];

// Procesar resultados
$items = [];
foreach ($rows as $row) {
    $sql2 = "SELECT nombre AS nombre_examen, adicional FROM examenes WHERE id = :id_examen";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(['id_examen' => $row['id_examen']]);
    $examen = $stmt2->fetch(PDO::FETCH_ASSOC);

    $adicional = $examen && $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
    $resultados_json = $row['resultados'] ? json_decode($row['resultados'], true) : [];

    usort($adicional, function ($a, $b) {
        return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
    });

    $valores = [];
    $examen_items = [];
    foreach ($adicional as $item) {
        if ($item['tipo'] === 'Parámetro' && empty($item['formula'])) {
            $nombre = $item['nombre'];
            $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
            $valores[$nombre] = $valor;
            $examen_items[] = array_merge($item, [
                "prueba" => $nombre,
                "valor" => $valor,
                "tipo" => "Parámetro"
            ]);
        } elseif ($item['tipo'] !== 'Parámetro') {
            $examen_items[] = array_merge($item, [
                "prueba" => $item['nombre']
            ]);
        }
    }
    foreach ($adicional as $item) {
        if ($item['tipo'] === 'Parámetro' && !empty($item['formula'])) {
            $formula = $item['formula'];
            $formula_eval = preg_replace_callback('/\[(.*?)\]/', function($matches) use ($valores) {
                $param = trim($matches[1]);
                return isset($valores[$param]) && is_numeric($valores[$param]) ? $valores[$param] : 0;
            }, $formula);
            $valor = '';
            try {
                $valor = eval('return ' . $formula_eval . ';');
                if (is_numeric($valor)) {
                    $valor = number_format($valor, 1, '.', '');
                }
            } catch (Throwable $e) {
                $valor = '';
            }
            $nombre = $item['nombre'];
            $valores[$nombre] = $valor;
            $examen_items[] = array_merge($item, [
                "prueba" => $nombre,
                "valor" => $valor,
                "tipo" => "Parámetro"
            ]);
        }
    }
    $items = array_merge($items, $examen_items);
}

// Datos de la empresa
$sql3 = "SELECT nombre, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1";
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$empresa = $stmt3->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    $empresa = [
        "nombre" => "",
        "direccion" => "",
        "telefono" => "",
        "celular" => "",
        "logo" => "",
        "firma" => ""
    ];
}

// Salida en JSON
echo json_encode([
    "paciente"   => $paciente,
    "items"      => $items,
    "empresa"    => [
        "nombre"    => $empresa['nombre'],
        "direccion" => $empresa['direccion'],
        "telefono"  => $empresa['telefono'],
        "celular"   => $empresa['celular'],
        "logo"      => $empresa['logo'] ?? "",
        "firma"     => $empresa['firma'] ?? ""
    ]
]);
exit;
