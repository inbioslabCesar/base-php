<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/servicios/ExamenesService.php';
require_once __DIR__ . '/servicios/CotizacionService.php';
require_once __DIR__ . '/vistas/ExamCardView.php';
require_once __DIR__ . '/vistas/PdfConfigView.php';
require_once __DIR__ . '/vistas/AlertView.php';
require_once __DIR__ . '/vistas/FormView.php';

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
// Inicializar variables

$examenes = [];
$referencia_personalizada = '';
$datos_paciente = [];

if ($cotizacion_id) {
    $examenesService = new ExamenesService($pdo);
    $cotizacionService = new CotizacionService($pdo);
    $examenes = $examenesService->obtenerExamenesPorCotizacion($cotizacion_id);
    $referencia_personalizada = $cotizacionService->obtenerReferenciaPersonalizada($cotizacion_id);
    $datos_paciente = $cotizacionService->obtenerDatosPaciente($cotizacion_id);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Exámenes - InbiosLab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>resultados/recursos/formulario.css">
</head>
<body>
<div class="header-container">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="dashboard.php?vista=cotizaciones" class="back-btn">
                <i class="bi bi-arrow-left"></i>
                Volver a Cotizaciones
            </a>
            <h1 class="header-title">
                <i class="bi bi-clipboard-data me-3"></i>
                Resultados de Exámenes
            </h1>
        </div>
    </div>
</div>
<div class="container mb-5">
    <?php
    if (!empty($examenes)) {
        echo FormView::render($examenes, $cotizacion_id, $referencia_personalizada, $datos_paciente);
    } else {
        echo AlertView::render('No hay exámenes asociados');
    }
    ?>
</div>
</script>
<?php
$v_formulario_js = @filemtime(__DIR__ . '/recursos/formulario.js') ?: time();
$v_validacion_js = @filemtime(__DIR__ . '/recursos/validacion-realtime.js') ?: time();
?>
<script src="<?= BASE_URL ?>resultados/recursos/formulario.js?v=<?= $v_formulario_js ?>"></script>
<script src="<?= BASE_URL ?>resultados/recursos/validacion-realtime.js?v=<?= $v_validacion_js ?>"></script>
</body>
</html>