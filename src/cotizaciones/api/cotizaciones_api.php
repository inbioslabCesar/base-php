<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../funciones/cotizaciones_utils.php';
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

// Si se pasa un parámetro 'ids', devolver solo esas cotizaciones (para pago masivo)
if (!empty($_GET['ids'])) {
    $ids = array_filter(array_map('trim', explode(',', $_GET['ids'])), 'is_numeric');
    if (count($ids) > 0) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT c.id, c.codigo, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, c.fecha, c.total,
            c.estado_muestra AS estado_examen, c.rol_creador, c.modificada, c.id_empresa, c.id_convenio, e.nombre_comercial, v.nombre AS nombre_convenio, c.referencia_personalizada
            FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id
            WHERE c.id IN ($in)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dataFinal = [];
        foreach ($data as $row) {
            $row['saldo'] = obtenerSaldoCotizacion($pdo, $row['id']);
            $row['total_pagado'] = $pdo->query("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = {$row['id']}")->fetchColumn();
            if (!empty($row['id_empresa']) && !empty($row['nombre_comercial'])) {
                $row['referencia'] = $row['nombre_comercial'];
            } elseif (!empty($row['id_convenio']) && !empty($row['nombre_convenio'])) {
                $row['referencia'] = $row['nombre_convenio'];
            } else {
                $row['referencia'] = 'Particular';
            }
            $dataFinal[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => count($dataFinal),
            "recordsFiltered" => count($dataFinal),
            "data" => $dataFinal
        ]);
        exit;
    }
}

try {
    $sql = "SELECT SQL_CALC_FOUND_ROWS c.id, c.codigo, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, c.fecha, c.total,
        c.estado_muestra AS estado_examen, c.rol_creador, c.modificada, c.id_empresa, c.id_convenio, e.nombre_comercial, v.nombre AS nombre_convenio, c.referencia_personalizada
        FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id";
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
        // Calcular saldo usando función utilitaria
        $row['saldo'] = obtenerSaldoCotizacion($pdo, $row['id']);
        // Calcular total pagado para estado de pago
        $row['total_pagado'] = $pdo->query("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = {$row['id']}")->fetchColumn();
        // Detectar si existe pago con método descarga_anticipada
        $stmtDescAnt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE id_cotizacion = ? AND metodo_pago = 'descarga_anticipada'");
        $stmtDescAnt->execute([$row['id']]);
        $row['tiene_descarga_anticipada'] = $stmtDescAnt->fetchColumn() > 0 ? 1 : 0;
        if (!empty($row['id_empresa']) && !empty($row['nombre_comercial'])) {
            $row['referencia'] = $row['nombre_comercial'];
        } elseif (!empty($row['id_convenio']) && !empty($row['nombre_convenio'])) {
            $row['referencia'] = $row['nombre_convenio'];
        } else {
            $row['referencia'] = 'Particular';
        }
        // Calcular porcentaje de resultados llenados
        $porcentaje = obtenerPorcentajeResultadosCotizacion($pdo, $row['id']);
        if ($porcentaje === 100) {
            $row['estado_examen'] = 'completado_100';
        } elseif ($porcentaje === 0) {
            $row['estado_examen'] = 'pendiente_0';
        } else {
            $row['estado_examen'] = 'pendiente_' . $porcentaje;
        }
        $row['porcentaje_examen'] = $porcentaje;
        $dataFinal[] = $row;
    }
    // Total filtrado
    $countSql = "SELECT COUNT(*) FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id";
    if ($where) {
        $countSql .= " WHERE " . implode(' AND ', $where);
    }
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalFiltered = $stmtCount->fetchColumn();
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
            "debug_get" => $_GET,
            "debug_info" => [
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalFiltered,
                "dataCount" => count($dataFinal)
            ]
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
