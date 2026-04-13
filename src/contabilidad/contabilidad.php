<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/currency.php';
$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));

$currencyCfg = currency_get_config($pdo);
$currencySymbol = (string)($currencyCfg['symbol'] ?? 'S/');

// Fecha de hoy
$hoy = date('Y-m-d');

// Ingresos de hoy
$stmt = $pdo->prepare("SELECT SUM(monto) AS ingresos_hoy FROM pagos WHERE DATE(fecha) = ?");
$stmt->execute([$hoy]);
$ingresosHoy = floatval($stmt->fetchColumn());

// Ingresos de hoy por método (desglose para cards)
$metodosObjetivo = ['efectivo', 'transferencia', 'tarjeta', 'yape', 'masivo'];
$ingresosPorMetodo = array_fill_keys($metodosObjetivo, 0.0);

$stmt = $pdo->prepare("SELECT LOWER(TRIM(metodo_pago)) AS metodo, SUM(monto) AS total FROM pagos WHERE DATE(fecha) = ? GROUP BY LOWER(TRIM(metodo_pago))");
$stmt->execute([$hoy]);
$rowsMetodos = $stmt->fetchAll();

foreach ($rowsMetodos as $rowMetodo) {
    $metodo = $rowMetodo['metodo'] ?? '';
    if ($metodo === 'plin' || $metodo === 'yape/plin') {
        $metodo = 'yape';
    }
    if (array_key_exists($metodo, $ingresosPorMetodo)) {
        $ingresosPorMetodo[$metodo] = floatval($rowMetodo['total']);
    }
}

// Egresos de hoy (asegúrate de tener la tabla egresos creada)
$stmt = $pdo->prepare("SELECT SUM(monto) AS egresos_hoy FROM egresos WHERE DATE(fecha) = ?");
$stmt->execute([$hoy]);
$egresosHoy = floatval($stmt->fetchColumn());

$hasEgresoCategoria = false;
$egresosTercerizadosHoy = 0.0;
$egresosTercerizadosMes = 0.0;
try {
    $stmtColCat = $pdo->prepare("SHOW COLUMNS FROM egresos LIKE 'categoria'");
    $stmtColCat->execute();
    $hasEgresoCategoria = (bool)$stmtColCat->fetch(PDO::FETCH_ASSOC);

    if ($hasEgresoCategoria) {
        $stmtTerHoy = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM egresos WHERE DATE(fecha) = ? AND categoria IN ('referenciado_laboratorio', 'referenciado_logistica')");
        $stmtTerHoy->execute([$hoy]);
        $egresosTercerizadosHoy = floatval($stmtTerHoy->fetchColumn());

        $stmtTerMes = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM egresos WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE()) AND categoria IN ('referenciado_laboratorio', 'referenciado_logistica')");
        $stmtTerMes->execute();
        $egresosTercerizadosMes = floatval($stmtTerMes->fetchColumn());
    }
} catch (Throwable $e) {
    $hasEgresoCategoria = false;
}

$referenciadosMetrics = [
    'pendiente_lab' => 0.0,
    'pendiente_log' => 0.0,
    'pendientes_total' => 0,
];
$hasReferenciadosCols = false;
try {
    $stmtColsRef = $pdo->query("SHOW COLUMNS FROM cotizaciones_detalle");
    $colsRef = $stmtColsRef ? $stmtColsRef->fetchAll(PDO::FETCH_ASSOC) : [];
    $mapRef = [];
    foreach ($colsRef as $colRef) {
        if (!empty($colRef['Field'])) {
            $mapRef[] = (string)$colRef['Field'];
        }
    }
    $hasReferenciadosCols = in_array('es_referenciado', $mapRef, true)
        && in_array('estado_liquidacion', $mapRef, true)
        && in_array('costo_laboratorio_referenciado', $mapRef, true)
        && in_array('costo_logistica_extra', $mapRef, true);

    if ($hasReferenciadosCols) {
        $stmtRef = $pdo->query("SELECT
            IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN costo_laboratorio_referenciado ELSE 0 END), 0) AS pendiente_lab,
            IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN costo_logistica_extra ELSE 0 END), 0) AS pendiente_log,
            IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes_total
            FROM cotizaciones_detalle");
        $referenciadosMetrics = $stmtRef->fetch(PDO::FETCH_ASSOC) ?: $referenciadosMetrics;
    }
} catch (Throwable $e) {
    $hasReferenciadosCols = false;
}

// Ganancia del día
$gananciaHoy = $ingresosHoy - $egresosHoy;

// Total de deuda de clientes (saldo pendiente sumado de todas las cotizaciones)
$stmt = $pdo->query("
    SELECT SUM(c.total - IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_cotizacion = c.id), 0)) AS deuda_total
    FROM cotizaciones c
        WHERE (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
            AND (c.total - IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_cotizacion = c.id), 0)) > 0
");
$deudaTotal = floatval($stmt->fetchColumn());

$cajaTablesReady = false;
$cajaHasTurnoColumn = false;
$cajaAbierta = null;
$maxTurnosPorDia = 2;
$turnosHoy = 0;
$resumenCaja = [
    'monto_inicial' => 0.0,
    'ingresos_efectivo' => 0.0,
    'egresos_efectivo' => 0.0,
    'ajustes_efectivo' => 0.0,
    'caja_teorica' => 0.0,
];
$ultimosCierres = [];
$paginaCierres = max(1, (int)($_GET['page_cierres'] ?? 1));
$cierresPorPaginaSolicitado = (int)($_GET['per_page_cierres'] ?? 3);
$cierresPorPaginaPermitidos = [3, 5, 10];
$cierresPorPagina = in_array($cierresPorPaginaSolicitado, $cierresPorPaginaPermitidos, true) ? $cierresPorPaginaSolicitado : 3;
$totalCierresCaja = 0;
$totalPaginasCierres = 1;
$reaperturaTableReady = false;
$reaperturaHasCajaOrigenCol = false;
$reaperturaHasTurnoRespCol = false;
$reaperturasPendientes = [];
$reaperturasRecientes = [];
$ingresosReaperturaPorCajaOrigen = [];
$paginaReaperturas = max(1, (int)($_GET['page_reap'] ?? 1));
$reaperturasPorPaginaSolicitado = (int)($_GET['per_page_reap'] ?? 3);
$reaperturasPorPaginaPermitidos = [3, 5, 10];
$reaperturasPorPagina = in_array($reaperturasPorPaginaSolicitado, $reaperturasPorPaginaPermitidos, true) ? $reaperturasPorPaginaSolicitado : 3;
$totalReaperturas = 0;
$totalPaginasReaperturas = 1;

$stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
$stmtTbl->execute();
$cajaTablesReady = (int)$stmtTbl->fetchColumn() > 0;

if ($cajaTablesReady) {
    $stmtTurnoCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas' AND COLUMN_NAME = 'numero_turno'");
    $stmtTurnoCol->execute();
    $cajaHasTurnoColumn = ((int)$stmtTurnoCol->fetchColumn() > 0);

    $stmtTurnosHoy = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE fecha_operacion = CURDATE()");
    $stmtTurnosHoy->execute();
    $turnosHoy = (int)$stmtTurnosHoy->fetchColumn();

    $stmtCaja = $pdo->prepare("SELECT * FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtCaja->execute();
    $cajaAbierta = $stmtCaja->fetch();

    if ($cajaAbierta) {
        $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
        $stmtMovTable->execute();
        $existsMov = (int)$stmtMovTable->fetchColumn() > 0;

        $ingresosEfectivo = 0.0;
        $egresosEfectivo = 0.0;
        $ajustesEfectivo = 0.0;

        if ($existsMov) {
            $stmtResumenMov = $pdo->prepare("SELECT
                IFNULL(SUM(CASE WHEN tipo = 'ingreso' AND afecta_efectivo = 1 THEN monto ELSE 0 END), 0) AS ingresos,
                IFNULL(SUM(CASE WHEN tipo = 'egreso' AND afecta_efectivo = 1 THEN monto ELSE 0 END), 0) AS egresos,
                IFNULL(SUM(CASE WHEN tipo = 'ajuste' AND afecta_efectivo = 1 AND origen NOT IN ('apertura','cierre') THEN monto ELSE 0 END), 0) AS ajustes
            FROM caja_movimientos
            WHERE caja_id = ?");
            $stmtResumenMov->execute([(int)$cajaAbierta['id']]);
            $resMov = $stmtResumenMov->fetch();

            $ingresosEfectivo = round((float)($resMov['ingresos'] ?? 0), 2);
            $egresosEfectivo = round((float)($resMov['egresos'] ?? 0), 2);
            $ajustesEfectivo = round((float)($resMov['ajustes'] ?? 0), 2);
        }

        $montoInicialCaja = round((float)($cajaAbierta['monto_inicial'] ?? 0), 2);
        $cajaTeorica = round($montoInicialCaja + $ingresosEfectivo - $egresosEfectivo + $ajustesEfectivo, 2);

        $resumenCaja = [
            'monto_inicial' => $montoInicialCaja,
            'ingresos_efectivo' => $ingresosEfectivo,
            'egresos_efectivo' => $egresosEfectivo,
            'ajustes_efectivo' => $ajustesEfectivo,
            'caja_teorica' => $cajaTeorica,
        ];
    }

    $stmtTotalCierres = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE estado = 'cerrada'");
    $stmtTotalCierres->execute();
    $totalCierresCaja = (int)$stmtTotalCierres->fetchColumn();
    $totalPaginasCierres = max(1, (int)ceil($totalCierresCaja / $cierresPorPagina));
    if ($paginaCierres > $totalPaginasCierres) {
        $paginaCierres = $totalPaginasCierres;
    }
    $offsetCierres = ($paginaCierres - 1) * $cierresPorPagina;

    $sqlCierres = $cajaHasTurnoColumn
        ? "SELECT id, fecha_operacion, COALESCE(numero_turno, 1) AS numero_turno, fecha_hora_cierre, ingresos_efectivo, monto_contado_efectivo, caja_teorica_efectivo, diferencia_efectivo FROM cajas WHERE estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT " . (int)$cierresPorPagina . " OFFSET " . (int)$offsetCierres
        : "SELECT id, fecha_operacion, 1 AS numero_turno, fecha_hora_cierre, ingresos_efectivo, monto_contado_efectivo, caja_teorica_efectivo, diferencia_efectivo FROM cajas WHERE estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT " . (int)$cierresPorPagina . " OFFSET " . (int)$offsetCierres;
    $stmtCierres = $pdo->prepare($sqlCierres);
    $stmtCierres->execute();
    $ultimosCierres = $stmtCierres->fetchAll();
}

$stmtReaperturaTable = $pdo->prepare("SHOW TABLES LIKE ?");
$stmtReaperturaTable->execute(['caja_reaperturas']);
$reaperturaTableReady = (bool)$stmtReaperturaTable->fetchColumn();

if ($reaperturaTableReady) {
    $stmtColsReap = $pdo->query("SHOW COLUMNS FROM caja_reaperturas");
    $defsReap = $stmtColsReap ? $stmtColsReap->fetchAll(\PDO::FETCH_ASSOC) : [];
    $colsReap = [];
    foreach ($defsReap as $defReap) {
        if (!empty($defReap['Field'])) {
            $colsReap[] = (string)$defReap['Field'];
        }
    }
    $reaperturaHasCajaOrigenCol = in_array('caja_origen_id', $colsReap, true);
    $reaperturaHasTurnoRespCol = in_array('turno_responsable', $colsReap, true);

    $sqlTurnoPend = $reaperturaHasTurnoRespCol ? "COALESCE(r.turno_responsable, 1)" : "1";
    $sqlTurnoRec = $reaperturaHasTurnoRespCol ? "COALESCE(r.turno_responsable, 1)" : "1";
    $sqlCajaOrigenRec = $reaperturaHasCajaOrigenCol ? "r.caja_origen_id" : "NULL";

    $stmtPendientes = $pdo->prepare("SELECT r.id, r.fecha_solicitud, r.motivo_solicitud,
        " . $sqlTurnoPend . " AS turno_responsable,
        CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,'')) AS solicitante
        FROM caja_reaperturas r
        LEFT JOIN usuarios u ON u.id = r.solicitado_por_id
        WHERE r.fecha_operacion = CURDATE() AND r.estado = 'pendiente'
        ORDER BY r.fecha_solicitud DESC, r.id DESC
        LIMIT 10");
    $stmtPendientes->execute();
    $reaperturasPendientes = $stmtPendientes->fetchAll();

    $stmtCountReap = $pdo->prepare("SELECT COUNT(*) FROM caja_reaperturas");
    $stmtCountReap->execute();
    $totalReaperturas = (int)$stmtCountReap->fetchColumn();
    $totalPaginasReaperturas = max(1, (int)ceil($totalReaperturas / $reaperturasPorPagina));
    if ($paginaReaperturas > $totalPaginasReaperturas) {
        $paginaReaperturas = $totalPaginasReaperturas;
    }
    $offsetReaperturas = ($paginaReaperturas - 1) * $reaperturasPorPagina;

    $stmtRecientes = $pdo->prepare("SELECT r.id, r.fecha_solicitud, r.fecha_aprobacion, r.estado,
        " . $sqlTurnoRec . " AS turno_responsable,
        " . $sqlCajaOrigenRec . " AS caja_origen_id,
        CONCAT(COALESCE(us.nombre,''), ' ', COALESCE(us.apellido,'')) AS solicitante,
        CONCAT(COALESCE(ua.nombre,''), ' ', COALESCE(ua.apellido,'')) AS aprobador,
        r.motivo_solicitud,
        r.observacion_aprobacion,
        r.caja_reabierta_id
        FROM caja_reaperturas r
        LEFT JOIN usuarios us ON us.id = r.solicitado_por_id
        LEFT JOIN usuarios ua ON ua.id = r.aprobado_por_id
        ORDER BY r.id DESC
        LIMIT :limit OFFSET :offset");
    $stmtRecientes->bindValue(':limit', $reaperturasPorPagina, \PDO::PARAM_INT);
    $stmtRecientes->bindValue(':offset', $offsetReaperturas, \PDO::PARAM_INT);
    $stmtRecientes->execute();
    $reaperturasRecientes = $stmtRecientes->fetchAll();

    if ($reaperturaHasCajaOrigenCol && !empty($ultimosCierres)) {
        $idsCajaOrigen = [];
        foreach ($ultimosCierres as $c) {
            $idCaja = (int)($c['id'] ?? 0);
            if ($idCaja > 0) {
                $idsCajaOrigen[] = $idCaja;
            }
        }

        if (!empty($idsCajaOrigen)) {
            $idsCajaOrigen = array_values(array_unique($idsCajaOrigen));
            $placeholders = implode(',', array_fill(0, count($idsCajaOrigen), '?'));

            $stmtMovTable = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmtMovTable->execute(['caja_movimientos']);
            $existsMov = (bool)$stmtMovTable->fetchColumn();

            if ($existsMov) {
                $sqlConsolidado = "SELECT r.caja_origen_id,
                    IFNULL(SUM(CASE WHEN m.tipo = 'ingreso' AND m.afecta_efectivo = 1 THEN m.monto ELSE 0 END), 0) AS ingreso_reapertura
                FROM caja_reaperturas r
                JOIN cajas c ON c.id = r.caja_reabierta_id
                LEFT JOIN caja_movimientos m ON m.caja_id = c.id
                WHERE r.estado = 'aprobada'
                  AND r.caja_origen_id IN (" . $placeholders . ")
                GROUP BY r.caja_origen_id";
                $stmtConsolidado = $pdo->prepare($sqlConsolidado);
                $stmtConsolidado->execute($idsCajaOrigen);
                $rowsConsolidado = $stmtConsolidado->fetchAll();

                foreach ($rowsConsolidado as $rowCon) {
                    $idCaja = (int)($rowCon['caja_origen_id'] ?? 0);
                    if ($idCaja > 0) {
                        $ingresosReaperturaPorCajaOrigen[$idCaja] = round((float)($rowCon['ingreso_reapertura'] ?? 0), 2);
                    }
                }
            }
        }
    }
}
?>
<style>
@media (max-width: 767.98px) {
    .resumen-card .card-header {
        font-size: 0.9rem;
        padding: 0.55rem 0.7rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .resumen-card .card-body {
        padding: 0.7rem;
    }

    .resumen-card .card-title {
        font-size: 1.55rem;
        margin-bottom: 0.2rem;
    }

    .resumen-card .card-text {
        font-size: 0.82rem;
        margin-bottom: 0;
    }

    .metodo-card .card-header {
        font-size: 0.85rem;
        padding: 0.5rem 0.65rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .metodo-card .card-body {
        padding: 0.65rem;
    }

    .metodo-card .card-title {
        font-size: 1.05rem;
        margin-bottom: 0.2rem;
    }

    .metodo-card .card-text {
        font-size: 0.8rem;
        margin-bottom: 0;
    }

    .contabilidad-actions .btn {
        width: 100%;
        margin-right: 0 !important;
    }
}
</style>
<div class="container mt-4">
    <h3 class="mb-4">Panel de Contabilidad</h3>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card resumen-card text-white bg-success mb-3">
                <div class="card-header"><i class="bi bi-wallet2"></i> Ingresos de Hoy</div>
                <div class="card-body">
                    <h4 class="card-title"><?= money_format_local($ingresosHoy, $currencyCfg) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-white bg-danger mb-3">
                <div class="card-header"><i class="bi bi-cash"></i> Egresos de Hoy</div>
                <div class="card-body">
                    <h4 class="card-title"><?= money_format_local($egresosHoy, $currencyCfg) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-white bg-primary mb-3">
                <div class="card-header"><i class="bi bi-graph-up"></i> Ganancia del Día</div>
                <div class="card-body">
                    <h4 class="card-title"><?= money_format_local($gananciaHoy, $currencyCfg) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-dark bg-warning mb-3">
                <div class="card-header"><i class="bi bi-person-x"></i> Deuda de Clientes</div>
                <div class="card-body">
                    <h4 class="card-title"><?= money_format_local($deudaTotal, $currencyCfg) ?></h4>
                    <p class="card-text">Saldo pendiente por cobrar</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-white bg-success mb-3">
                <div class="card-header"><i class="bi bi-cash-coin"></i> Efectivo</div>
                <div class="card-body">
                    <h5 class="card-title"><?= money_format_local($ingresosPorMetodo['efectivo'], $currencyCfg) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-dark bg-info mb-3">
                <div class="card-header"><i class="bi bi-bank"></i> Transferencia</div>
                <div class="card-body">
                    <h5 class="card-title"><?= money_format_local($ingresosPorMetodo['transferencia'], $currencyCfg) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-white bg-primary mb-3">
                <div class="card-header"><i class="bi bi-credit-card-2-front"></i> Tarjeta</div>
                <div class="card-body">
                    <h5 class="card-title"><?= money_format_local($ingresosPorMetodo['tarjeta'], $currencyCfg) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-white bg-secondary mb-3">
                <div class="card-header"><i class="bi bi-phone"></i> Yape/Plin</div>
                <div class="card-body">
                    <h5 class="card-title"><?= money_format_local($ingresosPorMetodo['yape'], $currencyCfg) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-dark bg-warning mb-3">
                <div class="card-header"><i class="bi bi-layers"></i> Masivo</div>
                <div class="card-body">
                    <h5 class="card-title"><?= money_format_local($ingresosPorMetodo['masivo'], $currencyCfg) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3 d-flex flex-column flex-md-row gap-2 contabilidad-actions">
        <a href="dashboard.php?vista=ingresos" class="btn btn-outline-success me-2">
            <i class="bi bi-list-ol"></i> Ver todos los ingresos
        </a>
        <a href="dashboard.php?vista=ingresos_diario" class="btn btn-outline-primary me-2">
            <i class="bi bi-calendar3"></i> Historial diario de ingresos
        </a>
        <a href="dashboard.php?vista=egresos" class="btn btn-outline-danger">
            <i class="bi bi-cash"></i> Registrar egresos
        </a>
        <a href="dashboard.php?vista=referenciados_liquidacion" class="btn btn-outline-warning">
            <i class="bi bi-truck"></i> Liquidar referenciados
        </a>
    </div>

    <?php if ($hasReferenciadosCols): ?>
    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Pendiente Lab. Referenciado</small>
                    <strong><?= money_format_local((float)($referenciadosMetrics['pendiente_lab'] ?? 0), $currencyCfg) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Pendiente Logística Referenciada</small>
                    <strong><?= money_format_local((float)($referenciadosMetrics['pendiente_log'] ?? 0), $currencyCfg) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-secondary">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Detalles Pendientes</small>
                    <strong><?= (int)($referenciadosMetrics['pendientes_total'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasEgresoCategoria): ?>
    <div class="row g-2 mb-4">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Egresos Tercerizados Hoy</small>
                    <strong><?= money_format_local($egresosTercerizadosHoy, $currencyCfg) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-dark">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Egresos Tercerizados Mes</small>
                    <strong><?= money_format_local($egresosTercerizadosMes, $currencyCfg) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-safe2"></i> Apertura y Cierre de Caja</span>
            <?php if ($cajaAbierta): ?>
                <span class="badge bg-success">Caja abierta (Turno <?= (int)(($cajaHasTurnoColumn ? ($cajaAbierta['numero_turno'] ?? 1) : 1)) ?>)</span>
            <?php else: ?>
                <span class="badge bg-secondary">Sin caja abierta</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!$cajaTablesReady): ?>
                <div class="alert alert-warning mb-0">
                    Falta crear las tablas de caja. Ejecuta <strong>sql/agregar_tablas_caja.sql</strong> (y si ya existían, <strong>sql/actualizar_caja_robusta.sql</strong>) y recarga la página.
                </div>
            <?php elseif (!$cajaAbierta): ?>
                <div class="alert alert-info">
                    Turnos registrados hoy: <strong><?= $turnosHoy ?></strong> / <strong><?= $maxTurnosPorDia ?></strong>
                </div>
                <form method="post" action="dashboard.php?action=caja_abrir" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Monto inicial (<?= htmlspecialchars($currencySymbol) ?>)</label>
                        <input type="number" step="0.01" min="0" name="monto_inicial" class="form-control" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observación de apertura (opcional)</label>
                        <input type="text" name="observacion_apertura" class="form-control" placeholder="Ej: Caja de turno mañana">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-unlock"></i> Abrir caja
                        </button>
                    </div>
                </form>

                <?php if ($turnosHoy >= $maxTurnosPorDia): ?>
                    <hr class="my-3">
                    <?php if (!$reaperturaTableReady): ?>
                        <div class="alert alert-warning mb-0">
                            Para gestionar reaperturas ejecuta <strong>sql/agregar_tabla_caja_reaperturas.sql</strong>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Ya se alcanzó el límite de turnos del día. Usa reapertura extraordinaria con autorización.
                        </div>

                        <?php if (in_array($rolActual, ['admin', 'recepcionista'], true)): ?>
                            <form method="post" action="dashboard.php?action=caja_reapertura_solicitar" class="row g-2 align-items-end mb-3">
                                <div class="col-md-10">
                                    <label class="form-label">Motivo de reapertura extraordinaria</label>
                                    <input type="text" name="motivo_reapertura" class="form-control" placeholder="Ej: Paciente urgente fuera de turno" required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-send"></i> Solicitar
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($reaperturasPendientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Solicitante</th>
                                            <th>Turno resp.</th>
                                            <th>Motivo</th>
                                            <?php if ($rolActual === 'admin'): ?>
                                                <th>Acción</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reaperturasPendientes as $rp): ?>
                                            <tr>
                                                <td><?= (int)($rp['id'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars((string)($rp['fecha_solicitud'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars(trim((string)($rp['solicitante'] ?? '')) !== '' ? (string)$rp['solicitante'] : 'Sin dato') ?></td>
                                                <td><?= (int)($rp['turno_responsable'] ?? 1) ?></td>
                                                <td><?= htmlspecialchars((string)($rp['motivo_solicitud'] ?? '')) ?></td>
                                                <?php if ($rolActual === 'admin'): ?>
                                                    <td>
                                                        <form method="post" action="dashboard.php?action=caja_reapertura_aprobar" class="d-flex flex-column gap-1">
                                                            <input type="hidden" name="reapertura_id" value="<?= (int)($rp['id'] ?? 0) ?>">
                                                            <input type="number" step="0.01" min="0" name="monto_inicial_reapertura" class="form-control form-control-sm" placeholder="Monto inicial (<?= htmlspecialchars($currencySymbol) ?>)" value="0">
                                                            <input type="text" name="observacion_aprobacion" class="form-control form-control-sm" placeholder="Observación aprobación (opcional)">
                                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar reapertura y abrir caja extraordinaria?');">Aprobar</button>
                                                        </form>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-success">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Monto inicial</small>
                                <strong><?= money_format_local($resumenCaja['monto_inicial'], $currencyCfg) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-primary">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Ingresos efectivo</small>
                                <strong><?= money_format_local($resumenCaja['ingresos_efectivo'], $currencyCfg) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-danger">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Egresos efectivo</small>
                                <strong><?= money_format_local($resumenCaja['egresos_efectivo'], $currencyCfg) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-warning">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Ajustes efectivo</small>
                                <strong><?= money_format_local($resumenCaja['ajustes_efectivo'], $currencyCfg) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-dark">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Caja teórica</small>
                                <strong><?= money_format_local($resumenCaja['caja_teorica'], $currencyCfg) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="post" action="dashboard.php?action=caja_cerrar" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Monto contado (<?= htmlspecialchars($currencySymbol) ?>)</label>
                        <input type="number" step="0.01" min="0" name="monto_contado_efectivo" class="form-control" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observación de cierre (opcional)</label>
                        <input type="text" name="observacion_cierre" class="form-control" placeholder="Ej: Cierre sin novedad">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Confirmar cierre de caja?');">
                            <i class="bi bi-lock"></i> Cerrar caja
                        </button>
                    </div>
                </form>

                <hr class="my-3">

                <form method="post" action="dashboard.php?action=caja_ajuste" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de corrección</label>
                        <select name="tipo_ajuste" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="sobrante">Sobrante (+)</option>
                            <option value="faltante">Faltante (-)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Monto (<?= htmlspecialchars($currencySymbol) ?>)</label>
                        <input type="number" step="0.01" min="0.01" name="monto_ajuste" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Descripción del ajuste</label>
                        <input type="text" name="descripcion_ajuste" class="form-control" placeholder="Motivo del ajuste (obligatorio)" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('¿Registrar ajuste manual de efectivo?');">
                            <i class="bi bi-sliders"></i> Registrar ajuste
                        </button>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">
                            Corrección manual excepcional: no usar para pagos o egresos normales.
                        </small>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cajaTablesReady && $totalCierresCaja > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <strong>Últimos cierres de caja</strong>
                <form method="get" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="vista" value="contabilidad">
                    <input type="hidden" name="page_cierres" value="1">
                    <label class="small text-muted mb-0">Por página</label>
                    <select name="per_page_cierres" class="form-select form-select-sm" style="max-width: 90px;" onchange="this.form.submit()">
                        <option value="3" <?= $cierresPorPagina === 3 ? 'selected' : '' ?>>3</option>
                        <option value="5" <?= $cierresPorPagina === 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $cierresPorPagina === 10 ? 'selected' : '' ?>>10</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Turno</th>
                                <th>Hora cierre</th>
                                <th>Ingresos turno</th>
                                <th>Ingreso reapertura</th>
                                <th>Total evaluado</th>
                                <th>Teórico</th>
                                <th>Contado</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosCierres as $c): ?>
                                <?php $dif = (float)($c['diferencia_efectivo'] ?? 0); ?>
                                <?php
                                    $idCaja = (int)($c['id'] ?? 0);
                                    $ingresoTurno = round((float)($c['ingresos_efectivo'] ?? 0), 2);
                                    $ingresoReapertura = round((float)($ingresosReaperturaPorCajaOrigen[$idCaja] ?? 0), 2);
                                    $ingresoTotalEvaluado = round($ingresoTurno + $ingresoReapertura, 2);
                                ?>
                                <tr>
                                    <td><?= (int)$c['id'] ?></td>
                                    <td><?= htmlspecialchars((string)$c['fecha_operacion']) ?></td>
                                    <td><?= (int)($c['numero_turno'] ?? 1) ?></td>
                                    <td><?= htmlspecialchars((string)$c['fecha_hora_cierre']) ?></td>
                                    <td><?= money_format_local($ingresoTurno, $currencyCfg) ?></td>
                                    <td><?= money_format_local($ingresoReapertura, $currencyCfg) ?></td>
                                    <td><strong><?= money_format_local($ingresoTotalEvaluado, $currencyCfg) ?></strong></td>
                                    <td><?= money_format_local((float)($c['caja_teorica_efectivo'] ?? 0), $currencyCfg) ?></td>
                                    <td><?= money_format_local((float)($c['monto_contado_efectivo'] ?? 0), $currencyCfg) ?></td>
                                    <td>
                                        <span class="badge <?= $dif < 0 ? 'bg-danger' : ($dif > 0 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                            <?= money_format_local($dif, $currencyCfg) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                    $queryCierresBase = [
                        'vista' => 'contabilidad',
                        'per_page_cierres' => $cierresPorPagina,
                    ];
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center p-2 border-top gap-2">
                    <small class="text-muted">
                        Mostrando <?= $totalCierresCaja > 0 ? (($paginaCierres - 1) * $cierresPorPagina + 1) : 0 ?> - <?= min($paginaCierres * $cierresPorPagina, $totalCierresCaja) ?> de <?= (int)$totalCierresCaja ?>
                    </small>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $paginaCierres <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryCierresBase, ['page_cierres' => max(1, $paginaCierres - 1)]))) ?>">Anterior</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">Página <?= (int)$paginaCierres ?> de <?= (int)$totalPaginasCierres ?></span></li>
                        <li class="page-item <?= $paginaCierres >= $totalPaginasCierres ? 'disabled' : '' ?>">
                            <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryCierresBase, ['page_cierres' => min($totalPaginasCierres, $paginaCierres + 1)]))) ?>">Siguiente</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($reaperturaTableReady && !empty($reaperturasRecientes)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <strong>Bitácora de reaperturas de caja</strong>
                <form method="get" class="d-flex align-items-center gap-2" id="formPerPageReaperturas">
                    <input type="hidden" name="vista" value="contabilidad">
                    <input type="hidden" name="page_cierres" value="<?= (int)$paginaCierres ?>">
                    <input type="hidden" name="per_page_cierres" value="<?= (int)$cierresPorPagina ?>">
                    <input type="hidden" name="page_reap" value="1">
                    <label class="small text-muted mb-0">Por página</label>
                    <select id="contabilidadPerPageReap" name="per_page_reap" class="form-select form-select-sm" style="max-width: 90px;" data-current="<?= (int)$reaperturasPorPagina ?>" onchange="this.form.submit()">
                        <option value="3" <?= $reaperturasPorPagina === 3 ? 'selected' : '' ?>>3</option>
                        <option value="5" <?= $reaperturasPorPagina === 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $reaperturasPorPagina === 10 ? 'selected' : '' ?>>10</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Estado</th>
                                <th>Turno resp.</th>
                                <th>Caja origen</th>
                                <th>Solicitante</th>
                                <th>Aprobador</th>
                                <th>F. solicitud</th>
                                <th>F. aprobación</th>
                                <th>Caja</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reaperturasRecientes as $rr): ?>
                                <?php
                                    $estado = (string)($rr['estado'] ?? '');
                                    $badgeEstado = $estado === 'aprobada' ? 'bg-success' : ($estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary');
                                ?>
                                <tr>
                                    <td><?= (int)($rr['id'] ?? 0) ?></td>
                                    <td><span class="badge <?= $badgeEstado ?>"><?= htmlspecialchars($estado) ?></span></td>
                                    <td><?= (int)($rr['turno_responsable'] ?? 1) ?></td>
                                    <td><?= (int)($rr['caja_origen_id'] ?? 0) > 0 ? (int)$rr['caja_origen_id'] : '-' ?></td>
                                    <td><?= htmlspecialchars(trim((string)($rr['solicitante'] ?? '')) !== '' ? (string)$rr['solicitante'] : 'Sin dato') ?></td>
                                    <td><?= htmlspecialchars(trim((string)($rr['aprobador'] ?? '')) !== '' ? (string)$rr['aprobador'] : '-') ?></td>
                                    <td><?= htmlspecialchars((string)($rr['fecha_solicitud'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($rr['fecha_aprobacion'] ?? '-')) ?></td>
                                    <td><?= (int)($rr['caja_reabierta_id'] ?? 0) > 0 ? (int)$rr['caja_reabierta_id'] : '-' ?></td>
                                    <td><?= htmlspecialchars((string)($rr['motivo_solicitud'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                    $queryReapBase = [
                        'vista' => 'contabilidad',
                        'page_cierres' => $paginaCierres,
                        'per_page_cierres' => $cierresPorPagina,
                        'per_page_reap' => $reaperturasPorPagina,
                    ];
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center p-2 border-top gap-2">
                    <small class="text-muted">
                        Mostrando <?= $totalReaperturas > 0 ? (($paginaReaperturas - 1) * $reaperturasPorPagina + 1) : 0 ?> - <?= min($paginaReaperturas * $reaperturasPorPagina, $totalReaperturas) ?> de <?= (int)$totalReaperturas ?>
                    </small>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $paginaReaperturas <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryReapBase, ['page_reap' => max(1, $paginaReaperturas - 1)]))) ?>">Anterior</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">Página <?= (int)$paginaReaperturas ?> de <?= (int)$totalPaginasReaperturas ?></span></li>
                        <li class="page-item <?= $paginaReaperturas >= $totalPaginasReaperturas ? 'disabled' : '' ?>">
                            <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryReapBase, ['page_reap' => min($totalPaginasReaperturas, $paginaReaperturas + 1)]))) ?>">Siguiente</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var selectReap = document.getElementById('contabilidadPerPageReap');
    if (!selectReap) return;

    if (selectReap.dataset.current) {
        selectReap.value = selectReap.dataset.current;
    }

    var storageKey = 'contabilidad_per_page_reaperturas';
    var allowed = Array.from(selectReap.options).map(function (opt) { return opt.value; });

    try {
        var saved = localStorage.getItem(storageKey);
        if (saved && allowed.indexOf(saved) !== -1) {
            var current = selectReap.value;
            selectReap.value = saved;
            if (saved !== current && selectReap.form) {
                selectReap.form.submit();
                return;
            }
        }
    } catch (error) {
    }

    selectReap.addEventListener('change', function () {
        try {
            localStorage.setItem(storageKey, selectReap.value);
        } catch (error) {
        }
    });
})();
</script>
