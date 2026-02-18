<?php
require_once __DIR__ . '/../conexion/conexion.php';

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
        ? "SELECT id, fecha_operacion, COALESCE(numero_turno, 1) AS numero_turno, fecha_hora_cierre, monto_contado_efectivo, caja_teorica_efectivo, diferencia_efectivo FROM cajas WHERE estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT " . (int)$cierresPorPagina . " OFFSET " . (int)$offsetCierres
        : "SELECT id, fecha_operacion, 1 AS numero_turno, fecha_hora_cierre, monto_contado_efectivo, caja_teorica_efectivo, diferencia_efectivo FROM cajas WHERE estado = 'cerrada' ORDER BY fecha_hora_cierre DESC LIMIT " . (int)$cierresPorPagina . " OFFSET " . (int)$offsetCierres;
    $stmtCierres = $pdo->prepare($sqlCierres);
    $stmtCierres->execute();
    $ultimosCierres = $stmtCierres->fetchAll();
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
                    <h4 class="card-title">S/ <?= number_format($ingresosHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-white bg-danger mb-3">
                <div class="card-header"><i class="bi bi-cash"></i> Egresos de Hoy</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($egresosHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-white bg-primary mb-3">
                <div class="card-header"><i class="bi bi-graph-up"></i> Ganancia del Día</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($gananciaHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card resumen-card text-dark bg-warning mb-3">
                <div class="card-header"><i class="bi bi-person-x"></i> Deuda de Clientes</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($deudaTotal, 2) ?></h4>
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
                    <h5 class="card-title">S/ <?= number_format($ingresosPorMetodo['efectivo'], 2) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-dark bg-info mb-3">
                <div class="card-header"><i class="bi bi-bank"></i> Transferencia</div>
                <div class="card-body">
                    <h5 class="card-title">S/ <?= number_format($ingresosPorMetodo['transferencia'], 2) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-white bg-primary mb-3">
                <div class="card-header"><i class="bi bi-credit-card-2-front"></i> Tarjeta</div>
                <div class="card-body">
                    <h5 class="card-title">S/ <?= number_format($ingresosPorMetodo['tarjeta'], 2) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-white bg-secondary mb-3">
                <div class="card-header"><i class="bi bi-phone"></i> Yape/Plin</div>
                <div class="card-body">
                    <h5 class="card-title">S/ <?= number_format($ingresosPorMetodo['yape'], 2) ?></h5>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card metodo-card text-dark bg-warning mb-3">
                <div class="card-header"><i class="bi bi-layers"></i> Masivo</div>
                <div class="card-body">
                    <h5 class="card-title">S/ <?= number_format($ingresosPorMetodo['masivo'], 2) ?></h5>
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
    </div>

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
                        <label class="form-label">Monto inicial (S/)</label>
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
            <?php else: ?>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-success">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Monto inicial</small>
                                <strong>S/ <?= number_format($resumenCaja['monto_inicial'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-primary">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Ingresos efectivo</small>
                                <strong>S/ <?= number_format($resumenCaja['ingresos_efectivo'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-danger">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Egresos efectivo</small>
                                <strong>S/ <?= number_format($resumenCaja['egresos_efectivo'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-warning">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Ajustes efectivo</small>
                                <strong>S/ <?= number_format($resumenCaja['ajustes_efectivo'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-dark">
                            <div class="card-body py-2">
                                <small class="text-muted d-block">Caja teórica</small>
                                <strong>S/ <?= number_format($resumenCaja['caja_teorica'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="post" action="dashboard.php?action=caja_cerrar" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Monto contado (S/)</label>
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
                        <label class="form-label">Monto (S/)</label>
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
                                <th>Teórico</th>
                                <th>Contado</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosCierres as $c): ?>
                                <?php $dif = (float)($c['diferencia_efectivo'] ?? 0); ?>
                                <tr>
                                    <td><?= (int)$c['id'] ?></td>
                                    <td><?= htmlspecialchars((string)$c['fecha_operacion']) ?></td>
                                    <td><?= (int)($c['numero_turno'] ?? 1) ?></td>
                                    <td><?= htmlspecialchars((string)$c['fecha_hora_cierre']) ?></td>
                                    <td>S/ <?= number_format((float)($c['caja_teorica_efectivo'] ?? 0), 2) ?></td>
                                    <td>S/ <?= number_format((float)($c['monto_contado_efectivo'] ?? 0), 2) ?></td>
                                    <td>
                                        <span class="badge <?= $dif < 0 ? 'bg-danger' : ($dif > 0 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                            S/ <?= number_format($dif, 2) ?>
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
</div>
