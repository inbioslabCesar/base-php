<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

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

// MODO EDICIÓN: actualizar cotización existente
if (!empty($_POST['id_cotizacion'])) {
    $id_cotizacion = intval($_POST['id_cotizacion']);
    // Si el usuario envía nueva fecha/hora, actualízala; si no, mantén la original
    $fecha_update = null;
    if (!empty($_POST['fecha_toma']) && !empty($_POST['hora_toma'])) {
        $fecha_update = $_POST['fecha_toma'] . ' ' . $_POST['hora_toma'] . ':00';
    }
    if ($fecha_update) {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET id_cliente=?, id_empresa=?, id_convenio=?, tipo_usuario=?, total=?, total_bruto=?, descuento_aplicado=?, fecha=?, modificada=1 WHERE id=?");
        $stmt->execute([
            $id_cliente,
            $id_empresa !== '' ? $id_empresa : null,
            $id_convenio !== '' ? $id_convenio : null,
            $tipo_usuario,
            $total,
            $total_bruto,
            $descuento,
            $fecha_update,
            $id_cotizacion
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET id_cliente=?, id_empresa=?, id_convenio=?, tipo_usuario=?, total=?, total_bruto=?, descuento_aplicado=?, modificada=1 WHERE id=?");
        $stmt->execute([
            $id_cliente,
            $id_empresa !== '' ? $id_empresa : null,
            $id_convenio !== '' ? $id_convenio : null,
            $tipo_usuario,
            $total,
            $total_bruto,
            $descuento,
            $id_cotizacion
        ]);
    }
    // Eliminar exámenes anteriores de cotizaciones_detalle y agregar los nuevos
    $pdo->prepare("DELETE FROM cotizaciones_detalle WHERE id_cotizacion = ?")->execute([$id_cotizacion]);
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
    foreach ($examenes_nuevos as $id_examen) {
        $sql = "INSERT INTO resultados_examenes (id_examen, id_cliente, id_cotizacion, resultados, estado) VALUES (?, ?, ?, '{}', 'pendiente')";
        $stmtRes = $pdo->prepare($sql);
        $stmtRes->execute([$id_examen, $id_cliente, $id_cotizacion]);
    }
    // Redirigir según el rol
    $rol = $_SESSION['rol'] ?? null;
    // Redirigir siempre a la tabla de cotizaciones después de editar
    $base = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$base}dashboard.php?vista=cotizaciones");
    exit;
} else {
    echo '<div class="alert alert-danger">No se recibió el ID de la cotización a editar.</div>';
    exit;
}
