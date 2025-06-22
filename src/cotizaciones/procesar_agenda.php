<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Validar datos recibidos
$id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
$tipo_toma = $_POST['tipo_toma'] ?? null;
$fecha_toma = $_POST['fecha_toma'] ?? null;
$hora_toma = $_POST['hora_toma'] ?? null;
$direccion_toma = $_POST['direccion_toma'] ?? null;

if ($_SESSION['rol'] == 'recepcionista') {
    if (empty($fecha_toma)) {
        $fecha_toma = date('Y-m-d');
    }
    if (empty($hora_toma)) {
        $hora_toma = date('H:i');
    }
}

// Validación básica
$errores = [];
if (!$id_cotizacion) $errores[] = "Cotización no especificada.";
if (!$tipo_toma) $errores[] = "Selecciona el tipo de toma.";
if (!$fecha_toma) $errores[] = "Selecciona la fecha.";
if (!$hora_toma) $errores[] = "Selecciona la hora.";
if ($tipo_toma == 'domicilio' && !$direccion_toma) $errores[] = "La dirección es obligatoria para toma a domicilio.";

if (count($errores) === 0) {
    // Actualizar la cotización
    $stmt = $pdo->prepare("UPDATE cotizaciones SET tipo_toma = ?, fecha_toma = ?, hora_toma = ?, direccion_toma = ? WHERE id = ?");
    $stmt->execute([$tipo_toma, $fecha_toma, $hora_toma, $direccion_toma, $id_cotizacion]);
    // Agrega aquí tu lógica para la redirección usando $acciones o muestra un mensaje de éxito
    if ($_SESSION['rol'] == 'recepcionista') {
        header("Location: dashboard.php?vista=cotizaciones");
        exit;
    } elseif ($_SESSION['rol'] == 'cliente') {
        header("Location: dashboard.php?vista=cotizaciones_clientes");
        exit;
    } else {
        // Redirección por defecto si el rol no está definido
        header("Location: dashboard.php");
        exit;
    }
} else {
    // Puedes guardar los errores en sesión o mostrarlos directamente en la vista
    $_SESSION['errores_agenda'] = $errores;
    $acciones[] = [
        'tipo' => 'redireccion',
        'url' => 'dashboard.php?vista=agendar_cita&id_cotizacion=' . $id_cotizacion
    ];
}
