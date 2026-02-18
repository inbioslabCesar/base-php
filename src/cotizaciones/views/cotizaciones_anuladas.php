<?php
require_once __DIR__ . '/../components/cotizaciones_badges.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

$dniFiltro      = trim($_GET['dni'] ?? '');
$empresaFiltro  = trim($_GET['empresa'] ?? '');
$convenioFiltro = trim($_GET['convenio'] ?? '');
$modoCotizaciones = 'anuladas';
$totalAnuladas = 0;

try {
    $stmtTotalAnuladas = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado_pago = 'anulada'");
    $totalAnuladas = (int)$stmtTotalAnuladas->fetchColumn();
} catch (\Throwable $e) {
    $totalAnuladas = 0;
}

$rolActualCotVista = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActualCotVista !== 'admin') {
    echo '<div class="alert alert-warning m-3">No tienes permiso para ver cotizaciones anuladas.</div>';
    return;
}

require_once __DIR__ . '/../api/cotizaciones_consultas.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>cotizaciones/styles/cotizaciones.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<div class="container-fluid mt-3 px-3">
    <div class="cotizaciones-header mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="mb-0">🗃️ Cotizaciones anuladas</h4>
                <span class="badge bg-danger">Total anuladas: <?= number_format($totalAnuladas) ?></span>
            </div>
            <a href="dashboard.php?vista=cotizaciones" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a cotizaciones
            </a>
        </div>
    </div>

    <?php require_once __DIR__ . '/../components/cotizaciones_anuladas_tabla.php'; ?>
</div>


