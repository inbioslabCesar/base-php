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
$msg = trim((string)($_GET['msg'] ?? ''));
$modoCotizaciones = 'activas';
$rolActualCotVista = strtolower(trim((string)($_SESSION['rol'] ?? '')));



// Consultas para cotizaciones
require_once __DIR__ . '/../api/cotizaciones_consultas.php';

// Paginación para cards móviles
//require_once __DIR__ . '/cotizaciones_paginacion.php';


?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>cotizaciones/styles/cotizaciones.css">

<div class="container-fluid mt-3 px-3">
    <?php if ($msg === 'anulado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Cotización anulada correctamente. Se aplicaron los reversos correspondientes.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($msg === 'ya_anulada'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            La cotización ya estaba anulada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($msg === 'sin_permiso'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            No tienes permisos para anular cotizaciones.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            No se pudo anular la cotización. Revisa logs y vuelve a intentar.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($msg === 'motivo_requerido'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Debes ingresar un motivo para anular la cotización.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- BLOQUE COMPONENTE: cotizaciones_header.php -->
    <?php require_once __DIR__ . '/../components/cotizaciones_header.php'; ?>

    <?php if ($rolActualCotVista === 'admin'): ?>
        <div class="mb-2 d-flex justify-content-end">
            <a href="dashboard.php?vista=cotizaciones_anuladas" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-archive"></i> Ver anuladas
            </a>
        </div>
    <?php endif; ?>

    <!-- Filtros mejorados -->


    <!-- Tabla de cotizaciones (única para desktop y móvil) -->
    <?php require_once __DIR__ . '/../components/cotizaciones_tabla.php'; ?>

</div>
<!-- DataTables y dependencias -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
