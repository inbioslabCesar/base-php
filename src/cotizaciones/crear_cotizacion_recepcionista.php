<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    echo "<div class='alert alert-danger'>Acceso no permitido.</div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_cotizacion'])) {
    $cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    $examenes = isset($_POST['examenes']) ? $_POST['examenes'] : [];
    $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];

    // Validar cliente_id
    if ($cliente_id === 0) {
        echo "<div class='alert alert-danger'>Error: No se seleccionó un cliente válido.</div>";
        exit;
    }
    // Validar que el cliente exista
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    if ($stmt->fetchColumn() == 0) {
        echo "<div class='alert alert-danger'>Error: El cliente seleccionado no existe.</div>";
        exit;
    }

    // Validar exámenes
    if (empty($examenes)) {
        echo "<div class='alert alert-warning'>Debes seleccionar al menos un examen.</div>";
        exit;
    }

    $total = 0;
    $detalles = [];

    foreach ($examenes as $examen_id) {
        $cantidad = isset($cantidades[$examen_id]) ? intval($cantidades[$examen_id]) : 1;
        $stmt = $pdo->prepare("SELECT nombre, precio_publico FROM examenes WHERE id = ?");
        $stmt->execute([$examen_id]);
        $examen = $stmt->fetch();
        if ($examen) {
            $subtotal = $examen['precio_publico'] * $cantidad;
            $total += $subtotal;
            $detalles[] = [
                'examen_id' => $examen_id,
                'nombre' => $examen['nombre'],
                'precio_unitario' => $examen['precio_publico'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ];
        }
    }

    // Generar código único para la cotización
    $codigo = 'COT-' . date('YmdHis') . '-' . rand(100,999);
    $fecha = date('Y-m-d');

    // Campos obligatorios según tu tabla
    $id_empresa = null; // Ajustar si es necesario
    $id_convenio = null; // Ajustar si es necesario
    $tipo_usuario = 'recepcionista'; // ENUM: 'cliente', 'empresa', 'convenio', 'recepcionista'
    $estado_pago = 'pendiente';      // ENUM: 'pendiente', 'pagado'
    $observaciones = '';             // Texto opcional
    $pdf_url = null;                 // Opcional
    $creado_por = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    $rol_creador = 'recepcionista';  // ENUM: 'cliente', 'recepcionista', 'laboratorista', etc.

    // Insertar cotización principal
    $stmt = $pdo->prepare("
        INSERT INTO cotizaciones (
            id_cliente, codigo, id_empresa, id_convenio, tipo_usuario, fecha, total, estado_pago, observaciones, pdf_url, creado_por, rol_creador
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $cliente_id,
        $codigo,
        $id_empresa,
        $id_convenio,
        $tipo_usuario,
        $fecha,
        $total,
        $estado_pago,
        $observaciones,
        $pdf_url,
        $creado_por,
        $rol_creador
    ]);
    $cotizacion_id = $pdo->lastInsertId();

    // Insertar detalles de la cotización
    foreach ($detalles as $detalle) {
        $stmt = $pdo->prepare("INSERT INTO cotizaciones_detalle (id_cotizacion, id_examen, nombre_examen, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cotizacion_id,
            $detalle['examen_id'],
            $detalle['nombre'],
            $detalle['precio_unitario'],
            $detalle['cantidad'],
            $detalle['subtotal']
        ]);
    }

    echo "<div class='alert alert-success'>¡Cotización guardada exitosamente para el cliente #$cliente_id!</div>";
    // Puedes redirigir si lo deseas:
    header("Location: dashboard.php?vista=cotizaciones");
    exit;
}
?>
