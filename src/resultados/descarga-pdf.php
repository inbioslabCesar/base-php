<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php'; // $pdo disponible

$id = $_GET['id'] ?? null;
if (!$id) {
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
        "examen" => "",
        "resultados" => [],
        "empresa" => [
            "nombre" => "",
            "direccion" => "",
            "telefono" => "",
            "celular" => "",
        ]
    ]);
    exit;
}
// 1. Obtener el resultado y datos del paciente
$sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni 
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
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
        "examen" => "",
        "resultados" => [],
        "empresa" => [
            "nombre" => "",
            "direccion" => "",
            "telefono" => "",
            "celular" => "",
        ]
    ]);
    exit;
}
// 2. Obtener el campo adicional del examen
$sql2 = "SELECT nombre AS nombre_examen, adicional FROM examenes WHERE id = :id_examen";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute(['id_examen' => $row['id_examen']]);
$examen = $stmt2->fetch(PDO::FETCH_ASSOC);

// 3. Decodificar los JSON
$adicional = json_decode($examen['adicional'], true);
$resultados_json = json_decode($row['resultados'], true);
// 4. Recorrer parámetros y armar array final
$resultados = [];
if (is_array($adicional)) {
    foreach ($adicional as $param) {
        if ($param['tipo'] === 'Parámetro') {
            $nombre = $param['nombre'];
            $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
            $referencias = [];
            if (!empty($param['referencias'])) {
                foreach ($param['referencias'] as $ref) {
                    $referencias[] = ($ref['desc'] ? $ref['desc'] . ': ' : '') . $ref['valor'];
                }
            }
            $resultados[] = [
                "prueba"        => $nombre,
                "metodologia"   => $param['metodologia'],
                "resultado"     => $valor,
                "unidades"      => $param['unidad'],
                "referencia"    => $referencias // Array para soportar múltiples referencias
            ];
        }
    }
}
// 5. Obtener datos dinámicos de la empresa desde config_empresa
$sql3 = "SELECT nombre, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1";
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$empresa = $stmt3->fetch(PDO::FETCH_ASSOC);

// Si no hay datos, pon valores vacíos para evitar undefined
if (!$empresa) {
    $empresa = [
        "nombre" => "",
        "direccion" => "",
        "telefono" => "",
        "celular" => "",
    ];
}

// 6. Salida en JSON
echo json_encode([
    "paciente" => [
        "nombre"         => trim($row['nombre'] . ' ' . $row['apellido']),
        "codigo_cliente" => isset($row['codigo_cliente']) ? $row['codigo_cliente'] : "",
        "dni"            => isset($row['dni']) ? $row['dni'] : "",
        "edad"           => $row['edad'],
        "sexo"           => $row['sexo'],
        "fecha"          => $row['fecha_ingreso'],
        "id"             => $row['id']
    ],
    "examen"     => $examen['nombre_examen'],
    "resultados" => $resultados,
    "empresa"    => [
        "nombre"    => $empresa['nombre'],
        "direccion" => $empresa['direccion'],
        "telefono"  => $empresa['telefono'],
        "celular"   => $empresa['celular'],
    ]
]);
