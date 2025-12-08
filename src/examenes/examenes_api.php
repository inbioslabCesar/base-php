<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'laboratorista', 'recepcionista'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado', 'debug_rol' => $rol, 'debug_session' => $_SESSION]);
    exit;
}

// Parámetros DataTables
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$orderCol = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

// Mapeo de columnas
$columns = ['codigo', 'nombre', 'area', 'metodologia', 'precio_publico', 'tiempo_respuesta', 'id'];
$orderBy = $columns[$orderCol] ?? 'id';

try {
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM examenes";
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(codigo LIKE ? OR nombre LIKE ? OR area LIKE ? OR metodologia LIKE ? OR precio_publico LIKE ? OR tiempo_respuesta LIKE ?)";
        $params = array_fill(0, 6, "%$search%");
    }
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total filtrado
    $totalFiltered = $pdo->query("SELECT FOUND_ROWS()") ->fetchColumn();
    // Total general
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM examenes") ->fetchColumn();

    // DEBUG opcional
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        header('Content-Type: application/json');
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "debug_sql" => $sql,
            "debug_params" => $params,
            "debug_session" => $_SESSION,
            "debug_get" => $_GET
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
