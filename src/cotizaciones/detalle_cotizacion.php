<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../auth/empresa_config.php';

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
                                    <span class="badge status-badge <?= $cotizacion['estado_pago'] === 'pagado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= htmlspecialchars(ucwords(strtolower($cotizacion['estado_pago']))) ?>
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
                        <a href="cotizaciones/descargar_cotizacion.php?id=<?= $cotizacion['id'] ?>" class="btn btn-action-detalle btn-success-custom" target="_blank">
                            <i class="bi bi-download"></i> Descargar PDF
                        </a>
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

    var html = `<div style=\"font-family:monospace; width:280px; padding:10px; margin:0 auto; display:block; text-align:center;\">
        <div style='font-size:1.1em; font-weight:bold;'>${empresa_nombre}</div>
        <div style='font-size:0.95em;'>RUC: ${empresa_ruc}</div>
        <div style='font-size:0.95em;'>${empresa_direccion}</div>
        <div style='font-size:0.95em;'>Celular: ${empresa_celular}</div>
        <div style='margin-bottom:8px;'>Ticket Cotización</div>
        <div>Código: <strong>${codigo}</strong></div>
        <div>Cliente: ${nombre}</div>
        <div>DNI: ${dni}</div>
        <div>Condición: ${condicion}</div>
        <div>Fecha: ${fecha}</div>
        <hr>
        <div style='font-weight:bold;'>Exámenes:</div>
        <table style='width:100%; font-size:0.95em; margin:0 auto; text-align:center;'>
            <thead><tr><th style='text-align:center;'>Examen</th><th style='text-align:center;'>Cant</th></tr></thead>
            <tbody>`;
    examenes.forEach(function(ex) {
        html += `<tr><td style='text-align:center;'>${ex.nombre_examen}</td><td style='text-align:center;'>${ex.cantidad}</td></tr>`;
    });
    html += `</tbody></table>
        <hr>
        <div style='font-size:1.2em;'>Total: S/ ${total}</div>
        <div style='margin-top:10px;'>Gracias por su preferencia</div>
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
</script>
</div>