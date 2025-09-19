<?php
// listado.php
?>
<div class="container">
    <h2>Gestión de Resultados de Exámenes</h2>
    <table id="tablaResultados" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Examen</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Fecha de ingreso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Aquí se cargarán los datos vía AJAX -->
        </tbody>
    </table>
</div>
<script src="/base-php/src/resultados/js/resultados.js"></script>
