

<?php
// API para DataTables server-side: listado de clientes
require_once __DIR__ . '/../conexion/conexion.php';
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
    // Consulta base
    $sql = "SELECT SQL_CALC_FOUND_ROWS id, codigo_cliente, nombre, apellido, dni, edad, email, telefono, estado FROM clientes";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE dni LIKE ? OR nombre LIKE ? OR apellido LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    $sql .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total filtrado
    $totalFiltered = $pdo->query("SELECT FOUND_ROWS()") ->fetchColumn();
    // Total general
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM clientes") ->fetchColumn();

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
