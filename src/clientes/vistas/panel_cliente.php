<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';

// Datos del cliente
$nombreSesion = $_SESSION['usuario'] ?? 'Cliente';
$nombre_cliente = is_array($nombreSesion)
    ? trim((string)($nombreSesion['nombre'] ?? 'Cliente'))
    : trim((string)$nombreSesion);
if ($nombre_cliente === '') {
    $nombre_cliente = 'Cliente';
}
$id_cliente = $_SESSION['cliente_id'] ?? 0;

// Consulta de promociones mejorada: incluye descuento y vigencia
$stmt = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (tipo_publico = 'clientes' OR tipo_publico = 'todos') AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin OR vigente = 1) ORDER BY fecha_inicio DESC");

$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta de cotizaciones y pagos/exámenes
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente
    FROM cotizaciones c
    JOIN clientes cl ON c.id_cliente = cl.id
    WHERE c.id_cliente = ?
      AND (c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
    ORDER BY c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagos por cotización
$pagosPorCotizacion = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        $sqlPagos = "SELECT id_cotizacion, SUM(monto) AS total_pagado
                     FROM pagos
                     WHERE id_cotizacion IN ($inQuery)
                     GROUP BY id_cotizacion";
        $stmtPagos = $pdo->prepare($sqlPagos);
        $stmtPagos->execute($idsCotizaciones);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pagos as $pago) {
            $pagosPorCotizacion[$pago['id_cotizacion']] = $pago['total_pagado'];
        }
    }
}

// Exámenes por cotización
$examenesPorCotizacion = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        $sqlExamenes = "SELECT re.id AS id_resultado, re.id_cotizacion, re.id_examen, re.estado, e.nombre AS nombre_examen
                        FROM resultados_examenes re
                        JOIN examenes e ON re.id_examen = e.id
                        WHERE re.id_cotizacion IN ($inQuery)";
        $stmtEx = $pdo->prepare($sqlExamenes);
        $stmtEx->execute($idsCotizaciones);
        $examenes = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
        foreach ($examenes as $ex) {
            $examenesPorCotizacion[$ex['id_cotizacion']][] = $ex;
        }
    }
}

// Resumen de cotizaciones
$pendientes = 0;
$total_deuda = 0;
$canceladas = 0;
foreach ($cotizaciones as $cotizacion) {
    $cotizacionId = $cotizacion['id'];
    $total = floatval($cotizacion['total']);
    $pagado = floatval($pagosPorCotizacion[$cotizacionId] ?? 0);
    $saldo = $total - $pagado;
    if ($saldo > 0) {
        $pendientes++;
        $total_deuda += $saldo;
    } else {
        $canceladas++;
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
.panel-shell {
    max-width: 1120px;
}

.panel-promo-carousel {
    background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid #dce8f8;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 12px 26px rgba(29, 63, 110, 0.10);
}

.panel-promo-slide {
    background: #ffffff;
    border: 1px solid #e5eef8;
    border-radius: 14px;
    padding: 14px;
}

.panel-promo-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #dfe8f6;
}

.panel-promo-content {
    background: #f3f8ff;
    border: 1px solid #dce9fc;
    border-radius: 12px;
    padding: 14px;
}

.panel-promo-content .desc {
    color: #28435d;
    line-height: 1.35;
    margin-bottom: 0.65rem;
}

.panel-promo-carousel .carousel-control-prev,
.panel-promo-carousel .carousel-control-next {
    width: 42px;
    height: 42px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 50%;
    background: rgba(33, 84, 145, 0.12);
}

.panel-promo-carousel .carousel-control-prev { left: -10px; }
.panel-promo-carousel .carousel-control-next { right: -10px; }

@media (max-width: 767.98px) {
    .panel-promo-image {
        height: 170px;
    }
}
</style>

<div class="container mt-4 panel-shell">

    <!-- Carrusel de promociones mejorado -->
    <?php if ($promociones): ?>
        <div id="promoCarousel" class="carousel slide mb-4 panel-promo-carousel" data-bs-ride="carousel" data-bs-interval="5600">
            <div class="carousel-indicators">
                <?php foreach ($promociones as $i => $promo): ?>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($promociones as $i => $promo): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="panel-promo-slide">
                        <div class="row justify-content-center align-items-center g-3">
                            <div class="col-12 col-lg-5">
                                <?php if (!empty($promo['imagen']) && file_exists(__DIR__ . "/../../promociones/assets/" . $promo['imagen'])): ?>
                                    <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="panel-promo-image" alt="Promo">
                                <?php else: ?>
                                    <div class="panel-promo-image d-flex align-items-center justify-content-center bg-secondary text-white">Sin imagen</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-lg-7 d-flex flex-column justify-content-center">
                                <div class="panel-promo-content">
                                    <h4 class="card-title text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                    <?php
                                    $descripcion_corta = mb_strimwidth($promo['descripcion'], 0, 180, '...');
                                    ?>
                                    <p class="desc"><?= nl2br(htmlspecialchars($descripcion_corta)) ?></p>
                                    <?php if ($promo['precio_promocional'] > 0): ?>
                                        <span class="badge bg-warning text-dark fs-6">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                    <?php endif; ?>
                                    <?php if ($promo['descuento'] > 0): ?>
                                        <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                                    <?php endif; ?>
                                    <div class="text-muted mt-2 mb-2">
                                        <i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                                        <?php if ($promo['vigente']): ?>
                                            <span class="badge bg-primary ms-2">Promoción vigente</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">No vigente</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="dashboard.php?vista=detalle_promocion&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Cards de resumen -->
    <div class="row mb-4">
        <div class="col-md-6 mb-2">
            <div class="card border-warning bg-warning bg-opacity-10 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-hourglass-split display-5 text-warning me-3"></i>
                    <div>
                        <h6 class="card-title mb-1">Cotizaciones Pendientes</h6>
                        <p class="mb-0"><?= $pendientes ?> en proceso</p>
                        <p class="mb-0"><strong>Total a pagar:</strong> S/ <?= number_format($total_deuda, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($canceladas > 0): ?>
        <div class="col-md-6 mb-2">
            <div class="card border-success bg-success bg-opacity-10 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-check-circle display-5 text-success me-3"></i>
                    <div>
                        <h6 class="card-title mb-1">Cotizaciones Pagadas</h6>
                        <p class="mb-0"><?= $canceladas ?> completadas</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bienvenida y botón rápido -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div>
                <h3 class="mb-2">¡Bienvenido, <?= htmlspecialchars($nombre_cliente) ?>!</h3>
                <p class="mb-0">Desde aquí puedes gestionar tus cotizaciones y acceder a promociones exclusivas.</p>
            </div>
            <a href="dashboard.php?vista=cotizaciones_clientes" class="btn btn-primary btn-lg mt-3 mt-md-0">
                <i class="bi bi-file-earmark-text"></i> Mis Cotizaciones
            </a>
        </div>
    </div>

    <?php
    $cotizadorContextType = 'cliente';
    $cotizadorForzarPrecioPublico = true;
    require __DIR__ . '/../../gestion/components/cotizador_rapido_panel.php';
    ?>
</div>

<!-- Bootstrap JS para el carrusel -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
