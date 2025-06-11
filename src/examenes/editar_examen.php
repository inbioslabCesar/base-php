<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
$codigo = $_POST['codigo'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? null;
$area = $_POST['area'] ?? null;
$metodologia = $_POST['metodologia'] ?? null;
$tiempo_respuesta = $_POST['tiempo_respuesta'] ?? null;
$preanalitica_cliente = $_POST['preanalitica_cliente'] ?? null;
$preanalitica_referencias = $_POST['preanalitica_referencias'] ?? null;
$tipo_muestra = $_POST['tipo_muestra'] ?? null;
$tipo_tubo = $_POST['tipo_tubo'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;
$precio_publico = $_POST['precio_publico'] ?? null;
$adicional = trim($_POST['adicional'] ?? '');
$vigente = isset($_POST['vigente']) ? (int)$_POST['vigente'] : 1;

// Asegurar JSON válido
if ($adicional === '') {
    $adicional = '{}';
}
if (json_decode($adicional) === null && $adicional !== '{}') {
    $_SESSION['mensaje'] = "El campo adicional debe ser un JSON válido (ejemplo: {}).";
    header('Location: dashboard.php?vista=examenes');
    exit;
}

if ($id && $codigo && $nombre && $precio_publico !== null) {
    try {
        $stmt = $pdo->prepare("UPDATE examenes SET codigo=?, nombre=?, descripcion=?, area=?, metodologia=?, tiempo_respuesta=?, preanalitica_cliente=?, preanalitica_referencias=?, tipo_muestra=?, tipo_tubo=?, observaciones=?, precio_publico=?, adicional=?, vigente=? WHERE id=?");
        $stmt->execute([
            $codigo,
            mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
            $descripcion,
            mb_convert_case($area, MB_CASE_TITLE, "UTF-8"),
            mb_convert_case($metodologia, MB_CASE_TITLE, "UTF-8"),
            $tiempo_respuesta,
            $preanalitica_cliente,
            $preanalitica_referencias,
            $tipo_muestra,
            $tipo_tubo,
            $observaciones,
            $precio_publico,
            $adicional,
            $vigente,
            $id
        ]);
        $_SESSION['mensaje'] = "Examen actualizado exitosamente.";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al actualizar el examen: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el examen.";
}

header('Location: dashboard.php?vista=examenes');
exit;
?>
