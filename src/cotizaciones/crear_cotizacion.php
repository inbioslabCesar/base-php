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
$fecha = date('Y-m-d H:i:s');
$total = 0;
$detalles = [];

// Validar que hay exámenes seleccionados
if (empty($examenes) || empty($cantidades) || count($examenes) != count($cantidades)) {
    echo '<div class="alert alert-danger">Debes seleccionar al menos un examen.</div>';
    exit;
}
// Obtener promociones activas para los exámenes seleccionados
$ids_examenes = implode(',', array_map('intval', $examenes));
$stmt = $pdo->prepare("
    SELECT p.*, pe.examen_id
    FROM promociones p
    JOIN promociones_examen pe ON p.id = pe.promocion_id
    WHERE p.activo = 1 AND p.vigente = 1
      AND p.fecha_inicio <= ? AND p.fecha_fin >= ?
      AND pe.examen_id IN ($ids_examenes)
");
$hoy = date('Y-m-d');
$stmt->execute([$hoy, $hoy]);
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapear promociones por examen
$promo_map = [];
foreach ($promos as $promo) {
    $promo_map[$promo['examen_id']] = $promo;
}

// Procesar cada examen y calcular totales
for ($i = 0; $i < count($examenes); $i++) {
    $examen_id = (int)$examenes[$i];
    $cantidad = (int)$cantidades[$i];

    // Obtener datos del examen
    $stmt = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
    $stmt->execute([$examen_id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) continue;

    $precio_unitario = floatval($examen['precio_publico']);
    $promo = $promo_map[$examen_id] ?? null;
    $precio_final = $precio_unitario;

    // Aplicar promoción si existe
    if ($promo) {
        if ($promo['descuento'] > 0) {
            $precio_final = $precio_unitario - ($precio_unitario * $promo['descuento'] / 100);
        } elseif ($promo['precio_promocional'] > 0) {
            $precio_final = floatval($promo['precio_promocional']);
        }
    }

    $subtotal = $precio_final * $cantidad;
    $total += $subtotal;

    $detalles[] = [
        'id_examen' => $examen_id,
        'nombre_examen' => $examen['nombre'],
        'precio_unitario' => $precio_final,
        'cantidad' => $cantidad,
        'subtotal' => $subtotal
    ];
}
// Preparar datos obligatorios para la tabla cotizaciones
$codigo = 'COT-' . strtoupper(uniqid());
$estado_pago = 'pendiente';
$rol_creador = $_SESSION['rol'] ?? 'cliente';
$creado_por = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? $_SESSION['cliente_id'] ?? null; // Ajusta según tu lógica

if (!$id_cliente || !$creado_por) {
    echo '<div class="alert alert-danger">No se encontró el cliente o el usuario creador en sesión.</div>';
    exit;
}

// Insertar cotización principal
$stmt = $pdo->prepare("INSERT INTO cotizaciones 
    (codigo, id_cliente, fecha, total, estado_pago, creado_por, rol_creador, tipo_toma, fecha_toma, hora_toma, direccion_toma)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $codigo, 
    $id_cliente, 
    $fecha, 
    $total, 
    $estado_pago, 
    $creado_por, 
    $rol_creador,
    $_POST['tipo_toma'] ?? null,
    $_POST['fecha_toma'] ?? null,
    $_POST['hora_toma'] ?? null,
    $_POST['direccion_toma'] ?? null
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
$rol = $_SESSION['rol'] ?? null;

// Redirigir según el rol
if ($rol == 'cliente' || $rol == 'recepcionista') {
    header("Location: dashboard.php?vista=agendar_cita&id_cotizacion=" . $id_cotizacion);
    exit;
}

// Puedes agregar otros roles o una redirección por defecto si es necesario
header("Location: dashboard.php?vista=cotizaciones");
exit;
