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

$stmtColsCot = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME IN ('anulada_at','anulada_por','anulado_motivo')");
$stmtColsCot->execute();
$colsCot = $stmtColsCot->fetchAll(\PDO::FETCH_COLUMN);
$hasAnuladaAt = in_array('anulada_at', $colsCot, true);
$hasAnuladaPor = in_array('anulada_por', $colsCot, true);
$hasAnuladoMotivo = in_array('anulado_motivo', $colsCot, true);

$selectAnuladaAt = $hasAnuladaAt ? "c.anulada_at AS anulada_at" : "NULL AS anulada_at";
$selectAnuladaPor = $hasAnuladaPor ? "c.anulada_por AS anulada_por" : "NULL AS anulada_por";
$selectAnuladaPorNombre = $hasAnuladaPor ? "CONCAT(COALESCE(ua.nombre,''), ' ', COALESCE(ua.apellido,'')) AS anulada_por_nombre" : "NULL AS anulada_por_nombre";
$selectAnuladoMotivo = $hasAnuladoMotivo ? "c.anulado_motivo AS anulado_motivo" : "NULL AS anulado_motivo";
$joinAnuladaUser = $hasAnuladaPor ? " LEFT JOIN usuarios ua ON ua.id = c.anulada_por " : " ";

$stmtColsRes = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resultados_examenes' AND COLUMN_NAME IN ('alarma_activa','alarma_dias','alarma_fecha_objetivo','alarma_estado')");
$stmtColsRes->execute();
$colsRes = $stmtColsRes->fetchAll(\PDO::FETCH_COLUMN);
$hasAlarmColumns = in_array('alarma_activa', $colsRes, true)
    && in_array('alarma_dias', $colsRes, true)
    && in_array('alarma_fecha_objetivo', $colsRes, true);


// Parámetros DataTables
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : null;
$orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
$modo = strtolower(trim((string)($_GET['modo'] ?? 'activas')));
$soloAnuladas = ($modo === 'anuladas');
$filtroAlerta = strtolower(trim((string)($_GET['filtro_alerta'] ?? '')));
$resumenAlertas = isset($_GET['resumen_alertas']) && (int)$_GET['resumen_alertas'] === 1;

// Mapeo de columnas (usar alias para evitar ambigüedad). Para columnas calculadas usar null.
$columns = [
    'c.id',              // 0 (checkbox)
    'cl.codigo_cliente', // 1 (mostrar código de cliente)
    'cl.nombre',         // 2 (nombre_cliente)
    'cl.dni',            // 3
    'c.fecha',           // 4
    'c.total',           // 5
    null,                // 6 referencia (calculada)
    null,                // 7 estado_pago (calculado)
    null,                // 8 estado_examen (calculado)
    'c.rol_creador',     // 9
    null                 // 10 acciones
];
// Orden por defecto seguro
$orderBy = 'c.id';
if ($orderCol !== null && isset($columns[$orderCol]) && $columns[$orderCol]) {
    $orderBy = $columns[$orderCol];
}

// Si se pasa un parámetro 'ids', devolver solo esas cotizaciones (para pago masivo)
if (!empty($_GET['ids'])) {
    $ids = array_filter(array_map('trim', explode(',', $_GET['ids'])), 'is_numeric');
    if (count($ids) > 0) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT c.id, c.id_cliente, c.codigo, cl.codigo_cliente AS codigo_cliente, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, c.fecha, c.total,
            c.estado_muestra AS estado_examen, c.rol_creador, c.modificada, c.id_empresa, c.id_convenio, e.nombre_comercial, v.nombre AS nombre_convenio, c.referencia_personalizada,
            $selectAnuladaAt,
            $selectAnuladaPor,
            $selectAnuladaPorNombre,
            $selectAnuladoMotivo
            FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id $joinAnuladaUser
            WHERE c.id IN ($in) " . ($soloAnuladas ? "AND c.estado_pago = 'anulada'" : "AND (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')");
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
    $sql = "SELECT SQL_CALC_FOUND_ROWS c.id, c.id_cliente, c.codigo, cl.codigo_cliente AS codigo_cliente, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, c.fecha, c.total,
        c.estado_muestra AS estado_examen, c.rol_creador, c.modificada, c.id_empresa, c.id_convenio, e.nombre_comercial, v.nombre AS nombre_convenio, c.referencia_personalizada,
        $selectAnuladaAt,
        $selectAnuladaPor,
        $selectAnuladaPorNombre,
        $selectAnuladoMotivo
        FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas e ON c.id_empresa = e.id LEFT JOIN convenios v ON c.id_convenio = v.id $joinAnuladaUser";
    $where = [];
    $params = [];
    $where[] = $soloAnuladas ? "c.estado_pago = 'anulada'" : "(c.estado_pago IS NULL OR c.estado_pago <> 'anulada')";
    if ($search !== '') {
        $where[] = "(cl.codigo_cliente LIKE ? OR c.codigo LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR cl.dni LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
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
        $where[] = "c.fecha >= ?";
        $params[] = $_GET['filtro_fecha_desde'];
    }
    if (!empty($_GET['filtro_fecha_hasta'])) {
        $where[] = "c.fecha <= ?";
        $params[] = $_GET['filtro_fecha_hasta'];
    }
    if ($filtroAlerta !== '' && in_array($filtroAlerta, ['vencido', 'por_vencer', 'en_tiempo'], true)) {
        if ($hasAlarmColumns) {
            $estadoExpr = "CASE
                WHEN NOW() > DATE_ADD(ra.fecha_ingreso, INTERVAL ra.alarma_dias DAY) THEN 'vencido'
                WHEN NOW() >= DATE_ADD(ra.fecha_ingreso, INTERVAL GREATEST(ra.alarma_dias - 1, 0) DAY) THEN 'por_vencer'
                ELSE 'en_tiempo'
            END";
            $where[] = "EXISTS (
                SELECT 1
                FROM resultados_examenes ra
                WHERE ra.id_cotizacion = c.id
                  AND (ra.estado IS NULL OR ra.estado <> 'completado')
                  AND ra.alarma_activa = 1
                  AND ra.alarma_dias IS NOT NULL
                  AND ra.alarma_dias > 0
                  AND {$estadoExpr} = ?
            )";
            $params[] = $filtroAlerta;
        } else {
            $where[] = "1 = 0";
        }
    }

    if ($resumenAlertas) {
        if (!$hasAlarmColumns) {
            header('Content-Type: application/json');
            echo json_encode([
                'vencido' => 0,
                'por_vencer' => 0,
                'en_tiempo' => 0,
                'total' => 0
            ]);
            exit;
        }

        $sqlResumen = "SELECT
                SUM(CASE
                    WHEN re.alarma_activa = 1
                     AND re.alarma_dias IS NOT NULL
                     AND re.alarma_dias > 0
                     AND (re.estado IS NULL OR re.estado <> 'completado')
                     AND NOW() > DATE_ADD(re.fecha_ingreso, INTERVAL re.alarma_dias DAY)
                    THEN 1 ELSE 0 END) AS vencido,
                SUM(CASE
                    WHEN re.alarma_activa = 1
                     AND re.alarma_dias IS NOT NULL
                     AND re.alarma_dias > 0
                     AND (re.estado IS NULL OR re.estado <> 'completado')
                     AND NOW() <= DATE_ADD(re.fecha_ingreso, INTERVAL re.alarma_dias DAY)
                     AND NOW() >= DATE_ADD(re.fecha_ingreso, INTERVAL GREATEST(re.alarma_dias - 1, 0) DAY)
                    THEN 1 ELSE 0 END) AS por_vencer,
                SUM(CASE
                    WHEN re.alarma_activa = 1
                     AND re.alarma_dias IS NOT NULL
                     AND re.alarma_dias > 0
                     AND (re.estado IS NULL OR re.estado <> 'completado')
                     AND NOW() < DATE_ADD(re.fecha_ingreso, INTERVAL GREATEST(re.alarma_dias - 1, 0) DAY)
                    THEN 1 ELSE 0 END) AS en_tiempo
            FROM cotizaciones c
            LEFT JOIN clientes cl ON c.id_cliente = cl.id
            LEFT JOIN empresas e ON c.id_empresa = e.id
            LEFT JOIN convenios v ON c.id_convenio = v.id
            LEFT JOIN resultados_examenes re ON re.id_cotizacion = c.id";
        if ($where) {
            $sqlResumen .= " WHERE " . implode(' AND ', $where);
        }

        $stmtResumen = $pdo->prepare($sqlResumen);
        $stmtResumen->execute($params);
        $res = $stmtResumen->fetch(\PDO::FETCH_ASSOC) ?: [];
        $vencido = (int)($res['vencido'] ?? 0);
        $porVencer = (int)($res['por_vencer'] ?? 0);
        $enTiempo = (int)($res['en_tiempo'] ?? 0);

        header('Content-Type: application/json');
        echo json_encode([
            'vencido' => $vencido,
            'por_vencer' => $porVencer,
            'en_tiempo' => $enTiempo,
            'total' => $vencido + $porVencer + $enTiempo,
        ]);
        exit;
    }

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

        $row['alerta_estado'] = 'sin_alarma';
        $row['alerta_vencido'] = 0;
        $row['alerta_por_vencer'] = 0;
        $row['alerta_en_tiempo'] = 0;
        $row['alerta_total'] = 0;

        if ($hasAlarmColumns) {
            $stmtAlarma = $pdo->prepare("SELECT
                    SUM(CASE
                        WHEN alarma_activa = 1
                         AND alarma_dias IS NOT NULL
                         AND alarma_dias > 0
                         AND (estado IS NULL OR estado <> 'completado')
                         AND NOW() > DATE_ADD(fecha_ingreso, INTERVAL alarma_dias DAY)
                        THEN 1 ELSE 0 END) AS vencido,
                    SUM(CASE
                        WHEN alarma_activa = 1
                         AND alarma_dias IS NOT NULL
                         AND alarma_dias > 0
                         AND (estado IS NULL OR estado <> 'completado')
                         AND NOW() <= DATE_ADD(fecha_ingreso, INTERVAL alarma_dias DAY)
                         AND NOW() >= DATE_ADD(fecha_ingreso, INTERVAL GREATEST(alarma_dias - 1, 0) DAY)
                        THEN 1 ELSE 0 END) AS por_vencer,
                    SUM(CASE
                        WHEN alarma_activa = 1
                         AND alarma_dias IS NOT NULL
                         AND alarma_dias > 0
                         AND (estado IS NULL OR estado <> 'completado')
                         AND NOW() < DATE_ADD(fecha_ingreso, INTERVAL GREATEST(alarma_dias - 1, 0) DAY)
                        THEN 1 ELSE 0 END) AS en_tiempo
                FROM resultados_examenes
                WHERE id_cotizacion = ?");
            $stmtAlarma->execute([$row['id']]);
            $alarm = $stmtAlarma->fetch(\PDO::FETCH_ASSOC) ?: [];

            $row['alerta_vencido'] = (int)($alarm['vencido'] ?? 0);
            $row['alerta_por_vencer'] = (int)($alarm['por_vencer'] ?? 0);
            $row['alerta_en_tiempo'] = (int)($alarm['en_tiempo'] ?? 0);
            $row['alerta_total'] = $row['alerta_vencido'] + $row['alerta_por_vencer'] + $row['alerta_en_tiempo'];

            if ($row['alerta_vencido'] > 0) {
                $row['alerta_estado'] = 'vencido';
            } elseif ($row['alerta_por_vencer'] > 0) {
                $row['alerta_estado'] = 'por_vencer';
            } elseif ($row['alerta_en_tiempo'] > 0) {
                $row['alerta_estado'] = 'en_tiempo';
            }
        }

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
    if ($soloAnuladas) {
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado_pago = 'anulada'")->fetchColumn();
    } else {
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado_pago IS NULL OR estado_pago <> 'anulada'")->fetchColumn();
    }

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
} catch (\Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => $e->getMessage()
    ]);
    exit;
}
