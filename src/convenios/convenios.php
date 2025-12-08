<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function capitalizar($texto) {
    return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
}

$stmt = $pdo->query("SELECT * FROM convenios ORDER BY id DESC");
$convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Convenios</h2>
    <?php if (!empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['mensaje']) ?></div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <a href="dashboard.php?vista=form_convenio" class="btn btn-success mb-3">Registrar Convenio</a>
    <div class="table-responsive">
        <table id="tabla-convenios" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Especialidad</th>
                    <th>Descuento (%)</th>
                    <th>Descripción</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables y Bootstrap JS (ajusta rutas/CDN según tu proyecto) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabla-convenios').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=convenios_api',
            type: 'GET'
        },
        pageLength: 3,
        lengthMenu: [[3, 5, 10], [3, 5, 10]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'dni' },
            { data: 'especialidad', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'descuento', render: function(data) { return data ? data + ' %' : '0 %'; } },
            { data: 'descripcion', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'email' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<a href='dashboard.php?vista=form_convenio&id=${row.id}' class='btn btn-primary btn-sm'>Editar</a>
                            <a href='dashboard.php?action=eliminar_convenio&id=${row.id}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este convenio?\");'>Eliminar</a>`;
                }
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: 'Exportar Excel', className: 'btn btn-success' },
            { extend: 'pdf', text: 'Exportar PDF', className: 'btn btn-danger' },
            { extend: 'print', text: 'Imprimir', className: 'btn btn-secondary' }
        ]
    });
});
</script>
