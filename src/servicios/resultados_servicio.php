<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
require_once __DIR__ . '/../cotizaciones/funciones/cotizaciones_utils.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger">No se pudo inicializar el modulo de servicios.</div></div>';
    return;
}

$servicioId = (int)($_SESSION['servicio_id'] ?? 0);
if ($servicioId <= 0 || strtolower(trim((string)($_SESSION['rol'] ?? ''))) !== 'servicio') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

$q = trim((string)($_GET['q'] ?? ''));
$estado = strtolower(trim((string)($_GET['estado'] ?? '')));
$estado = in_array($estado, ['pendiente', 'proceso', 'finalizado', 'anulado'], true) ? $estado : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
if ($perPage <= 0) {
    $perPage = 10;
}
if ($perPage > 50) {
    $perPage = 50;
}

$fromSql = "FROM cotizaciones c
        INNER JOIN clientes cl ON cl.id = c.id_cliente
    INNER JOIN resultados_examenes re ON re.id_cotizacion = c.id
        WHERE (
            c.servicio_id = ?
            OR (c.servicio_id IS NULL AND EXISTS (
                SELECT 1 FROM servicio_cliente sc2
                WHERE sc2.cliente_id = cl.id AND sc2.servicio_id = ?
            ))
        )
          AND (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')";

$listSelect = "SELECT
            c.id AS cotizacion_id,
            c.codigo AS codigo_cotizacion,
            c.fecha,
            cl.nombre,
            cl.apellido,
            cl.dni,
            c.profesional_solicitante_nombre,
            COUNT(re.id) AS total_examenes,
            SUM(CASE WHEN re.estado = 'finalizado' THEN 1 ELSE 0 END) AS examenes_finalizados";
$params = [$servicioId, $servicioId];

if ($q !== '') {
    $fromSql .= " AND (
        c.codigo LIKE ? OR
        cl.nombre LIKE ? OR
        cl.apellido LIKE ? OR
        cl.dni LIKE ? OR
        c.profesional_solicitante_nombre LIKE ?
    )";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($estado !== '') {
    $fromSql .= " AND re.estado = ?";
    $params[] = $estado;
}

$countSql = "SELECT COUNT(DISTINCT c.id) AS total_resultados " . $fromSql;

$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalFilas = (int)$stmtCount->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalFilas / $perPage));
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $perPage;

$sql = $listSelect . " " . $fromSql . "
        GROUP BY c.id, c.codigo, c.fecha, cl.nombre, cl.apellido, cl.dni, c.profesional_solicitante_nombre
        ORDER BY c.id DESC LIMIT {$offset}, {$perPage}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pre-cargar detalle de exámenes por cotización (evita N+1)
$examenesPorCotizacion = [];
$ids = [];
if (!empty($filas)) {
    $ids = array_map('intval', array_column($filas, 'cotizacion_id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtExam = $pdo->prepare(
        "SELECT re.id AS resultado_id, re.id_cotizacion, e.nombre AS nombre_examen, re.resultados, re.estado
         FROM resultados_examenes re
         JOIN examenes e ON e.id = re.id_examen
         WHERE re.id_cotizacion IN ($placeholders)
         ORDER BY re.id ASC"
    );
    $stmtExam->execute($ids);
    foreach ($stmtExam->fetchAll(PDO::FETCH_ASSOC) as $ex) {
        $examenesPorCotizacion[(int)$ex['id_cotizacion']][] = $ex;
    }
}

// Pre-computar porcentaje real de llenado por examen por lote (evita N+1 por cotizacion)
$porcentajesPorCotizacion = !empty($ids) ? obtenerPorcentajesPorExamenLote($pdo, $ids) : [];
$porcentajesPorResultadoId = [];
foreach ($porcentajesPorCotizacion as $cid => $map) {
    foreach ($map as $reId => $pct) {
        $porcentajesPorResultadoId[$reId] = $pct;
    }
}
?>
<div class="container mt-4">
    <h4 class="mb-3">Resultados del Servicio</h4>

    <form method="get" action="dashboard.php" class="row g-2 mb-3">
        <input type="hidden" name="vista" value="servicio_resultados">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Buscar por cotizacion, paciente o DNI" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="proceso" <?= $estado === 'proceso' ? 'selected' : '' ?>>Proceso</option>
                <option value="finalizado" <?= $estado === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                <option value="anulado" <?= $estado === 'anulado' ? 'selected' : '' ?>>Anulado</option>
            </select>
        </div>
        <div class="col-md-1">
            <select class="form-select" name="per_page" onchange="this.form.submit()" title="Registros por página">
                <?php foreach ([5, 10, 20, 50] as $pp): ?>
                    <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <div class="col-md-2 d-grid">
            <a href="dashboard.php?vista=servicio_resultados" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <?php if (!$filas): ?>
        <div class="alert alert-warning">No hay resultados disponibles para los pacientes asociados a este servicio.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Cotizacion</th>
                        <th>Paciente</th>
                        <th>Profesional</th>
                        <th>DNI</th>
                        <th>Fecha</th>
                        <th>Examenes</th>
                        <th>Resultados</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $row): ?>
                        <?php
                        $cotizacionId = (int)$row['cotizacion_id'];
                        $examenesDetalle = $examenesPorCotizacion[$cotizacionId] ?? [];
                        $totalExamenes = count($examenesDetalle);
                        $examenesCompletos = 0;
                        $imprimibles = 0;
                        $imprimiblesCompletos = 0;
                        foreach ($examenesDetalle as $ex) {
                            $resJsonTmp = $ex['resultados'] ? json_decode($ex['resultados'], true) : [];
                            $marcadoTmp = !isset($resJsonTmp['imprimir_examen']) || intval($resJsonTmp['imprimir_examen']) === 1;
                            $pctExamen = $porcentajesPorResultadoId[(int)($ex['resultado_id'] ?? 0)] ?? 0;
                            if ($pctExamen === 100) {
                                $examenesCompletos++;
                            }
                            if ($marcadoTmp) {
                                $imprimibles++;
                                if ($pctExamen === 100) {
                                    $imprimiblesCompletos++;
                                }
                            }
                        }

                        $porcentajeImprimible = $imprimibles > 0
                            ? (int)round(($imprimiblesCompletos / $imprimibles) * 100)
                            : 0;

                        $porcentajeResumen = $totalExamenes > 0
                            ? (int)round(($examenesCompletos / $totalExamenes) * 100)
                            : 0;

                        if ($examenesCompletos > 0 && $examenesCompletos === $totalExamenes) {
                            $resultBadge = '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Completado 100%</span>';
                            $descargarDisabled = 'target="_blank"';
                        } elseif ($examenesCompletos > 0) {
                            $resultBadge = '<span class="badge bg-warning text-dark">Parcial: ' . $porcentajeResumen . '%</span>';
                            $descargarDisabled = ($porcentajeImprimible < 100)
                                ? 'disabled style="pointer-events: none; opacity: 0.6;"'
                                : 'target="_blank"';
                        } else {
                            $resultBadge = '<span class="badge bg-danger">Pendiente: 0%</span>';
                            $descargarDisabled = 'disabled style="pointer-events: none; opacity: 0.6;"';
                        }

                        $collapseId = 'det-' . $cotizacionId;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($row['codigo_cotizacion'] ?: ('COT-' . $row['cotizacion_id']))) ?></td>
                            <td><?= htmlspecialchars(trim((string)$row['nombre'] . ' ' . (string)$row['apellido'])) ?></td>
                            <td><?= htmlspecialchars(trim((string)($row['profesional_solicitante_nombre'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars((string)$row['dni']) ?></td>
                            <td><?= htmlspecialchars((string)$row['fecha']) ?></td>
                            <td><?= (int)$row['total_examenes'] ?></td>
                            <td><?= $resultBadge ?></td>
                            <td class="d-flex flex-wrap gap-1">
                                <?php if (!empty($examenesDetalle)): ?>
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#<?= $collapseId ?>"
                                        aria-expanded="false">
                                    <i class="bi bi-list-ul"></i> Detalle
                                </button>
                                <?php endif; ?>
                                <a class="btn btn-success btn-sm" <?= $descargarDisabled ?> href="dashboard.php?action=descarga-pdf&cotizacion_id=<?= $cotizacionId ?>">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php if (!empty($examenesDetalle)): ?>
                        <tr class="collapse" id="<?= $collapseId ?>">
                            <td colspan="8" class="p-0">
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <small class="text-muted fw-semibold">Exámenes de esta cotización:</small>
                                    <ul class="list-unstyled mb-0 mt-1">
                                        <?php foreach ($examenesDetalle as $ex): ?>
                                            <?php
                                            $resJson = $ex['resultados'] ? json_decode($ex['resultados'], true) : [];
                                            $marcado = !isset($resJson['imprimir_examen']) || intval($resJson['imprimir_examen']) === 1;
                                            $pctExamen = $porcentajesPorResultadoId[(int)$ex['resultado_id']] ?? 0;
                                            if ($pctExamen === 100) {
                                                $exBadge = '<span class="badge bg-success">Completado</span>';
                                            } elseif ($pctExamen > 0) {
                                                $exBadge = '<span class="badge bg-warning text-dark">Parcial ' . $pctExamen . '%</span>';
                                            } else {
                                                $exBadge = '<span class="badge bg-danger">Pendiente</span>';
                                            }
                                            $imprimirIcon = $marcado
                                                ? '<i class="bi bi-printer-fill text-success" title="Marcado para imprimir"></i>'
                                                : '<i class="bi bi-printer text-muted" title="No marcado para imprimir"></i>';
                                            ?>
                                            <li class="py-1 border-bottom d-flex align-items-center gap-2">
                                                <?= $imprimirIcon ?>
                                                <span class="<?= $marcado ? '' : 'text-muted' ?>">
                                                    <?= htmlspecialchars((string)$ex['nombre_examen']) ?>
                                                </span>
                                                <?= $exBadge ?>
                                                <?php if (!$marcado): ?>
                                                    <small class="text-muted fst-italic">— excluido del PDF</small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalFilas > 0): ?>
            <nav class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="text-muted small">
                    Mostrando <?= count($filas) ?> de <?= (int)$totalFilas ?> resultados · Página <?= (int)$page ?> de <?= (int)$totalPaginas ?>
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $buildPageUrl = function (int $targetPage) use ($q, $estado, $perPage): string {
                        return 'dashboard.php?' . http_build_query([
                            'vista' => 'servicio_resultados',
                            'q' => $q,
                            'estado' => $estado,
                            'per_page' => $perPage,
                            'page' => $targetPage,
                        ]);
                    };
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                    </li>
                    <?php
                    $inicio = max(1, $page - 2);
                    $fin = min($totalPaginas, $page + 2);
                    if ($inicio > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl(1), ENT_QUOTES, 'UTF-8') . '">1</a></li>';
                        if ($inicio > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    for ($p = $inicio; $p <= $fin; $p++) {
                        $active = $p === $page ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($p), ENT_QUOTES, 'UTF-8') . '">' . $p . '</a></li>';
                    }
                    if ($fin < $totalPaginas) {
                        if ($fin < $totalPaginas - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($totalPaginas), ENT_QUOTES, 'UTF-8') . '">' . $totalPaginas . '</a></li>';
                    }
                    ?>
                    <li class="page-item <?= $page >= $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page >= $totalPaginas ? '#' : htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
