<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';

// Datos del cliente
$nombre_cliente = $_SESSION['usuario'] ?? 'Cliente';
$id_cliente = $_SESSION['cliente_id'] ?? 0;

// Consulta de promociones mejorada: incluye descuento y vigencia
$stmt = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (tipo_publico = 'clientes' OR tipo_publico = 'todos') AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin OR vigente = 1) ORDER BY fecha_inicio DESC");

$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta de cotizaciones y pagos/exámenes
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.id_cliente = ?
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

<div class="container mt-4">

    <!-- Carrusel de promociones mejorado -->
    <?php if ($promociones): ?>
        <div id="promoCarousel" class="carousel slide mb-4 shadow-sm rounded" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($promociones as $i => $promo): ?>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($promociones as $i => $promo): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="row justify-content-center align-items-center bg-light rounded p-3">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <?php if (!empty($promo['imagen']) && file_exists(__DIR__ . "/../../promociones/assets/" . $promo['imagen'])): ?>
                                    <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="d-block w-100 rounded" alt="Promo">
                                <?php else: ?>
                                    <div class="bg-secondary text-white text-center py-5 rounded">Sin imagen</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7 d-flex flex-column justify-content-center">
                                <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h4 class="card-title text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                        <?php
                                        $descripcion_corta = mb_strimwidth($promo['descripcion'], 0, 200, '...');
                                        ?>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($descripcion_corta)) ?></p>
                                        <?php if ($promo['precio_promocional'] > 0): ?>
                                            <span class="badge bg-warning text-dark fs-5 mb-2">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                        <?php endif; ?>
                                        <?php if ($promo['descuento'] > 0): ?>
                                            <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                                        <?php endif; ?>
                                        <div class="text-muted mb-1">
                                            <i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                                            <?php if ($promo['vigente']): ?>
                                                <span class="badge bg-primary ms-2">¡Promoción vigente!</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-2">No vigente</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="dashboard.php?vista=detalle_promocion&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">
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
                        <h6 class="card-title mb-1">Cotizaciones Canceladas</h6>
                        <p class="mb-0"><?= $canceladas ?> pagadas</p>
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
</div>

<!-- Bootstrap JS para el carrusel -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
