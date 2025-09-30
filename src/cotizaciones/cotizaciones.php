<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Botón según rol
$rol = $_SESSION['rol'] ?? '';
$botonTexto = '';
$botonUrl   = '';

if ($rol === 'recepcionista' || $rol === 'admin') {
    $botonTexto = 'Nueva Cotización';
    $botonUrl   = 'dashboard.php?vista=clientes';
} elseif ($rol === 'laboratorista') {
    $botonTexto = 'Panel de Laboratorio';
    $botonUrl   = 'dashboard.php?vista=laboratorista';
}

// Filtros recibidos por GET
$dniFiltro      = trim($_GET['dni'] ?? '');
$empresaFiltro  = trim($_GET['empresa'] ?? '');
$convenioFiltro = trim($_GET['convenio'] ?? '');

// Consultar empresas y convenios para los selects
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Consulta principal con LEFT JOIN para empresa y convenio
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni,
        e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        LEFT JOIN empresas e ON c.id_empresa = e.id
        LEFT JOIN convenios v ON c.id_convenio = v.id";
$condiciones = [];
$params = [];
if ($dniFiltro !== '') {
    $condiciones[] = "cl.dni = ?";
    $params[] = $dniFiltro;
}
if ($empresaFiltro !== '') {
    $condiciones[] = "c.id_empresa = ?";
    $params[] = $empresaFiltro;
}
if ($convenioFiltro !== '') {
    $condiciones[] = "c.id_convenio = ?";
    $params[] = $convenioFiltro;
}
if ($condiciones) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY c.fecha DESC, c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Paginación para cards móviles
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = 3;
$total_cotizaciones = count($cotizaciones);
$total_paginas = ceil($total_cotizaciones / $por_pagina);
$inicio = ($pagina - 1) * $por_pagina;
$cotizaciones_pagina = array_slice($cotizaciones, $inicio, $por_pagina);

// Consulta para exámenes de cada cotización
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

// Consulta pagos por cotización
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
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
/* Estilos para cotizaciones responsivas */
.cotizaciones-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    margin-bottom: 0;
}

.cotizaciones-filters {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
}

/* Vista desktop (tabla normal) */
.desktop-view {
    display: block;
}

.mobile-view {
    display: none;
}

/* Vista móvil con cards */
.cotizacion-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.cotizacion-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header-cotizacion {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
}

.card-header-cotizacion .codigo {
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-cotizacion .fecha {
    opacity: 0.9;
    font-size: 0.9rem;
}

.card-body-cotizacion {
    padding: 20px;
}

.patient-info-card {
    background: rgba(102, 126, 234, 0.1);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.patient-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.patient-dni {
    color: #6c757d;
    font-size: 0.9rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f3f4;
}

.info-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.9rem;
}

.info-value {
    font-weight: 600;
    color: #495057;
}

.status-section {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.badge-status {
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.actions-card {
    background: #f8f9fa;
    padding: 15px 20px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-card-action {
    flex: 0 0 auto;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-weight: 500;
    padding: 0;
    font-size: 1.1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-card-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Mejoras para la tabla en desktop */
.table-modern {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.table-modern thead th {
    background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 15px 12px;
}

.table-modern tbody td {
    padding: 12px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .cotizaciones-header {
        text-align: center;
        padding: 15px;
    }
    
    .cotizaciones-filters {
        padding: 15px;
    }
    
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
    
    .actions-card {
        justify-content: center;
    }
    
    .btn-card-action {
        flex: 0 0 auto;
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .btn-card-action {
        width: 38px;
        height: 38px;
        font-size: 0.9rem;
    }
    
    .status-section {
        justify-content: center;
    }
}

/* Animaciones */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cotizacion-card {
    animation: slideInUp 0.4s ease-out;
}
</style>

<style>
/* Estilos para vista responsiva de cotizaciones */
.cotizaciones-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.main-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
}

.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    position: relative;
}

.header-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.header-section h4 {
    margin: 0;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.header-actions {
    position: relative;
    z-index: 1;
}

.filters-section {
    background: #f8f9fa;
    padding: 25px 30px;
    border-bottom: 1px solid #dee2e6;
}

.filter-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.content-section {
    padding: 30px;
}

/* Vista desktop - tabla */
.desktop-view {
    display: block;
}

.mobile-view {
    display: none;
}

/* Estilos para cards en móvil */
.cotizacion-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.cotizacion-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    position: relative;
}

.card-header-custom .codigo {
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-custom .fecha {
    opacity: 0.9;
    font-size: 0.9rem;
}

.card-body-custom {
    padding: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f3f4;
}

.info-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.9rem;
}

.info-value {
    font-weight: 600;
    color: #495057;
}

.patient-info {
    background: rgba(102, 126, 234, 0.1);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.patient-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.patient-dni {
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.badge-custom {
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.actions-section {
    background: #f8f9fa;
    padding: 15px 20px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    flex: 1;
    min-width: 100px;
    border-radius: 20px;
    font-weight: 500;
    padding: 8px 15px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .cotizaciones-container {
        padding: 10px;
    }
    
    .header-section {
        padding: 20px;
        text-align: center;
    }
    
    .filters-section {
        padding: 20px;
    }
    
    .content-section {
        padding: 20px;
    }
    
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
    
    .filter-card {
        padding: 15px;
    }
    
    .actions-section {
        justify-content: center;
    }
    
    .btn-action {
        flex: 1 1 calc(50% - 4px);
        min-width: 0;
    }
}

@media (max-width: 576px) {
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .btn-action {
        flex: 1 1 100%;
    }
    
    .status-badges {
        justify-content: center;
    }
}

/* Mejoras para DataTables */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5rem 1rem;
    margin: 0 2px;
    border-radius: 20px;
}

.dataTables_wrapper .dataTables_length select {
    border-radius: 20px;
    padding: 5px 10px;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 20px;
    padding: 8px 15px;
    border: 2px solid #e9ecef;
}

.table-custom {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.table-custom thead th {
    background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 15px 12px;
}

.table-custom tbody td {
    padding: 12px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.table-custom tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cotizacion-card {
    animation: fadeInUp 0.3s ease-out;
}

.cotizacion-card:nth-child(even) {
    animation-delay: 0.1s;
}

.cotizacion-card:nth-child(odd) {
    animation-delay: 0.2s;
}
</style>

<div class="container mt-4">
    <!-- Header mejorado -->
    <div class="cotizaciones-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-2 mb-md-0">📊 Historial de Cotizaciones</h4>
            <?php if ($botonTexto && $botonUrl): ?>
                <a href="<?= $botonUrl ?>" class="btn btn-light">
                    <i class="bi bi-plus-circle me-2"></i><?= $botonTexto ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros mejorados -->
    <div class="cotizaciones-filters">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="vista" value="cotizaciones">
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🔍 DNI</label>
                <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🏢 Empresa</label>
                <select name="empresa" class="form-select">
                    <option value="">Seleccionar empresa...</option>
                    <?php foreach ($empresas as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($empresaFiltro == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold">🤝 Convenio</label>
                <select name="convenio" class="form-select">
                    <option value="">Seleccionar convenio...</option>
                    <?php foreach ($convenios as $conv): ?>
                        <option value="<?= $conv['id'] ?>" <?= ($convenioFiltro == $conv['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($conv['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="dashboard.php?vista=cotizaciones" class="btn btn-outline-secondary flex-fill">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Vista Desktop - Tabla -->
    <div class="desktop-view">
        <div class="table-responsive">
            <table id="tablaCotizaciones" class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Paciente</th>
                        <th>DNI</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Referencia</th>
                        <th>Estado Pago</th>
                        <th>Estado Examen</th>
                        <th>Rol Creador</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($cotizaciones): ?>
                    <?php foreach ($cotizaciones as $cotizacion): ?>
                        <tr>
                            <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['dni'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                            <td>S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></td>
                            <!-- Columna de referencia -->
                            <td>
                                <?php
                                if (!empty($cotizacion['id_empresa']) && (!empty($cotizacion['nombre_comercial']) || !empty($cotizacion['razon_social']))) {
                                    echo '<span class="badge bg-info text-dark">' .
                                        htmlspecialchars($cotizacion['nombre_comercial'] ?: $cotizacion['razon_social']) .
                                        '</span>';
                                } elseif (!empty($cotizacion['id_convenio']) && !empty($cotizacion['nombre_convenio'])) {
                                    echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Particular</span>';
                                }
                                ?>
                            </td>

                            <!-- Estado Pago calculado -->
                            <td>
                                <?php
                                $total = floatval($cotizacion['total']);
                                $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
                                $saldo = $total - $pagado;

                                if ($saldo <= 0) {
                                    $badgeClassPago = 'bg-success';
                                    $iconPago = 'bi-check-circle-fill';
                                    $textoPago = 'Pagado';
                                } elseif ($pagado > 0) {
                                    $badgeClassPago = 'bg-warning text-dark';
                                    $iconPago = 'bi-hourglass-split';
                                    $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
                                } else {
                                    $badgeClassPago = 'bg-danger';
                                    $iconPago = 'bi-x-circle-fill';
                                    $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
                                }
                                ?>
                                <span class="badge <?= $badgeClassPago ?>">
                                    <i class="bi <?= $iconPago ?>"></i>
                                    <?= $textoPago ?>
                                </span>
                            </td>
                            <!-- Estado Examen -->
                            <td>
                                <?php
                                $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
                                $pendientes = array_filter($examenes, function ($ex) {
                                    return $ex['estado'] === 'pendiente';
                                });
                                if ($pendientes) {
                                    echo "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente</span>";
                                } else {
                                    echo "<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado</span>";
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></td>
                            <td>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                    <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-sm mb-1" title="Ver cotización">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista'): ?>
                                    <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-sm mb-1" title="Editar o agregar resultados">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (($rol === 'admin' || $rol === 'recepcionista') && $saldo > 0): ?>
                                    <a href="dashboard.php?vista=pago_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-warning btn-sm mb-1" title="Registrar pago">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin'): ?>
                                    <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-sm mb-1" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                    <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-1" title="Descargar PDF de todos los resultados" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No hay cotizaciones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Vista Móvil - Cards -->
<div class="mobile-view mt-3">
    <?php if ($cotizaciones_pagina): ?>
        <?php foreach ($cotizaciones_pagina as $cotizacion): ?>
            <?php
            // Calcular estado de pago
            $total = floatval($cotizacion['total']);
            $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
            $saldo = $total - $pagado;

            if ($saldo <= 0) {
                $badgeClassPago = 'bg-success';
                $iconPago = 'bi-check-circle-fill';
                $textoPago = 'Pagado';
            } elseif ($pagado > 0) {
                $badgeClassPago = 'bg-warning text-dark';
                $iconPago = 'bi-hourglass-split';
                $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
            } else {
                $badgeClassPago = 'bg-danger';
                $iconPago = 'bi-x-circle-fill';
                $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
            }

            // Estado de exámenes
            $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
            $pendientes = array_filter($examenes, function ($ex) {
                return $ex['estado'] === 'pendiente';
            });
            ?>
            <div class="cotizacion-card">
                <div class="card-header-cotizacion">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="codigo">📋 <?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></div>
                            <div class="fecha"><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5">S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></div>
                            <small><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body-cotizacion">
                    <!-- Información del paciente -->
                    <div class="patient-info-card">
                        <div class="patient-name">👤 <?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></div>
                        <div class="patient-dni">DNI: <?= htmlspecialchars($cotizacion['dni'] ?? '') ?></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="info-row">
                        <span class="info-label">📋 Tipo:</span>
                        <span class="info-value">
                            <?php
                            if (!empty($cotizacion['id_empresa']) && (!empty($cotizacion['nombre_comercial']) || !empty($cotizacion['razon_social']))) {
                                echo '<span class="badge bg-info">' .
                                    htmlspecialchars($cotizacion['nombre_comercial'] ?: $cotizacion['razon_social']) .
                                    '</span>';
                            } elseif (!empty($cotizacion['id_convenio']) && !empty($cotizacion['nombre_convenio'])) {
                                echo '<span class="badge bg-warning">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Particular</span>';
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Estados -->
                    <div class="status-section">
                        <span class="badge badge-status <?= $badgeClassPago ?>">
                            <i class="bi <?= $iconPago ?>"></i>
                            <?= $textoPago ?>
                        </span>
                        <span class="badge badge-status <?= $pendientes ? 'bg-warning text-dark' : 'bg-success' ?>">
                            <i class="bi <?= $pendientes ? 'bi-hourglass-split' : 'bi-check-circle-fill' ?>"></i>
                            <?= $pendientes ? 'Examen Pendiente' : 'Examen Completado' ?>
                        </span>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="actions-card">
                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                        <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-card-action" title="Ver cotización">
                            <i class="bi bi-eye"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista'): ?>
                        <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-card-action" title="Editar o agregar resultados">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (($rol === 'admin' || $rol === 'recepcionista') && $saldo > 0): ?>
                        <a href="dashboard.php?vista=pago_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-warning btn-card-action" title="Registrar pago">
                            <i class="bi bi-cash-coin"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($rol === 'admin'): ?>
                        <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-card-action" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                        <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-card-action" title="Descargar PDF de todos los resultados" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
        <!-- Paginación para cards -->
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php
                    // Mantener todos los parámetros activos en la URL
                    $params = $_GET;
                    foreach ($params as $key => $value) {
                        if ($key === 'pagina') unset($params[$key]);
                    }
                    $baseUrl = '?' . http_build_query($params);
                ?>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl . ($baseUrl !== '?' ? '&' : '') . 'pagina=' . $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="text-muted">
                <i class="bi bi-inbox display-1 d-block mb-3"></i>
                <h5>No hay cotizaciones registradas</h5>
                <p>Cuando se registren cotizaciones aparecerán aquí.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

</div>
<!-- DataTables y dependencias -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaCotizaciones').DataTable({
            "pageLength": 3,
            "lengthMenu": [3, 5, 10, 25, 50],
            "order": [], // No aplicar ordenamiento inicial, mantener el orden de la consulta
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            "responsive": true,
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "columnDefs": [
                { "orderable": false, "targets": [9] } // Deshabilitar ordenamiento en columna de acciones
            ]
        });
    });
</script>