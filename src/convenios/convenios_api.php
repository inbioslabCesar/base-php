<?php
// Endpoint server-side para DataTables convenios
require_once __DIR__ . '/funciones/convenios_crud.php';
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

$total = convenios_count();
$filtered = $total;
$convenios = [];

if ($search !== '') {
    $convenios = convenios_buscar($search, $orderBy, $orderDir, $start, $length);
    $filtered = convenios_count($search);
} else {
    $convenios = convenios_listar($orderBy, $orderDir, $start, $length);
}

$data = [];
foreach ($convenios as $c) {
    $data[] = [
        'id' => $c['id'] ?? '',
        'nombre' => $c['nombre'] ?? '',
        'dni' => $c['dni'] ?? '',
        'especialidad' => $c['especialidad'] ?? '',
        'descuento' => $c['descuento'] ?? '',
        'descripcion' => $c['descripcion'] ?? '',
        'email' => $c['email'] ?? '',
    ];
}

$response = [
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $data
];

echo json_encode($response);
