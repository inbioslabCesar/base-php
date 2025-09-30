<?php
require_once __DIR__ . '/../conexion/conexion.php';

$idCotizacion = $_GET['id'] ?? null;
$cotizacion = null;
$msg = $_GET['msg'] ?? '';

$totalPagado = 0;
$saldo = 0;
if ($idCotizacion) {
    $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $stmt->execute([$idCotizacion]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtPagos = $pdo->prepare("SELECT SUM(monto) AS total_pagado FROM pagos WHERE id_cotizacion = ?");
    $stmtPagos->execute([$idCotizacion]);
    $totalPagado = floatval($stmtPagos->fetchColumn());
    $saldo = floatval($cotizacion['total']) - $totalPagado;
}
?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .payment-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .payment-header {
        background: var(--primary-gradient);
        color: white;
        padding: 2rem;
        border-radius: 15px 15px 0 0;
        text-align: center;
        box-shadow: var(--card-shadow);
    }

    .payment-card {
        background: white;
        border-radius: 0 0 15px 15px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .amount-section {
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        padding: 1.5rem;
        margin: 1rem;
        border-radius: 10px;
        border-left: 5px solid #e17055;
    }

    .editable-total {
        background: var(--warning-gradient);
        color: white;
        padding: 1.5rem;
        margin: 1rem;
        border-radius: 10px;
        border-left: 5px solid #d63031;
    }

    .payment-info {
        background: var(--info-gradient);
        padding: 1.5rem;
        margin: 1rem;
        border-radius: 10px;
        border-left: 5px solid #74b9ff;
    }

    .form-control-modern {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }

    .btn-modern {
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-primary-modern {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-success-modern {
        background: var(--success-gradient);
        color: white;
    }

    .btn-warning-modern {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }

    .history-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .alert-modern {
        border: none;
        border-radius: 10px;
        padding: 1rem 1.5rem;
        margin: 1rem 0;
    }

    .toggle-edit {
        background: rgba(255,255,255,0.2);
        border: 2px solid rgba(255,255,255,0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .toggle-edit:hover {
        background: rgba(255,255,255,0.3);
        color: white;
    }
</style>

<div class="payment-container">
    <div class="container">
        <div class="payment-header">
            <h2 class="mb-3">
                <i class="bi bi-credit-card me-2"></i>
                Gestión de Pagos - Cotización #<?= htmlspecialchars($idCotizacion) ?>
            </h2>
            <button type="button" class="toggle-edit" onclick="toggleEditTotal()">
                <i class="bi bi-pencil-square me-1"></i>
                Modificar Monto Total
            </button>
        </div>
        <div class="payment-card">
            <?php if ($msg == "error"): ?>
                <div class="alert alert-danger alert-modern">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    El monto debe ser positivo y no mayor al saldo pendiente (excepto para descarga anticipada).
                </div>
            <?php elseif ($msg == "success"): ?>
                <div class="alert alert-success alert-modern">
                    <i class="bi bi-check-circle me-2"></i>
                    Pago registrado correctamente.
                </div>
            <?php elseif ($msg == "total_updated"): ?>
                <div class="alert alert-info alert-modern">
                    <i class="bi bi-info-circle me-2"></i>
                    Monto total actualizado correctamente.
                </div>
            <?php endif; ?>

            <?php if ($cotizacion): ?>
                
                <!-- Formulario para modificar monto total -->
                <div class="editable-total" id="editTotalSection" style="display: none;">
                    <h5 class="mb-3">
                        <i class="bi bi-currency-dollar me-2"></i>
                        Modificar Monto Total de la Cotización
                    </h5>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>¡Atención!</strong> Esta acción modificará el monto total acordado con el paciente. 
                        Use esta función cuando haya renegociado el precio con el cliente.
                    </div>
                    <form method="post" action="dashboard.php?action=actualizar_total_cotizacion" id="formEditTotal">
                        <input type="hidden" name="id_cotizacion" value="<?= htmlspecialchars($idCotizacion) ?>">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="form-label text-white">
                                    <strong>Nuevo Monto Total (S/)</strong>
                                </label>
                                <input type="number" 
                                       step="0.01" 
                                       name="nuevo_total" 
                                       class="form-control form-control-modern" 
                                       value="<?= htmlspecialchars($cotizacion['total']) ?>" 
                                       min="0.01" 
                                       required
                                       id="nuevoTotal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white">
                                    <strong>Motivo del Cambio</strong>
                                </label>
                                <input type="text" 
                                       name="motivo_cambio" 
                                       class="form-control form-control-modern" 
                                       placeholder="Ej: Renegociación con paciente, descuento aplicado..."
                                       maxlength="200">
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-warning-modern">
                                <i class="bi bi-save me-1"></i>
                                Actualizar Monto
                            </button>
                            <button type="button" class="btn btn-secondary btn-modern" onclick="toggleEditTotal()">
                                <i class="bi bi-x-lg me-1"></i>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información de montos -->
                <div class="amount-section">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="bi bi-calculator me-2"></i>Monto Total Acordado</h6>
                            <h4 class="text-primary fw-bold">S/ <?= number_format($cotizacion['total'], 2) ?></h4>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-cash-stack me-2"></i>Monto Abonado</h6>
                            <h4 class="text-success fw-bold">S/ <?= number_format($totalPagado, 2) ?></h4>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-hourglass-split me-2"></i>Saldo Pendiente</h6>
                            <h4 class="<?= $saldo > 0 ? 'text-danger' : 'text-success' ?> fw-bold">
                                S/ <?= number_format($saldo, 2) ?>
                            </h4>
                        </div>
                    </div>
                </div>

                <!-- Formulario de pago -->
                <?php if ($saldo > 0): ?>
                    <form method="post" action="dashboard.php?action=pago_cotizacion_guardar" class="p-4" id="formPago">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($idCotizacion) ?>">
                        
                        <h5 class="mb-4">
                            <i class="bi bi-credit-card me-2"></i>
                            Registrar Nuevo Pago
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-cash me-2"></i>
                                    <strong>Monto a Abonar (S/)</strong>
                                </label>
                                <input type="number" 
                                       step="0.01" 
                                       name="monto_abonado" 
                                       class="form-control form-control-modern" 
                                       min="0" 
                                       max="<?= $saldo ?>" 
                                       required 
                                       id="montoAbonado"
                                       placeholder="0.00">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Máximo disponible: S/ <?= number_format($saldo, 2) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-credit-card me-2"></i>
                                    <strong>Método de Pago</strong>
                                </label>
                                <select name="metodo" class="form-control form-control-modern" required id="metodoPago">
                                    <option value="">Selecciona método...</option>
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="tarjeta">💳 Tarjeta</option>
                                    <option value="transferencia">🏦 Transferencia</option>
                                    <option value="yape">📱 Yape</option>
                                    <option value="descarga_anticipada">⏰ Descarga anticipada (pago pendiente)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar me-2"></i>
                                    <strong>Fecha de Pago</strong>
                                </label>
                                <input type="date" 
                                       name="fecha_pago" 
                                       class="form-control form-control-modern" 
                                       value="<?= date('Y-m-d') ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-success-modern flex-fill">
                                        <i class="bi bi-save me-2"></i>
                                        Registrar Pago
                                    </button>
                                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary btn-modern">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Volver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="payment-info text-center">
                        <i class="bi bi-check-circle-fill display-4 text-success mb-3"></i>
                        <h4 class="text-success">¡Cotización Completamente Pagada!</h4>
                        <p class="mb-3">Esta cotización no tiene saldo pendiente.</p>
                        <a href="dashboard.php?vista=cotizaciones" class="btn btn-primary-modern">
                            <i class="bi bi-arrow-left me-2"></i>
                            Volver a Cotizaciones
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Información importante -->
            <div class="payment-info">
                <h6><i class="bi bi-info-circle me-2"></i>Información Importante</h6>
                <ul class="mb-0">
                    <li><strong>Descarga de resultados:</strong> El cliente solo podrá descargar sus resultados cuando el estado de pago sea "pagado" o si el método de pago fue "descarga anticipada".</li>
                    <li><strong>Modificar monto:</strong> Use la opción "Modificar Monto Total" solo cuando haya renegociado el precio con el paciente.</li>
                    <li><strong>Historial:</strong> Todos los cambios quedan registrados en el historial de pagos.</li>
                </ul>
            </div>
            
            <!-- Historial de pagos -->
            <?php
            $stmtHistorial = $pdo->prepare("SELECT monto, metodo_pago, fecha, observaciones FROM pagos WHERE id_cotizacion = ? ORDER BY fecha DESC");
            $stmtHistorial->execute([$idCotizacion]);
            $historialPagos = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
            if ($historialPagos):
            ?>
            <div class="p-4">
                <h5 class="mb-3">
                    <i class="bi bi-clock-history me-2"></i>
                    Historial de Pagos y Cambios
                </h5>
                <div class="history-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--primary-gradient); color: white;">
                                <tr>
                                    <th><i class="bi bi-cash me-1"></i>Monto</th>
                                    <th><i class="bi bi-credit-card me-1"></i>Tipo</th>
                                    <th><i class="bi bi-calendar me-1"></i>Fecha</th>
                                    <th><i class="bi bi-info-circle me-1"></i>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historialPagos as $pago): ?>
                                    <tr class="<?= $pago['metodo_pago'] === 'cambio_total' ? 'table-warning' : '' ?>">
                                        <td class="fw-bold">
                                            <?php if ($pago['metodo_pago'] === 'cambio_total'): ?>
                                                <span class="text-warning">
                                                    <i class="bi bi-arrow-repeat me-1"></i>
                                                    Cambio de Total
                                                </span>
                                            <?php else: ?>
                                                <span class="text-success">S/ <?= number_format($pago['monto'], 2) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago['metodo_pago'] === 'cambio_total'): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                    Modificación
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <?php
                                                    $metodos = [
                                                        'efectivo' => '💵 Efectivo',
                                                        'tarjeta' => '💳 Tarjeta',
                                                        'transferencia' => '🏦 Transferencia',
                                                        'yape' => '📱 Yape',
                                                        'descarga_anticipada' => '⏰ Descarga Anticipada'
                                                    ];
                                                    echo $metodos[$pago['metodo_pago']] ?? ucfirst(str_replace('_', ' ', $pago['metodo_pago']));
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($pago['fecha'])) ?></td>
                                        <td>
                                            <?php if (!empty($pago['observaciones'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($pago['observaciones']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
            <div class="payment-card">
                <div class="payment-info text-center">
                    <i class="bi bi-exclamation-triangle-fill display-4 text-danger mb-3"></i>
                    <h4 class="text-danger">Cotización No Encontrada</h4>
                    <p class="mb-3">No se pudo encontrar la cotización solicitada.</p>
                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-primary-modern">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Cotizaciones
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleEditTotal() {
    const section = document.getElementById('editTotalSection');
    const isVisible = section.style.display !== 'none';
    section.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        document.getElementById('nuevoTotal').focus();
    }
}

// Dinámicamente cambia el mínimo según el método de pago
document.addEventListener('DOMContentLoaded', function() {
    const metodoPago = document.getElementById('metodoPago');
    const montoAbonado = document.getElementById('montoAbonado');
    
    if (metodoPago && montoAbonado) {
        metodoPago.addEventListener('change', function() {
            if (this.value === 'descarga_anticipada') {
                montoAbonado.min = 0;
                montoAbonado.placeholder = 'Puede ser 0 para descarga anticipada';
            } else {
                montoAbonado.min = 0.01;
                montoAbonado.placeholder = 'Monto mayor a 0';
            }
        });
    }
    
    // Validación del formulario de editar total
    const formEditTotal = document.getElementById('formEditTotal');
    if (formEditTotal) {
        formEditTotal.addEventListener('submit', function(e) {
            const nuevoTotal = parseFloat(document.getElementById('nuevoTotal').value);
            const totalActual = <?= $cotizacion['total'] ?? 0 ?>;
            
            if (nuevoTotal === totalActual) {
                e.preventDefault();
                alert('El nuevo monto debe ser diferente al monto actual.');
                return false;
            }
            
            if (!confirm('¿Está seguro de que desea modificar el monto total de la cotización? Esta acción se registrará en el historial.')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
