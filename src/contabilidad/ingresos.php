<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Filtros por fecha (por defecto mes actual)
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// Consulta de ingresos filtrados
$stmt = $pdo->prepare("
    SELECT p.id, p.monto, p.metodo_pago, p.fecha, c.codigo AS codigo_cotizacion, cl.nombre, cl.apellido
    FROM pagos p
    JOIN cotizaciones c ON p.id_cotizacion = c.id
    JOIN clientes cl ON c.id_cliente = cl.id
    WHERE DATE(p.fecha) BETWEEN ? AND ?
    ORDER BY p.fecha DESC
");
$stmt->execute([$desde, $hasta]);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
    <h3 class="mb-4">Listado de Ingresos</h3>
    <form method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="vista" value="ingresos">
        <div class="col-auto">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="dashboard.php?vista=ingresos" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
    <div class="table-responsive">
        <table id="tablaIngresos" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Monto</th>
                    <th>Método</th>
                    <th>Fecha</th>
                    <th>Código Cotización</th>
                    <th>Cliente</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pagos): ?>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td>S/ <?= number_format($pago['monto'], 2) ?></td>
                            <td><?= ucfirst($pago['metodo_pago']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($pago['fecha'])) ?></td>
                            <td><?= htmlspecialchars($pago['codigo_cotizacion']) ?></td>
                            <td><?= htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay ingresos registrados en el periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="dashboard.php?vista=contabilidad" class="btn btn-secondary mt-3">Volver a Contabilidad</a>
</div>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- Botones para exportar -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tablaIngresos').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Exportar a Excel',
                    className: 'btn btn-success mb-2'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="bi bi-file-earmark-pdf"></i> Exportar a PDF',
                    className: 'btn btn-danger mb-2',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 10;
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Imprimir',
                    className: 'btn btn-info mb-2'
                }
            ]
        });
    });
</script>
