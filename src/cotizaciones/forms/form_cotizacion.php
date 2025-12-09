<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

$rol = $_SESSION['rol'] ?? null;
$isEdit = isset($_GET['edit']) && $_GET['edit'] == 1 && isset($_GET['id']);
$cotizacionData = null;
$examenesCotizacion = [];

// Exámenes catálogo
$stmt = $pdo->query("SELECT id, codigo, nombre, descripcion, tiempo_respuesta, preanalitica_cliente, observaciones, precio_publico FROM examenes WHERE vigente = 1 ORDER BY nombre");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$examenes_json = json_encode($examenes);

// Empresas y convenios
$empresas = [];
$convenios = [];
if ($rol === 'admin' || $rol === 'recepcionista') {
    $stmtEmp = $pdo->query("SELECT id, razon_social, nombre_comercial, descuento FROM empresas WHERE estado = 1 ORDER BY nombre_comercial");
    $empresas = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    $stmtConv = $pdo->query("SELECT id, nombre, descuento FROM convenios ORDER BY nombre");
    $convenios = $stmtConv->fetchAll(PDO::FETCH_ASSOC);
}

// Si es edición, cargar datos de la cotización y sus exámenes
if ($isEdit) {
    $id_cotizacion = intval($_GET['id']);
    // Cotización principal
    $stmtCot = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $stmtCot->execute([$id_cotizacion]);
    $cotizacionData = $stmtCot->fetch(PDO::FETCH_ASSOC);
    if (!$cotizacionData) {
        echo "<div class='alert alert-danger mt-4'>No se encontró la cotización a editar.</div>";
        exit;
    }
    // Exámenes de la cotización
    $stmtDet = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = ?");
    $stmtDet->execute([$id_cotizacion]);
    $examenesCotizacion = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
    // Cliente asociado
    $id_cliente = $cotizacionData['id_cliente'];
} else {
    // Alta normal
    if ($rol === 'cliente') {
        $id_cliente = $_SESSION['cliente_id'] ?? '';
    } else {
        $id_cliente = isset($_GET['id']) ? intval($_GET['id']) : '';
    }
}

// Validar cliente
if (empty($id_cliente)) {
    echo "<div class='alert alert-danger mt-4'>No se pudo identificar al cliente. Por favor, vuelve al listado de clientes.</div>";
    exit;
}

// Descuentos
$descuento_cliente = 0;
if ($id_cliente) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM clientes WHERE id = ?");
    $stmtDesc->execute([$id_cliente]);
    $descuento_cliente = $stmtDesc->fetchColumn() ?: 0;
}
$descuento_empresa_convenio = 0;
if ($rol === 'empresa' && !empty($_SESSION['empresa_id'])) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM empresas WHERE id = ?");
    $stmtDesc->execute([$_SESSION['empresa_id']]);
    $descuento_empresa_convenio = $stmtDesc->fetchColumn() ?: 0;
} elseif ($rol === 'convenio' && !empty($_SESSION['convenio_id'])) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM convenios WHERE id = ?");
    $stmtDesc->execute([$_SESSION['convenio_id']]);
    $descuento_empresa_convenio = $stmtDesc->fetchColumn() ?: 0;
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- jQuery debe ir antes de cualquier script que use '$' -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<style>
    /* Forzar color de texto en las opciones del dropdown de Select2 al hacer hover */
.select2-container--default .select2-results__option--highlighted,
.select2-container--default .select2-results__option--highlighted * {
    color: red !important; /* Azul oscuro, visible */
}


/* Forzar el color blanco en los badges de precio del dropdown de Select2 */
.select2-results__option .badge.bg-success {
    color: #fff !important;
}

/* Estilos modernos para el formulario de cotización */
.cotizacion-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.cotizacion-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
    margin-bottom: 100px; /* Espacio para footer fijo */
}

.cotizacion-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 0;
    position: relative;
    overflow: hidden;
}

.cotizacion-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.cotizacion-header h3 {
    margin: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cotizacion-body {
    padding: 2rem;
}

.section-header {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #2196f3;
}

.section-header h5 {
    margin: 0;
    color: #1565c0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group-modern {
    margin-bottom: 1.5rem;
}

.form-label-modern {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control-modern {
    border: 2px solid #e3e6f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: #fafbfc;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
}

.form-select-modern {
    border: 2px solid #e3e6f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    background: #fafbfc;
    transition: all 0.3s ease;
}

.form-select-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
}

.alert-modern {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #ffc107;
    color: #856404;
}

.examenes-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid #e3e6f0;
}

.examenes-table .table {
    margin: 0;
}

.examenes-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.examenes-table thead th {
    border: none;
    padding: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.examenes-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
}

.examenes-table tbody tr:hover {
    background-color: #e3f0ff !important; /* Azul claro, visible */
    color: #1a237e !important; /* Texto azul oscuro, visible */
    transition: background 0.2s, color 0.2s;
}

/* Forzar el color de texto de todos los elementos dentro de la fila al hacer hover */
.examenes-table tbody tr:hover td,
.examenes-table tbody tr:hover td * {
    color: #1a237e !important;
}

/* Estilos especiales para campos de precio */
.precioExamen {
    text-align: right;
    font-weight: 600;
    color: #28a745;
    background: rgba(40, 167, 69, 0.05);
    border-left: none;
}

.precioExamen:focus {
    background: white;
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    color: #2c3e50;
    border-left: none;
}

.precioExamen::placeholder {
    color: #6c757d;
    font-weight: normal;
}

.cantidadExamen {
    text-align: center;
    font-weight: 600;
}

/* Estilos para el input-group de precio */
.input-group .input-group-text {
    border-radius: 12px 0 0 12px;
    font-weight: 600;
    font-size: 0.9rem;
}

.input-group .precioExamen {
    border-radius: 0 12px 12px 0;
}

/* Mejorar el grupo completo */
.input-group:focus-within .input-group-text {
    border-color: #667eea;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

/* Tooltip para ayuda */
.precioExamen[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
}

.btn-modern {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-success-modern {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-success-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    color: white;
}

.btn-danger-modern {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn-info-modern {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.btn-secondary-modern {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
}

.footer-cotizacion {
    background: white;
    border-top: 1px solid #e3e6f0;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
    z-index: 1040;
}

.total-section {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    border: 2px solid #28a745;
}

.total-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #155724;
}

.descuento-badge {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: #212529;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.sin-examenes {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.sin-examenes i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Select2 personalizado */
.select2-container--default .select2-selection--single {
    border: 2px solid #e3e6f0;
    border-radius: 12px;
    height: 48px;
    background: #fafbfc;
    transition: all 0.3s ease;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 44px;
    padding-left: 1rem;
    color: #2c3e50;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
}

/* Dropdown del Select2 */
.select2-dropdown {
    border: 2px solid #667eea;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-top: none;
    margin-top: -1px;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 0.5rem;
    margin: 0.5rem;
    width: calc(100% - 1rem);
    background: #fafbfc;
}

.select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 0.1rem rgba(102, 126, 234, 0.25);
}

.select2-container--default .select2-results__option {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.select2-container--default .select2-results__option--highlighted {
    background-color: rgba(102, 126, 234, 0.1);
    color: #212529;
}

.select2-container--default .select2-results__option:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

/* Mejorar apariencia de la búsqueda */
.select2-search--dropdown {
    padding: 0.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #e3e6f0;
}

.select2-results__message {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Estilos para las opciones formateadas */
.select2-results__option .badge {
    font-size: 0.75rem;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d;
}

/* Animaciones */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

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

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .cotizacion-container {
        padding: 0.5rem 0;
    }
    .cotizacion-body {
        padding: 0.5rem;
    }
    .footer-cotizacion .container {
        flex-direction: column;
        gap: 1rem;
    }
    .total-section {
        text-align: center;
    }
    .examenes-table {
        border-radius: 10px;
        box-shadow: none;
        margin-bottom: 0.5rem;
    }
    .examenes-table .table {
        font-size: 0.92rem;
    }
    .examenes-table thead th, .examenes-table tbody td {
        padding: 0.5rem 0.3rem;
    }
    .examenes-table thead th:nth-child(2),
    .examenes-table tbody td:nth-child(2) {
        width: 65px !important;
        min-width: 55px !important;
        max-width: 75px !important;
        text-align: center;
    }
    .examenes-table tbody td {
        vertical-align: middle;
    }
    .btn-remove {
        min-width: 32px;
        min-height: 32px;
        padding: 0.25rem 0.5rem;
        font-size: 1.1rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
<div class="cotizacion-container">
    <div class="container">
        <div class="cotizacion-card card">
            <div class="cotizacion-header">
                <h3>
                    <i class="bi bi-file-earmark-medical"></i>
                    <?php if ($isEdit): ?>Editar Cotización<?php else: ?>Nueva Cotización<?php endif; ?>
                </h3>
                <p class="mb-0 opacity-75">
                    <?php if ($isEdit): ?>Modifica los datos y exámenes de la cotización seleccionada<?php else: ?>Selecciona los exámenes y configura los detalles de la cotización<?php endif; ?>
                </p>
            </div>
            
            <div class="cotizacion-body">
                <div class="alert-modern fade-in-up">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Importante:</strong> Después de guardar la cotización, podrás agendar la cita para la toma de muestra.
                </div>

                <form action="<?= BASE_URL ?>dashboard.php?action=<?= $isEdit ? 'editar_cotizacion' : 'crear_cotizacion' ?>" method="POST" id="formCotizacion">

                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id_cotizacion" value="<?= htmlspecialchars($cotizacionData['id']) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="id_cliente" value="<?= htmlspecialchars($id_cliente) ?>">
                    <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="<?= $descuento_empresa_convenio ?: $descuento_cliente ?>">

                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                        <div class="section-header fade-in-up">
                            <h5>
                                <i class="bi bi-person-gear"></i>
                                Configuración de Cliente
                            </h5>
                        </div>

                        <div class="row fade-in-up">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label for="tipoCliente" class="form-label-modern">
                                        <i class="bi bi-tag"></i>
                                        Tipo de cliente
                                    </label>
                                    <select id="tipoCliente" name="tipo_usuario" class="form-select form-select-modern" required>
                                        <option value="">Seleccione...</option>
                                        <option value="cliente" <?= ($isEdit && $cotizacionData['tipo_usuario'] == 'cliente') ? 'selected' : '' ?>>
                                            <i class="bi bi-person"></i>
                                            Particular
                                        </option>
                                        <option value="empresa" <?= ($isEdit && $cotizacionData['tipo_usuario'] == 'empresa') ? 'selected' : '' ?>>
                                            <i class="bi bi-building"></i>
                                            Empresa
                                        </option>
                                        <option value="convenio" <?= ($isEdit && $cotizacionData['tipo_usuario'] == 'convenio') ? 'selected' : '' ?>>
                                            <i class="bi bi-handshake"></i>
                                            Convenio
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group-modern d-none" id="selectEmpresa">
                                    <label for="empresa" class="form-label-modern">
                                        <i class="bi bi-building"></i>
                                        Empresa
                                    </label>
                                    <select id="empresa" name="id_empresa" class="form-select form-select-modern">
                                        <option value="">Seleccione empresa...</option>
                                        <?php foreach ($empresas as $emp): ?>
                                            <option value="<?= $emp['id'] ?>" data-descuento="<?= $emp['descuento'] ?>" <?= ($isEdit && $cotizacionData['id_empresa'] == $emp['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                                                <?php if ($emp['descuento'] > 0): ?>
                                                    <span class="descuento-badge"><?= $emp['descuento'] ?>% desc.</span>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group-modern d-none" id="selectConvenio">
                                    <label for="convenio" class="form-label-modern">
                                        <i class="bi bi-handshake"></i>
                                        Convenio
                                    </label>
                                    <select id="convenio" name="id_convenio" class="form-select form-select-modern">
                                        <option value="">Seleccione convenio...</option>
                                        <?php foreach ($convenios as $conv): ?>
                                            <option value="<?= $conv['id'] ?>" data-descuento="<?= $conv['descuento'] ?>" <?= ($isEdit && $cotizacionData['id_convenio'] == $conv['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($conv['nombre']) ?>
                                                <?php if ($conv['descuento'] > 0): ?>
                                                    <span class="descuento-badge"><?= $conv['descuento'] ?>% desc.</span>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="section-header fade-in-up">
                        <h5>
                            <i class="bi bi-search"></i>
                            Selección de Exámenes
                        </h5>
                    </div>

                    <div class="form-group-modern fade-in-up">
                        <label for="buscadorExamen" class="form-label-modern">
                            <i class="bi bi-flask"></i>
                            Buscar examen
                        </label>
                        <div class="position-relative">
                            <select id="buscadorExamen" class="form-select" style="width:100%;">
                                <option value="">Haz clic aquí y escribe el nombre del examen...</option>
                                <?php foreach ($examenes as $ex): ?>
                                    <option value="<?= $ex['id']; ?>"
                                        data-nombre="<?= htmlspecialchars($ex['nombre']); ?>"
                                        data-precio="<?= $ex['precio_publico']; ?>">
                                        <?= htmlspecialchars($ex['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-lightbulb text-warning"></i>
                                <strong>Tip:</strong> Haz clic en el campo y escribe para buscar. Puedes buscar por nombre del examen.
                            </small>
                        </div>
                    </div>

                    <div class="section-header fade-in-up">
                        <h5>
                            <i class="bi bi-list-check"></i>
                            Exámenes Seleccionados
                        </h5>
                    </div>

                    <div id="examenes-seleccionados" class="fade-in-up"></div>
                <!-- El bloque de precarga de exámenes en modo edición se movió al final del archivo para asegurar que jQuery esté cargado -->
                </form>
                </div>
                </div>
                </div>
                </div>
                
                <!-- Footer fijo mejorado -->


                <!-- Precarga de exámenes seleccionados en modo edición: debe ir después de todos los scripts externos -->
                <?php if ($isEdit): ?>
                <script>
                const examenesCotizacion = <?php echo json_encode($examenesCotizacion); ?>;
                $(document).ready(function() {
                    // Esperar a que examenesData esté disponible
                    if (typeof examenesData === 'undefined') {
                        console.error('examenesData no está disponible');
                        return;
                    }
                    console.log('examenesCotizacion:', examenesCotizacion);
                    console.log('examenesData:', examenesData);
                    if (Array.isArray(examenesCotizacion) && examenesCotizacion.length > 0) {
                        examenesSeleccionados = examenesCotizacion.map(function(ex) {
                            let info = examenesData.find(e => e.id == ex.id_examen);
                            return {
                                id: ex.id_examen,
                                codigo: info ? info.codigo : '',
                                nombre: ex.nombre_examen || (info ? info.nombre : ''),
                                precio_unitario: parseFloat(ex.precio_unitario), // SIEMPRE el precio editado
                                precio_publico: parseFloat(ex.precio_unitario), // Para edición, igual al editado
                                cantidad: parseInt(ex.cantidad),
                                descripcion: info ? info.descripcion : '',
                                tiempo_respuesta: info ? info.tiempo_respuesta : '',
                                preanalitica_cliente: info ? info.preanalitica_cliente : '',
                                observaciones: info ? info.observaciones : ''
                            };
                        });
                    } else {
                        examenesSeleccionados = [];
                    }
                    renderizarLista();
                    // Mostrar empresa/convenio si corresponde
                    <?php if ($cotizacionData['tipo_usuario'] == 'empresa'): ?>
                        $('#tipoCliente').val('empresa').trigger('change');
                        $('#empresa').val('<?= $cotizacionData['id_empresa'] ?>').trigger('change');
                    <?php elseif ($cotizacionData['tipo_usuario'] == 'convenio'): ?>
                        $('#tipoCliente').val('convenio').trigger('change');
                        $('#convenio').val('<?= $cotizacionData['id_convenio'] ?>').trigger('change');
                    <?php else: ?>
                        $('#tipoCliente').val('cliente').trigger('change');
                    <?php endif; ?>
                });
                </script>
                <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer fijo mejorado -->
<div class="fixed-bottom footer-cotizacion p-3" id="footerCotizacion">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div class="total-section">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-calculator text-success"></i>
                <div>
                    <small class="text-muted">Total a pagar:</small>
                    <div class="total-amount" id="totalCotizacion">S/. 0.00</div>
                </div>
                <div id="descuentoInfo" class="d-none">
                    <span class="descuento-badge">
                        <i class="bi bi-tag"></i>
                        <span id="descuentoTexto">0% desc.</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" form="formCotizacion" class="btn btn-success-modern btn-modern">
                <i class="bi bi-check-circle"></i>
                <?php echo $isEdit ? 'Actualizar Cotización' : 'Guardar Cotización'; ?>
            </button>
            <a href="javascript:history.back()" class="btn btn-secondary-modern btn-modern">
                <i class="bi bi-x-circle"></i>
                Cancelar
            </a>
        </div>
    </div>
</div>
<!-- Modal mejorado para detalles del examen -->
<div class="modal fade" id="modalDetalleExamen" tabindex="-1" aria-labelledby="modalDetalleExamenLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="modalDetalleExamenLabel">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Detalle del Examen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detalleExamenBody" style="padding: 2rem;">
                <!-- Detalle dinámico -->
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var rolUsuario = '<?= $rol ?>';
let examenesData = <?= $examenes_json ?>;
let examenesSeleccionados = [];
let descuentoCliente = <?= $descuento_cliente ?>;
let descuentoActual = <?= $descuento_empresa_convenio ?: $descuento_cliente ?>;
let isEdit = <?= $isEdit ? 'true' : 'false' ?>;

// Inicializar descuento y precios al cargar como empresa/convenio
$(document).ready(function() {
    // Inicializar Select2 para el buscador de exámenes con configuración mejorada
    $('#buscadorExamen').select2({
        placeholder: "Escribe el nombre del examen que buscas...",
        allowClear: true,
        width: '100%',
        minimumInputLength: 0, // Permitir búsqueda desde el primer carácter
        closeOnSelect: false, // No cerrar automáticamente al seleccionar
        templateResult: formatExamenOption, // Formato personalizado para opciones
        templateSelection: formatExamenSelection, // Formato para selección
        language: {
            noResults: function() {
                return "No se encontraron exámenes";
            },
            searching: function() {
                return "Buscando exámenes...";
            },
            inputTooShort: function() {
                return "Escribe para buscar exámenes";
            }
        }
    });

    // Abrir automáticamente cuando se hace foco en el campo
    $('#buscadorExamen').on('select2:opening', function(e) {
        // Asegurar que el campo de búsqueda tenga foco inmediatamente
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 50);
    });

    // Limpiar el campo después de seleccionar un examen
    $('#buscadorExamen').on('select2:select', function(e) {
        // Pequeño delay para que se procese la selección
        setTimeout(function() {
            $('#buscadorExamen').val(null).trigger('change');
            $('.select2-search__field').attr('placeholder', 'Buscar otro examen...');
                // Enfocar y seleccionar todo el texto para nueva búsqueda
                var $searchField = $('.select2-search__field');
                if ($searchField.length) {
                    $searchField.focus();
                    $searchField[0].select();
                }
        }, 100);
    });

        // Enfocar automáticamente el campo de búsqueda al abrir (reforzado)
        $('#buscadorExamen').on('select2:open', function() {
            setTimeout(function() {
                var $searchField = $('.select2-search__field');
                if ($searchField.length) {
                    $searchField.focus();
                    $searchField[0].select(); // Selecciona el texto si hay
                }
            }, 10);
        });

    // Mejorar la experiencia al hacer clic en el campo
    $('.select2-selection').on('click', function() {
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 150);
    });

    // Auto-abrir el dropdown al hacer foco
    $('#buscadorExamen').on('focus', function() {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            return;
        }
        setTimeout(function() {
            $('#buscadorExamen').select2('open');
        }, 100);
    });
    
    if (rolUsuario === 'empresa' || rolUsuario === 'convenio') {
        actualizarDescuento();
        renderizarLista();
    }
    
    // Agregar animaciones a elementos
    $('.fade-in-up').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });
});

// Función para formatear las opciones en el dropdown
function formatExamenOption(examen) {
    if (!examen.id) {
        return examen.text;
    }
    
    // Obtener datos del examen
    let examenData = examenesData.find(ex => ex.id == examen.id);
    if (!examenData) {
        return examen.text;
    }

    var $option = $(
        '<div class="d-flex justify-content-between align-items-center">' +
            '<div>' +
                '<div class="fw-bold">' + examenData.nombre + '</div>' +
                '<small class="text-muted">Código: ' + (examenData.codigo || 'N/A') + '</small>' +
            '</div>' +
            '<div class="text-end">' +
                '<span class="badge bg-success">S/. ' + parseFloat(examenData.precio_publico).toFixed(2) + '</span>' +
            '</div>' +
        '</div>'
    );
    return $option;
}

// Función para formatear la selección
function formatExamenSelection(examen) {
    return examen.text || "Buscar examen...";
}

// Manejo de tipo de cliente y descuento
$('#tipoCliente').on('change', function() {
    let tipo = $(this).val();
    $('#selectEmpresa, #selectConvenio').addClass('d-none');
    $('#empresa, #convenio').prop('required', false);
    if (tipo === 'empresa') {
        $('#selectEmpresa').removeClass('d-none');
        $('#empresa').prop('required', true);
        $('#convenio').val('');
    } else if (tipo === 'convenio') {
        $('#selectConvenio').removeClass('d-none');
        $('#convenio').prop('required', true);
        $('#empresa').val('');
    } else {
        $('#empresa, #convenio').val('');
    }
    actualizarDescuento();
});

// Detectar selección de empresa/convenio y actualizar descuento
$('#empresa').on('change', actualizarDescuento);
$('#convenio').on('change', actualizarDescuento);

function actualizarDescuento() {
    // Prioridad: si el usuario es empresa o convenio, usar ese descuento
    if (rolUsuario === 'empresa' || rolUsuario === 'convenio') {
        descuentoActual = <?= $descuento_empresa_convenio ?: 0 ?>;
    } else {
        let tipo = $('#tipoCliente').val();
        descuentoActual = 0;
        if (tipo === 'empresa') {
            let desc = $('#empresa option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
        } else if (tipo === 'convenio') {
            let desc = $('#convenio option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
        } else if (tipo === 'cliente' || tipo === undefined) {
            descuentoActual = descuentoCliente;
        }
    }
    $('#descuento_aplicado').val(descuentoActual);
    // Solo aplicar descuento si NO estamos en modo edición
    if (isEdit) {
        // En edición, los precios ya están descontados, no recalcular
        renderizarLista();
    } else {
        // En creación, sí aplicar descuento
        examenesSeleccionados.forEach((ex, idx) => {
            ex.precio_unitario = aplicarDescuento(ex.precio_publico, descuentoActual);
        });
        renderizarLista();
    }
}

function aplicarDescuento(precio, descuento) {
    const precioNum = Number(precio);
    const descuentoNum = Number(descuento);
    const resultado = precioNum * (1 - (descuentoNum / 100));
    return resultado.toFixed(2);
}

// Select2 para buscar exámenes
$('#buscadorExamen').select2({
    placeholder: "Escribe para buscar un examen...",
    allowClear: true,
    width: '100%'
});


$('#buscadorExamen').on('select2:select', function(e) {
    let id = e.params.data.id;
    let examen = examenesData.find(ex => ex.id == id);
    let existente = examenesSeleccionados.find(ex => ex.id == id);
    if (!existente) {
        let precioPublico = Number(examen.precio_publico);
        let precioConDescuento = aplicarDescuento(precioPublico, descuentoActual);
        examenesSeleccionados.push({
            ...examen,
            precio_unitario: precioConDescuento,
            cantidad: 1
        });
    } else {
        existente.cantidad += 1;
    }
    renderizarLista();
    $(this).val('').trigger('change');
});

// Cambiar cantidad
$(document).on('input', '.cantidadExamen', function() {
    let idx = $(this).data('idx');
    examenesSeleccionados[idx].cantidad = parseInt($(this).val()) || 1;
    renderizarLista();
});

// Cambiar precio manualmente (solo admin/recep) - Optimizado para escritura completa
$(document).on('keyup blur', '.precioExamen', function(e) {
    let idx = $(this).data('idx');
    let inputValue = $(this).val();
    
    // Solo procesar en ciertos eventos para evitar actualizaciones prematuras
    let shouldUpdate = false;
    
    if (e.type === 'blur') {
        // Siempre actualizar al perder el foco
        shouldUpdate = true;
    } else if (e.type === 'keyup') {
        // Solo actualizar en Enter, Tab, o después de una pausa en la escritura
        if (e.key === 'Enter' || e.key === 'Tab') {
            shouldUpdate = true;
            $(this).blur(); // Quitar foco para confirmar el cambio
        }
    }
    
    if (shouldUpdate) {
        // Limpiar el valor: permitir solo números, puntos y hasta 2 decimales
        let cleanValue = inputValue.replace(/[^\d.]/g, '');
        
        // Asegurar que solo haya un punto decimal
        let parts = cleanValue.split('.');
        if (parts.length > 2) {
            cleanValue = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Limitar a 2 decimales
        if (parts[1] && parts[1].length > 2) {
            cleanValue = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        // Actualizar el campo si fue modificado
        if (inputValue !== cleanValue) {
            $(this).val(cleanValue);
        }
        
        // Convertir a número para cálculos
        let nuevoPrecio = parseFloat(cleanValue);
        if (isNaN(nuevoPrecio) || nuevoPrecio < 0) {
            nuevoPrecio = 0;
        }
        
        // Actualizar el precio en el array
        examenesSeleccionados[idx].precio_unitario = nuevoPrecio;
        
        // Re-renderizar para actualizar totales
        renderizarLista();
    }
});

// Validación especial al perder el foco para asegurar formato correcto
$(document).on('blur', '.precioExamen', function() {
    let value = parseFloat($(this).val());
    if (isNaN(value) || value < 0) {
        value = 0;
    }
    $(this).val(value.toFixed(2));
});

// Seleccionar todo el texto al hacer foco para facilitar edición
$(document).on('focus', '.precioExamen', function() {
    $(this).select();
});

// Agregar debounce para actualización con retraso (alternativa más suave)
let precioTimeout;
$(document).on('input', '.precioExamen', function() {
    let $input = $(this);
    let idx = $input.data('idx');
    
    // Limpiar timeout anterior
    clearTimeout(precioTimeout);
    
    // Establecer nuevo timeout para actualizar después de 1 segundo de inactividad
    precioTimeout = setTimeout(function() {
        let inputValue = $input.val();
        
        // Limpiar el valor
        let cleanValue = inputValue.replace(/[^\d.]/g, '');
        let parts = cleanValue.split('.');
        if (parts.length > 2) {
            cleanValue = parts[0] + '.' + parts.slice(1).join('');
        }
        if (parts[1] && parts[1].length > 2) {
            cleanValue = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        if (inputValue !== cleanValue) {
            $input.val(cleanValue);
        }
        
        let nuevoPrecio = parseFloat(cleanValue);
        if (isNaN(nuevoPrecio) || nuevoPrecio < 0) {
            nuevoPrecio = 0;
        }
        
        examenesSeleccionados[idx].precio_unitario = nuevoPrecio;
        renderizarLista();
    }, 1000); // 1 segundo de retraso
});

// Quitar examen
$(document).on('click', '.btn-remove', function() {
    let idx = $(this).data('idx');
    examenesSeleccionados.splice(idx, 1);
    renderizarLista();
});

// Ver detalles
$(document).on('click', '.btn-detalle', function() {
    let idx = $(this).data('idx');
    let ex = examenesSeleccionados[idx];
    let detalle = `
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title text-primary">
                        <i class="bi bi-tag me-2"></i>Información Básica
                    </h6>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-bold">Código:</td>
                            <td>${ex.codigo || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Nombre:</td>
                            <td>${ex.nombre}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Precio:</td>
                            <td class="text-success fw-bold">S/. ${parseFloat(ex.precio_unitario).toFixed(2)}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title text-info">
                        <i class="bi bi-clock me-2"></i>Tiempo y Proceso
                    </h6>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-bold">Tiempo de respuesta:</td>
                            <td>${ex.tiempo_respuesta || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Preanalítica:</td>
                            <td>${ex.preanalitica_cliente || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title text-warning">
                        <i class="bi bi-file-text me-2"></i>Descripción y Observaciones
                    </h6>
                    <div class="mb-3">
                        <strong>Descripción:</strong>
                        <p class="mb-0 mt-1">${ex.descripcion || 'No disponible'}</p>
                    </div>
                    <div>
                        <strong>Observaciones:</strong>
                        <p class="mb-0 mt-1">${ex.observaciones || 'No hay observaciones'}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
    $('#detalleExamenBody').html(detalle);
    let modal = new bootstrap.Modal(document.getElementById('modalDetalleExamen'));
    modal.show();
});

function renderizarLista() {
    let html = '';
    let total = 0;
    if (examenesSeleccionados.length === 0) {
        html = `
        <div class="sin-examenes">
            <i class="bi bi-clipboard-x"></i>
            <h5>No hay exámenes seleccionados</h5>
            <p class="text-muted">Usa el buscador de arriba para agregar exámenes a la cotización</p>
        </div>`;
    } else {
        html += `
        <div class="examenes-table">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><i class="bi bi-flask me-2"></i>Examen</th>
                        <th style="width:120px;"><i class="bi bi-123 me-2"></i>Cantidad</th>
                        <th style="width:140px;"><i class="bi bi-currency-dollar me-2"></i>Precio (S/.)</th>
                        <th style="width:120px;"><i class="bi bi-calculator me-2"></i>Subtotal</th>
                        <th style="width:160px;"><i class="bi bi-gear me-2"></i>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
        
        examenesSeleccionados.forEach((ex, idx) => {
            let precio = parseFloat(ex.precio_unitario);
            let subtotal = precio * ex.cantidad;
            total += subtotal;
            html += `
            <tr class="fade-in-up">
                <td>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-flask text-primary me-2"></i>
                        <div>
                            <strong>${ex.nombre}</strong>
                            <br>
                            <small class="text-muted">Código: ${ex.codigo || 'N/A'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" min="1" class="form-control form-control-modern cantidadExamen" 
                           data-idx="${idx}" value="${ex.cantidad}">
                </td>
                <td>
                    ${
                        (rolUsuario === 'admin' || rolUsuario === 'recepcionista')
                            ? `<div class="input-group">
                                <span class="input-group-text bg-success text-white">S/.</span>
                                <input type="text" class="form-control form-control-modern precioExamen" 
                                      data-idx="${idx}" value="${precio.toFixed(2)}" 
                                      placeholder="0.00" 
                                      pattern="[0-9]+(\.[0-9]{1,2})?" 
                                      title="Ingresa el precio (ej: 25.50)">
                               </div>`
                            : `<div class="form-control-plaintext fw-bold text-success">S/. ${precio.toFixed(2)}</div>`
                    }
                    <input type="hidden" name="examenes[]" value="${ex.id}">
                    <input type="hidden" name="cantidades[]" value="${ex.cantidad}">
                    <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
                </td>
                <td>
                    <div class="fw-bold text-success fs-6">S/. ${subtotal.toFixed(2)}</div>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-info-modern btn-modern btn-sm btn-detalle" 
                                data-idx="${idx}" title="Ver detalles">
                            <i class="bi bi-info-circle"></i>
                        </button>
                        <button type="button" class="btn btn-danger-modern btn-modern btn-sm btn-remove" 
                                data-idx="${idx}" title="Quitar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
    }
    
    $('#examenes-seleccionados').html(html);
    $('#totalCotizacion').text('S/. ' + total.toFixed(2));
    
    // Actualizar información de descuento
    if (descuentoActual > 0) {
        $('#descuentoInfo').removeClass('d-none');
        $('#descuentoTexto').text(descuentoActual + '% desc.');
    } else {
        $('#descuentoInfo').addClass('d-none');
    }
    
    // Actualizar los campos hidden con los valores actuales
    examenesSeleccionados.forEach((ex, idx) => {
        $(`input[name="cantidades[]"]:eq(${idx})`).val(ex.cantidad);
        $(`input[name="precios[]"]:eq(${idx})`).val(parseFloat(ex.precio_unitario).toFixed(2));
    });
}
</script>
