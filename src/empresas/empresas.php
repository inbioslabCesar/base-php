<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener todas las empresas
$stmt = $pdo->query("SELECT * FROM empresas");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<!-- Incluye CSS de Bootstrap y DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container mt-4">
    <h2>Lista de Empresas</h2>
    <a href="dashboard.php?vista=form_empresa" class="btn btn-primary mb-3">Agregar Empresa</a>
    <div class="table-responsive">
        <table id="tabla-empresas" class="table table-bordered table-striped">
           <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>RUC</th>
        <th>Razón Social</th>
        <th>Nombre Comercial</th>
        <th>Dirección</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Representante</th>
        <th>Convenio</th>
        <th>Estado</th>
        <th>Descuento (%)</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody>
<?php if ($empresas): ?>
    <?php foreach ($empresas as $empresa): ?>
        <tr>
            <td><?= htmlspecialchars($empresa['id'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['ruc'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(capitalizar($empresa['razon_social'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(capitalizar($empresa['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(capitalizar($empresa['representante'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['convenio'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(capitalizar($empresa['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($empresa['descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <a href="dashboard.php?vista=form_empresa&id=<?= $empresa['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                <a href="dashboard.php?action=eliminar_empresa&id=<?= $empresa['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta empresa?');">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="12">No hay empresas registradas.</td>
    </tr>
<?php endif; ?>
</tbody>
        </table>
    </div>
</div>

<!-- Incluye JS de Bootstrap y DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
    $('#tabla-empresas').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Exportar Excel',
                className: 'btn btn-success'
            },
            {
                extend: 'pdfHtml5',
                text: 'Exportar PDF',
                className: 'btn btn-danger'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-info'
            }
        ]
    });
});
</script>
