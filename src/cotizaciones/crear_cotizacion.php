<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Validar sesión de usuario
if (!isset($_SESSION['rol'])) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    exit;
}

// Recibir datos del formulario
$id_cliente = $_POST['id_cliente'] ?? null;
$examenes = $_POST['examenes'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];
$precios = $_POST['precios'] ?? [];
$descuento_aplicado = $_POST['descuento_aplicado'] ?? 0;
$tipo_usuario = $_POST['tipo_usuario'] ?? 'cliente';
$id_empresa = $tipo_usuario === 'empresa' ? ($_POST['id_empresa'] ?? null) : null;
$id_convenio = $tipo_usuario === 'convenio' ? ($_POST['id_convenio'] ?? null) : null;
$fecha = date('Y-m-d H:i:s');
$total = 0;
$total_bruto = 0;
$detalles = [];

// Validar que hay exámenes seleccionados y los datos coinciden
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
// Procesar cada examen y calcular totales
for ($i = 0; $i < count($examenes); $i++) {
    $examen_id = (int)$examenes[$i];
    $cantidad = (int)$cantidades[$i];
    $precio_unitario = floatval($precios[$i]); // Precio ya con descuento aplicado

    // Obtener precio original y nombre del examen para el total_bruto y detalle
    $stmt = $pdo->prepare("SELECT nombre, precio_publico FROM examenes WHERE id = ?");
    $stmt->execute([$examen_id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) continue;

    $subtotal_bruto = floatval($examen['precio_publico']) * $cantidad;
    $subtotal = $precio_unitario * $cantidad;

    $total_bruto += $subtotal_bruto;
    $total += $subtotal;

    $detalles[] = [
        'id_examen' => $examen_id,
        'nombre_examen' => $examen['nombre'],
        'precio_unitario' => $precio_unitario,
        'cantidad' => $cantidad,
        'subtotal' => $subtotal
    ];
}
// Preparar datos obligatorios para la tabla cotizaciones
$codigo = 'COT-' . strtoupper(uniqid());
$estado_pago = 'pendiente';
$rol_creador = $_SESSION['rol'] ?? 'cliente';
$creado_por = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? $_SESSION['cliente_id'] ?? null;

if (!$id_cliente || !$creado_por) {
    echo '<div class="alert alert-danger">No se encontró el cliente o el usuario creador en sesión.</div>';
    exit;
}

// Insertar cotización principal
$stmt = $pdo->prepare("INSERT INTO cotizaciones 
    (codigo, id_cliente, id_empresa, id_convenio, tipo_usuario, fecha, total, total_bruto, estado_pago, creado_por, rol_creador, tipo_toma, fecha_toma, hora_toma, direccion_toma, descuento_aplicado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $codigo, 
    $id_cliente, 
    $id_empresa,
    $id_convenio,
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
    $descuento_aplicado
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
if ($rol == 'cliente' || $rol == 'recepcionista' || $rol == 'admin') {
    header("Location: dashboard.php?vista=agendar_cita&id_cotizacion=" . $id_cotizacion);
    exit;
}
header("Location: dashboard.php?vista=cotizaciones");
exit;
