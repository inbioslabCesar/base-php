<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Fecha de hoy
$hoy = date('Y-m-d');

// Ingresos de hoy
$stmt = $pdo->prepare("SELECT SUM(monto) AS ingresos_hoy FROM pagos WHERE DATE(fecha) = ?");
$stmt->execute([$hoy]);
$ingresosHoy = floatval($stmt->fetchColumn());

// Egresos de hoy (asegúrate de tener la tabla egresos creada)
$stmt = $pdo->prepare("SELECT SUM(monto) AS egresos_hoy FROM egresos WHERE DATE(fecha) = ?");
$stmt->execute([$hoy]);
$egresosHoy = floatval($stmt->fetchColumn());

// Ganancia del día
$gananciaHoy = $ingresosHoy - $egresosHoy;

// Total de deuda de clientes (saldo pendiente sumado de todas las cotizaciones)
$stmt = $pdo->query("
    SELECT SUM(c.total - IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_cotizacion = c.id), 0)) AS deuda_total
    FROM cotizaciones c
    WHERE (c.total - IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_cotizacion = c.id), 0)) > 0
");
$deudaTotal = floatval($stmt->fetchColumn());
?>
<div class="container mt-4">
    <h3 class="mb-4">Panel de Contabilidad</h3>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header"><i class="bi bi-wallet2"></i> Ingresos de Hoy</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($ingresosHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header"><i class="bi bi-cash"></i> Egresos de Hoy</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($egresosHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header"><i class="bi bi-graph-up"></i> Ganancia del Día</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($gananciaHoy, 2) ?></h4>
                    <p class="card-text"><?= date('d/m/Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-dark bg-warning mb-3">
                <div class="card-header"><i class="bi bi-person-x"></i> Deuda de Clientes</div>
                <div class="card-body">
                    <h4 class="card-title">S/ <?= number_format($deudaTotal, 2) ?></h4>
                    <p class="card-text">Saldo pendiente por cobrar</p>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <a href="dashboard.php?vista=ingresos" class="btn btn-outline-success me-2">
            <i class="bi bi-list-ol"></i> Ver todos los ingresos
        </a>
        <a href="dashboard.php?vista=egresos" class="btn btn-outline-danger">
            <i class="bi bi-cash"></i> Registrar egresos
        </a>
    </div>
</div>
