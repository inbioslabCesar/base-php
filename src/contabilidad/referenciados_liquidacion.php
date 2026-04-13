<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

$hasReferenciadoCols = hasColumn($pdo, 'cotizaciones_detalle', 'es_referenciado')
    && hasColumn($pdo, 'cotizaciones_detalle', 'laboratorio_referenciado_nombre')
    && hasColumn($pdo, 'cotizaciones_detalle', 'costo_laboratorio_referenciado')
    && hasColumn($pdo, 'cotizaciones_detalle', 'costo_logistica_extra')
    && hasColumn($pdo, 'cotizaciones_detalle', 'estado_liquidacion')
    && hasColumn($pdo, 'cotizaciones_detalle', 'fecha_liquidacion');

$estadoFiltro = strtolower(trim((string)($_GET['estado'] ?? 'pendiente')));
if (!in_array($estadoFiltro, ['pendiente', 'liquidado', 'todos'], true)) {
    $estadoFiltro = 'pendiente';
}

$cards = [
    'pendiente_lab' => 0.0,
    'pendiente_log' => 0.0,
    'liquidado_hoy' => 0.0,
    'pendientes_total' => 0,
];

$rows = [];

if ($hasReferenciadoCols) {
    $stmtCards = $pdo->query("SELECT
        IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN costo_laboratorio_referenciado ELSE 0 END), 0) AS pendiente_lab,
        IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN costo_logistica_extra ELSE 0 END), 0) AS pendiente_log,
        IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'liquidado' AND DATE(fecha_liquidacion) = CURDATE() THEN (costo_laboratorio_referenciado + costo_logistica_extra) ELSE 0 END), 0) AS liquidado_hoy,
        IFNULL(SUM(CASE WHEN es_referenciado = 1 AND estado_liquidacion = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes_total
        FROM cotizaciones_detalle");
    $cards = $stmtCards->fetch(PDO::FETCH_ASSOC) ?: $cards;

    $where = "cd.es_referenciado = 1";
    if ($estadoFiltro === 'pendiente') {
        $where .= " AND cd.estado_liquidacion = 'pendiente'";
    } elseif ($estadoFiltro === 'liquidado') {
        $where .= " AND cd.estado_liquidacion = 'liquidado'";
    }

    $stmtRows = $pdo->query("SELECT
        cd.id,
        cd.id_cotizacion,
        c.codigo,
        c.fecha,
        cl.nombre AS cliente_nombre,
        cl.apellido AS cliente_apellido,
        cd.nombre_examen,
        cd.laboratorio_referenciado_nombre,
        cd.costo_laboratorio_referenciado,
        cd.costo_logistica_extra,
        cd.estado_liquidacion,
        cd.fecha_liquidacion
        FROM cotizaciones_detalle cd
        INNER JOIN cotizaciones c ON c.id = cd.id_cotizacion
        LEFT JOIN clientes cl ON cl.id = c.id_cliente
        WHERE {$where}
        ORDER BY c.fecha DESC, cd.id DESC");
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Liquidación de Referenciados</h3>
        <a href="dashboard.php?vista=contabilidad" class="btn btn-outline-secondary">Volver a Contabilidad</a>
    </div>

    <?php if (!$hasReferenciadoCols): ?>
        <div class="alert alert-warning">
            Falta la migración de referenciados. Ejecuta <strong>sql/2026_02_24_agregar_tercerizados_liquidacion.sql</strong>.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['ok']) && $_GET['ok'] === 'liquidado'): ?>
        <div class="alert alert-success">Liquidación registrada correctamente.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-danger">No se pudo completar la operación: <?= htmlspecialchars((string)$_GET['msg']) ?>.</div>
    <?php endif; ?>

    <?php if ($hasReferenciadoCols): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <small class="text-muted d-block">Pendiente laboratorio</small>
                        <h5 class="mb-0">S/ <?= number_format((float)($cards['pendiente_lab'] ?? 0), 2) ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <small class="text-muted d-block">Pendiente logística</small>
                        <h5 class="mb-0">S/ <?= number_format((float)($cards['pendiente_log'] ?? 0), 2) ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <small class="text-muted d-block">Liquidado hoy</small>
                        <h5 class="mb-0">S/ <?= number_format((float)($cards['liquidado_hoy'] ?? 0), 2) ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <small class="text-muted d-block">Detalles pendientes</small>
                        <h5 class="mb-0"><?= (int)($cards['pendientes_total'] ?? 0) ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="vista" value="referenciados_liquidacion">
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="pendiente" <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="liquidado" <?= $estadoFiltro === 'liquidado' ? 'selected' : '' ?>>Liquidado</option>
                    <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Cotización</th>
                        <th>Cliente</th>
                        <th>Examen</th>
                        <th>Laboratorio</th>
                        <th>Costo Lab</th>
                        <th>Costo Logística</th>
                        <th>Estado</th>
                        <th>Fecha liquidación</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $cliente = trim(((string)($row['cliente_nombre'] ?? '')) . ' ' . ((string)($row['cliente_apellido'] ?? '')));
                                $estado = (string)($row['estado_liquidacion'] ?? 'pendiente');
                                $badge = $estado === 'liquidado' ? 'bg-success' : 'bg-warning text-dark';
                            ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars((string)($row['codigo'] ?? ('COT-' . $row['id_cotizacion']))) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string)($row['fecha'] ?? '')) ?></small>
                                </td>
                                <td><?= htmlspecialchars($cliente !== '' ? $cliente : '—') ?></td>
                                <td><?= htmlspecialchars((string)($row['nombre_examen'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['laboratorio_referenciado_nombre'] ?? '—')) ?></td>
                                <td>S/ <?= number_format((float)($row['costo_laboratorio_referenciado'] ?? 0), 2) ?></td>
                                <td>S/ <?= number_format((float)($row['costo_logistica_extra'] ?? 0), 2) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($estado)) ?></span></td>
                                <td><?= !empty($row['fecha_liquidacion']) ? htmlspecialchars((string)$row['fecha_liquidacion']) : '—' ?></td>
                                <td>
                                    <?php if ($estado === 'pendiente'): ?>
                                        <form method="post" action="dashboard.php?action=liquidar_referenciado" onsubmit="return confirm('¿Confirmas liquidar este detalle referenciado?');">
                                            <input type="hidden" name="id_detalle" value="<?= (int)$row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Liquidar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success">Aplicado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay registros para el filtro seleccionado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
