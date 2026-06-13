<?php
require_once __DIR__ . '/../conexion/conexion.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$fechaInicio = $fecha . ' 00:00:00';
$fechaFin = date('Y-m-d H:i:s', strtotime($fecha . ' +1 day'));

$perPagePermitidos = [3, 5, 10];
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 3;
if (!in_array($perPage, $perPagePermitidos, true)) {
    $perPage = 3;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$metodosObjetivo = ['efectivo', 'transferencia', 'tarjeta', 'yape', 'masivo'];
$ingresosPorMetodo = array_fill_keys($metodosObjetivo, 0.0);
$totalDia = 0.0;
$egresosDia = 0.0;
$totalEgresosRegistros = 0;
$netoDia = 0.0;

$stmtTotales = $pdo->prepare("SELECT LOWER(TRIM(metodo_pago)) AS metodo, SUM(monto) AS total FROM pagos WHERE fecha >= ? AND fecha < ? GROUP BY LOWER(TRIM(metodo_pago))");
$stmtTotales->execute([$fechaInicio, $fechaFin]);
$rowsTotales = $stmtTotales->fetchAll(\PDO::FETCH_ASSOC);

foreach ($rowsTotales as $row) {
    $metodo = strtolower(trim((string)($row['metodo'] ?? '')));
    if ($metodo === 'plin' || $metodo === 'yape/plin') {
        $metodo = 'yape';
    }

    $monto = (float)($row['total'] ?? 0);
    if (array_key_exists($metodo, $ingresosPorMetodo)) {
        $ingresosPorMetodo[$metodo] += $monto;
    }
    $totalDia += $monto;
}

$stmtEgresosTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'egresos'");
$stmtEgresosTable->execute();
$existsEgresos = (int)$stmtEgresosTable->fetchColumn() > 0;

if ($existsEgresos) {
    $stmtEgresosTotales = $pdo->prepare("SELECT IFNULL(SUM(monto), 0) AS total_egresos, COUNT(*) AS total_registros FROM egresos WHERE fecha >= ? AND fecha < ?");
    $stmtEgresosTotales->execute([$fechaInicio, $fechaFin]);
    $rowEgresosTotales = $stmtEgresosTotales->fetch(\PDO::FETCH_ASSOC) ?: [];
    $egresosDia = (float)($rowEgresosTotales['total_egresos'] ?? 0);
    $totalEgresosRegistros = (int)($rowEgresosTotales['total_registros'] ?? 0);
}

$netoDia = $totalDia - $egresosDia;

$stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
$stmtMovTable->execute();
$existsMov = (int)$stmtMovTable->fetchColumn() > 0;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE fecha >= ? AND fecha < ?");
$stmtCount->execute([$fechaInicio, $fechaFin]);
$totalRegistros = (int)$stmtCount->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalRegistros / $perPage));
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}

$offset = ($page - 1) * $perPage;

$sqlDetalle = $existsMov
    ? "
    SELECT
        p.id AS id_pago,
        p.fecha,
        p.monto,
        p.metodo_pago,
        c.id AS id_cotizacion,
        c.codigo AS codigo_cotizacion,
        CONCAT(COALESCE(cl.nombre, ''), ' ', COALESCE(cl.apellido, '')) AS cliente,
        um.nombre AS usuario_nombre,
        um.apellido AS usuario_apellido
    FROM pagos p
    LEFT JOIN cotizaciones c ON c.id = p.id_cotizacion
    LEFT JOIN clientes cl ON cl.id = c.id_cliente
    LEFT JOIN (
                SELECT cm.referencia_id, MAX(cm.usuario_id) AS usuario_id
                FROM caja_movimientos cm
                INNER JOIN pagos px ON px.id = cm.referencia_id
                WHERE cm.tipo = 'ingreso'
                    AND cm.origen = 'pago'
                    AND cm.referencia_tipo IN ('pago_individual', 'pago_masivo')
                    AND cm.referencia_id IS NOT NULL
                    AND px.fecha >= ?
                    AND px.fecha < ?
                GROUP BY cm.referencia_id
    ) mov ON mov.referencia_id = p.id
    LEFT JOIN usuarios um ON um.id = mov.usuario_id
        WHERE p.fecha >= ?
            AND p.fecha < ?
    ORDER BY p.fecha DESC, p.id DESC
    LIMIT {$perPage} OFFSET {$offset}
"
    : "
    SELECT
        p.id AS id_pago,
        p.fecha,
        p.monto,
        p.metodo_pago,
        c.id AS id_cotizacion,
        c.codigo AS codigo_cotizacion,
        CONCAT(COALESCE(cl.nombre, ''), ' ', COALESCE(cl.apellido, '')) AS cliente,
        NULL AS usuario_nombre,
        NULL AS usuario_apellido
    FROM pagos p
    LEFT JOIN cotizaciones c ON c.id = p.id_cotizacion
    LEFT JOIN clientes cl ON cl.id = c.id_cliente
    WHERE p.fecha >= ?
      AND p.fecha < ?
    ORDER BY p.fecha DESC, p.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmtDetalle = $pdo->prepare($sqlDetalle);
if ($existsMov) {
    $stmtDetalle->execute([$fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
} else {
    $stmtDetalle->execute([$fechaInicio, $fechaFin]);
}
$detallePagos = $stmtDetalle->fetchAll(\PDO::FETCH_ASSOC);

function metodo_label(string $metodo): string
{
    $metodo = strtolower(trim($metodo));
    if ($metodo === 'plin' || $metodo === 'yape/plin' || $metodo === 'yape') {
        return 'Yape/Plin';
    }
    if ($metodo === 'transferencia') {
        return 'Transferencia';
    }
    if ($metodo === 'tarjeta') {
        return 'Tarjeta';
    }
    if ($metodo === 'efectivo') {
        return 'Efectivo';
    }
    if ($metodo === 'masivo') {
        return 'Masivo';
    }
    return ucfirst($metodo);
}
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <h3 class="mb-0">Historial diario de ingresos</h3>
        <a href="dashboard.php?vista=contabilidad" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Contabilidad
        </a>
    </div>

    <form method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="vista" value="ingresos_diario">
        <div class="col-12 col-md-3">
            <label class="form-label">Fecha a consultar</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control" required>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Registros por página</label>
            <select name="per_page" class="form-select">
                <option value="3" <?= $perPage === 3 ? 'selected' : '' ?>>3</option>
                <option value="5" <?= $perPage === 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Ver día
            </button>
        </div>
    </form>

    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-danger">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Egreso del día</small>
                    <strong>S/ <?= number_format($egresosDia, 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-success">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Ingreso bruto del día</small>
                    <strong>S/ <?= number_format($totalDia, 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card <?= $netoDia >= 0 ? 'border-primary' : 'border-danger' ?>">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Neto contable estimado</small>
                    <strong>S/ <?= number_format($netoDia, 2) ?></strong>
                    <small class="text-muted d-block">Ingresos - egresos del periodo</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-secondary">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Movimientos del día</small>
                    <strong><?= (int)$totalRegistros ?> ingresos / <?= (int)$totalEgresosRegistros ?> egresos</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="dashboard.php?<?= htmlspecialchars(http_build_query(['vista' => 'egresos', 'desde' => $fecha, 'hasta' => $fecha])) ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-cash"></i> Ver egresos de este día
        </a>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-success">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Efectivo</small>
                    <strong>S/ <?= number_format((float)$ingresosPorMetodo['efectivo'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-info">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Transferencia</small>
                    <strong>S/ <?= number_format((float)$ingresosPorMetodo['transferencia'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-primary">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Tarjeta</small>
                    <strong>S/ <?= number_format((float)$ingresosPorMetodo['tarjeta'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-secondary">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Yape/Plin</small>
                    <strong>S/ <?= number_format((float)$ingresosPorMetodo['yape'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Masivo</small>
                    <strong>S/ <?= number_format((float)$ingresosPorMetodo['masivo'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card border-dark">
                <div class="card-body py-2">
                    <small class="text-muted d-block">Ingresos brutos</small>
                    <strong>S/ <?= number_format($totalDia, 2) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong>Pagos del <?= htmlspecialchars(date('d/m/Y', strtotime($fecha))) ?></strong>
            <span class="badge bg-secondary"><?= $totalRegistros ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Hora</th>
                            <th>Cotización</th>
                            <th>Cliente</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($detallePagos)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay pagos para esta fecha.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detallePagos as $row): ?>
                                <?php
                                $usuario = trim((string)(($row['usuario_nombre'] ?? '') . ' ' . ($row['usuario_apellido'] ?? '')));
                                if ($usuario === '') {
                                    $usuario = 'Sin dato';
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)date('H:i:s', strtotime((string)$row['fecha']))) ?></td>
                                    <td>
                                        #<?= (int)($row['id_cotizacion'] ?? 0) ?>
                                        <?php if (!empty($row['codigo_cotizacion'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars((string)$row['codigo_cotizacion']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(trim((string)($row['cliente'] ?? 'Sin cliente'))) ?></td>
                                    <td><?= htmlspecialchars(metodo_label((string)($row['metodo_pago'] ?? ''))) ?></td>
                                    <td>S/ <?= number_format((float)($row['monto'] ?? 0), 2) ?></td>
                                    <td><?= htmlspecialchars($usuario) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalRegistros > 0): ?>
                <?php
                $inicio = $offset + 1;
                $fin = min($offset + $perPage, $totalRegistros);
                $maxPaginasVisibles = 5;
                $mitadVentana = (int)floor($maxPaginasVisibles / 2);
                $paginaInicio = max(1, $page - $mitadVentana);
                $paginaFin = min($totalPaginas, $paginaInicio + $maxPaginasVisibles - 1);
                if (($paginaFin - $paginaInicio + 1) < $maxPaginasVisibles) {
                    $paginaInicio = max(1, $paginaFin - $maxPaginasVisibles + 1);
                }
                $baseParams = [
                    'vista' => 'ingresos_diario',
                    'fecha' => $fecha,
                    'per_page' => $perPage,
                ];
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3 py-2 border-top">
                    <small class="text-muted">
                        Mostrando <?= $inicio ?> - <?= $fin ?> de <?= $totalRegistros ?> registros
                    </small>
                    <nav aria-label="Paginación historial diario">
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $prevPage = max(1, $page - 1);
                            $nextPage = min($totalPaginas, $page + 1);
                            ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $prevPage]))) ?>">Anterior</a>
                            </li>
                            <?php if ($paginaInicio > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => 1]))) ?>">1</a>
                                </li>
                                <?php if ($paginaInicio > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $paginaInicio; $p <= $paginaFin; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $p]))) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($paginaFin < $totalPaginas): ?>
                                <?php if ($paginaFin < ($totalPaginas - 1)): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $totalPaginas]))) ?>"><?= $totalPaginas ?></a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= $page >= $totalPaginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $nextPage]))) ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
