<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cotizacion = intval($_POST['id_cotizacion']);
    $tipo_toma = $_POST['tipo_toma'];
    $fecha_toma = $_POST['fecha_toma'];
    $hora_toma = $_POST['hora_toma'];
    $direccion_toma = ($tipo_toma === 'domicilio') ? trim($_POST['direccion_toma']) : null;

    // Determinar si es agendada o inmediata
    $hoy = date('Y-m-d');
    $hora_actual = date('H:i');
    if ($tipo_toma === 'laboratorio') {
        if (
            ($fecha_toma > $hoy) ||
            ($fecha_toma === $hoy && $hora_toma > $hora_actual)
        ) {
            $estado_muestra = 'pendiente'; // Agendada a futuro
        } else {
            $estado_muestra = 'realizada'; // Es para ya mismo
        }
    } else if ($tipo_toma === 'domicilio') {
        $estado_muestra = 'pendiente'; // Siempre es agendada
    } else {
        $estado_muestra = 'pendiente';
    }

    $stmt = $pdo->prepare("UPDATE cotizaciones SET tipo_toma = ?, fecha_toma = ?, hora_toma = ?, direccion_toma = ?, estado_muestra = ? WHERE id = ?");
    $stmt->execute([$tipo_toma, $fecha_toma, $hora_toma, $direccion_toma, $estado_muestra, $id_cotizacion]);

    $rol = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
if ($rol === 'cliente') {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=cotizaciones_clientes');
} elseif ($rol === 'empresa') {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=cotizaciones_empresas');
} elseif ($rol === 'convenio') {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=cotizaciones_convenios');
} else {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=cotizaciones');
}
exit;

}
