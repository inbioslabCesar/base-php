


<?php
// API para DataTables server-side: listado de clientes
require_once __DIR__ . '/funciones/clientes_crud.php';
require_once __DIR__ . '/../resultados/servicios/EdadPacienteService.php';
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
$length = ($length === -1) ? -1 : max(1, $length);
$search = trim($_GET['search']['value'] ?? '');
// Ordenamiento (sanitizado)
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : null;
$orderDir = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';


// Mapeo de columnas permitidas (las que se pueden ordenar). "Acciones" no se ordena.
$columns = ['id', 'codigo_cliente', 'nombre', 'apellido', 'dni', 'edad', 'email', 'telefono', 'estado', 'fecha_registro'];
// Orden por defecto: últimos registrados primero
$orderBy = 'fecha_registro';
if ($orderCol !== null && isset($columns[$orderCol])) {
    $orderBy = $columns[$orderCol];
}

try {
    date_default_timezone_set('America/Lima');

    if ($search !== '') {
        $data = clientes_buscar($search, $orderBy, $orderDir, $start, $length);
        $totalFiltered = clientes_count($search);
    } else {
        $data = clientes_listar($orderBy, $orderDir, $start, $length);
        $totalFiltered = clientes_count();
    }
    $totalRecords = clientes_count();

    foreach ($data as &$row) {
        $edadResol = EdadPacienteService::resolverEdadParaEvento(
            $row['fecha_nacimiento'] ?? null,
            date('Y-m-d H:i:s'),
            ($row['edad_referida_valor'] ?? null) !== null && (string)$row['edad_referida_valor'] !== ''
                ? (string)$row['edad_referida_valor']
                : ($row['edad'] ?? null),
            $row['edad_referida_fecha'] ?? null
        );
        $edadTexto = EdadPacienteService::formatearEdadDetalladaDesdeValor(
            (string)($edadResol['edad_valor'] ?? ''),
            $edadResol['edad_texto'] ?? ($row['edad'] ?? '')
        );
        $row['edad'] = $edadTexto;
        $row['edad_display'] = $edadTexto;
        $row['edad_valor'] = $edadResol['edad_valor'];
    }
    unset($row);

    // Modo debug opcional
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        header('Content-Type: application/json');
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "debug" => [
                "orderBy" => $orderBy,
                "orderDir" => $orderDir,
                "search" => $search
            ]
        ]);
        exit;
    }

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
