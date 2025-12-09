


<?php
// API para DataTables server-side: listado de clientes
require_once __DIR__ . '/funciones/clientes_crud.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'recepcionista'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Parámetros DataTables
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$orderCol = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'desc';


// Mapeo de columnas permitidas
$columns = ['id', 'codigo_cliente', 'nombre', 'apellido', 'dni', 'edad', 'email', 'telefono', 'estado'];
$orderBy = $columns[$orderCol] ?? 'id';

try {
    if ($search !== '') {
        $data = clientes_buscar($search, $orderBy, $orderDir, $start, $length);
        $totalFiltered = clientes_count($search);
    } else {
        $data = clientes_listar($orderBy, $orderDir, $start, $length);
        $totalFiltered = clientes_count();
    }
    $totalRecords = clientes_count();

    header('Content-Type: application/json');
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data
    ]);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => $e->getMessage()
    ]);
    exit;
}
