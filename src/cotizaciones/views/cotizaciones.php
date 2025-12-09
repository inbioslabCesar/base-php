<?php
// Helpers para badges de estado
require_once __DIR__ . '/../components/cotizaciones_badges.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

// Botón según rol
require_once __DIR__ . '/../components/cotizaciones_boton.php';

// Filtros recibidos por GET
$dniFiltro      = trim($_GET['dni'] ?? '');
$empresaFiltro  = trim($_GET['empresa'] ?? '');
$convenioFiltro = trim($_GET['convenio'] ?? '');



// Consultas para cotizaciones
require_once __DIR__ . '/../api/cotizaciones_consultas.php';

// Paginación para cards móviles
//require_once __DIR__ . '/cotizaciones_paginacion.php';


?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>cotizaciones/cotizaciones.css">

<div class="container mt-4">
    <!-- BLOQUE COMPONENTE: cotizaciones_header.php -->
    <?php require_once __DIR__ . '/../components/cotizaciones_header.php'; ?>

    <!-- Filtros mejorados -->


    <!-- Tabla de cotizaciones (única para desktop y móvil) -->
    <?php require_once __DIR__ . '/../components/cotizaciones_tabla.php'; ?>

</div>
<!-- DataTables y dependencias -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
