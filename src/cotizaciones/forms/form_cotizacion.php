<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/currency.php';

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$key];
}

$currencyCfg = currency_get_config($pdo);
$currencySymbol = $currencyCfg['symbol'];

$rol = $_SESSION['rol'] ?? null;
$operacionContext = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [
    'modo_operativo' => 'PARTICULAR',
    'es_particular' => true,
    'es_sis' => false,
    'es_mixto' => false,
];
$derivacionServicioHabilitada = !empty($operacionContext['es_sis']);
$modoOperativoActual = strtoupper((string)($operacionContext['modo_operativo'] ?? 'PARTICULAR'));
$mostrarCoberturaSis = in_array($modoOperativoActual, ['SIS', 'MIXTO'], true);
$sisForzado = ($modoOperativoActual === 'SIS');
$sisCotizacionExistente = false;
$isEdit = isset($_GET['edit']) && $_GET['edit'] == 1 && isset($_GET['id']);
$cotizacionData = null;
$examenesCotizacion = [];
$emitirComprobante = 0;
$tipoComprobanteCliente = 'boleta';
$receptorRuc = '';
$receptorRazonSocial = '';
$receptorDireccion = '';
$sisNumeroAfiliacionValor = '';
$sisNumeroAutorizacionValor = '';
$sisNumeroFuaValor = '';
$sisObservacionesValor = '';
$hasDetalleReferenciadoCols = false;

// Exámenes catálogo
$hasExamenPrecioConvenio = tableHasColumn($pdo, 'examenes', 'precio_convenio');
$selectPrecioConvenio = $hasExamenPrecioConvenio ? 'precio_convenio' : 'NULL AS precio_convenio';
$stmt = $pdo->query("SELECT id, codigo, nombre, descripcion, tiempo_respuesta, preanalitica_cliente, observaciones, precio_publico, {$selectPrecioConvenio} FROM examenes WHERE vigente = 1 ORDER BY nombre");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$examenes_json = json_encode($examenes);

// Empresas y convenios
$empresas = [];
$convenios = [];
$hasEmpresaUsarPrecioConvenio = tableHasColumn($pdo, 'empresas', 'usar_precio_convenio');
$hasConvenioUsarPrecioConvenio = tableHasColumn($pdo, 'convenios', 'usar_precio_convenio');
$hasClienteUsarPrecioConvenio = tableHasColumn($pdo, 'clientes', 'usar_precio_convenio');
if ($rol === 'admin' || $rol === 'recepcionista') {
    $selectUsarEmp = $hasEmpresaUsarPrecioConvenio ? 'usar_precio_convenio' : '0 AS usar_precio_convenio';
    $selectUsarConv = $hasConvenioUsarPrecioConvenio ? 'usar_precio_convenio' : '0 AS usar_precio_convenio';
    $stmtEmp = $pdo->query("SELECT id, razon_social, nombre_comercial, descuento, {$selectUsarEmp} FROM empresas WHERE estado = 1 ORDER BY nombre_comercial");
    $empresas = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    $stmtConv = $pdo->query("SELECT id, nombre, descuento, {$selectUsarConv} FROM convenios ORDER BY nombre");
    $convenios = $stmtConv->fetchAll(PDO::FETCH_ASSOC);
}

$servicios = [];
$servicioSeleccionado = 0;
$profesionalSolicitanteSeleccionado = 0;
$profesionalSolicitanteSnapshot = '';
$profesionalSolicitanteTipoSnapshot = '';
$profesionalSolicitanteRegistroSnapshot = '';
$servicioProfesionalesMap = [];

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
    $profesionalSolicitanteSeleccionado = (int)($cotizacionData['profesional_solicitante_id'] ?? 0);
    $profesionalSolicitanteSnapshot = trim((string)($cotizacionData['profesional_solicitante_nombre'] ?? ''));
    $profesionalSolicitanteTipoSnapshot = trim((string)($cotizacionData['profesional_solicitante_tipo'] ?? ''));
    $profesionalSolicitanteRegistroSnapshot = trim((string)($cotizacionData['profesional_solicitante_registro'] ?? ''));
    $emitirComprobante = (int)($cotizacionData['emitir_comprobante'] ?? 1);

    // Particular con Factura: precargar si existe en BD
    if (!empty($cotizacionData['comprobante_tipo']) && in_array(strtolower((string)$cotizacionData['comprobante_tipo']), ['boleta','factura'], true)) {
        $tipoComprobanteCliente = strtolower((string)$cotizacionData['comprobante_tipo']);
    } else {
        // Compatibilidad con registros antiguos
        $tipoComprobanteCliente = !empty($cotizacionData['id_empresa']) ? 'factura' : 'boleta';
    }
    $receptorRuc = (string)($cotizacionData['receptor_numero_documento'] ?? '');
    $receptorRazonSocial = (string)($cotizacionData['receptor_razon_social'] ?? '');
    $receptorDireccion = (string)($cotizacionData['receptor_direccion'] ?? '');
    $sisCotizacionExistente = ((int)($cotizacionData['es_sis'] ?? 0) === 1);
    $sisNumeroAfiliacionValor = (string)($cotizacionData['sis_numero_afiliacion'] ?? '');
    $sisNumeroAutorizacionValor = (string)($cotizacionData['sis_numero_autorizacion'] ?? '');
    $sisNumeroFuaValor = (string)($cotizacionData['sis_numero_fua'] ?? '');
    $sisObservacionesValor = (string)($cotizacionData['sis_observaciones'] ?? '');

    if ($sisCotizacionExistente) {
        try {
            $tablaSisExiste = (bool)$pdo->query("SHOW TABLES LIKE 'sis_coberturas'")->fetchColumn();
            if ($tablaSisExiste) {
                $sisRow = null;
                $sisCoberturaId = (int)($cotizacionData['sis_cobertura_id'] ?? 0);
                if ($sisCoberturaId > 0) {
                    $stmtSis = $pdo->prepare("SELECT numero_afiliacion, numero_autorizacion, numero_fua, observaciones FROM sis_coberturas WHERE id = ? LIMIT 1");
                    $stmtSis->execute([$sisCoberturaId]);
                    $sisRow = $stmtSis->fetch(PDO::FETCH_ASSOC) ?: null;
                }
                if (!$sisRow) {
                    $stmtSis = $pdo->prepare("SELECT numero_afiliacion, numero_autorizacion, numero_fua, observaciones FROM sis_coberturas WHERE cotizacion_id = ? ORDER BY id DESC LIMIT 1");
                    $stmtSis->execute([$id_cotizacion]);
                    $sisRow = $stmtSis->fetch(PDO::FETCH_ASSOC) ?: null;
                }
                if ($sisRow) {
                    $sisNumeroAfiliacionValor = (string)($sisRow['numero_afiliacion'] ?? '');
                    $sisNumeroAutorizacionValor = (string)($sisRow['numero_autorizacion'] ?? '');
                    $sisNumeroFuaValor = (string)($sisRow['numero_fua'] ?? '');
                    $sisObservacionesValor = (string)($sisRow['observaciones'] ?? '');
                }
            }
        } catch (Throwable $e) {
            // Mantener fallback con los datos disponibles en cotizaciones.
        }
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

$formMsg = trim((string)($_GET['msg'] ?? ''));

try {
    $stmtColsDet = $pdo->query("SHOW COLUMNS FROM cotizaciones_detalle");
    $colsDet = $stmtColsDet ? $stmtColsDet->fetchAll(PDO::FETCH_ASSOC) : [];
    $mapDet = [];
    foreach ($colsDet as $colDet) {
        if (!empty($colDet['Field'])) {
            $mapDet[] = (string)$colDet['Field'];
        }
    }
    $hasDetalleReferenciadoCols = in_array('es_referenciado', $mapDet, true)
        && in_array('laboratorio_referenciado_nombre', $mapDet, true)
        && in_array('costo_laboratorio_referenciado', $mapDet, true)
        && in_array('costo_logistica_extra', $mapDet, true);
} catch (Throwable $e) {
    $hasDetalleReferenciadoCols = false;
}

// Validar cliente
if (empty($id_cliente)) {
    echo "<div class='alert alert-danger mt-4'>No se pudo identificar al cliente. Por favor, vuelve al listado de clientes.</div>";
    exit;
}

try {
    if ($derivacionServicioHabilitada) {
        $tablaServiciosExiste = (bool)$pdo->query("SHOW TABLES LIKE 'servicios'")->fetchColumn();
        $tablaServicioClienteExiste = (bool)$pdo->query("SHOW TABLES LIKE 'servicio_cliente'")->fetchColumn();
        $tablaServicioProfesionalExiste = (bool)$pdo->query("SHOW TABLES LIKE 'servicio_profesional'")->fetchColumn();
        $tablaProfesionalesExiste = (bool)$pdo->query("SHOW TABLES LIKE 'profesionales_solicitantes'")->fetchColumn();

        if ($tablaServiciosExiste) {
            $stmtServicios = $pdo->query("SELECT id, nombre, codigo FROM servicios WHERE estado = 'activo' ORDER BY nombre");
            $servicios = $stmtServicios ? $stmtServicios->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        if ($tablaServicioClienteExiste && !empty($id_cliente)) {
            $stmtServicioSel = $pdo->prepare("SELECT servicio_id FROM servicio_cliente WHERE cliente_id = ? ORDER BY id DESC LIMIT 1");
            $stmtServicioSel->execute([(int)$id_cliente]);
            $servicioSeleccionado = (int)($stmtServicioSel->fetchColumn() ?: 0);
        }

        if ($tablaServicioProfesionalExiste && $tablaProfesionalesExiste) {
            $stmtServicioProfesionales = $pdo->query("SELECT sp.servicio_id, p.id AS profesional_id, p.nombres, p.apellidos, p.tipo_profesional, p.registro_profesional
                FROM servicio_profesional sp
                INNER JOIN profesionales_solicitantes p ON p.id = sp.profesional_id
                WHERE p.estado = 'activo'
                ORDER BY p.nombres, p.apellidos");
            $rowsServicioProfesionales = $stmtServicioProfesionales ? $stmtServicioProfesionales->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($rowsServicioProfesionales as $rowProf) {
                $srvId = (int)($rowProf['servicio_id'] ?? 0);
                $profId = (int)($rowProf['profesional_id'] ?? 0);
                if ($srvId <= 0 || $profId <= 0) {
                    continue;
                }
                if (!isset($servicioProfesionalesMap[$srvId])) {
                    $servicioProfesionalesMap[$srvId] = [];
                }

                $servicioProfesionalesMap[$srvId][] = [
                    'id' => $profId,
                    'nombre' => trim((string)($rowProf['nombres'] ?? '') . ' ' . (string)($rowProf['apellidos'] ?? '')),
                    'tipo' => trim((string)($rowProf['tipo_profesional'] ?? '')),
                    'registro' => trim((string)($rowProf['registro_profesional'] ?? '')),
                ];
            }
        }
    }
} catch (Throwable $e) {
    $servicios = [];
    $servicioSeleccionado = 0;
    $servicioProfesionalesMap = [];
}

// Descuentos
$descuento_cliente = 0;
$usarPrecioConvenioCliente = 0;
if ($id_cliente) {
    $selectUsarCli = $hasClienteUsarPrecioConvenio ? 'usar_precio_convenio' : '0 AS usar_precio_convenio';
    $stmtDesc = $pdo->prepare("SELECT descuento, {$selectUsarCli} FROM clientes WHERE id = ?");
    $stmtDesc->execute([$id_cliente]);
    $clienteRow = $stmtDesc->fetch(PDO::FETCH_ASSOC) ?: [];
    $descuento_cliente = (float)($clienteRow['descuento'] ?? 0);
    $usarPrecioConvenioCliente = (int)($clienteRow['usar_precio_convenio'] ?? 0);
}
$descuento_empresa_convenio = 0;
$usarPrecioConvenioSesion = 0;
if ($rol === 'empresa' && !empty($_SESSION['empresa_id'])) {
    $selectUsarEmp = $hasEmpresaUsarPrecioConvenio ? 'usar_precio_convenio' : '0 AS usar_precio_convenio';
    $stmtDesc = $pdo->prepare("SELECT descuento, {$selectUsarEmp} FROM empresas WHERE id = ?");
    $stmtDesc->execute([$_SESSION['empresa_id']]);
    $empresaSesion = $stmtDesc->fetch(PDO::FETCH_ASSOC) ?: [];
    $descuento_empresa_convenio = (float)($empresaSesion['descuento'] ?? 0);
    $usarPrecioConvenioSesion = (int)($empresaSesion['usar_precio_convenio'] ?? 0);
} elseif ($rol === 'convenio' && !empty($_SESSION['convenio_id'])) {
    $selectUsarConv = $hasConvenioUsarPrecioConvenio ? 'usar_precio_convenio' : '0 AS usar_precio_convenio';
    $stmtDesc = $pdo->prepare("SELECT descuento, {$selectUsarConv} FROM convenios WHERE id = ?");
    $stmtDesc->execute([$_SESSION['convenio_id']]);
    $convenioSesion = $stmtDesc->fetch(PDO::FETCH_ASSOC) ?: [];
    $descuento_empresa_convenio = (float)($convenioSesion['descuento'] ?? 0);
    $usarPrecioConvenioSesion = (int)($convenioSesion['usar_precio_convenio'] ?? 0);
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
    overflow-x: auto;
    overflow-y: hidden;
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
        min-width: 980px;
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
    .tercerizado-costos .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .tercerizado-costos .form-label {
        font-size: 0.78rem;
        line-height: 1.2;
    }
    .acciones-cell {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    .acciones-cell .btn {
        min-width: 34px;
        min-height: 34px;
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

                <div id="cotizacionFormAlert" class="alert alert-danger d-none" role="alert"></div>
                <?php if ($formMsg === 'sis_cobertura_requerida'): ?>
                    <div class="alert alert-warning" role="alert">
                        Debes ingresar número de afiliación o número de acreditación para registrar una cotización SIS.
                    </div>
                <?php endif; ?>
                <?php if ($formMsg === 'profesional_solicitante_requerido'): ?>
                    <div class="alert alert-warning" role="alert">
                        Debes seleccionar el profesional solicitante asociado al servicio para continuar.
                    </div>
                <?php endif; ?>
                <?php if ($formMsg === 'profesional_solicitante_invalido'): ?>
                    <div class="alert alert-warning" role="alert">
                        El profesional solicitante seleccionado no está activo o no pertenece al servicio elegido.
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>dashboard.php?action=<?= $isEdit ? 'editar_cotizacion' : 'crear_cotizacion' ?>" method="POST" id="formCotizacion">

                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id_cotizacion" value="<?= htmlspecialchars($cotizacionData['id']) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="id_cliente" value="<?= htmlspecialchars($id_cliente) ?>">
                    <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="<?= $descuento_empresa_convenio ?: $descuento_cliente ?>">
                    <input type="hidden" name="detalles_json" id="detallesJsonCotizacion" value="[]">
                    <div id="hiddenPayloadCotizacion"></div>

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
                                    <select id="tipoCliente" name="tipo_usuario" class="form-select form-select-modern" <?= $sisForzado ? 'disabled' : 'required' ?>>
                                        <option value="">Seleccione...</option>
                                        <option value="cliente" <?= (($isEdit && $cotizacionData['tipo_usuario'] == 'cliente') || (!$isEdit && $sisForzado)) ? 'selected' : '' ?>>
                                            <i class="bi bi-person"></i>
                                            <?= $sisForzado ? 'SIS' : 'Particular' ?>
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
                                    <?php if ($sisForzado): ?>
                                        <input type="hidden" name="tipo_usuario" value="cliente">
                                        <small class="text-muted d-block mt-2">Modo SIS activo: el tipo de cliente se fija automaticamente.</small>
                                    <?php endif; ?>
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
                                            <option value="<?= $emp['id'] ?>" data-descuento="<?= $emp['descuento'] ?>" data-usa-precio-convenio="<?= (int)($emp['usar_precio_convenio'] ?? 0) ?>" <?= ($isEdit && $cotizacionData['id_empresa'] == $emp['id']) ? 'selected' : '' ?>>
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
                                            <option value="<?= $conv['id'] ?>" data-descuento="<?= $conv['descuento'] ?>" data-usa-precio-convenio="<?= (int)($conv['usar_precio_convenio'] ?? 0) ?>" <?= ($isEdit && $cotizacionData['id_convenio'] == $conv['id']) ? 'selected' : '' ?>>
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

                        <div class="row fade-in-up">
                            <?php if ($derivacionServicioHabilitada): ?>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label for="servicioAsignado" class="form-label-modern">
                                            <i class="bi bi-hospital"></i>
                                            Servicio
                                        </label>
                                        <select id="servicioAsignado" name="servicio_id" class="form-select form-select-modern" <?= !empty($servicios) ? 'required' : 'disabled' ?>>
                                            <option value="">Seleccione servicio...</option>
                                            <?php foreach ($servicios as $servicioOpt): ?>
                                                <option value="<?= (int)$servicioOpt['id'] ?>" <?= ((int)$servicioSeleccionado === (int)$servicioOpt['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$servicioOpt['nombre']) ?>
                                                    <?php if (!empty($servicioOpt['codigo'])): ?>
                                                        (<?= htmlspecialchars((string)$servicioOpt['codigo']) ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (empty($servicios)): ?>
                                            <small class="text-muted d-block mt-2">No hay servicios activos registrados.</small>
                                        <?php else: ?>
                                            <small class="text-muted d-block mt-2">Este paciente quedará asociado automáticamente al servicio seleccionado al guardar.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label for="profesionalSolicitante" class="form-label-modern">
                                            <i class="bi bi-person-vcard"></i>
                                            Profesional solicitante
                                        </label>
                                        <select id="profesionalSolicitante" name="profesional_solicitante_id" class="form-select form-select-modern" disabled>
                                            <option value="">Seleccione profesional...</option>
                                        </select>
                                        <div class="invalid-feedback">Selecciona el profesional solicitante para el servicio.</div>
                                        <small class="text-muted d-block mt-2" id="profesionalSolicitanteHint">Selecciona un servicio para ver los profesionales disponibles.</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="servicio_id" value="0">
                                <input type="hidden" name="profesional_solicitante_id" value="0">
                            <?php endif; ?>

                            <div class="<?= $derivacionServicioHabilitada ? 'col-md-4' : 'col-md-12' ?>">
                                <div class="form-group-modern">
                                    <label for="emitirComprobante" class="form-label-modern">
                                        <i class="bi bi-receipt"></i>
                                        Comprobante
                                    </label>
                                    <select id="emitirComprobante" name="emitir_comprobante" class="form-select form-select-modern" required>
                                        <option value="1" <?= ($emitirComprobante === 1) ? 'selected' : '' ?>>Sí (Boleta/Factura SUNAT)</option>
                                        <option value="0" <?= ($emitirComprobante === 0) ? 'selected' : '' ?>>No (Solo Ticket)</option>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Si eliges “Solo Ticket”, no se emitirá CPE (XML/CDR/PDF).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row fade-in-up" id="rowTipoComprobanteCliente">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label for="tipoComprobanteCliente" class="form-label-modern">
                                        <i class="bi bi-file-earmark-text"></i>
                                        Tipo de comprobante (Particular)
                                    </label>
                                    <select id="tipoComprobanteCliente" name="tipo_comprobante_cliente" class="form-select form-select-modern">
                                        <option value="boleta" <?= ($tipoComprobanteCliente === 'boleta') ? 'selected' : '' ?>>Boleta (DNI)</option>
                                        <option value="factura" <?= ($tipoComprobanteCliente === 'factura') ? 'selected' : '' ?>>Factura (RUC)</option>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Para “Factura” en particular, completa RUC y Razón Social.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4 d-none" id="colFacturaRuc">
                                <div class="form-group-modern">
                                    <label for="receptorRuc" class="form-label-modern">
                                        <i class="bi bi-123"></i>
                                        RUC (11 dígitos)
                                    </label>
                                    <input type="text" id="receptorRuc" name="receptor_ruc" class="form-control" maxlength="11" pattern="[0-9]{11}" value="<?= htmlspecialchars($receptorRuc) ?>">
                                </div>
                            </div>

                            <div class="col-md-4 d-none" id="colFacturaRazon">
                                <div class="form-group-modern">
                                    <label for="receptorRazon" class="form-label-modern">
                                        <i class="bi bi-building"></i>
                                        Razón Social
                                    </label>
                                    <input type="text" id="receptorRazon" name="receptor_razon_social" class="form-control" maxlength="255" value="<?= htmlspecialchars($receptorRazonSocial) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row fade-in-up d-none" id="rowFacturaDireccion">
                            <div class="col-md-12">
                                <div class="form-group-modern">
                                    <label for="receptorDireccion" class="form-label-modern">
                                        <i class="bi bi-geo-alt"></i>
                                        Dirección (opcional)
                                    </label>
                                    <input type="text" id="receptorDireccion" name="receptor_direccion" class="form-control" maxlength="255" value="<?= htmlspecialchars($receptorDireccion) ?>">
                                </div>
                            </div>
                        </div>

                        <?php if ($mostrarCoberturaSis): ?>
                            <div class="section-header fade-in-up mt-4">
                                <h5>
                                    <i class="bi bi-shield-check"></i>
                                    Cobertura SIS
                                </h5>
                            </div>

                            <div class="row fade-in-up align-items-start">
                                <div class="col-md-3 mb-3">
                                    <div class="form-check form-switch mt-4 pt-2">
                                        <?php if ($sisForzado): ?>
                                            <input type="hidden" name="es_sis" value="1">
                                            <input class="form-check-input" type="checkbox" id="esSis" checked disabled>
                                        <?php else: ?>
                                            <input class="form-check-input" type="checkbox" id="esSis" name="es_sis" value="1" <?= $sisCotizacionExistente ? 'checked' : '' ?>>
                                        <?php endif; ?>
                                        <label class="form-check-label" for="esSis">Marcar atención como SIS</label>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3" id="sisFieldAfiliacionWrap">
                                    <div class="form-group-modern">
                                        <label for="sisNumeroAfiliacion" class="form-label-modern">
                                            <i class="bi bi-person-badge"></i>
                                            Número de afiliación
                                        </label>
                                        <input type="text" id="sisNumeroAfiliacion" name="sis_numero_afiliacion" class="form-control form-control-modern" maxlength="80" value="<?= htmlspecialchars($sisNumeroAfiliacionValor) ?>">
                                        <div class="invalid-feedback">Ingresa afiliación o autorización para continuar en SIS.</div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3" id="sisFieldAutorizacionWrap">
                                    <div class="form-group-modern">
                                        <label for="sisNumeroAutorizacion" class="form-label-modern">
                                            <i class="bi bi-key"></i>
                                            Número de acreditación
                                        </label>
                                        <input type="text" id="sisNumeroAutorizacion" name="sis_numero_autorizacion" class="form-control form-control-modern" maxlength="80" value="<?= htmlspecialchars($sisNumeroAutorizacionValor) ?>">
                                        <div class="invalid-feedback">Ingresa autorización o afiliación para continuar en SIS.</div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3" id="sisFieldFuaWrap">
                                    <div class="form-group-modern">
                                        <label for="sisNumeroFua" class="form-label-modern">
                                            <i class="bi bi-receipt"></i>
                                            FUA / referencia
                                        </label>
                                        <input type="text" id="sisNumeroFua" name="sis_numero_fua" class="form-control form-control-modern" maxlength="80" value="<?= htmlspecialchars($sisNumeroFuaValor) ?>">
                                    </div>
                                </div>

                                <div class="col-12 mb-3" id="sisFieldObsWrap">
                                    <div class="form-group-modern">
                                        <label for="sisObservaciones" class="form-label-modern">
                                            <i class="bi bi-journal-text"></i>
                                            Observaciones de cobertura
                                        </label>
                                        <textarea id="sisObservaciones" name="sis_observaciones" class="form-control form-control-modern" rows="3" maxlength="1000"><?= htmlspecialchars($sisObservacionesValor) ?></textarea>
                                        <small class="text-muted d-block mt-2">Requiere al menos número de afiliación o autorización. La atención se registrará con monto S/ 0.00.</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                const sisActivoEnEdicion = <?= ($sisForzado || $sisCotizacionExistente) ? 'true' : 'false' ?>;
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
                            let esReferenciado = parseInt(ex.es_referenciado || 0) === 1;
                            let costoLab = parseFloat(ex.costo_laboratorio_referenciado || 0);
                            let costoLogistica = parseFloat(ex.costo_logistica_extra || 0);
                            let precioEdicion = parseFloat(ex.precio_unitario);
                            if (sisActivoEnEdicion) {
                                precioEdicion = 0;
                            }
                            return {
                                id: ex.id_examen,
                                codigo: info ? info.codigo : '',
                                nombre: ex.nombre_examen || (info ? info.nombre : ''),
                                precio_unitario: precioEdicion,
                                precio_publico: info ? Number(info.precio_publico || 0) : precioEdicion,
                                precio_convenio: info ? Number(info.precio_convenio || 0) : 0,
                                cantidad: parseInt(ex.cantidad),
                                descripcion: info ? info.descripcion : '',
                                tiempo_respuesta: info ? info.tiempo_respuesta : '',
                                preanalitica_cliente: info ? info.preanalitica_cliente : '',
                                observaciones: info ? info.observaciones : '',
                                es_referenciado: esReferenciado ? 1 : 0,
                                laboratorio_referenciado_nombre: (ex.laboratorio_referenciado_nombre || '').toString(),
                                costo_laboratorio_referenciado: isNaN(costoLab) ? 0 : costoLab,
                                costo_logistica_extra: isNaN(costoLogistica) ? 0 : costoLogistica
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


<!-- Footer fijo mejorado -->
<div class="fixed-bottom footer-cotizacion p-3" id="footerCotizacion">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div class="total-section">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-calculator text-success"></i>
                <div>
                    <small class="text-muted">Total a pagar:</small>
                    <div class="total-amount" id="totalCotizacion"><?= htmlspecialchars(money_format_local(0, $currencyCfg)) ?></div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var rolUsuario = '<?= $rol ?>';
let examenesData = <?= $examenes_json ?>;
let examenesSeleccionados = [];
let descuentoCliente = <?= $descuento_cliente ?>;
let descuentoActual = <?= $descuento_empresa_convenio ?: $descuento_cliente ?>;
let usarPrecioConvenioCliente = <?= (int)$usarPrecioConvenioCliente ?>;
let usarPrecioConvenioSesion = <?= (int)$usarPrecioConvenioSesion ?>;
let usarPrecioConvenioActual = <?= (int)($usarPrecioConvenioSesion ?: $usarPrecioConvenioCliente) ?>;
let examenIdPrefill = <?= isset($_GET['examen_id']) ? (int)$_GET['examen_id'] : 0 ?>;
let isEdit = <?= $isEdit ? 'true' : 'false' ?>;
let hasDetalleReferenciadoCols = <?= $hasDetalleReferenciadoCols ? 'true' : 'false' ?>;
const sisForzadoEnSistema = <?= $sisForzado ? 'true' : 'false' ?>;
const sisCotizacionInicial = <?= $sisCotizacionExistente ? 'true' : 'false' ?>;
const formCurrencyConfig = <?= json_encode($currencyCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const servicioProfesionalesMap = <?= json_encode($servicioProfesionalesMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const profesionalSolicitanteInicial = <?= (int)$profesionalSolicitanteSeleccionado ?>;
const profesionalSolicitanteSnapshot = <?= json_encode($profesionalSolicitanteSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const profesionalSolicitanteTipoSnapshot = <?= json_encode($profesionalSolicitanteTipoSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const profesionalSolicitanteRegistroSnapshot = <?= json_encode($profesionalSolicitanteRegistroSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function formatMoneySafe(amount) {
    if (typeof window.formatMoney === 'function') {
        return window.formatMoney(amount);
    }

    const numericAmount = Number(amount || 0);
    const decimals = Number(formCurrencyConfig.decimals ?? 2);
    const decimalSeparator = formCurrencyConfig.decimal_separator ?? '.';
    const thousandsSeparator = formCurrencyConfig.thousands_separator ?? ',';
    const symbol = formCurrencyConfig.symbol ?? '$';
    const position = formCurrencyConfig.position === 'suffix' ? 'suffix' : 'prefix';
    const fixed = numericAmount.toFixed(decimals);
    const parts = fixed.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    const formattedNumber = decimals > 0 ? parts.join(decimalSeparator) : parts[0];

    return position === 'suffix'
        ? `${formattedNumber} ${symbol}`
        : `${symbol} ${formattedNumber}`;
}

function isSisActive() {
    const sisCheckbox = $('#esSis');
    if (sisForzadoEnSistema) {
        return true;
    }
    if (sisCheckbox.length > 0) {
        return sisCheckbox.is(':checked') || sisCheckbox.is(':disabled');
    }
    return sisCotizacionInicial;
}

function resolvePrecioConvenio(examen) {
    const precioConvenio = Number(examen?.precio_convenio ?? 0);
    return Number.isFinite(precioConvenio) ? precioConvenio : 0;
}

function resolvePrecioPublico(examen) {
    const precioPublico = Number(examen?.precio_publico ?? 0);
    return Number.isFinite(precioPublico) ? precioPublico : 0;
}

function calcularPrecioPorPolitica(examen) {
    if (isSisActive()) {
        return 0;
    }

    const precioPublico = resolvePrecioPublico(examen);
    const precioConvenio = resolvePrecioConvenio(examen);

    // Si el perfil usa precio convenio y existe precio definido, usarlo exacto.
    if (Number(usarPrecioConvenioActual) === 1 && precioConvenio > 0) {
        return Number(precioConvenio.toFixed(2));
    }

    return Number(aplicarDescuento(precioPublico, descuentoActual));
}

function escapeHiddenAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function syncHiddenPayloadFromSeleccion() {
    const sisActive = isSisActive();
    const payload = [];
    const jsonPayload = [];

    if (!Array.isArray(examenesSeleccionados) || examenesSeleccionados.length === 0) {
        $('#hiddenPayloadCotizacion').html('');
        $('#detallesJsonCotizacion').val('[]');
        return;
    }

    examenesSeleccionados.forEach((ex) => {
        const idExamen = parseInt(ex.id, 10) || 0;
        const cantidad = Math.max(1, parseInt(ex.cantidad, 10) || 1);
        const precio = sisActive ? 0 : (parseFloat(ex.precio_unitario) || 0);
        const esRef = parseInt(ex.es_referenciado || 0, 10) === 1 ? 1 : 0;
        const laboratorioRef = (ex.laboratorio_referenciado_nombre || '').toString().trim();
        const costoLab = isNaN(parseFloat(ex.costo_laboratorio_referenciado)) ? 0 : Math.max(0, parseFloat(ex.costo_laboratorio_referenciado));
        const costoLog = isNaN(parseFloat(ex.costo_logistica_extra)) ? 0 : Math.max(0, parseFloat(ex.costo_logistica_extra));

        payload.push(`<input type="hidden" name="examenes[]" value="${idExamen}">`);
        payload.push(`<input type="hidden" name="cantidades[]" value="${cantidad}">`);
        payload.push(`<input type="hidden" name="precios[]" value="${precio.toFixed(2)}">`);
        payload.push(`<input type="hidden" name="referenciado_flags[]" value="${esRef}">`);
        payload.push(`<input type="hidden" name="laboratorio_referenciado_nombre[]" value="${escapeHiddenAttr(laboratorioRef)}">`);
        payload.push(`<input type="hidden" name="costo_laboratorio_referenciado[]" value="${costoLab.toFixed(2)}">`);
        payload.push(`<input type="hidden" name="costo_logistica_extra[]" value="${costoLog.toFixed(2)}">`);

        jsonPayload.push({
            id_examen: idExamen,
            cantidad: cantidad,
            precio_unitario: Number(precio.toFixed(2)),
            es_referenciado: esRef,
            laboratorio_referenciado_nombre: laboratorioRef,
            costo_laboratorio_referenciado: Number(costoLab.toFixed(2)),
            costo_logistica_extra: Number(costoLog.toFixed(2))
        });
    });

    $('#hiddenPayloadCotizacion').html(payload.join(''));
    $('#detallesJsonCotizacion').val(JSON.stringify(jsonPayload));
}

function showCotizacionFormError(message) {
    const $alert = $('#cotizacionFormAlert');
    $alert.text(message || 'Debes seleccionar al menos un examen.');
    $alert.removeClass('d-none');
    try {
        $alert[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (e) {
        // noop
    }
}

function clearSisCoverageValidation() {
    $('#sisNumeroAfiliacion, #sisNumeroAutorizacion').removeClass('is-invalid');
}

function clearProfessionalValidation() {
    $('#profesionalSolicitante').removeClass('is-invalid');
}

function buildProfessionalOptionLabel(prof) {
    const nombre = String(prof?.nombre || '').trim();
    const tipo = String(prof?.tipo || '').trim();
    const registro = String(prof?.registro || '').trim();
    let label = nombre;
    if (tipo !== '') {
        label += ' - ' + tipo;
    }
    if (registro !== '') {
        label += ' (' + registro + ')';
    }
    return label;
}

function renderProfesionalesPorServicio() {
    const servicioId = parseInt($('#servicioAsignado').val() || '0', 10);
    const $selectProf = $('#profesionalSolicitante');
    const $hint = $('#profesionalSolicitanteHint');
    const selectedBefore = parseInt($selectProf.val() || '0', 10);
    const keyServicio = String(servicioId);
    const profesionales = servicioId > 0 && Array.isArray(servicioProfesionalesMap[keyServicio])
        ? servicioProfesionalesMap[keyServicio]
        : [];

    $selectProf.empty();
    $selectProf.append('<option value="">Seleccione profesional...</option>');

    const idsDisponibles = [];
    profesionales.forEach((prof) => {
        const id = parseInt(prof.id || 0, 10);
        if (id <= 0) {
            return;
        }
        idsDisponibles.push(id);
        $selectProf.append(new Option(buildProfessionalOptionLabel(prof), String(id), false, false));
    });

    const objetivoSeleccion = selectedBefore > 0 ? selectedBefore : profesionalSolicitanteInicial;
    if (objetivoSeleccion > 0 && idsDisponibles.includes(objetivoSeleccion)) {
        $selectProf.val(String(objetivoSeleccion));
    } else if (
        objetivoSeleccion > 0 &&
        objetivoSeleccion === profesionalSolicitanteInicial &&
        profesionalSolicitanteSnapshot !== '' &&
        !idsDisponibles.includes(objetivoSeleccion)
    ) {
        let snapLabel = profesionalSolicitanteSnapshot;
        if (profesionalSolicitanteTipoSnapshot !== '') {
            snapLabel += ' - ' + profesionalSolicitanteTipoSnapshot;
        }
        if (profesionalSolicitanteRegistroSnapshot !== '') {
            snapLabel += ' (' + profesionalSolicitanteRegistroSnapshot + ')';
        }
        $selectProf.append(new Option(snapLabel + ' [registrado]', String(objetivoSeleccion), true, true));
        $selectProf.val(String(objetivoSeleccion));
    } else {
        $selectProf.val('');
    }

    if (servicioId <= 0) {
        $selectProf.prop('disabled', true);
        $hint.text('Selecciona un servicio para ver los profesionales disponibles.');
    } else if (profesionales.length === 0) {
        $selectProf.prop('disabled', true);
        $hint.text('Este servicio no tiene profesionales solicitantes activos asociados.');
    } else {
        $selectProf.prop('disabled', false);
        $hint.text('Selecciona el profesional que solicita el examen.');
    }

    clearProfessionalValidation();
}

function validateProfessionalRequirement() {
    const servicioId = parseInt($('#servicioAsignado').val() || '0', 10);
    const $selectProf = $('#profesionalSolicitante');
    if (servicioId <= 0 || $selectProf.is(':disabled')) {
        clearProfessionalValidation();
        return true;
    }

    const profesionalId = parseInt($selectProf.val() || '0', 10);
    if (profesionalId <= 0) {
        $selectProf.addClass('is-invalid');
        return false;
    }

    clearProfessionalValidation();
    return true;
}

function validateSisCoverageRequirement() {
    const sisActive = isSisActive();
    if (!sisActive) {
        clearSisCoverageValidation();
        return true;
    }

    const $afiliacion = $('#sisNumeroAfiliacion');
    const $autorizacion = $('#sisNumeroAutorizacion');
    const afiliacion = ($afiliacion.val() || '').toString().trim();
    const autorizacion = ($autorizacion.val() || '').toString().trim();
    const ok = afiliacion !== '' || autorizacion !== '';

    if (!ok) {
        $afiliacion.addClass('is-invalid');
        $autorizacion.addClass('is-invalid');
        return false;
    }

    clearSisCoverageValidation();
    return true;
}

$('#formCotizacion').on('submit', function(e) {
    syncHiddenPayloadFromSeleccion();
    if (!Array.isArray(examenesSeleccionados) || examenesSeleccionados.length === 0) {
        e.preventDefault();
        showCotizacionFormError('Debes seleccionar al menos una prueba/examen antes de guardar la cotización.');
        return false;
    }

    if (!validateSisCoverageRequirement()) {
        e.preventDefault();
        showCotizacionFormError('Para registrar o actualizar una cotización SIS debes ingresar número de afiliación o número de acreditación.');
        try {
            document.getElementById('sisNumeroAfiliacion')?.focus();
        } catch (err) {
            // noop
        }
        return false;
    }

    if (!validateProfessionalRequirement()) {
        e.preventDefault();
        showCotizacionFormError('Debes seleccionar el profesional solicitante asociado al servicio antes de guardar.');
        try {
            document.getElementById('profesionalSolicitante')?.focus();
        } catch (err) {
            // noop
        }
        return false;
    }

    $('#cotizacionFormAlert').addClass('d-none').text('');
});

$('#sisNumeroAfiliacion, #sisNumeroAutorizacion').on('input blur', function() {
    if (isSisActive()) {
        validateSisCoverageRequirement();
    } else {
        clearSisCoverageValidation();
    }
});

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

    $('#servicioAsignado').on('change', function() {
        renderProfesionalesPorServicio();
    });
    $('#profesionalSolicitante').on('change', function() {
        clearProfessionalValidation();
    });
    renderProfesionalesPorServicio();
    
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

    const precioDisplay = calcularPrecioPorPolitica(examenData);
    var $option = $(
        '<div class="d-flex justify-content-between align-items-center">' +
            '<div>' +
                '<div class="fw-bold">' + examenData.nombre + '</div>' +
                '<small class="text-muted">Código: ' + (examenData.codigo || 'N/A') + '</small>' +
            '</div>' +
            '<div class="text-end">' +
                '<span class="badge bg-success">' + formatMoneySafe(precioDisplay) + '</span>' +
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

    // Particular: habilitar selector de Boleta/Factura y campos de factura
    syncFacturaFields();
    syncSisFields();
    actualizarDescuento();
});

function syncFacturaFields() {
    const tipoCliente = $('#tipoCliente').val();
    const emitir = $('#emitirComprobante').val();
    const tipoComp = $('#tipoComprobanteCliente').val();

    // Mostrar solo cuando es Particular y se emitirá CPE
    const show = (tipoCliente === 'cliente' && String(emitir) === '1');
    $('#rowTipoComprobanteCliente').toggleClass('d-none', !show);

    // Si no se emitirá CPE (Solo Ticket) o no es particular, deshabilitar campos para que no se envíen
    $('#tipoComprobanteCliente').prop('disabled', !show);

    const isFactura = (show && tipoComp === 'factura');
    $('#colFacturaRuc, #colFacturaRazon').toggleClass('d-none', !isFactura);
    $('#rowFacturaDireccion').toggleClass('d-none', !isFactura);

    $('#receptorRuc, #receptorRazon').prop('required', isFactura);
    $('#receptorRuc, #receptorRazon, #receptorDireccion').prop('disabled', !isFactura);
}

$('#emitirComprobante').on('change', syncFacturaFields);
$('#tipoComprobanteCliente').on('change', syncFacturaFields);

function syncSisFields() {
    const sisCheckbox = $('#esSis');
    const sisEnabled = isSisActive();
    if (sisCheckbox.length) {
        $('#sisFieldAfiliacionWrap, #sisFieldAutorizacionWrap, #sisFieldFuaWrap, #sisFieldObsWrap').toggleClass('d-none', !sisEnabled);
        $('#sisNumeroAfiliacion, #sisNumeroAutorizacion, #sisNumeroFua, #sisObservaciones').prop('disabled', !sisEnabled);
    }

    // Si la atencion es SIS, no exigir tipo de cliente manual.
    if (sisEnabled && $('#tipoCliente').length) {
        $('#tipoCliente').val('cliente').prop('required', false);
        if (!sisForzadoEnSistema) {
            $('#tipoCliente').prop('disabled', true);
        }
        $('#empresa, #convenio').val('');
        $('#selectEmpresa, #selectConvenio').addClass('d-none');
        $('#empresa, #convenio').prop('required', false);
    } else if (!sisForzadoEnSistema && $('#tipoCliente').length) {
        $('#tipoCliente').prop('disabled', false).prop('required', true);
    }

    // En SIS, toda la cotización se registra en S/ 0.00.
    if (Array.isArray(examenesSeleccionados) && examenesSeleccionados.length > 0) {
        examenesSeleccionados.forEach((ex) => {
            if (sisEnabled) {
                ex.precio_unitario = 0;
            } else if (!isEdit) {
                ex.precio_unitario = calcularPrecioPorPolitica(ex);
            }
        });
        renderizarLista();
    }
}

$('#esSis').on('change', syncSisFields);

// Inicializar visibilidad al cargar
$(document).ready(function() {
    syncFacturaFields();
    syncSisFields();
});

// Detectar selección de empresa/convenio y actualizar descuento
$('#empresa').on('change', actualizarDescuento);
$('#convenio').on('change', actualizarDescuento);

function actualizarDescuento() {
    // Prioridad: si el usuario es empresa o convenio, usar ese descuento
    if (rolUsuario === 'empresa' || rolUsuario === 'convenio') {
        descuentoActual = <?= $descuento_empresa_convenio ?: 0 ?>;
        usarPrecioConvenioActual = Number(usarPrecioConvenioSesion) === 1 ? 1 : 0;
    } else {
        let tipo = $('#tipoCliente').val();
        descuentoActual = 0;
        usarPrecioConvenioActual = 0;
        if (tipo === 'empresa') {
            let desc = $('#empresa option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
            let usar = $('#empresa option:selected').attr('data-usa-precio-convenio');
            usarPrecioConvenioActual = Number(usar) === 1 ? 1 : 0;
        } else if (tipo === 'convenio') {
            let desc = $('#convenio option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
            let usar = $('#convenio option:selected').attr('data-usa-precio-convenio');
            usarPrecioConvenioActual = Number(usar) === 1 ? 1 : 0;
        } else if (tipo === 'cliente' || tipo === undefined) {
            descuentoActual = descuentoCliente;
            usarPrecioConvenioActual = Number(usarPrecioConvenioCliente) === 1 ? 1 : 0;
        }
    }
    $('#descuento_aplicado').val(descuentoActual);
    // Solo aplicar descuento si NO estamos en modo edición
    if (isEdit) {
        if (isSisActive()) {
            examenesSeleccionados.forEach((ex) => {
                ex.precio_unitario = 0;
            });
        }
        // En edición, los precios ya están descontados, no recalcular (salvo SIS)
        renderizarLista();
    } else {
        // En creación, sí aplicar descuento o forzar cero en SIS
        examenesSeleccionados.forEach((ex, idx) => {
            ex.precio_unitario = calcularPrecioPorPolitica(ex);
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
    agregarExamenPorId(id, true);
    renderizarLista();
    $(this).val('').trigger('change');
});

function agregarExamenPorId(id, incrementarSiExiste) {
    let examen = examenesData.find(ex => ex.id == id);
    if (!examen) {
        return false;
    }

    let existente = examenesSeleccionados.find(ex => ex.id == id);
    if (!existente) {
        let precioCalculado = calcularPrecioPorPolitica(examen);
        examenesSeleccionados.push({
            ...examen,
            precio_unitario: precioCalculado,
            cantidad: 1,
            es_referenciado: 0,
            laboratorio_referenciado_nombre: '',
            costo_laboratorio_referenciado: 0,
            costo_logistica_extra: 0
        });
    } else if (incrementarSiExiste) {
        existente.cantidad += 1;
    }

    return true;
}

// Cambiar cantidad
$(document).on('input', '.cantidadExamen', function() {
    let idx = $(this).data('idx');
    examenesSeleccionados[idx].cantidad = parseInt($(this).val()) || 1;
    renderizarLista();
});

// Cambiar precio manualmente (solo admin/recep) - Optimizado para escritura completa
$(document).on('keyup blur', '.precioExamen', function(e) {
    if (isSisActive()) {
        $(this).val('0.00');
        return;
    }
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
    if (isSisActive()) {
        $(this).val('0.00');
        return;
    }
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

$(document).on('change', '.referenciadoCheck', function() {
    let idx = parseInt($(this).data('idx'), 10);
    let checked = $(this).is(':checked');
    if (!Number.isInteger(idx) || !examenesSeleccionados[idx]) return;
    examenesSeleccionados[idx].es_referenciado = checked ? 1 : 0;
    if (!checked) {
        examenesSeleccionados[idx].laboratorio_referenciado_nombre = '';
        examenesSeleccionados[idx].costo_laboratorio_referenciado = 0;
        examenesSeleccionados[idx].costo_logistica_extra = 0;
    }
    renderizarLista();
});

function syncReferenciadoHidden(idx) {
    if (!Number.isInteger(idx) || !examenesSeleccionados[idx]) return;
    const ex = examenesSeleccionados[idx];
    $(`input[name="referenciado_flags[]"]:eq(${idx})`).val(parseInt(ex.es_referenciado || 0) === 1 ? 1 : 0);
    $(`input[name="laboratorio_referenciado_nombre[]"]:eq(${idx})`).val((ex.laboratorio_referenciado_nombre || '').toString().trim());
    $(`input[name="costo_laboratorio_referenciado[]"]:eq(${idx})`).val(parseFloat(ex.costo_laboratorio_referenciado || 0).toFixed(2));
    $(`input[name="costo_logistica_extra[]"]:eq(${idx})`).val(parseFloat(ex.costo_logistica_extra || 0).toFixed(2));
}

$(document).on('input', '.laboratorioReferenciadoInput', function() {
    let idx = parseInt($(this).data('idx'), 10);
    if (!Number.isInteger(idx) || !examenesSeleccionados[idx]) return;
    examenesSeleccionados[idx].laboratorio_referenciado_nombre = ($(this).val() || '').toString().trim();
    syncReferenciadoHidden(idx);
});

$(document).on('input', '.costoLabInput', function() {
    let idx = parseInt($(this).data('idx'), 10);
    if (!Number.isInteger(idx) || !examenesSeleccionados[idx]) return;
    let monto = parseFloat($(this).val());
    examenesSeleccionados[idx].costo_laboratorio_referenciado = (isNaN(monto) || monto < 0) ? 0 : monto;
    syncReferenciadoHidden(idx);
});

$(document).on('input', '.costoLogisticaInput', function() {
    let idx = parseInt($(this).data('idx'), 10);
    if (!Number.isInteger(idx) || !examenesSeleccionados[idx]) return;
    let monto = parseFloat($(this).val());
    examenesSeleccionados[idx].costo_logistica_extra = (isNaN(monto) || monto < 0) ? 0 : monto;
    syncReferenciadoHidden(idx);
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
                            <td class="text-success fw-bold">${formatMoneySafe(ex.precio_unitario)}</td>
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
    const sisActive = isSisActive();
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
                        <th style="width:140px;"><i class="bi bi-currency-dollar me-2"></i>Precio (<?= htmlspecialchars($currencySymbol) ?>)</th>
                        <th style="width:280px;"><i class="bi bi-truck me-2"></i>Tercerizado</th>
                        <th style="width:120px;"><i class="bi bi-calculator me-2"></i>Subtotal</th>
                        <th style="width:160px;"><i class="bi bi-gear me-2"></i>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
        
        examenesSeleccionados.forEach((ex, idx) => {
            if (sisActive) {
                ex.precio_unitario = 0;
            }
            let precio = sisActive ? 0 : parseFloat(ex.precio_unitario);
            if (isNaN(precio) || precio < 0) {
                precio = 0;
            }
            let subtotal = precio * ex.cantidad;
            total += subtotal;
            let esRef = parseInt(ex.es_referenciado || 0) === 1;
            let laboratorioRef = (ex.laboratorio_referenciado_nombre || '').toString();
            let costoLab = parseFloat(ex.costo_laboratorio_referenciado || 0);
            let costoLog = parseFloat(ex.costo_logistica_extra || 0);
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
                        (!sisActive && (rolUsuario === 'admin' || rolUsuario === 'recepcionista'))
                            ? `<div class="input-group">
                                <span class="input-group-text bg-success text-white"><?= htmlspecialchars($currencySymbol) ?></span>
                                <input type="text" class="form-control form-control-modern precioExamen" 
                                      data-idx="${idx}" value="${precio.toFixed(2)}" 
                                      placeholder="0.00" 
                                      pattern="[0-9]+(\.[0-9]{1,2})?" 
                                      title="Ingresa el precio (ej: 25.50)">
                               </div>`
                            : `<div class="form-control-plaintext fw-bold text-success">${formatMoneySafe(precio)}</div>`
                    }
                </td>
                <td>
                    ${hasDetalleReferenciadoCols ? `
                        <div class="form-check mb-2">
                            <input class="form-check-input referenciadoCheck" type="checkbox" data-idx="${idx}" id="ref_${idx}" ${esRef ? 'checked' : ''}>
                            <label class="form-check-label" for="ref_${idx}">Referenciado</label>
                        </div>
                        <label class="form-label form-label-sm mb-1">Laboratorio externo</label>
                        <input type="text" class="form-control form-control-sm mb-2 laboratorioReferenciadoInput" data-idx="${idx}" placeholder="Laboratorio externo" value="${laboratorioRef.replace(/"/g, '&quot;')}" ${esRef ? '' : 'disabled'}>
                        <div class="row g-1 tercerizado-costos">
                            <div class="col-6">
                                <label class="form-label form-label-sm mb-1">Costo laboratorio</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm costoLabInput" data-idx="${idx}" placeholder="Costo lab" value="${(isNaN(costoLab) ? 0 : costoLab).toFixed(2)}" ${esRef ? '' : 'disabled'}>
                            </div>
                            <div class="col-6">
                                <label class="form-label form-label-sm mb-1">Costo logística</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm costoLogisticaInput" data-idx="${idx}" placeholder="Costo logística" value="${(isNaN(costoLog) ? 0 : costoLog).toFixed(2)}" ${esRef ? '' : 'disabled'}>
                            </div>
                        </div>
                    ` : `
                        <span class="badge bg-secondary">No habilitado</span>
                    `}
                </td>
                <td>
                    <div class="fw-bold text-success fs-6">${formatMoneySafe(subtotal)}</div>
                </td>
                <td>
                    <div class="d-flex gap-1 acciones-cell">
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
    $('#totalCotizacion').text(formatMoneySafe(total));
    
    // Actualizar información de descuento
    if (Number(usarPrecioConvenioActual) === 1) {
        $('#descuentoInfo').removeClass('d-none');
        $('#descuentoTexto').text('Precio convenio');
    } else if (descuentoActual > 0) {
        $('#descuentoInfo').removeClass('d-none');
        $('#descuentoTexto').text(descuentoActual + '% desc.');
    } else {
        $('#descuentoInfo').addClass('d-none');
    }

    syncHiddenPayloadFromSeleccion();
}

if (!isEdit && Number(examenIdPrefill) > 0) {
    if (agregarExamenPorId(examenIdPrefill, false)) {
        renderizarLista();
    }
}
</script>
