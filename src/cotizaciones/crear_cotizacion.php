<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Recibir datos del formulario
$id_cliente = $_POST['id_cliente'] ?? null;
$examenes = $_POST['examenes'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];
$precios = $_POST['precios'] ?? [];
$tipo_usuario = $_POST['tipo_usuario'] ?? 'cliente';
$id_empresa = $_POST['id_empresa'] ?? null;
$id_convenio = $_POST['id_convenio'] ?? null;

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
    echo '<div class="alert alert-danger">No se encontró el cliente o el usuario creador en sesión.</div>';
    exit;
}

// Validar datos de exámenes
if (
    empty($examenes) ||
    empty($cantidades) ||
    empty($precios) ||
    count($examenes) != count($cantidades) ||
    count($examenes) != count($precios)
) {
    echo '<div class="alert alert-danger">Debes seleccionar al menos un examen y completar todos los datos.</div>';
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

    $precio_unitario = floatval($examen['precio_publico']);
    $precio_unitario_desc = $precio_unitario;
    if ($descuento > 0) {
        $precio_unitario_desc = $precio_unitario * (1 - ($descuento / 100));
    }

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

//$id_empresa = !empty($id_empresa) ? $id_empresa : null;
//$id_convenio = !empty($id_convenio) ? $id_convenio : null;

// Preparar datos obligatorios para la tabla cotizaciones
$codigo = 'COT-' . strtoupper(uniqid());
$estado_pago = 'pendiente';
$fecha = date('Y-m-d H:i:s');
// Insertar cotización principal
$stmt = $pdo->prepare("INSERT INTO cotizaciones
    (codigo, id_cliente, id_empresa, id_convenio, tipo_usuario, fecha, total, total_bruto, estado_pago, creado_por, rol_creador, tipo_toma, fecha_toma, hora_toma, direccion_toma, descuento_aplicado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $codigo, 
    $id_cliente, 
    $id_empresa !== '' ? $id_empresa : null,
    $id_convenio !== '' ? $id_convenio : null,
    $tipo_usuario,
    $fecha, 
    $total, 
    $total_bruto,
    $estado_pago, 
    $creado_por, 
    $rol_creador,
    $_POST['tipo_toma'] ?? null,
    $_POST['fecha_toma'] ?? null,
    $_POST['hora_toma'] ?? null,
    $_POST['direccion_toma'] ?? null,
    $descuento // Guarda el porcentaje de descuento aplicado realmente usado
]);

$id_cotizacion = $pdo->lastInsertId();

// Insertar detalles de la cotización
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

// Insertar en resultados_examenes para cada examen cotizado
foreach ($detalles as $detalle) {
    $id_examen = $detalle['id_examen'];
    $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado) VALUES (?, ?, ?, '{}', 'pendiente')";
    $stmtRes = $pdo->prepare($sql);
    $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion]);
}

// Redirigir según el rol
$rol = $_SESSION['rol'] ?? null;
if (
    $rol == 'cliente' ||
    $rol == 'recepcionista' ||
    $rol == 'admin' ||
    $rol == 'empresa' ||
    $rol == 'convenio'
) {
    header("Location: dashboard.php?vista=agendar_cita&id_cotizacion=" . $id_cotizacion);
    exit;
}
header("Location: dashboard.php?vista=cotizaciones");
exit;
