<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>ID de cotización no especificado.</div>";
    exit;
}

// Consulta principal: cotización + cliente
$stmt = $pdo->prepare("
    SELECT cotizaciones.*, clientes.nombre AS nombre_cliente, clientes.apellido AS apellido_cliente, clientes.dni
    FROM cotizaciones
    LEFT JOIN clientes ON cotizaciones.id_cliente = clientes.id
    WHERE cotizaciones.id = ?
");
$stmt->execute([$id]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    echo "<div class='alert alert-warning'>Cotización no encontrada.</div>";
    exit;
}

// Consulta de exámenes cotizados
$stmt = $pdo->prepare("
    SELECT cd.*, e.preanalitica_cliente, e.nombre AS nombre_examen
    FROM cotizaciones_detalle cd
    LEFT JOIN examenes e ON cd.id_examen = e.id
    WHERE cd.id_cotizacion = ?
");
$stmt->execute([$id]);
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-3">Detalle de Cotización</h2>
        </div>
    </div>
    <div class="row gy-3">
        <div class="col-md-6 col-12">
            <div class="card h-100">
                <div class="card-body">
                    <p><strong>Código:</strong> <?= htmlspecialchars($cotizacion['codigo']) ?></p>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cotizacion['nombre_cliente'] . ' ' . $cotizacion['apellido_cliente']) ?></p>
                    <p><strong>DNI:</strong> <?= htmlspecialchars($cotizacion['dni']) ?></p>
                    <p><strong>Fecha:</strong> <?= htmlspecialchars($cotizacion['fecha']) ?></p>
                    <?php
                    // Mostrar tipo de usuario y nombre de empresa/convenio si aplica
                    $tipo = $cotizacion['tipo_usuario'] ?? '';
                    $info = '';
                    if ($tipo === 'empresa' && !empty($cotizacion['id_empresa'])) {
                        $stmtEmp = $pdo->prepare("SELECT nombre_comercial, razon_social FROM empresas WHERE id = ?");
                        $stmtEmp->execute([$cotizacion['id_empresa']]);
                        $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
                        $info = 'Empresa: ' . htmlspecialchars($emp['nombre_comercial'] ?? $emp['razon_social'] ?? '');
                    } elseif ($tipo === 'convenio' && !empty($cotizacion['id_convenio'])) {
                        $stmtConv = $pdo->prepare("SELECT nombre FROM convenios WHERE id = ?");
                        $stmtConv->execute([$cotizacion['id_convenio']]);
                        $conv = $stmtConv->fetch(PDO::FETCH_ASSOC);
                        $info = 'Convenio: ' . htmlspecialchars($conv['nombre'] ?? '');
                    } else {
                        $info = 'Particular';
                    }
                    ?>
                    <p><strong>Condición:</strong> <?= $info ?></p>
                    <p><strong>Total:</strong> S/. <?= number_format($cotizacion['total'], 2) ?></p>
                    <p><strong>Estado de pago:</strong> <?= htmlspecialchars(ucwords(strtolower($cotizacion['estado_pago']))) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12">
            <div class="card h-100">
                <div class="card-body">
                    <p><strong>Fecha de toma:</strong> <?= htmlspecialchars($cotizacion['fecha_toma'] ?? 'No asignada') ?></p>
                    <p><strong>Hora de toma:</strong> <?= htmlspecialchars($cotizacion['hora_toma'] ?? 'No asignada') ?></p>
                    <p><strong>Tipo de toma:</strong> <?= htmlspecialchars(ucwords(strtolower($cotizacion['tipo_toma'] ?? 'No asignado'))) ?></p>
                    <p><strong>Dirección de toma:</strong> <?= htmlspecialchars(ucwords(strtolower($cotizacion['direccion_toma'] ?? 'No asignada'))) ?></p>
                    <p><strong>Observaciones:</strong> <?= htmlspecialchars(ucwords(strtolower($cotizacion['observaciones'] ?? 'No asignadas'))) ?></p>
                    <p><strong>Rol creador:</strong> <?= htmlspecialchars(ucwords(strtolower($cotizacion['rol_creador'] ?? 'No asignado'))) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4>Exámenes Cotizados</h4>
            <style>
                .tabla-examenes-cotizados {
                    font-size: 0.92em;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-sm align-middle tabla-examenes-cotizados">
                    <thead>
                        <tr>
                            <th>Examen</th>
                            <th style="width: 200px;">Condición Cliente</th>
                            <th style="width: 200px;" class="text-end">P. Unit.</th>
                            <th style="width: 200px;" class="text-center">Cant.</th>
                            <th class="text-end">Subt.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($examenes): ?>
                            <?php foreach ($examenes as $examen): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($examen['nombre_examen']); ?></td>
                                    <td ><?php echo nl2br(htmlspecialchars($examen['preanalitica_cliente']??'')); ?></td>
                                    <td class="text-end">S/. <?= number_format($examen['precio_unitario'], 2) ?></td>
                                    <td style="width: 200px;" class="text-center"><?= $examen['cantidad'] ?></td>
                                    <td style="width: 200px;" class="text-end">S/. <?= number_format($examen['subtotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay exámenes cotizados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="cotizaciones/descargar_cotizacion.php?id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-2" target="_blank">
                <i class="bi bi-download"></i> Descargar PDF
            </a>
        </div>
    </div>
</div>