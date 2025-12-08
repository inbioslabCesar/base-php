<?php
// Endpoint server-side para DataTables empresas
require_once __DIR__ . '/funciones/empresas_crud.php';
header('Content-Type: application/json');

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

$columns = [
    'id', 'nombre', 'ruc', 'direccion', 'telefono', 'email', 'estado', 'fecha_creacion'
];
$orderBy = $columns[$orderCol] ?? 'id';

$total = empresas_count();
$filtered = $total;
$empresas = [];

if ($search !== '') {
    $empresas = empresas_buscar($search, $orderBy, $orderDir, $start, $length);
    $filtered = empresas_count($search);
} else {
    $empresas = empresas_listar($orderBy, $orderDir, $start, $length);
}

$data = [];
foreach ($empresas as $e) {
    $data[] = [
        'id' => $e['id'] ?? '',
        'ruc' => $e['ruc'] ?? '',
        'razon_social' => $e['razon_social'] ?? '',
        'nombre_comercial' => $e['nombre_comercial'] ?? '',
        'direccion' => $e['direccion'] ?? '',
        'telefono' => $e['telefono'] ?? '',
        'email' => $e['email'] ?? '',
        'representante' => $e['representante'] ?? '',
        'convenio' => $e['convenio'] ?? '',
        'estado' => $e['estado'] ?? '',
        'descuento' => $e['descuento'] ?? '',
        'fecha_creacion' => $e['fecha_creacion'] ?? '',
    ];
}

$response = [
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $data
];

echo json_encode($response);
