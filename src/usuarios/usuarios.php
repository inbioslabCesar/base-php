<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener todos los usuarios
$stmt = $pdo->query("SELECT * FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<!-- Incluye CSS de Bootstrap y DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container mt-4">
    <h2>Lista de Usuarios</h2>
    <a href="dashboard.php?vista=form_usuario" class="btn btn-primary mb-3">Agregar Usuario</a>
    <div class="table-responsive">
        <table id="tabla-usuarios" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>DNI</th>
                    <th>Sexo</th>
                    <th>Fecha Nacimiento</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Cargo</th>
                    <th>Profesión</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
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
    $('#tabla-usuarios').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=usuarios_api',
            type: 'GET'
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columns: [
            { data: 'id' },
            { data: 'nombre', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'apellido', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'dni' },
            { data: 'sexo', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'fecha_nacimiento' },
            { data: 'email' },
            { data: 'telefono' },
            { data: 'direccion' },
            { data: 'cargo' },
            { data: 'profesion' },
            { data: 'rol', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'estado', render: function(data) {
                if (data === 'activo') {
                    return `<span class='badge bg-success'>Activo</span>`;
                } else {
                    return `<span class='badge bg-danger'>Inactivo</span>`;
                }
            } },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<a href='dashboard.php?vista=form_usuario&id=${row.id}' class='btn btn-warning btn-sm'>Editar</a>
                            <a href='dashboard.php?action=eliminar_usuario&id=${row.id}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\");'>Eliminar</a>`;
                }
            }
        ],
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
