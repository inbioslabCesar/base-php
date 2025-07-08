<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
$id_cliente = $_SESSION['cliente_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;

// Control de acceso seguro
if (!$id_cliente || strtolower(trim($rol)) !== 'cliente') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

// Consulta solo las cotizaciones del cliente logueado
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni 
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.id_cliente = ?
        ORDER BY c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-4">
    <h4 class="mb-3">Mis Cotizaciones</h4>
    <div class="alert alert-info">
        Puedes descargar tus resultados solo si no tienes deuda pendiente y todos los exámenes están completos.<br>
        El porcentaje de resultados indica el avance de tus exámenes.
    </div>
    <div class="table-responsive">
        <table id="tablaCotizaciones" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre y Apellido</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Abonado</th>
                    <th>Pendiente</th>
                    <th>Resultados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cotizaciones): ?>
                    <?php foreach ($cotizaciones as $cotizacion): ?>
                        <?php
                        $cotizacionId = $cotizacion['id'];
                        $total = floatval($cotizacion['total']);
                        $pagado = floatval($pagosPorCotizacion[$cotizacionId] ?? 0);
                        $saldo = $total - $pagado;

                        $badgePendiente = $saldo > 0
                            ? '<span class="badge bg-danger">S/ ' . number_format($saldo, 2) . '</span>'
                            : '<span class="badge bg-success">S/ 0.00</span>';

                        $badgeAbonado = $pagado > 0
                            ? '<span class="badge bg-primary">S/ ' . number_format($pagado, 2) . '</span>'
                            : '<span class="badge bg-secondary">S/ 0.00</span>';

                        $examenes = $examenesPorCotizacion[$cotizacionId] ?? [];
                        $totalExamenes = count($examenes);
                        $completados = count(array_filter($examenes, fn($ex) => $ex['estado'] !== 'pendiente'));
                        $porcentaje = $totalExamenes ? round(($completados / $totalExamenes) * 100) : 0;
                        if ($porcentaje === 100) {
                            $resultBadge = '<span class="badge bg-success">Completado: 100%</span>';
                        } elseif ($porcentaje > 0) {
                            $resultBadge = '<span class="badge bg-warning text-dark">Parcial: ' . $porcentaje . '%</span>';
                        } else {
                            $resultBadge = '<span class="badge bg-danger">Pendiente: 0%</span>';
                        }

                        $descargarDisabled = ($saldo > 0 || $porcentaje < 100) ? 'disabled style="pointer-events: none; opacity: 0.6;"' : 'target="_blank"';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                            <td><span class="badge bg-info">S/ <?= number_format($total, 2) ?></span></td>
                            <td><?= $badgeAbonado ?></td>
                            <td><?= $badgePendiente ?></td>
                            <td><?= $resultBadge ?></td>
                            <td>
                                <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacionId ?>"
                                    class="btn btn-info btn-sm mb-1"
                                    title="Ver cotización">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="resultados/descarga-pdf.html?cotizacion_id=<?= $cotizacionId ?>"
                                    class="btn btn-success btn-sm mb-1"
                                    title="Descargar PDF de todos los resultados"
                                    <?= $descargarDisabled ?>>
                                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No tienes cotizaciones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables y Bootstrap JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaCotizaciones').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>