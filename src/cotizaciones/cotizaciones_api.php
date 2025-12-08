<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'recepcionista', 'laboratorista'])) {
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
$orderDir = $_GET['order'][0]['dir'] ?? 'desc';

// Mapeo de columnas
        $columns = ['id', 'codigo', 'nombre_cliente', 'dni', 'fecha', 'total', 'referencia', 'estado_pago', 'estado_examen', 'rol_creador', 'acciones'];
$orderBy = $columns[$orderCol] ?? 'id';

try {
    $sql = "SELECT SQL_CALC_FOUND_ROWS c.id, c.codigo, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, c.fecha, c.total, c.estado_pago, c.estado_muestra AS estado_examen, c.rol_creador, c.modificada, c.id_empresa, c.id_convenio, e.nombre_comercial, v.nombre AS nombre_convenio, c.referencia_personalizada FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id";
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(c.codigo LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR cl.dni LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
    }
    // Filtros extra
    if (!empty($_GET['filtro_dni'])) {
        $where[] = "cl.dni LIKE ?";
        $params[] = "%" . trim($_GET['filtro_dni']) . "%";
    }
    if (!empty($_GET['filtro_empresa'])) {
        $where[] = "c.id_empresa = ?";
        $params[] = $_GET['filtro_empresa'];
    }
    if (!empty($_GET['filtro_convenio'])) {
        $where[] = "c.id_convenio = ?";
        $params[] = $_GET['filtro_convenio'];
    }
    if (!empty($_GET['filtro_fecha_desde'])) {
        $where[] = "fecha >= ?";
        $params[] = $_GET['filtro_fecha_desde'];
    }
    if (!empty($_GET['filtro_fecha_hasta'])) {
        $where[] = "fecha <= ?";
        $params[] = $_GET['filtro_fecha_hasta'];
    }
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Procesar referencia para cada fila
    $dataFinal = [];
    foreach ($data as $row) {
        if (!empty($row['id_empresa']) && !empty($row['nombre_comercial'])) {
            $row['referencia'] = $row['nombre_comercial'];
        } elseif (!empty($row['id_convenio']) && !empty($row['nombre_convenio'])) {
            $row['referencia'] = $row['nombre_convenio'];
        } else {
            $row['referencia'] = 'Particular';
        }
        $dataFinal[] = $row;
    }
    // Total filtrado
    $totalFiltered = $pdo->query("SELECT FOUND_ROWS()") ->fetchColumn();
    // Total general
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM cotizaciones") ->fetchColumn();

    // DEBUG: incluir parámetros y SQL en la respuesta si se solicita
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        header('Content-Type: application/json');
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $dataFinal,
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
        "data" => $dataFinal
    ]);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => $e->getMessage()
    ]);
    exit;
}
