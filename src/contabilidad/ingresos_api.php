<?php
require_once __DIR__ . '/../conexion/conexion.php';
header('Content-Type: application/json');

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

$columns = [
    'codigo', 'fecha', 'metodo_pago', 'cliente', 'tipo_paciente', 'referencia', 'total', 'adelanto', 'deuda'
];
$orderBy = $columns[$orderCol] ?? 'fecha';

// Filtros
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$tipo_paciente = $_GET['tipo_paciente'] ?? 'todos';
$filtro_convenio = $_GET['filtro_convenio'] ?? '';
$filtro_empresa = $_GET['filtro_empresa'] ?? '';

$where = "WHERE DATE(c.fecha) BETWEEN ? AND ?";
$params = [$desde, $hasta];
if ($tipo_paciente == 'convenio') {
    $where .= " AND c.id_convenio IS NOT NULL";
    if ($filtro_convenio) {
        $where .= " AND c.id_convenio = ?";
        $params[] = $filtro_convenio;
    }
} elseif ($tipo_paciente == 'empresa') {
    $where .= " AND c.id_empresa IS NOT NULL";
    if ($filtro_empresa) {
        $where .= " AND c.id_empresa = ?";
        $params[] = $filtro_empresa;
    }
} elseif ($tipo_paciente == 'particular') {
    $where .= " AND c.id_convenio IS NULL AND c.id_empresa IS NULL";
}

// Búsqueda
if ($search !== '') {
    $where .= " AND (c.codigo LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR conv.nombre LIKE ? OR emp.nombre_comercial LIKE ?)";
    $searchLike = "%$search%";
    $params = array_merge($params, [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
}

// Total registros
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones c JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN convenios conv ON c.id_convenio = conv.id LEFT JOIN empresas emp ON c.id_empresa = emp.id $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Consulta paginada
$sql = "SELECT 
        c.codigo AS codigo_cotizacion,
        c.fecha,
        (SELECT GROUP_CONCAT(DISTINCT p3.metodo_pago SEPARATOR ', ') FROM pagos p3 WHERE p3.id_cotizacion = c.id) AS metodo_pago,
        CONCAT(cl.nombre, ' ', cl.apellido) AS cliente,
        CASE 
            WHEN c.id_empresa IS NOT NULL THEN 'Empresa'
            WHEN c.id_convenio IS NOT NULL THEN 'Convenio'
            ELSE 'Particular'
        END AS tipo_paciente,
        CASE 
            WHEN c.id_empresa IS NOT NULL THEN emp.nombre_comercial
            WHEN c.id_convenio IS NOT NULL THEN conv.nombre
            ELSE 'Particular'
        END AS referencia,
        c.total AS total_cotizacion,
        (SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_cotizacion = c.id) AS adelanto,
        GREATEST(0, c.total - (SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_cotizacion = c.id)) AS deuda
    FROM cotizaciones c
    JOIN clientes cl ON c.id_cliente = cl.id
    LEFT JOIN convenios conv ON c.id_convenio = conv.id
    LEFT JOIN empresas emp ON c.id_empresa = emp.id
    $where
    ORDER BY c.$orderBy $orderDir
    LIMIT $start, $length";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($rows as $r) {
    $data[] = [
        'codigo_cotizacion' => $r['codigo_cotizacion'],
        'fecha' => $r['fecha'],
        'metodo_pago' => $r['metodo_pago'],
        'cliente' => $r['cliente'],
        'tipo_paciente' => $r['tipo_paciente'],
        'referencia' => $r['referencia'],
        'total_cotizacion' => 'S/ ' . number_format($r['total_cotizacion'], 2),
        'adelanto' => 'S/ ' . number_format($r['adelanto'], 2),
        'deuda' => ($r['deuda'] > 0) ? '<span class="badge bg-danger">S/ ' . number_format($r['deuda'], 2) . '</span>' : '<span class="badge bg-success">Sin deuda</span>'
    ];
}

$response = [
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $total,
    'data' => $data
];

echo json_encode($response);
