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
if ($condiciones) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY c.fecha DESC, c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
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
    }
}
