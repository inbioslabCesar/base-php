<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
function capitalizar($texto)
{
    return mb_convert_case(trim($texto), MB_CASE_TITLE, "UTF-8");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = capitalizar($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $area = capitalizar($_POST['area'] ?? '');
    $metodologia = capitalizar($_POST['metodologia'] ?? '');
    $tiempo_respuesta = trim($_POST['tiempo_respuesta'] ?? '');
    $preanalitica_cliente = trim($_POST['preanalitica_cliente'] ?? '');
    $preanalitica_referencias = trim($_POST['preanalitica_referencias'] ?? '');
    $tipo_muestra = capitalizar($_POST['tipo_muestra'] ?? '');
    $tipo_tubo = capitalizar($_POST['tipo_tubo'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $precio_publico = floatval($_POST['precio_publico'] ?? 0);
    $adicional = $_POST['adicional'] ?? '';
    $vigente = isset($_POST['vigente']) ? 1 : 0; 


    if (empty($nombre) || empty($adicional)) {
    $_SESSION['error'] = 'Faltan datos obligatorios';
    header('Location: dashboard.php?vista=format'); // Cambia por la ruta correcta
    exit;
}

if (json_decode($adicional) === null && json_last_error() !== JSON_ERROR_NONE) {
    $_SESSION['error'] = 'El campo adicional no contiene un JSON válido';
    header('Location: dashboard.php?vista=format'); // Cambia por la ruta correcta
    exit;
}

    try {
        $stmt = $pdo->prepare("INSERT INTO examenes 
            (codigo, nombre, descripcion, area, metodologia, tiempo_respuesta, preanalitica_cliente, preanalitica_referencias, tipo_muestra, tipo_tubo, observaciones, precio_publico,adicional, vigente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
        $stmt->execute([
            $codigo,
            $nombre,
            $descripcion,
            $area,
            $metodologia,
            $tiempo_respuesta,
            $preanalitica_cliente,
            $preanalitica_referencias,
            $tipo_muestra,
            $tipo_tubo,
            $observaciones,
            $precio_publico,
            $adicional,
            $vigente
        ]);
        $_SESSION['mensaje'] = "Examen creado correctamente.";
        header('Location: dashboard.php?vista=examenes');
        exit;
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al crear examen: " . $e->getMessage();
        header('Location: dashboard.php?vista=form_examen');
        exit;
    }
}
