<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../funciones/cotizaciones_utils.php';
require_once __DIR__ . '/../../config/currency.php';
$id_cliente = $_SESSION['cliente_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;
$currencyCfg = currency_get_config($pdo);

// Control de acceso seguro
if (!$id_cliente || strtolower(trim($rol)) !== 'cliente') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}
// Consulta solo las cotizaciones válidas para la vista del cliente
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni 
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.id_cliente = ?
                    AND (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
          AND (
                c.rol_creador = 'cliente'
                OR (
                    c.rol_creador IN ('admin', 'recepcionista')
                    AND (c.id_empresa IS NULL OR c.id_empresa = 0)
                    AND (c.id_convenio IS NULL OR c.id_convenio = 0)
                )
            )
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
$porcentajePorCotizacion = [];
if ($cotizaciones) {
    foreach ($cotizaciones as $cotizacionTmp) {
        $cotizacionIdTmp = (int)($cotizacionTmp['id'] ?? 0);
        if ($cotizacionIdTmp > 0) {
            $porcentajePorCotizacion[$cotizacionIdTmp] = (int)obtenerPorcentajeResultadosCotizacion($pdo, $cotizacionIdTmp);
        }
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container mt-4">
    <h4 class="mb-3">Mis Cotizaciones</h4>
    <div class="d-flex justify-content-end mb-3">
        <a href="dashboard.php?vista=form_cotizacion" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Cotización
        </a>
    </div>
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
                            ? '<span class="badge bg-danger">' . htmlspecialchars(money_format_local($saldo, $currencyCfg)) . '</span>'
                            : '<span class="badge bg-success">' . htmlspecialchars(money_format_local(0, $currencyCfg)) . '</span>';
                        $badgeAbonado = $pagado > 0
                            ? '<span class="badge bg-primary">' . htmlspecialchars(money_format_local($pagado, $currencyCfg)) . '</span>'
                            : '<span class="badge bg-secondary">' . htmlspecialchars(money_format_local(0, $currencyCfg)) . '</span>';
                        $porcentaje = (int)($porcentajePorCotizacion[$cotizacionId] ?? 0);
                        if ((int)$porcentaje === 100) {
                            $resultBadge = '<span class="badge bg-success" title="Porcentaje de resultados: 100%">Completado: 100%</span>';
                        } elseif ($porcentaje > 0) {
                            $resultBadge = '<span class="badge bg-warning text-dark" title="Porcentaje de resultados: ' . $porcentaje . '%">Parcial: ' . $porcentaje . '%</span>';
                        } else {
                            $resultBadge = '<span class="badge bg-danger" title="Porcentaje de resultados: 0%">Pendiente: 0%</span>';
                        }
                        $descargarDisabled = ($saldo > 0 || $porcentaje < 100) ? 'disabled style="pointer-events: none; opacity: 0.6;"' : 'target="_blank"';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars(money_format_local($total, $currencyCfg)) ?></span></td>
                            <td><?= $badgeAbonado ?></td>
                            <td><?= $badgePendiente ?></td>
                            <td><?= $resultBadge ?></td>
                            <td>
                                <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacionId ?>"
                                    class="btn btn-info btn-sm mb-1"
                                    title="Ver cotización">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacionId ?>"
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
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaCotizaciones').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[2, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':not(:last-child)' }
                }
            ]
        });
    });
</script>
