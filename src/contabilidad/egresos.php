<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Filtros por fecha (por defecto mes actual)
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// Registrar egreso si se envió el formulario
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = floatval($_POST['monto'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    if ($monto > 0 && $descripcion !== '') {
        $stmt = $pdo->prepare("INSERT INTO egresos (monto, descripcion, fecha) VALUES (?, ?, ?)");
        $stmt->execute([$monto, $descripcion, $fecha]);
        $msg = "Egreso registrado correctamente.";
    } else {
        $msg = "Completa todos los campos correctamente.";
    }
}

// Consultar egresos filtrados
$stmt = $pdo->prepare("SELECT id, monto, descripcion, fecha FROM egresos WHERE DATE(fecha) BETWEEN ? AND ? ORDER BY fecha DESC");
$stmt->execute([$desde, $hasta]);
$egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
    <h3 class="mb-4">Registro y Listado de Egresos</h3>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="vista" value="egresos">
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
            <a href="dashboard.php?vista=egresos" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>

    <form method="post" class="card p-3 mb-4 shadow-sm">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Monto (S/)</label>
                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Descripción</label>
                <input type="text" name="descripcion" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-danger">Registrar</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table id="tablaEgresos" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <th>Monto</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </thead>
            <tbody>
                <?php if ($egresos): ?>
                    <?php foreach ($egresos as $egreso): ?>
                        <tr>
                            <td>S/ <?= number_format($egreso['monto'], 2) ?></td>
                            <td><?= htmlspecialchars($egreso['descripcion']) ?></td>
                            <td><?= date('d/m/Y', strtotime($egreso['fecha'])) ?></td>
                            <td>
                                <a href="dashboard.php?vista=egresos_editar&id=<?= $egreso['id'] ?>" class="btn btn-primary btn-sm mb-1" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="dashboard.php?action=egresos_eliminar&id=<?= $egreso['id'] ?>"
                                    class="btn btn-danger btn-sm mb-1"
                                    title="Eliminar"
                                    onclick="return confirm('¿Seguro que deseas eliminar este egreso?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No hay egresos registrados en el periodo.</td>
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
        $('#tablaEgresos').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
                "pageLength": 5,
                "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
            dom: 'Bfrtip',
            buttons: [{
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