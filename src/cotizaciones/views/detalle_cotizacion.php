<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../auth/empresa_config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>ID de cotización no especificado.</div>";
    exit;
}

// Consulta principal: cotización + cliente
$stmt = $pdo->prepare("
    SELECT cotizaciones.*, clientes.nombre AS nombre_cliente, clientes.apellido AS apellido_cliente, clientes.dni, clientes.codigo_cliente
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

$requiereCpe = ((int)($cotizacion['emitir_comprobante'] ?? 1) === 1);

// Consulta de exámenes cotizados
$stmt = $pdo->prepare("
    SELECT cd.*, e.preanalitica_cliente, e.nombre AS nombre_examen
    FROM cotizaciones_detalle cd
    LEFT JOIN examenes e ON cd.id_examen = e.id
    WHERE cd.id_cotizacion = ?
");
$stmt->execute([$id]);
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información adicional según tipo
$tipo = $cotizacion['tipo_usuario'] ?? '';
$info_tipo = '';
$badge_class = 'bg-secondary';

if ($tipo === 'empresa' && !empty($cotizacion['id_empresa'])) {
    $stmtEmp = $pdo->prepare("SELECT nombre_comercial, razon_social FROM empresas WHERE id = ?");
    $stmtEmp->execute([$cotizacion['id_empresa']]);
    $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    $info_tipo = $emp['nombre_comercial'] ?? $emp['razon_social'] ?? 'Empresa';
    $badge_class = 'bg-info';
} elseif ($tipo === 'convenio' && !empty($cotizacion['id_convenio'])) {
    $stmtConv = $pdo->prepare("SELECT nombre FROM convenios WHERE id = ?");
    $stmtConv->execute([$cotizacion['id_convenio']]);
    $conv = $stmtConv->fetch(PDO::FETCH_ASSOC);
    $info_tipo = $conv['nombre'] ?? 'Convenio';
    $badge_class = 'bg-warning text-dark';
} else {
    $info_tipo = 'Particular';
    $badge_class = 'bg-secondary';
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
/* Estilos para detalle de cotización */
.detalle-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.main-card-detalle {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
    margin-bottom: 30px;
}

.header-detalle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    position: relative;
}

.header-detalle::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.header-detalle h2 {
    margin: 0;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.codigo-cotizacion {
    font-size: 1.2rem;
    font-weight: 700;
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 25px;
    display: inline-block;
    margin-top: 10px;
    position: relative;
    z-index: 1;
}

.info-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.card-header-custom {
    background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    border: none;
}

.card-body-info {
    padding: 25px;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 1rem;
}

.info-content {
    flex: 1;
}

.info-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.info-value {
    color: #6c757d;
    font-size: 1rem;
}

.examenes-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.examenes-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 20px 25px;
    font-weight: 600;
}

.table-examenes {
    margin: 0;
    font-size: 0.95rem;
}

.table-examenes thead th {
    background: #f8f9fa;
    border: none;
    color: #495057;
    font-weight: 600;
    padding: 15px 12px;
}

.table-examenes tbody td {
    padding: 15px 12px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.table-examenes tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

.examen-nombre {
    font-weight: 600;
    color: #495057;
}

.precio-cell {
    font-weight: 600;
    color: #28a745;
}

.cantidad-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.9rem;
}

.subtotal-cell {
    font-weight: 700;
    color: #495057;
    font-size: 1.1rem;
}

.total-section {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
    padding: 20px 25px;
    text-align: center;
}

.total-amount {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.total-label {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 5px;
}

.action-buttons {
    background: #f8f9fa;
    padding: 20px 25px;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-action-detalle {
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
}

.btn-success-custom {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.btn-success-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    color: white;
}

.btn-secondary-custom {
    background: #6c757d;
    color: white;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    transform: translateY(-2px);
    color: white;
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .detalle-container {
        padding: 10px;
    }
    
    .header-detalle {
        padding: 20px;
        text-align: center;
    }
    
    .card-body-info {
        padding: 20px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .info-icon {
        margin-bottom: 10px;
        margin-right: 0;
    }
    
    .table-examenes {
        font-size: 0.85rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-action-detalle {
        width: 100%;
        justify-content: center;
        max-width: 300px;
    }
}

/* Animaciones */
@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.info-card {
    animation: fadeInScale 0.4s ease-out;
}

.info-card:nth-child(even) {
    animation-delay: 0.1s;
}

.info-card:nth-child(odd) {
    animation-delay: 0.2s;
}
</style>

<div class="detalle-container">
    <div class="container">
        <div class="main-card-detalle">
            <!-- Header principal -->
            <div class="header-detalle">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2>📋 Detalle de Cotización</h2>
                        <div class="codigo-cotizacion">
                            Código: <?= htmlspecialchars($cotizacion['codigo']) ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="h4 mb-1">S/ <?= number_format($cotizacion['total'], 2) ?></div>
                        <small><?= htmlspecialchars($cotizacion['fecha']) ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Información del Cliente -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="bi bi-person-circle me-2"></i>Información del Cliente
                    </div>
                    <div class="card-body-info">
                        <?php
                        // Calcular estado de pago dinámico según pagos registrados
                        $stmtPagosDet = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = ?");
                        $stmtPagosDet->execute([$cotizacion['id']]);
                        $totalPagadoDet = (float)$stmtPagosDet->fetchColumn();
                        $saldoDet = max(0, (float)$cotizacion['total'] - $totalPagadoDet);
                        $estado_pago_calc = ($saldoDet <= 0) ? 'pagado' : (($totalPagadoDet > 0) ? 'abonado' : 'pendiente');
                        ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Nombre Completo</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['nombre_cliente'] . ' ' . $cotizacion['apellido_cliente']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-card-text"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">DNI</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['dni']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Condición</div>
                                <div class="info-value">
                                    <span class="badge status-badge <?= $badge_class ?>"><?= $info_tipo ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Estado de Pago</div>
                                <div class="info-value">
                                    <span class="badge status-badge <?= $estado_pago_calc === 'pagado' ? 'bg-success' : ($estado_pago_calc === 'abonado' ? 'bg-info' : 'bg-warning text-dark') ?>">
                                        <?= htmlspecialchars(ucwords(strtolower($estado_pago_calc))) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Toma de Muestra -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="bi bi-calendar-event me-2"></i>Información de Toma
                    </div>
                    <div class="card-body-info">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Fecha de Toma</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['fecha_toma'] ?? 'No asignada') ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Hora de Toma</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['hora_toma'] ?? 'No asignada') ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Tipo de Toma</div>
                                <div class="info-value">
                                    <span class="badge status-badge <?= ($cotizacion['tipo_toma'] ?? '') === 'domicilio' ? 'bg-info' : 'bg-primary' ?>">
                                        <?= htmlspecialchars(ucwords(strtolower($cotizacion['tipo_toma'] ?? 'No asignado'))) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($cotizacion['direccion_toma'])): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-house"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Dirección</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['direccion_toma']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($cotizacion['observaciones'])): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-chat-text"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Observaciones</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['observaciones']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-person-gear"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Creado por</div>
                                <div class="info-value">
                                    <span class="badge status-badge bg-dark">
                                        <?= htmlspecialchars(ucwords(strtolower($cotizacion['rol_creador'] ?? 'No asignado'))) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exámenes Cotizados -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="examenes-section">
                    <div class="examenes-header">
                        <h4 class="mb-0">
                            <i class="bi bi-clipboard-data me-2"></i>Exámenes Cotizados
                        </h4>
                    </div>
                    
                    <?php if ($examenes): ?>
                        <div class="table-responsive">
                            <table class="table table-examenes">
                                <thead>
                                    <tr>
                                        <th>Examen</th>
                                        <th>Condición Cliente</th>
                                        <th class="text-end">P. Unitario</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($examenes as $examen): ?>
                                        <tr>
                                            <td>
                                                <div class="examen-nombre"><?= htmlspecialchars($examen['nombre_examen']) ?></div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= nl2br(htmlspecialchars($examen['preanalitica_cliente'] ?? 'Sin condiciones especiales')) ?></small>
                                            </td>
                                            <td class="text-end precio-cell">S/ <?= number_format($examen['precio_unitario'], 2) ?></td>
                                            <td class="text-center">
                                                <span class="cantidad-badge"><?= $examen['cantidad'] ?></span>
                                            </td>
                                            <td class="text-end subtotal-cell">S/ <?= number_format($examen['subtotal'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="total-section">
                            <div class="total-label">Total de la Cotización</div>
                            <div class="total-amount">S/ <?= number_format($cotizacion['total'], 2) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>No hay exámenes cotizados</h5>
                            <p>Esta cotización no tiene exámenes registrados.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <a href="<?= BASE_URL ?>dashboard.php?action=descargar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-action-detalle btn-success-custom" target="_blank">
                            <i class="bi bi-download"></i> Descargar PDF
                        </a>
                        <?php if ($requiereCpe && $estado_pago_calc === 'pagado'): ?>
                            <a id="btnEmitirCpe" href="<?= BASE_URL ?>dashboard.php?action=emitir_comprobante&id=<?= $cotizacion['id'] ?>" class="btn btn-action-detalle btn-warning">
                                <i class="bi bi-receipt"></i> Emitir Comprobante
                            </a>
                        <?php elseif (!$requiereCpe): ?>
                            <span class="badge status-badge bg-secondary">Solo Ticket (sin CPE)</span>
                        <?php endif; ?>

                        <?php if ($requiereCpe): ?>
                            <button type="button" class="btn btn-action-detalle btn-primary" onclick="verEstadoComprobante()">
                                <i class="bi bi-info-circle"></i> Ver Estado
                            </button>
                            <div id="cpeDownloads" style="display:none">
                                <a href="#" onclick="descargarCuandoListo('xml')" class="btn btn-action-detalle btn-secondary-custom">
                                    <i class="bi bi-filetype-xml"></i> Descargar XML
                                </a>
                                <a href="<?= BASE_URL ?>dashboard.php?action=descargar_comprobante&id=<?= $cotizacion['id'] ?>&tipo=pdf" class="btn btn-action-detalle btn-secondary-custom">
                                    <i class="bi bi-filetype-pdf"></i> Descargar PDF CPE
                                </a>
                                <a href="#" onclick="descargarCuandoListo('cdr')" class="btn btn-action-detalle btn-secondary-custom">
                                    <i class="bi bi-file-zip"></i> Descargar CDR
                                </a>
                            </div>
                            <div id="cpeDownloadsPlaceholder" class="alert alert-info py-2 px-3" style="display:none">
                                <div class="d-flex align-items-center" style="gap:10px">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span>XML/CDR aún en preparación. Se habilitarán al ser aceptado por SUNAT.</span>
                                    <button type="button" class="btn btn-sm btn-secondary-custom" onclick="descargarCuandoListo('xml')">
                                        <i class="bi bi-clock"></i> Esperar disponibilidad
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="btn btn-action-detalle btn-info" onclick="imprimirTicketCotizacion()">
                            <i class="bi bi-printer"></i> Imprimir Ticket
                        </button>
                        <a href="javascript:history.back()" class="btn btn-action-detalle btn-secondary-custom">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function imprimirTicketCotizacion() {
    var codigo = "<?= htmlspecialchars($cotizacion['codigo']) ?>";
    var codigoPaciente = "<?= htmlspecialchars($cotizacion['codigo_cliente'] ?? '') ?>";
    var nombre = "<?= htmlspecialchars($cotizacion['nombre_cliente'] . ' ' . $cotizacion['apellido_cliente']) ?>";
    var dni = "<?= htmlspecialchars($cotizacion['dni']) ?>";
    var fecha = "<?= htmlspecialchars($cotizacion['fecha']) ?>";
    var total = "<?= number_format($cotizacion['total'], 2) ?>";
    var condicion = "<?= $info_tipo ?>";
    var examenes = <?php echo json_encode($examenes); ?>;
    var empresa_nombre = "<?= htmlspecialchars($config['nombre'] ?? '') ?>";
    var empresa_ruc = "<?= htmlspecialchars($config['ruc'] ?? '') ?>";
    var empresa_direccion = "<?= htmlspecialchars($config['direccion'] ?? '') ?>";
    var empresa_celular = "<?= htmlspecialchars($config['celular'] ?? '') ?>";
    var empresa_dominio = "<?= htmlspecialchars($config['dominio'] ?? '') ?>";

        var rows = '';
        examenes.forEach(function(ex) {
                var precio = ex.precio_unitario ? parseFloat(ex.precio_unitario) : (ex.precio ? parseFloat(ex.precio) : (ex.precio_publico ? parseFloat(ex.precio_publico) : 0));
                rows += `<tr><td>${ex.nombre_examen}</td><td class='qty'>${ex.cantidad}</td><td class='price'>S/ ${precio.toFixed(2)}</td></tr>`;
        });
       var html = `
                <div class="receipt">
                    <style>
                        /* Ajustes de tamaño para el ticket (modifica aquí para cambiar manualmente) */
                        .receipt{width:280px;margin:0 auto;font-family:'Courier New',monospace;color:#333;}
                        .center{text-align:center}
                        .small{font-size:12px} /* texto secundario */
                        .separator{border-top:2px dotted #aaa;margin:8px 0}
                        .info-table,.items-table{width:100%;border-collapse:collapse}
                        .info-table td{padding:4px 0;font-size:12px} /* datos clave */
                        .info-table td.label{color:#555}
                        .info-table td.value{text-align:right}
                        .items-table thead th{font-weight:700;font-size:13px;text-align:left;padding:6px 0;border-bottom:2px dotted #aaa}
                        .items-table thead th.qty{text-align:right}
                        .items-table thead th.price{text-align:right}
                        .items-table tbody td{padding:4px 0;border-bottom:1px dotted #ddd;font-size:12px}
                        .items-table tbody td.qty{text-align:right}
                        .items-table tbody td.price{text-align:right}
                        .total-row{margin-top:10px;border-top:2px dotted #000;padding-top:8px;font-weight:700;display:flex;justify-content:space-between;font-size:16px}
                        .footer{margin-top:10px;font-size:12px}
                    </style>
                    <div class="center">
                        <div style="font-size:22px;font-weight:700;text-transform:uppercase">${empresa_nombre}</div>
                        <div class="small">RUC: ${empresa_ruc}</div>
                        <div class="small">${empresa_direccion}</div>
                        <div class="small">Celular: ${empresa_celular}</div>
                        ${empresa_dominio ? `<div class="small">${empresa_dominio}</div>` : ''}
                        <div class="separator"></div>
                        <div class="small" style="font-weight:700">Ticket Cotización</div>
                    </div>
                    <table class="info-table">
                        <tbody>
                            <tr><td class="label">Cód. Paciente</td><td class="value"><strong>${codigoPaciente}</strong></td></tr>
                            <tr><td class="label">Cód. Cotización</td><td class="value">${codigo}</td></tr>
                            <tr><td class="label">Paciente</td><td class="value">${nombre}</td></tr>
                            <tr><td class="label">DNI</td><td class="value">${dni}</td></tr>
                            <tr><td class="label">Referencia</td><td class="value">${condicion}</td></tr>
                            <tr><td class="label">Fecha</td><td class="value">${fecha}</td></tr>
                        </tbody>
                    </table>
                    <div class="separator"></div>
                    <div class="center" style="font-weight:700;font-size:17px">Exámenes</div>
                    <table class="items-table">
                        <thead><tr><th>Examen</th><th class="qty">Cant</th><th class="price">Precio</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                    <div class="total-row"><span>TOTAL</span><span>S/ ${total}</span></div>
                    <div class="footer center small">Gracias por su preferencia</div>
                </div>`;

    Swal.fire({
        title: 'Vista previa del ticket',
        html: html,
        showCancelButton: true,
        confirmButtonText: 'Imprimir',
        cancelButtonText: 'Cerrar',
        customClass: {
            popup: 'swal2-ticket-modal'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Imprimir solo el contenido del ticket
            var printWin = window.open('', 'PrintTicket', 'width=320,height=600');
            printWin.document.write('<html><head><title>Ticket Cotización</title></head><body>' + html + '<script>window.print();setTimeout(()=>window.close(),500);<\/script></body></html>');
            printWin.document.close();
        }
    });
}

function verEstadoComprobante() {
    const id = <?= (int)$cotizacion['id'] ?>;
    fetch('<?= BASE_URL ?>dashboard.php?action=estado_comprobante&id=' + id)
        .then(r => r.json())
        .then(data => {
            const st = data && data.status ? data.status : { status: 'sin_estado' };
            const estado = (typeof st === 'string') ? st : (st.status || 'sin_estado');
            const detalle = (typeof st === 'object') ? st : {};
            let html = `<div style="text-align:left">` +
                `<div><strong>Estado:</strong> ${estado.toUpperCase()}</div>` +
                (detalle.tipo ? `<div><strong>Tipo:</strong> ${detalle.tipo}</div>` : '') +
                (detalle.remote_id ? `<div><strong>ID remoto:</strong> ${detalle.remote_id}</div>` : '') +
                (detalle.sunat_code ? `<div><strong>Código SUNAT:</strong> ${detalle.sunat_code}</div>` : '') +
                (detalle.sunat_message ? `<div><strong>Mensaje SUNAT:</strong> ${detalle.sunat_message}</div>` : '') +
                (detalle.token_present !== undefined ? `<div><strong>Token listo:</strong> ${detalle.token_present ? 'Sí' : 'No'}</div>` : '') +
                (detalle.updated_at ? `<div><strong>Actualizado:</strong> ${detalle.updated_at}</div>` : '') +
                `</div>`;
            Swal.fire({ title: 'Estado del comprobante', html, icon: 'info' });
        })
        .catch(err => {
            Swal.fire({ title: 'Error', text: 'No se pudo obtener el estado', icon: 'error' });
        });
}

function descargarCuandoListo(tipo) {
    const id = <?= (int)$cotizacion['id'] ?>;
    const urlEstado = '<?= BASE_URL ?>dashboard.php?action=estado_comprobante&id=' + id;
    const urlDescarga = (t) => '<?= BASE_URL ?>dashboard.php?action=descargar_comprobante&id=' + id + '&tipo=' + t;
    let intentos = 0, maxIntentos = 8, intervalo = 4000;
    const chequear = () => {
        fetch(urlEstado).then(r => r.json()).then(d => {
            const st = d && d.status ? d.status : { status: 'sin_estado' };
            const estado = (typeof st === 'string') ? st.toLowerCase() : (String(st.status || 'sin_estado').toLowerCase());
            if (estado === 'aceptado') {
                window.location.href = urlDescarga(tipo);
                return;
            }
            intentos++;
            if (intentos >= maxIntentos) {
                Swal.fire({
                    title: 'XML/CDR aún no disponible',
                    html: 'Estado actual: ' + (estado.toUpperCase()) + '<br>Intenta nuevamente en unos segundos.',
                    icon: 'info'
                });
            } else {
                if (intentos === 1) {
                    Swal.fire({ title: 'Esperando respuesta de SUNAT…', html: 'Revisando estado cada 4s', icon: 'info', timer: intervalo - 500, showConfirmButton: false });
                }
                setTimeout(chequear, intervalo);
            }
        }).catch(() => {
            intentos++;
            if (intentos < maxIntentos) setTimeout(chequear, intervalo);
            else Swal.fire({ title: 'No se pudo verificar', text: 'Intenta nuevamente más tarde.', icon: 'warning' });
        });
    };
    chequear();
}
</script>
<script>
// Ajuste dinámico del botón de emisión según estado remoto
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('btnEmitirCpe');
    if (!btn) return;
    const id = <?= (int)$cotizacion['id'] ?>;
    fetch('<?= BASE_URL ?>dashboard.php?action=estado_comprobante&id=' + id)
        .then(r => r.json())
        .then(data => {
            const st = data && data.status ? data.status : { status: 'sin_estado' };
            const estado = (typeof st === 'string') ? st.toLowerCase() : (String(st.status || 'sin_estado').toLowerCase());
            const hasRemote = !!(st.remote_id || (st.data && st.data.remote_id));
            if (estado === 'aceptado') {
                btn.textContent = 'Ya Emitido';
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-secondary-custom');
                btn.setAttribute('disabled', 'disabled');
                btn.style.pointerEvents = 'none';
                btn.title = 'El comprobante ya fue aceptado por SUNAT';
                // Mostrar descargas y ocultar placeholder
                var dw = document.getElementById('cpeDownloads');
                var ph = document.getElementById('cpeDownloadsPlaceholder');
                if (ph) ph.style.display = 'none';
                if (dw) dw.style.display = 'flex';
                if (dw) { dw.style.gap = '15px'; dw.style.flexWrap = 'wrap'; dw.style.justifyContent = 'center'; }
            } else if (hasRemote) {
                // Si existe ID remoto pero no aceptado, el botón actúa como reintento
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Reintentar Envío';
                btn.title = 'Reintentar envío a SUNAT';
                // Ocultar descargas y mostrar placeholder
                var dw = document.getElementById('cpeDownloads');
                var ph = document.getElementById('cpeDownloadsPlaceholder');
                if (dw) dw.style.display = 'none';
                if (ph) ph.style.display = 'block';
            } else {
                // Aún sin enviar: mantener ocultas las descargas y mostrar placeholder informativo
                var dw = document.getElementById('cpeDownloads');
                var ph = document.getElementById('cpeDownloadsPlaceholder');
                if (dw) dw.style.display = 'none';
                if (ph) ph.style.display = 'block';
            }
        })
        .catch(() => {});
});
</script>
                