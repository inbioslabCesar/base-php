<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo '<div class="container mt-4"><div class="alert alert-danger">No hay conexion a base de datos.</div></div>';
    return;
}

$fecha = isset($_GET['fecha']) ? trim((string)$_GET['fecha']) : date('Y-m-d');
$dt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dt || $dt->format('Y-m-d') !== $fecha) {
    $fecha = date('Y-m-d');
}

function offline_monitor_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

function offline_monitor_module_stats(PDO $pdo, string $tableName, string $fecha): array {
    if (!offline_monitor_table_exists($pdo, $tableName)) {
        return [
            'exists' => false,
            'total' => 0,
            'aplicado' => 0,
            'pendiente' => 0,
            'error' => 0,
            'duplicados' => 0,
            'aplicado_pct' => 0.0,
            'error_pct' => 0.0,
            'duplicado_pct' => 0.0,
        ];
    }

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'aplicado' THEN 1 ELSE 0 END) AS aplicado,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) AS error_count,
                COUNT(DISTINCT operation_id) AS unique_ops
            FROM {$tableName}
            WHERE DATE(created_at) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $total = (int)($row['total'] ?? 0);
    $aplicado = (int)($row['aplicado'] ?? 0);
    $pendiente = (int)($row['pendiente'] ?? 0);
    $error = (int)($row['error_count'] ?? 0);
    $uniqueOps = (int)($row['unique_ops'] ?? 0);
    $duplicados = max(0, $total - $uniqueOps);
    $base = $total > 0 ? (float)$total : 1.0;

    return [
        'exists' => true,
        'total' => $total,
        'aplicado' => $aplicado,
        'pendiente' => $pendiente,
        'error' => $error,
        'duplicados' => $duplicados,
        'aplicado_pct' => round(($aplicado / $base) * 100, 2),
        'error_pct' => round(($error / $base) * 100, 2),
        'duplicado_pct' => round(($duplicados / $base) * 100, 2),
    ];
}

function offline_monitor_module_stats_range(PDO $pdo, string $tableName, string $startDate, string $endDate): array {
    if (!offline_monitor_table_exists($pdo, $tableName)) {
        return [
            'exists' => false,
            'total' => 0,
            'aplicado' => 0,
            'pendiente' => 0,
            'error' => 0,
            'duplicados' => 0,
            'aplicado_pct' => 0.0,
            'error_pct' => 0.0,
            'duplicado_pct' => 0.0,
        ];
    }

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'aplicado' THEN 1 ELSE 0 END) AS aplicado,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) AS error_count,
                COUNT(DISTINCT operation_id) AS unique_ops
            FROM {$tableName}
            WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $total = (int)($row['total'] ?? 0);
    $aplicado = (int)($row['aplicado'] ?? 0);
    $pendiente = (int)($row['pendiente'] ?? 0);
    $error = (int)($row['error_count'] ?? 0);
    $uniqueOps = (int)($row['unique_ops'] ?? 0);
    $duplicados = max(0, $total - $uniqueOps);
    $base = $total > 0 ? (float)$total : 1.0;

    return [
        'exists' => true,
        'total' => $total,
        'aplicado' => $aplicado,
        'pendiente' => $pendiente,
        'error' => $error,
        'duplicados' => $duplicados,
        'aplicado_pct' => round(($aplicado / $base) * 100, 2),
        'error_pct' => round(($error / $base) * 100, 2),
        'duplicado_pct' => round(($duplicados / $base) * 100, 2),
    ];
}

function offline_monitor_global_from_stats(array $stats): array {
    $global = [
        'total' => 0,
        'aplicado' => 0,
        'pendiente' => 0,
        'error' => 0,
        'duplicados' => 0,
        'aplicado_pct' => 0.0,
        'error_pct' => 0.0,
        'duplicado_pct' => 0.0,
    ];

    foreach ($stats as $row) {
        $global['total'] += (int)($row['total'] ?? 0);
        $global['aplicado'] += (int)($row['aplicado'] ?? 0);
        $global['pendiente'] += (int)($row['pendiente'] ?? 0);
        $global['error'] += (int)($row['error'] ?? 0);
        $global['duplicados'] += (int)($row['duplicados'] ?? 0);
    }

    $base = $global['total'] > 0 ? (float)$global['total'] : 1.0;
    $global['aplicado_pct'] = round(($global['aplicado'] / $base) * 100, 2);
    $global['error_pct'] = round(($global['error'] / $base) * 100, 2);
    $global['duplicado_pct'] = round(($global['duplicados'] / $base) * 100, 2);

    return $global;
}

$modules = [
    'agenda' => 'agenda_sync_operaciones',
    'caja' => 'caja_sync_operaciones',
    'inventario' => 'inventario_sync_operaciones',
    'pacientes' => 'clientes_sync_operaciones',
    'resultados' => 'resultados_sync_operaciones',
];

$stats = [];

foreach ($modules as $module => $tableName) {
    $row = offline_monitor_module_stats($pdo, $tableName, $fecha);
    $stats[$module] = array_merge(['table' => $tableName], $row);
}

$global = offline_monitor_global_from_stats($stats);

$endDtWeekly = new DateTime($fecha);
$startDtWeekly = (clone $endDtWeekly)->modify('-6 days');
$startDateWeekly = $startDtWeekly->format('Y-m-d');
$endDateWeekly = $endDtWeekly->format('Y-m-d');

$statsWeekly = [];
foreach ($modules as $module => $tableName) {
    $row = offline_monitor_module_stats_range($pdo, $tableName, $startDateWeekly, $endDateWeekly);
    $statsWeekly[$module] = array_merge(['table' => $tableName], $row);
}
$globalWeekly = offline_monitor_global_from_stats($statsWeekly);

$estadoFallas = $global['total'] > 0 ? ($global['error_pct'] < 1.0 ? 'OK' : 'ALERTA') : 'SIN_DATOS';
$estadoDup = $global['total'] > 0 ? ($global['duplicado_pct'] < 0.2 ? 'OK' : 'ALERTA') : 'SIN_DATOS';
$estadoAplicado = $global['total'] > 0 ? ($global['aplicado_pct'] >= 95.0 ? 'OK' : 'ALERTA') : 'SIN_DATOS';

$estadoFallasWeekly = $globalWeekly['total'] > 0 ? ($globalWeekly['error_pct'] < 1.0 ? 'OK' : 'ALERTA') : 'SIN_DATOS';
$estadoDupWeekly = $globalWeekly['total'] > 0 ? ($globalWeekly['duplicado_pct'] < 0.2 ? 'OK' : 'ALERTA') : 'SIN_DATOS';
$estadoAplicadoWeekly = $globalWeekly['total'] > 0 ? ($globalWeekly['aplicado_pct'] >= 95.0 ? 'OK' : 'ALERTA') : 'SIN_DATOS';

$alertasActivas = [];
if ($estadoFallas === 'ALERTA') {
    $alertasActivas[] = 'Operaciones fallidas fuera de objetivo (< 1.00%).';
}
if ($estadoDup === 'ALERTA') {
    $alertasActivas[] = 'Duplicados fuera de objetivo (< 0.20%).';
}
if ($estadoAplicado === 'ALERTA') {
    $alertasActivas[] = 'Sincronizacion aplicada por debajo de objetivo (>= 95.00%).';
}

$fechaHoy = date('Y-m-d');
?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Monitoreo Offline</h4>
        <form class="d-flex flex-wrap align-items-center gap-2" method="get" action="dashboard.php">
            <input type="hidden" name="vista" value="offline_monitor">
            <label for="fecha" class="form-label mb-0">Fecha</label>
            <input type="date" id="fecha" name="fecha" class="form-control" value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>" style="max-width: 190px;">
            <button type="submit" class="btn btn-primary btn-sm">Ver</button>
            <a href="dashboard.php?vista=offline_monitor" class="btn btn-outline-secondary btn-sm">Hoy</a>
        </form>
    </div>

    <?php if (!empty($alertasActivas)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <div class="fw-semibold">KPI en ALERTA</div>
            <?php foreach ($alertasActivas as $mensajeAlerta): ?>
                <div class="small"><?= htmlspecialchars($mensajeAlerta, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($global['total'] > 0): ?>
    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span class="small fw-semibold">Todos los KPI del dia estan dentro de objetivo.</span>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Operaciones fallidas</div>
                    <div class="fs-4 fw-bold"><?= number_format((float)$global['error_pct'], 2, '.', '') ?>%</div>
                    <span class="badge <?= $estadoFallas === 'OK' ? 'bg-success' : ($estadoFallas === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoFallas ?></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Duplicados</div>
                    <div class="fs-4 fw-bold"><?= number_format((float)$global['duplicado_pct'], 2, '.', '') ?>%</div>
                    <span class="badge <?= $estadoDup === 'OK' ? 'bg-success' : ($estadoDup === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoDup ?></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Aplicado</div>
                    <div class="fs-4 fw-bold"><?= number_format((float)$global['aplicado_pct'], 2, '.', '') ?>%</div>
                    <span class="badge <?= $estadoAplicado === 'OK' ? 'bg-success' : ($estadoAplicado === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoAplicado ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold">
            Semaforo semanal (ultimos 7 dias: <?= htmlspecialchars($startDateWeekly, ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars($endDateWeekly, ENT_QUOTES, 'UTF-8') ?>)
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Operaciones fallidas</div>
                        <div class="fs-5 fw-bold"><?= number_format((float)$globalWeekly['error_pct'], 2, '.', '') ?>%</div>
                        <span class="badge <?= $estadoFallasWeekly === 'OK' ? 'bg-success' : ($estadoFallasWeekly === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoFallasWeekly ?></span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Duplicados</div>
                        <div class="fs-5 fw-bold"><?= number_format((float)$globalWeekly['duplicado_pct'], 2, '.', '') ?>%</div>
                        <span class="badge <?= $estadoDupWeekly === 'OK' ? 'bg-success' : ($estadoDupWeekly === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoDupWeekly ?></span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Aplicado</div>
                        <div class="fs-5 fw-bold"><?= number_format((float)$globalWeekly['aplicado_pct'], 2, '.', '') ?>%</div>
                        <span class="badge <?= $estadoAplicadoWeekly === 'OK' ? 'bg-success' : ($estadoAplicadoWeekly === 'ALERTA' ? 'bg-danger' : 'bg-secondary') ?>"><?= $estadoAplicadoWeekly ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold">Detalle por modulo (<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>)</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 align-middle">
                <thead>
                    <?php
                        $rowClass = '';
                        if ((int)$row['error'] > 0) {
                            $rowClass = 'table-danger';
                        } elseif ((int)$row['pendiente'] > 0 || (int)$row['duplicados'] > 0) {
                            $rowClass = 'table-warning';
                        }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <th>Modulo</th>
                        <th>Total</th>
                        <th>Aplicado</th>
                        <th>Pendiente</th>
                        <th>Error</th>
                        <th>Duplicado</th>
                        <th>Apl%</th>
                        <th>Err%</th>
                        <th>Dup%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $module => $row): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>
                            <?php if (empty($row['exists'])): ?><span class="badge bg-secondary ms-1">N/A</span><?php endif; ?>
                        </td>
                        <td><?= (int)$row['total'] ?></td>
                        <td><?= (int)$row['aplicado'] ?></td>
                        <td><?= (int)$row['pendiente'] ?></td>
                        <td><?= (int)$row['error'] ?></td>
                        <td><?= (int)$row['duplicados'] ?></td>
                        <td><?= number_format((float)$row['aplicado_pct'], 2, '.', '') ?>%</td>
                        <td><?= number_format((float)$row['error_pct'], 2, '.', '') ?>%</td>
                        <td><?= number_format((float)$row['duplicado_pct'], 2, '.', '') ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td>GLOBAL</td>
                        <td><?= (int)$global['total'] ?></td>
                        <td><?= (int)$global['aplicado'] ?></td>
                        <td><?= (int)$global['pendiente'] ?></td>
                        <td><?= (int)$global['error'] ?></td>
                        <td><?= (int)$global['duplicados'] ?></td>
                        <td><?= number_format((float)$global['aplicado_pct'], 2, '.', '') ?>%</td>
                        <td><?= number_format((float)$global['error_pct'], 2, '.', '') ?>%</td>
                        <td><?= number_format((float)$global['duplicado_pct'], 2, '.', '') ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Incidencias locales (navegador actual)</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnOfflineIncidenciasReload">Recargar</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnOfflineIncidenciasClear">Limpiar</button>
            </div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">Fuente: localStorage offline_sync_incidents_v1</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="offlineIncidenciasTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Modulo</th>
                            <th>Detalle</th>
                            <th>Ruta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="text-muted">Sin datos cargados.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-secondary mt-3 small">
        Comando sugerido para historico diario:
        <strong>php scripts/reporte_kpi_offline_sync.php --date=<?= htmlspecialchars($fechaHoy, ENT_QUOTES, 'UTF-8') ?> --save</strong>
    </div>
</div>

<script>
(function () {
    var KEY = 'offline_sync_incidents_v1';
    var table = document.getElementById('offlineIncidenciasTable');
    var reloadBtn = document.getElementById('btnOfflineIncidenciasReload');
    var clearBtn = document.getElementById('btnOfflineIncidenciasClear');
    if (!table) return;

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function loadRows() {
        var rows = [];
        try {
            var raw = localStorage.getItem(KEY) || '[]';
            var parsed = JSON.parse(raw);
            rows = Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            rows = [];
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">Sin incidencias registradas.</td></tr>';
            return;
        }

        rows.sort(function (a, b) {
            return String(b && b.at ? b.at : '').localeCompare(String(a && a.at ? a.at : ''));
        });

        var html = rows.slice(0, 200).map(function (r) {
            var at = r && r.at ? new Date(r.at).toLocaleString() : '-';
            var mod = r && r.module ? r.module : '-';
            var det = r && r.detail ? r.detail : '-';
            var path = r && r.path ? r.path : '-';
            return '<tr>'
                + '<td>' + esc(at) + '</td>'
                + '<td>' + esc(mod) + '</td>'
                + '<td>' + esc(det) + '</td>'
                + '<td>' + esc(path) + '</td>'
                + '</tr>';
        }).join('');
        tbody.innerHTML = html;
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', loadRows);
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (!window.confirm('Se eliminaran incidencias locales del navegador actual. ¿Continuar?')) {
                return;
            }
            localStorage.removeItem(KEY);
            loadRows();
        });
    }

    loadRows();
})();
</script>
