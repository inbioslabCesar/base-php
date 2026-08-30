<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

// Protección: si la solicitud no es POST, redirigir a la vista de cotizaciones
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=dato_invalido");
    exit;
}

// Recibir datos del formulario
$id_cliente = $_POST['id_cliente'] ?? null;
$examenes = $_POST['examenes'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];
$precios = $_POST['precios'] ?? [];
$referenciado_flags = $_POST['referenciado_flags'] ?? [];
$laboratorio_referenciado_nombre = $_POST['laboratorio_referenciado_nombre'] ?? [];
$costo_laboratorio_referenciado = $_POST['costo_laboratorio_referenciado'] ?? [];
$costo_logistica_extra = $_POST['costo_logistica_extra'] ?? [];
$tipo_usuario = $_POST['tipo_usuario'] ?? 'cliente';
$id_empresa = $_POST['id_empresa'] ?? null;
$id_convenio = $_POST['id_convenio'] ?? null;
$servicio_id = isset($_POST['servicio_id']) ? (int)$_POST['servicio_id'] : 0;
$profesional_solicitante_id = isset($_POST['profesional_solicitante_id']) ? (int)$_POST['profesional_solicitante_id'] : 0;
$emitir_comprobante = isset($_POST['emitir_comprobante']) ? (int)$_POST['emitir_comprobante'] : 0;
$emitir_comprobante = ($emitir_comprobante === 0) ? 0 : 1;

// Particular pero Factura (RUC)
$tipo_comprobante_cliente = strtolower(trim((string)($_POST['tipo_comprobante_cliente'] ?? 'boleta')));
$receptor_ruc = preg_replace('/\D+/', '', (string)($_POST['receptor_ruc'] ?? ''));
$receptor_razon_social = trim((string)($_POST['receptor_razon_social'] ?? ''));
$receptor_direccion = trim((string)($_POST['receptor_direccion'] ?? ''));

// Normalizar valores vacíos a null para evitar errores SQL
$id_empresa = !empty($id_empresa) ? $id_empresa : null;
$id_convenio = !empty($id_convenio) ? $id_convenio : null;

// Validar datos mínimos
$rol_creador = $_SESSION['rol'] ?? 'cliente';
$creado_por = null;
$operacionContext = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [
    'modo_operativo' => 'PARTICULAR',
    'es_particular' => true,
    'es_sis' => false,
    'es_mixto' => false,
];
$derivacionServicioHabilitada = !empty($operacionContext['es_sis']);
$sisActivoEnSistema = !empty($operacionContext['es_sis']);
$servicio_id = $derivacionServicioHabilitada ? $servicio_id : 0;
$profesional_solicitante_id = $derivacionServicioHabilitada ? $profesional_solicitante_id : 0;
$esAtencionSis = $sisActivoEnSistema || !empty($_POST['es_sis']);
$sisNumeroAfiliacion = trim((string)($_POST['sis_numero_afiliacion'] ?? ''));
$sisNumeroAutorizacion = trim((string)($_POST['sis_numero_autorizacion'] ?? ''));
$sisNumeroFua = trim((string)($_POST['sis_numero_fua'] ?? ''));
$sisObservaciones = trim((string)($_POST['sis_observaciones'] ?? ''));

if ($rol_creador === 'empresa') {
    $id_empresa = $_SESSION['empresa_id'] ?? $id_empresa;
    $creado_por = $id_empresa;
    $stmt = $pdo->prepare("SELECT 1 FROM empresa_cliente WHERE empresa_id = ? AND cliente_id = ?");
    $stmt->execute([$id_empresa, $id_cliente]);
    if (!$stmt->fetch()) {
        echo '<div class="alert alert-danger">El cliente no está asociado a esta empresa.</div>';
        exit;
    }
} elseif ($rol_creador === 'convenio') {
    $id_convenio = $_SESSION['convenio_id'] ?? $id_convenio;
    $creado_por = $id_convenio;
    $stmt = $pdo->prepare("SELECT 1 FROM convenio_cliente WHERE convenio_id = ? AND cliente_id = ?");
    $stmt->execute([$id_convenio, $id_cliente]);
    if (!$stmt->fetch()) {
        echo '<div class="alert alert-danger">El cliente no está asociado a este convenio.</div>';
        exit;
    }
} elseif (in_array($rol_creador, ['admin', 'recepcionista', 'laboratorista'])) {
    $creado_por = $_SESSION['usuario_id'] ?? null;
} elseif ($rol_creador === 'cliente') {
    $creado_por = $_SESSION['cliente_id'] ?? null;
}

if (!$id_cliente || !$creado_por) {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=sesion_incompleta");
    exit;
}

if ($esAtencionSis && $sisNumeroAfiliacion === '' && $sisNumeroAutorizacion === '') {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    $idClienteRedirect = (int)$id_cliente;
    header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idClienteRedirect}&msg=sis_cobertura_requerida");
    exit;
}

$profesionalSolicitante = null;
if ($derivacionServicioHabilitada && $servicio_id > 0) {
    $debeElegirProfesional = servicioTieneProfesionalesActivos($pdo, $servicio_id);
    if ($debeElegirProfesional && $profesional_solicitante_id <= 0) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        $idClienteRedirect = (int)$id_cliente;
        header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idClienteRedirect}&msg=profesional_solicitante_requerido");
        exit;
    }
}

if ($derivacionServicioHabilitada && $profesional_solicitante_id > 0) {
    $profesionalSolicitante = obtenerProfesionalSolicitanteValido($pdo, $servicio_id, $profesional_solicitante_id);
    if (!$profesionalSolicitante) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        $idClienteRedirect = (int)$id_cliente;
        header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idClienteRedirect}&msg=profesional_solicitante_invalido");
        exit;
    }
}

// Helper: comprobar si una columna existe en la tabla cotizaciones (MySQL/MariaDB)
function cotizacionesHasColumn(PDO $pdo, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM cotizaciones LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function cotizacionesDetalleHasColumn(PDO $pdo, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM cotizaciones_detalle LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function servicioTieneProfesionalesActivos(PDO $pdo, int $servicioId): bool {
    if ($servicioId <= 0) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM servicio_profesional sp
            INNER JOIN profesionales_solicitantes p ON p.id = sp.profesional_id
            WHERE sp.servicio_id = ? AND p.estado = 'activo'");
        $stmt->execute([$servicioId]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function obtenerProfesionalSolicitanteValido(PDO $pdo, int $servicioId, int $profesionalId): ?array {
    if ($profesionalId <= 0) {
        return null;
    }
    try {
        if ($servicioId > 0) {
            $stmt = $pdo->prepare("SELECT p.id, p.nombres, p.apellidos, p.tipo_profesional, p.registro_profesional
                FROM profesionales_solicitantes p
                INNER JOIN servicio_profesional sp ON sp.profesional_id = p.id
                WHERE p.id = ? AND sp.servicio_id = ? AND p.estado = 'activo'
                LIMIT 1");
            $stmt->execute([$profesionalId, $servicioId]);
        } else {
            $stmt = $pdo->prepare("SELECT p.id, p.nombres, p.apellidos, p.tipo_profesional, p.registro_profesional
                FROM profesionales_solicitantes p
                WHERE p.id = ? AND p.estado = 'activo'
                LIMIT 1");
            $stmt->execute([$profesionalId]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

// Si es Particular y se quiere Factura, exigir columnas y datos
$wantsFacturaParticular = ($tipo_usuario === 'cliente' && $emitir_comprobante === 1 && $tipo_comprobante_cliente === 'factura');
if ($wantsFacturaParticular) {
    if (!cotizacionesHasColumn($pdo, 'comprobante_tipo') || !cotizacionesHasColumn($pdo, 'receptor_numero_documento') || !cotizacionesHasColumn($pdo, 'receptor_razon_social')) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        header("Location: {$base}dashboard.php?vista=cotizaciones&msg=bd_sin_campos_factura_particular");
        exit;
    }
    if (strlen($receptor_ruc) !== 11) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        header("Location: {$base}dashboard.php?vista=cotizaciones&msg=ruc_invalido");
        exit;
    }
    if ($receptor_razon_social === '') {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        header("Location: {$base}dashboard.php?vista=cotizaciones&msg=razon_social_requerida");
        exit;
    }
}

// Validar datos de exámenes
if (
    empty($examenes) ||
    empty($cantidades) ||
    empty($precios) ||
    count($examenes) != count($cantidades) ||
    count($examenes) != count($precios)
) {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=datos_incompletos");
    exit;
}
// Calcular descuento según tipo de usuario o selección en el formulario
$descuento = 0;

// Si el rol en sesión es empresa/convenio, prioriza el descuento de la sesión
if ($rol_creador === 'empresa' && !empty($_SESSION['empresa_id'])) {
    $id_empresa = $_SESSION['empresa_id'];
    $stmtDesc = $pdo->prepare("SELECT descuento FROM empresas WHERE id = ?");
    $stmtDesc->execute([$id_empresa]);
    $descuento = $stmtDesc->fetchColumn() ?: 0;
} elseif ($rol_creador === 'convenio' && !empty($_SESSION['convenio_id'])) {
    $id_convenio = $_SESSION['convenio_id'];
    $stmtDesc = $pdo->prepare("SELECT descuento FROM convenios WHERE id = ?");
    $stmtDesc->execute([$id_convenio]);
    $descuento = $stmtDesc->fetchColumn() ?: 0;
}

// Si el rol es admin/recepcionista y selecciona empresa/convenio en el formulario
elseif ($tipo_usuario === 'empresa' && $id_empresa) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM empresas WHERE id = ?");
    $stmtDesc->execute([$id_empresa]);
    $descuento = $stmtDesc->fetchColumn() ?: 0;
} elseif ($tipo_usuario === 'convenio' && $id_convenio) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM convenios WHERE id = ?");
    $stmtDesc->execute([$id_convenio]);
    $descuento = $stmtDesc->fetchColumn() ?: 0;
} else {
    // Particular/cliente
    $descuento = 0;
}

if ($esAtencionSis) {
    $descuento = 0;
}


// Procesar cada examen y calcular totales
$total = 0;
$total_bruto = 0;
$detalles = [];

for ($i = 0; $i < count($examenes); $i++) {
    $examen_id = (int)$examenes[$i];
    $cantidad = (int)$cantidades[$i];

    $stmt = $pdo->prepare("SELECT nombre, precio_publico FROM examenes WHERE id = ?");
    $stmt->execute([$examen_id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) continue;

    // Tomar el precio modificado desde el formulario (admin/recepcionista)
    $precio_unitario_desc = floatval($precios[$i]);

    // Para mostrar totales de referencia, puedes seguir usando el precio original y el descuento calculado
    $precio_unitario = floatval($examen['precio_publico']);
    $subtotal_bruto = $precio_unitario * $cantidad;

    $subtotal = $precio_unitario_desc * $cantidad;

    $es_referenciado = (int)($referenciado_flags[$i] ?? 0) === 1 ? 1 : 0;
    $laboratorio_ref = trim((string)($laboratorio_referenciado_nombre[$i] ?? ''));
    $costo_lab = max(0, (float)($costo_laboratorio_referenciado[$i] ?? 0));
    $costo_log = max(0, (float)($costo_logistica_extra[$i] ?? 0));
    if ($es_referenciado !== 1) {
        $laboratorio_ref = '';
        $costo_lab = 0.0;
        $costo_log = 0.0;
    }

    $total_bruto += $subtotal_bruto;
    $total += $subtotal;

    $detalles[] = [
        'id_examen' => $examen_id,
        'nombre_examen' => $examen['nombre'],
        'precio_unitario' => $precio_unitario_desc,
        'cantidad' => $cantidad,
        'subtotal' => $subtotal,
        'es_referenciado' => $es_referenciado,
        'laboratorio_referenciado_nombre' => $laboratorio_ref,
        'costo_laboratorio_referenciado' => $costo_lab,
        'costo_logistica_extra' => $costo_log,
    ];
}

if ($esAtencionSis) {
    $emitir_comprobante = 0;
    $total = 0;
    $total_bruto = 0;
    foreach ($detalles as &$detalleSis) {
        $detalleSis['precio_unitario'] = 0;
        $detalleSis['subtotal'] = 0;
    }
    unset($detalleSis);
}

// Si no quedó ningún detalle válido, no permitir crear cotización
if (empty($detalles)) {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=sin_examenes");
    exit;
}



// SOLO CREAR NUEVA COTIZACION
$codigo = 'COT-' . strtoupper(uniqid());
$estado_pago = 'pendiente';
$fecha = null;
if (!empty($_POST['fecha_toma']) && !empty($_POST['hora_toma'])) {
    // Usar la fecha y hora seleccionadas por el usuario
    $fecha = $_POST['fecha_toma'] . ' ' . $_POST['hora_toma'] . ':00';
} else {
    // Usar la fecha y hora actual del servidor (zona horaria Lima)
    date_default_timezone_set('America/Lima');
    $fecha = date('Y-m-d H:i:s');
}
$cols = [
    'codigo','id_cliente','id_empresa','id_convenio','tipo_usuario','fecha','total','total_bruto','estado_pago','emitir_comprobante','creado_por','rol_creador','tipo_toma','fecha_toma','hora_toma','direccion_toma','descuento_aplicado'
];
$vals = [
    $codigo,
    $id_cliente,
    $id_empresa !== '' ? $id_empresa : null,
    $id_convenio !== '' ? $id_convenio : null,
    $tipo_usuario,
    $fecha,
    $total,
    $total_bruto,
    $estado_pago,
    $emitir_comprobante,
    $creado_por,
    $rol_creador,
    $_POST['tipo_toma'] ?? null,
    $_POST['fecha_toma'] ?? null,
    $_POST['hora_toma'] ?? null,
    $_POST['direccion_toma'] ?? null,
    $descuento,
];

if (app_database_has_column($pdo, 'cotizaciones', 'es_sis')) {
    $cols[] = 'es_sis';
    $vals[] = $esAtencionSis ? 1 : 0;
}
if (app_database_has_column($pdo, 'cotizaciones', 'sis_monto_atencion')) {
    $cols[] = 'sis_monto_atencion';
    $vals[] = 0;
}
if (app_database_has_column($pdo, 'cotizaciones', 'sis_registrado_en')) {
    $cols[] = 'sis_registrado_en';
    $vals[] = $esAtencionSis ? $fecha : null;
}
if (app_database_has_column($pdo, 'cotizaciones', 'sis_registrado_por')) {
    $cols[] = 'sis_registrado_por';
    $vals[] = $esAtencionSis ? $creado_por : null;
}
if (cotizacionesHasColumn($pdo, 'servicio_id')) {
    $cols[] = 'servicio_id';
    $vals[] = $derivacionServicioHabilitada && $servicio_id > 0 ? $servicio_id : null;
}
if (cotizacionesHasColumn($pdo, 'profesional_solicitante_id')) {
    $cols[] = 'profesional_solicitante_id';
    $vals[] = ($profesionalSolicitante && !empty($profesionalSolicitante['id'])) ? (int)$profesionalSolicitante['id'] : null;
}
if (cotizacionesHasColumn($pdo, 'profesional_solicitante_nombre')) {
    $cols[] = 'profesional_solicitante_nombre';
    $vals[] = $profesionalSolicitante
        ? trim((string)($profesionalSolicitante['nombres'] ?? '') . ' ' . (string)($profesionalSolicitante['apellidos'] ?? ''))
        : null;
}
if (cotizacionesHasColumn($pdo, 'profesional_solicitante_tipo')) {
    $cols[] = 'profesional_solicitante_tipo';
    $vals[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['tipo_profesional'] ?? '')) : null;
}
if (cotizacionesHasColumn($pdo, 'profesional_solicitante_registro')) {
    $cols[] = 'profesional_solicitante_registro';
    $vals[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['registro_profesional'] ?? '')) : null;
}

// Guardar tipo y receptor solo si existen columnas
if (cotizacionesHasColumn($pdo, 'comprobante_tipo')) {
    $cols[] = 'comprobante_tipo';
    if ($tipo_usuario === 'empresa' && !empty($id_empresa) && $emitir_comprobante === 1) {
        $vals[] = 'factura';
    } elseif ($wantsFacturaParticular) {
        $vals[] = 'factura';
    } else {
        $vals[] = 'boleta';
    }
}

if ($wantsFacturaParticular) {
    if (cotizacionesHasColumn($pdo, 'receptor_tipo_documento')) {
        $cols[] = 'receptor_tipo_documento';
        $vals[] = '6';
    }
    if (cotizacionesHasColumn($pdo, 'receptor_numero_documento')) {
        $cols[] = 'receptor_numero_documento';
        $vals[] = $receptor_ruc;
    }
    if (cotizacionesHasColumn($pdo, 'receptor_razon_social')) {
        $cols[] = 'receptor_razon_social';
        $vals[] = $receptor_razon_social;
    }
    if (cotizacionesHasColumn($pdo, 'receptor_direccion')) {
        $cols[] = 'receptor_direccion';
        $vals[] = ($receptor_direccion !== '') ? $receptor_direccion : null;
    }
}

$placeholders = implode(',', array_fill(0, count($cols), '?'));
$sql = 'INSERT INTO cotizaciones (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
$stmt = $pdo->prepare($sql);
$stmt->execute($vals);
$id_cotizacion = $pdo->lastInsertId();

if ($esAtencionSis && app_database_has_table($pdo, 'sis_coberturas')) {
    try {
        $stmtSis = $pdo->prepare("INSERT INTO sis_coberturas (cotizacion_id, cliente_id, numero_afiliacion, numero_autorizacion, numero_fua, estado_validacion, monto_atencion, observaciones, creado_por, creada_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtSis->execute([
            $id_cotizacion,
            $id_cliente,
            $sisNumeroAfiliacion !== '' ? $sisNumeroAfiliacion : null,
            $sisNumeroAutorizacion !== '' ? $sisNumeroAutorizacion : null,
            $sisNumeroFua !== '' ? $sisNumeroFua : null,
            'autorizada',
            0,
            $sisObservaciones !== '' ? $sisObservaciones : null,
            $creado_por,
            $fecha,
        ]);
        $sisCoberturaId = (int)$pdo->lastInsertId();
        if (app_database_has_column($pdo, 'cotizaciones', 'sis_cobertura_id')) {
            $stmtLink = $pdo->prepare('UPDATE cotizaciones SET sis_cobertura_id = ? WHERE id = ?');
            $stmtLink->execute([$sisCoberturaId, $id_cotizacion]);
        }
    } catch (Throwable $e) {
        // La cotización ya quedó creada; no interrumpir el flujo por una cobertura no persistida.
    }
}
$hasRefCols = cotizacionesDetalleHasColumn($pdo, 'es_referenciado')
    && cotizacionesDetalleHasColumn($pdo, 'laboratorio_referenciado_nombre')
    && cotizacionesDetalleHasColumn($pdo, 'costo_laboratorio_referenciado')
    && cotizacionesDetalleHasColumn($pdo, 'costo_logistica_extra')
    && cotizacionesDetalleHasColumn($pdo, 'estado_liquidacion');

if ($hasRefCols) {
    $stmt = $pdo->prepare("INSERT INTO cotizaciones_detalle (id_cotizacion, id_examen, nombre_examen, precio_unitario, cantidad, subtotal, es_referenciado, laboratorio_referenciado_nombre, costo_laboratorio_referenciado, costo_logistica_extra, estado_liquidacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
} else {
    $stmt = $pdo->prepare("INSERT INTO cotizaciones_detalle (id_cotizacion, id_examen, nombre_examen, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
}

foreach ($detalles as $detalle) {
    if ($hasRefCols) {
        $stmt->execute([
            $id_cotizacion,
            $detalle['id_examen'],
            $detalle['nombre_examen'],
            $detalle['precio_unitario'],
            $detalle['cantidad'],
            $detalle['subtotal'],
            $detalle['es_referenciado'],
            $detalle['laboratorio_referenciado_nombre'] !== '' ? $detalle['laboratorio_referenciado_nombre'] : null,
            $detalle['costo_laboratorio_referenciado'],
            $detalle['costo_logistica_extra'],
            $detalle['es_referenciado'] === 1 ? 'pendiente' : 'liquidado',
        ]);
    } else {
        $stmt->execute([
            $id_cotizacion,
            $detalle['id_examen'],
            $detalle['nombre_examen'],
            $detalle['precio_unitario'],
            $detalle['cantidad'],
            $detalle['subtotal']
        ]);
    }
}
foreach ($detalles as $detalle) {
    $id_examen = $detalle['id_examen'];


if ($servicio_id > 0) {
    try {
        $tablaServiciosExiste = (bool)$pdo->query("SHOW TABLES LIKE 'servicios'")->fetchColumn();
        $tablaServicioClienteExiste = (bool)$pdo->query("SHOW TABLES LIKE 'servicio_cliente'")->fetchColumn();
        if ($tablaServiciosExiste && $tablaServicioClienteExiste) {
            $stmtServVal = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE id = ? AND estado = 'activo'");
            $stmtServVal->execute([$servicio_id]);
            if ((int)$stmtServVal->fetchColumn() > 0) {
                $stmtAsoc = $pdo->prepare("INSERT IGNORE INTO servicio_cliente (servicio_id, cliente_id) VALUES (?, ?)");
                $stmtAsoc->execute([$servicio_id, (int)$id_cliente]);
            }
        }
    } catch (Throwable $e) {
        // No interrumpir la cotizacion por fallo de asociacion a servicio.
    }
}
    // Snapshot del formato (adicional) para que el histórico no cambie si se edita el examen luego.
    $hasSnapshotCol = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
        $hasSnapshotCol = !empty($col);
    } catch (Exception $e) {
        $hasSnapshotCol = false;
    }
    $adicional_snapshot = null;
    if ($hasSnapshotCol) {
        $stmtAd = $pdo->prepare('SELECT adicional FROM examenes WHERE id = ?');
        $stmtAd->execute([$id_examen]);
        $adicional_snapshot = $stmtAd->fetchColumn();
    }

    static $hasOrderCol = null;
    static $orderPos = 1;
    if ($hasOrderCol === null) {
        try {
            $colOrder = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'orden_impresion'")->fetch(PDO::FETCH_ASSOC);
            $hasOrderCol = !empty($colOrder);
        } catch (Exception $e) {
            $hasOrderCol = false;
        }
    }

    if ($hasSnapshotCol) {
        if ($hasOrderCol) {
            $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, adicional_snapshot, estado, orden_impresion) VALUES (?, ?, ?, '{}', ?, 'pendiente', ?)";
            $stmtRes = $pdo->prepare($sql);
            $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $adicional_snapshot, $orderPos]);
        } else {
            $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, adicional_snapshot, estado) VALUES (?, ?, ?, '{}', ?, 'pendiente')";
            $stmtRes = $pdo->prepare($sql);
            $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $adicional_snapshot]);
        }
    } else {
        if ($hasOrderCol) {
            $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado, orden_impresion) VALUES (?, ?, ?, '{}', 'pendiente', ?)";
            $stmtRes = $pdo->prepare($sql);
            $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $orderPos]);
        } else {
            $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado) VALUES (?, ?, ?, '{}', 'pendiente')";
            $stmtRes = $pdo->prepare($sql);
            $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion]);
        }
    }
    $orderPos++;
}

// Redirigir según el rol
$rol = $_SESSION['rol'] ?? null;
// Redirigir a agendar cita después de crear cotización
$base = defined('BASE_URL') ? BASE_URL : '../';
header("Location: {$base}dashboard.php?vista=agendar_cita&id_cotizacion=" . $id_cotizacion);
exit;
