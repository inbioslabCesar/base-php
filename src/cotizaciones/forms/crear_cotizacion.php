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
$tipo_usuario = $_POST['tipo_usuario'] ?? 'cliente';
$id_empresa = $_POST['id_empresa'] ?? null;
$id_convenio = $_POST['id_convenio'] ?? null;
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

    $total_bruto += $subtotal_bruto;
    $total += $subtotal;

    $detalles[] = [
        'id_examen' => $examen_id,
        'nombre_examen' => $examen['nombre'],
        'precio_unitario' => $precio_unitario_desc,
        'cantidad' => $cantidad,
        'subtotal' => $subtotal
    ];
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
$stmt = $pdo->prepare("INSERT INTO cotizaciones_detalle (id_cotizacion, id_examen, nombre_examen, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($detalles as $detalle) {
    $stmt->execute([
        $id_cotizacion,
        $detalle['id_examen'],
        $detalle['nombre_examen'],
        $detalle['precio_unitario'],
        $detalle['cantidad'],
        $detalle['subtotal']
    ]);
}
foreach ($detalles as $detalle) {
    $id_examen = $detalle['id_examen'];
    $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado) VALUES (?, ?, ?, '{}', 'pendiente')";
    $stmtRes = $pdo->prepare($sql);
    $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion]);
}

// Redirigir según el rol
$rol = $_SESSION['rol'] ?? null;
// Redirigir a agendar cita después de crear cotización
$base = defined('BASE_URL') ? BASE_URL : '../';
header("Location: {$base}dashboard.php?vista=agendar_cita&id_cotizacion=" . $id_cotizacion);
exit;
