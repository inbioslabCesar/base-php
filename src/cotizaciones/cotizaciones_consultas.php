<?php
// Consultar empresas y convenios para los selects
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Consulta principal con LEFT JOIN para empresa y convenio
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni,
        e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        LEFT JOIN empresas e ON c.id_empresa = e.id
        LEFT JOIN convenios v ON c.id_convenio = v.id";
$condiciones = [];
$params = [];
if ($dniFiltro !== '') {
    $condiciones[] = "cl.dni = ?";
    $params[] = $dniFiltro;
}
if ($empresaFiltro !== '') {
    $condiciones[] = "c.id_empresa = ?";
    $params[] = $empresaFiltro;
}
if ($convenioFiltro !== '') {
    $condiciones[] = "c.id_convenio = ?";
    $params[] = $convenioFiltro;
}
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $condiciones[] = "DATE(c.fecha) BETWEEN ? AND ?";
    $params[] = $_GET['fecha_desde'];
    $params[] = $_GET['fecha_hasta'];
} elseif (!empty($_GET['fecha_desde'])) {
    $condiciones[] = "DATE(c.fecha) >= ?";
    $params[] = $_GET['fecha_desde'];
} elseif (!empty($_GET['fecha_hasta'])) {
    $condiciones[] = "DATE(c.fecha) <= ?";
    $params[] = $_GET['fecha_hasta'];
}
if ($condiciones) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY c.fecha DESC, c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Normalizar campos de empresa/convenio para cada cotización (para cards móviles)
foreach ($cotizaciones as &$cot) {
    $cot['id_empresa'] = $cot['id_empresa'] ?? null;
    $cot['nombre_comercial'] = $cot['nombre_comercial'] ?? null;
    $cot['razon_social'] = $cot['razon_social'] ?? null;
    $cot['id_convenio'] = $cot['id_convenio'] ?? null;
    $cot['nombre_convenio'] = $cot['nombre_convenio'] ?? null;
}
unset($cot);

// Consulta para exámenes de cada cotización
$examenesPorCotizacion = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        $sqlExamenes = "SELECT re.id AS id_resultado, re.id_cotizacion, re.id_examen, re.estado, e.nombre AS nombre_examen
                        FROM resultados_examenes re
                        JOIN examenes e ON re.id_examen = e.id
                        WHERE re.id_cotizacion IN ($inQuery)";
        $stmtEx = $pdo->prepare($sqlExamenes);
        $stmtEx->execute($idsCotizaciones);
        $examenes = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
        foreach ($examenes as $ex) {
            $examenesPorCotizacion[$ex['id_cotizacion']][] = $ex;
        }
    }
}

// Consulta pagos por cotización
$pagosPorCotizacion = [];
$pagosPorCotizacionDetalle = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        // Total pagado por cotización
        $sqlPagos = "SELECT id_cotizacion, SUM(monto) AS total_pagado
                     FROM pagos
                     WHERE id_cotizacion IN ($inQuery)
                     GROUP BY id_cotizacion";
        $stmtPagos = $pdo->prepare($sqlPagos);
        $stmtPagos->execute($idsCotizaciones);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pagos as $pago) {
            $pagosPorCotizacion[$pago['id_cotizacion']] = $pago['total_pagado'];
        }
        // Detalle de pagos por cotización (para método de pago)
        $sqlPagosDetalle = "SELECT id_cotizacion, metodo_pago, fecha, id, monto FROM pagos WHERE id_cotizacion IN ($inQuery) ORDER BY fecha DESC, id DESC";
        $stmtPagosDetalle = $pdo->prepare($sqlPagosDetalle);
        $stmtPagosDetalle->execute($idsCotizaciones);
        $pagosDetalle = $stmtPagosDetalle->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pagosDetalle as $pago) {
            $pagosPorCotizacionDetalle[$pago['id_cotizacion']][] = $pago;
        }
    }
}
