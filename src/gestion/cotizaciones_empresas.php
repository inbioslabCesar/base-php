<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Control de acceso seguro para empresa
$id_empresa = $_SESSION['empresa_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;
if (!$id_empresa || strtolower(trim($rol)) !== 'empresa') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

// Filtro por fecha
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$whereFecha = '';
$params = [$id_empresa];

if ($fechaInicio && $fechaFin) {
    $whereFecha = " AND DATE(c.fecha) BETWEEN ? AND ? ";
    $params[] = $fechaInicio;
    $params[] = $fechaFin;
}

// Consulta cotizaciones de la empresa logueada
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni 
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.id_empresa = ? $whereFecha
        ORDER BY c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ... (resto de consultas igual que antes)
$totalEmpresa = 0.0;
$totalPagadoEmpresa = 0.0;
$saldoEmpresa = 0.0;

if (!empty($cotizaciones)) {
    foreach ($cotizaciones as $cot) {
        $totalEmpresa += floatval($cot['total'] ?? 0);
        $totalPagadoEmpresa += floatval($pagosPorCotizacion[$cot['id']] ?? 0);
    }
    $saldoEmpresa = $totalEmpresa - $totalPagadoEmpresa;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container mt-4">
    <h4 class="mb-3">Cotizaciones de la Empresa</h4>
    <form class="row g-3 mb-3" method="get">
        <input type="hidden" name="vista" value="cotizaciones_empresas">
        <div class="col-auto">
            <label for="fecha_inicio" class="form-label">Desde:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fechaInicio) ?>">
        </div>
        <div class="col-auto">
            <label for="fecha_fin" class="form-label">Hasta:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fechaFin) ?>">
        </div>
        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="dashboard.php?vista=cotizaciones_empresas" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
    <div class="alert alert-secondary mb-3">
    <strong>Total cotizado:</strong> S/ <?= number_format($totalEmpresa, 2) ?>  
    <strong>Total pagado:</strong> S/ <?= number_format($totalPagadoEmpresa, 2) ?>  
    <strong>Saldo pendiente:</strong>
    <span class="badge <?= $saldoEmpresa > 0 ? 'bg-danger' : 'bg-success' ?>">
        S/ <?= number_format($saldoEmpresa, 2) ?>
    </span>
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

    if ((int)$porcentaje === 100) {
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
        <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacionId ?>"
            class="btn btn-success btn-sm mb-1"
            title="Descargar PDF de todos los resultados"
            <?= $descargarDisabled ?>
            target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
        </a>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="8" class="text-center">No hay cotizaciones registradas para la empresa.</td>
</tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- DataTables y extensiones para exportar -->
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
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Imprimir',
                className: 'btn btn-primary btn-sm'
            }
        ]
    });
});
</script>
