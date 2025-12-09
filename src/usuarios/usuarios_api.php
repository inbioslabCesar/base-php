<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Endpoint server-side para DataTables usuarios
try {
    require_once __DIR__ . '/funciones/usuarios_crud.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
header('Content-Type: application/json');

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

$columns = [
    'id', 'nombre', 'apellido', 'usuario', 'rol', 'email', 'estado', 'fecha_creacion'
];
$orderBy = $columns[$orderCol] ?? 'id';

$total = usuarios_count();
$filtered = $total;
$usuarios = [];

if ($search !== '') {
    $usuarios = usuarios_buscar($search, $orderBy, $orderDir, $start, $length);
    $filtered = usuarios_count($search);
} else {
    $usuarios = usuarios_listar($orderBy, $orderDir, $start, $length);
}

$data = [];
foreach ($usuarios as $u) {
    $data[] = [
        'id' => $u['id'] ?? '',
        'nombre' => $u['nombre'] ?? '',
        'apellido' => $u['apellido'] ?? '',
        'usuario' => $u['usuario'] ?? '',
        'dni' => $u['dni'] ?? '',
        'sexo' => $u['sexo'] ?? '',
        'fecha_nacimiento' => $u['fecha_nacimiento'] ?? '',
        'email' => $u['email'] ?? '',
        'telefono' => $u['telefono'] ?? '',
        'direccion' => $u['direccion'] ?? '',
        'cargo' => $u['cargo'] ?? '',
        'profesion' => $u['profesion'] ?? '',
        'rol' => $u['rol'] ?? '',
        'estado' => $u['estado'] ?? '',
        'fecha_creacion' => $u['fecha_creacion'] ?? '',
    ];
}

$response = [
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $data
];

echo json_encode($response);
