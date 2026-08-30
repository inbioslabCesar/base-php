<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

// Protección: si no es una solicitud POST válida, redirigir a la lista
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
$detalles_json = $_POST['detalles_json'] ?? '[]';
$emitir_comprobante = isset($_POST['emitir_comprobante']) ? (int)$_POST['emitir_comprobante'] : 1;
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

$esEdicionCotizacion = !empty($_POST['id_cotizacion']);
if ($esAtencionSis && $sisNumeroAfiliacion === '' && $sisNumeroAutorizacion === '') {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    if ($esEdicionCotizacion) {
        $idCotizacionEdicion = (int)($_POST['id_cotizacion'] ?? 0);
        if ($idCotizacionEdicion > 0) {
            header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idCotizacionEdicion}&edit=1&msg=sis_cobertura_requerida");
            exit;
        }
    }
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=sis_cobertura_requerida");
    exit;
}

$profesionalSolicitante = null;
if ($derivacionServicioHabilitada && $servicio_id > 0) {
    $debeElegirProfesional = servicioTieneProfesionalesActivos($pdo, $servicio_id);
    if ($debeElegirProfesional && $profesional_solicitante_id <= 0) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        if ($esEdicionCotizacion) {
            $idCotizacionEdicion = (int)($_POST['id_cotizacion'] ?? 0);
            if ($idCotizacionEdicion > 0) {
                header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idCotizacionEdicion}&edit=1&msg=profesional_solicitante_requerido");
                exit;
            }
        }
        header("Location: {$base}dashboard.php?vista=cotizaciones&msg=profesional_solicitante_requerido");
        exit;
    }
}

if ($derivacionServicioHabilitada && $profesional_solicitante_id > 0) {
    $profesionalSolicitante = obtenerProfesionalSolicitanteValido($pdo, $servicio_id, $profesional_solicitante_id);
    if (!$profesionalSolicitante) {
        $base = defined('BASE_URL') ? BASE_URL : '../';
        if ($esEdicionCotizacion) {
            $idCotizacionEdicion = (int)($_POST['id_cotizacion'] ?? 0);
            if ($idCotizacionEdicion > 0) {
                header("Location: {$base}dashboard.php?vista=form_cotizacion&id={$idCotizacionEdicion}&edit=1&msg=profesional_solicitante_invalido");
                exit;
            }
        }
        header("Location: {$base}dashboard.php?vista=cotizaciones&msg=profesional_solicitante_invalido");
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
if (is_string($detalles_json) && trim($detalles_json) !== '') {
    $detallesDecodificados = json_decode($detalles_json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($detallesDecodificados) && !empty($detallesDecodificados)) {
        $examenes = [];
        $cantidades = [];
        $precios = [];
        $referenciado_flags = [];
        $laboratorio_referenciado_nombre = [];
        $costo_laboratorio_referenciado = [];
        $costo_logistica_extra = [];

        foreach ($detallesDecodificados as $detalleJson) {
            $idExamen = (int)($detalleJson['id_examen'] ?? 0);
            if ($idExamen <= 0) {
                continue;
            }
            $examenes[] = $idExamen;
            $cantidades[] = max(1, (int)($detalleJson['cantidad'] ?? 1));
            $precios[] = max(0, (float)($detalleJson['precio_unitario'] ?? 0));
            $referenciado_flags[] = ((int)($detalleJson['es_referenciado'] ?? 0) === 1) ? 1 : 0;
            $laboratorio_referenciado_nombre[] = trim((string)($detalleJson['laboratorio_referenciado_nombre'] ?? ''));
            $costo_laboratorio_referenciado[] = max(0, (float)($detalleJson['costo_laboratorio_referenciado'] ?? 0));
            $costo_logistica_extra[] = max(0, (float)($detalleJson['costo_logistica_extra'] ?? 0));
        }
    }
}

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
    foreach ($detalles as &$detalleSis) {
        $detalleSis['precio_unitario'] = 0;
        $detalleSis['subtotal'] = 0;
    }
    unset($detalleSis);
    $total = 0;
    $total_bruto = 0;
    $emitir_comprobante = 0;
}

// Si no quedó ningún detalle válido, no permitir actualizar cotización
if (empty($detalles)) {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=sin_examenes");
    exit;
}

// MODO EDICIÓN: actualizar cotización existente
if (!empty($_POST['id_cotizacion'])) {
    $id_cotizacion = intval($_POST['id_cotizacion']);
    // Si el usuario envía nueva fecha/hora, actualízala; si no, mantén la original
    $fecha_update = null;
    if (!empty($_POST['fecha_toma']) && !empty($_POST['hora_toma'])) {
        $fecha_update = $_POST['fecha_toma'] . ' ' . $_POST['hora_toma'] . ':00';
    }
    if ($fecha_update) {
        $set = [
            'id_cliente=?',
            'id_empresa=?',
            'id_convenio=?',
            'tipo_usuario=?',
            'emitir_comprobante=?',
            'total=?',
            'total_bruto=?',
            'descuento_aplicado=?',
            'fecha=?',
            'modificada=1'
        ];
        $params = [
            $id_cliente,
            $id_empresa !== '' ? $id_empresa : null,
            $id_convenio !== '' ? $id_convenio : null,
            $tipo_usuario,
            $emitir_comprobante,
            $total,
            $total_bruto,
            $descuento,
            $fecha_update,
        ];

        if (cotizacionesHasColumn($pdo, 'servicio_id')) {
            $set[] = 'servicio_id=?';
            $params[] = $derivacionServicioHabilitada && $servicio_id > 0 ? $servicio_id : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_id')) {
            $set[] = 'profesional_solicitante_id=?';
            $params[] = ($profesionalSolicitante && !empty($profesionalSolicitante['id'])) ? (int)$profesionalSolicitante['id'] : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_nombre')) {
            $set[] = 'profesional_solicitante_nombre=?';
            $params[] = $profesionalSolicitante
                ? trim((string)($profesionalSolicitante['nombres'] ?? '') . ' ' . (string)($profesionalSolicitante['apellidos'] ?? ''))
                : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_tipo')) {
            $set[] = 'profesional_solicitante_tipo=?';
            $params[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['tipo_profesional'] ?? '')) : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_registro')) {
            $set[] = 'profesional_solicitante_registro=?';
            $params[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['registro_profesional'] ?? '')) : null;
        }

        if (cotizacionesHasColumn($pdo, 'comprobante_tipo')) {
            $set[] = 'comprobante_tipo=?';
            if ($tipo_usuario === 'empresa' && !empty($id_empresa) && $emitir_comprobante === 1) {
                $params[] = 'factura';
            } elseif ($wantsFacturaParticular) {
                $params[] = 'factura';
            } else {
                $params[] = 'boleta';
            }
        }

        if ($wantsFacturaParticular) {
            if (cotizacionesHasColumn($pdo, 'receptor_tipo_documento')) { $set[] = 'receptor_tipo_documento=?'; $params[] = '6'; }
            if (cotizacionesHasColumn($pdo, 'receptor_numero_documento')) { $set[] = 'receptor_numero_documento=?'; $params[] = $receptor_ruc; }
            if (cotizacionesHasColumn($pdo, 'receptor_razon_social')) { $set[] = 'receptor_razon_social=?'; $params[] = $receptor_razon_social; }
            if (cotizacionesHasColumn($pdo, 'receptor_direccion')) { $set[] = 'receptor_direccion=?'; $params[] = ($receptor_direccion !== '') ? $receptor_direccion : null; }
        }

        $sql = 'UPDATE cotizaciones SET ' . implode(',', $set) . ' WHERE id=?';
        $params[] = $id_cotizacion;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $set = [
            'id_cliente=?',
            'id_empresa=?',
            'id_convenio=?',
            'tipo_usuario=?',
            'emitir_comprobante=?',
            'total=?',
            'total_bruto=?',
            'descuento_aplicado=?',
            'modificada=1'
        ];
        $params = [
            $id_cliente,
            $id_empresa !== '' ? $id_empresa : null,
            $id_convenio !== '' ? $id_convenio : null,
            $tipo_usuario,
            $emitir_comprobante,
            $total,
            $total_bruto,
            $descuento,
        ];

        if (cotizacionesHasColumn($pdo, 'servicio_id')) {
            $set[] = 'servicio_id=?';
            $params[] = $derivacionServicioHabilitada && $servicio_id > 0 ? $servicio_id : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_id')) {
            $set[] = 'profesional_solicitante_id=?';
            $params[] = ($profesionalSolicitante && !empty($profesionalSolicitante['id'])) ? (int)$profesionalSolicitante['id'] : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_nombre')) {
            $set[] = 'profesional_solicitante_nombre=?';
            $params[] = $profesionalSolicitante
                ? trim((string)($profesionalSolicitante['nombres'] ?? '') . ' ' . (string)($profesionalSolicitante['apellidos'] ?? ''))
                : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_tipo')) {
            $set[] = 'profesional_solicitante_tipo=?';
            $params[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['tipo_profesional'] ?? '')) : null;
        }
        if (cotizacionesHasColumn($pdo, 'profesional_solicitante_registro')) {
            $set[] = 'profesional_solicitante_registro=?';
            $params[] = $profesionalSolicitante ? trim((string)($profesionalSolicitante['registro_profesional'] ?? '')) : null;
        }

        if (cotizacionesHasColumn($pdo, 'comprobante_tipo')) {
            $set[] = 'comprobante_tipo=?';
            if ($tipo_usuario === 'empresa' && !empty($id_empresa) && $emitir_comprobante === 1) {
                $params[] = 'factura';
            } elseif ($wantsFacturaParticular) {
                $params[] = 'factura';
            } else {
                $params[] = 'boleta';
            }
        }

        if ($wantsFacturaParticular) {
            if (cotizacionesHasColumn($pdo, 'receptor_tipo_documento')) { $set[] = 'receptor_tipo_documento=?'; $params[] = '6'; }
            if (cotizacionesHasColumn($pdo, 'receptor_numero_documento')) { $set[] = 'receptor_numero_documento=?'; $params[] = $receptor_ruc; }
            if (cotizacionesHasColumn($pdo, 'receptor_razon_social')) { $set[] = 'receptor_razon_social=?'; $params[] = $receptor_razon_social; }
            if (cotizacionesHasColumn($pdo, 'receptor_direccion')) { $set[] = 'receptor_direccion=?'; $params[] = ($receptor_direccion !== '') ? $receptor_direccion : null; }
        }

        $sql = 'UPDATE cotizaciones SET ' . implode(',', $set) . ' WHERE id=?';
        $params[] = $id_cotizacion;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    if ($esAtencionSis && app_database_has_table($pdo, 'sis_coberturas')) {
        try {
            $fechaSis = $fecha_update ?: date('Y-m-d H:i:s');
            $stmtSis = $pdo->prepare('SELECT id FROM sis_coberturas WHERE cotizacion_id = ? LIMIT 1');
            $stmtSis->execute([$id_cotizacion]);
            $sisCoberturaId = (int)($stmtSis->fetchColumn() ?: 0);

            if ($sisCoberturaId > 0) {
                $stmtUpdSis = $pdo->prepare("UPDATE sis_coberturas SET cliente_id = ?, numero_afiliacion = ?, numero_autorizacion = ?, numero_fua = ?, estado_validacion = ?, monto_atencion = 0, observaciones = ?, creado_por = ?, creada_en = COALESCE(creada_en, ?), actualizada_en = NOW() WHERE id = ?");
                $stmtUpdSis->execute([
                    $id_cliente,
                    $sisNumeroAfiliacion !== '' ? $sisNumeroAfiliacion : null,
                    $sisNumeroAutorizacion !== '' ? $sisNumeroAutorizacion : null,
                    $sisNumeroFua !== '' ? $sisNumeroFua : null,
                    'autorizada',
                    $sisObservaciones !== '' ? $sisObservaciones : null,
                    $creado_por,
                    $fechaSis,
                    $sisCoberturaId,
                ]);
            } else {
                $stmtInsSis = $pdo->prepare("INSERT INTO sis_coberturas (cotizacion_id, cliente_id, numero_afiliacion, numero_autorizacion, numero_fua, estado_validacion, monto_atencion, observaciones, creado_por, creada_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtInsSis->execute([
                    $id_cotizacion,
                    $id_cliente,
                    $sisNumeroAfiliacion !== '' ? $sisNumeroAfiliacion : null,
                    $sisNumeroAutorizacion !== '' ? $sisNumeroAutorizacion : null,
                    $sisNumeroFua !== '' ? $sisNumeroFua : null,
                    'autorizada',
                    0,
                    $sisObservaciones !== '' ? $sisObservaciones : null,
                    $creado_por,
                    $fechaSis,
                ]);
                $sisCoberturaId = (int)$pdo->lastInsertId();
            }

            if (app_database_has_column($pdo, 'cotizaciones', 'sis_cobertura_id')) {
                $stmtLink = $pdo->prepare('UPDATE cotizaciones SET sis_cobertura_id = ?, sis_monto_atencion = 0, sis_registrado_en = ?, sis_registrado_por = ?, es_sis = 1 WHERE id = ?');
                $stmtLink->execute([
                    $sisCoberturaId,
                    $fechaSis,
                    $creado_por,
                    $id_cotizacion,
                ]);
            }
        } catch (Throwable $e) {
            // La cotización ya quedó actualizada; la cobertura SIS no debe romper la edición.
        }
    }

    // Eliminar exámenes anteriores de cotizaciones_detalle y agregar los nuevos
    $estadoLiquidacionAnteriorPorExamen = [];
    if (cotizacionesDetalleHasColumn($pdo, 'estado_liquidacion') && cotizacionesDetalleHasColumn($pdo, 'id_examen')) {
        $stmtPrev = $pdo->prepare("SELECT id_examen, es_referenciado, estado_liquidacion FROM cotizaciones_detalle WHERE id_cotizacion = ?");
        $stmtPrev->execute([$id_cotizacion]);
        $prevRows = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);
        foreach ($prevRows as $prevRow) {
            $prevExamen = (int)($prevRow['id_examen'] ?? 0);
            if ($prevExamen > 0) {
                $estadoLiquidacionAnteriorPorExamen[$prevExamen] = [
                    'es_referenciado' => (int)($prevRow['es_referenciado'] ?? 0),
                    'estado_liquidacion' => (string)($prevRow['estado_liquidacion'] ?? 'pendiente'),
                ];
            }
        }
    }

    $pdo->prepare("DELETE FROM cotizaciones_detalle WHERE id_cotizacion = ?")->execute([$id_cotizacion]);
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
            $estadoLiquidacion = $detalle['es_referenciado'] === 1 ? 'pendiente' : 'liquidado';
            if ($detalle['es_referenciado'] === 1) {
                $estadoPrevio = $estadoLiquidacionAnteriorPorExamen[(int)$detalle['id_examen']] ?? null;
                $eraReferenciado = (int)($estadoPrevio['es_referenciado'] ?? 0) === 1;
                $estadoPrevioValor = (string)($estadoPrevio['estado_liquidacion'] ?? 'pendiente');
                if ($eraReferenciado && $estadoPrevioValor === 'liquidado') {
                    $estadoLiquidacion = 'liquidado';
                }
            }
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
                $estadoLiquidacion,
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

    // --- RESULTADOS DE EXAMENES ---
    // Obtener exámenes actuales en la BD
    $stmtExist = $pdo->prepare("SELECT id_examen FROM resultados_examenes WHERE id_cotizacion = ?");
    $stmtExist->execute([$id_cotizacion]);
    $examenes_existentes = array_column($stmtExist->fetchAll(PDO::FETCH_ASSOC), 'id_examen');
    // Exámenes nuevos y exámenes que permanecen
    $examenes_nuevos = [];
    $examenes_actuales = array_column($detalles, 'id_examen');
    foreach ($examenes_actuales as $id_examen) {
        if (!in_array($id_examen, $examenes_existentes)) {
            $examenes_nuevos[] = $id_examen;
        }
    }
    // Exámenes eliminados
    $examenes_eliminados = array_diff($examenes_existentes, $examenes_actuales);
    // Eliminar solo los resultados de exámenes eliminados
    if (count($examenes_eliminados) > 0) {
        $in = implode(',', array_fill(0, count($examenes_eliminados), '?'));
        $sqlDel = "DELETE FROM resultados_examenes WHERE id_cotizacion = ? AND id_examen IN ($in)";
        $pdo->prepare($sqlDel)->execute(array_merge([$id_cotizacion], $examenes_eliminados));
    }
    // Insertar resultados vacíos solo para exámenes nuevos
    $hasSnapshotCol = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
        $hasSnapshotCol = !empty($col);
    } catch (Exception $e) {
        $hasSnapshotCol = false;
    }

    $hasOrderCol = false;
    try {
        $colOrder = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'orden_impresion'")->fetch(PDO::FETCH_ASSOC);
        $hasOrderCol = !empty($colOrder);
    } catch (Exception $e) {
        $hasOrderCol = false;
    }

    $nextOrderPos = 1;
    if ($hasOrderCol) {
        $stmtMaxOrder = $pdo->prepare("SELECT IFNULL(MAX(orden_impresion), 0) FROM resultados_examenes WHERE id_cotizacion = ?");
        $stmtMaxOrder->execute([$id_cotizacion]);
        $nextOrderPos = ((int)$stmtMaxOrder->fetchColumn()) + 1;
    }

    foreach ($examenes_nuevos as $id_examen) {
        if ($hasSnapshotCol) {
            $stmtAd = $pdo->prepare('SELECT adicional FROM examenes WHERE id = ?');
            $stmtAd->execute([$id_examen]);
            $adicional_snapshot = $stmtAd->fetchColumn();
            if ($hasOrderCol) {
                $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, adicional_snapshot, estado, orden_impresion) VALUES (?, ?, ?, '{}', ?, 'pendiente', ?)";
                $stmtRes = $pdo->prepare($sql);
                $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $adicional_snapshot, $nextOrderPos]);
            } else {
                $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, adicional_snapshot, estado) VALUES (?, ?, ?, '{}', ?, 'pendiente')";
                $stmtRes = $pdo->prepare($sql);
                $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $adicional_snapshot]);
            }
        } else {
            if ($hasOrderCol) {
                $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado, orden_impresion) VALUES (?, ?, ?, '{}', 'pendiente', ?)";
                $stmtRes = $pdo->prepare($sql);
                $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion, $nextOrderPos]);
            } else {
                $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado) VALUES (?, ?, ?, '{}', 'pendiente')";
                $stmtRes = $pdo->prepare($sql);
                $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion]);
            }
        }
        if ($hasOrderCol) {
            $nextOrderPos++;
        }
    }

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
            // No interrumpir la edicion por fallo de asociacion a servicio.
        }
    }

    // Redirigir según el rol
    $rol = $_SESSION['rol'] ?? null;
    // Redirigir siempre a la tabla de cotizaciones después de editar
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=cotizacion_actualizada");
    exit;
} else {
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones&msg=falta_id");
    exit;
}
