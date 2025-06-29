$(document).ready(function() {
    $('#tablaResultados').DataTable({
        "ajax": "/base-php/src/resultados/api_listado.php",
        "columns": [
            { "data": "id" },
            { "data": "examen" },
            { "data": "cliente" },
            { "data": "estado" },
            { "data": "fecha_ingreso" },
            { "data": "acciones" }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        }
    });
});
