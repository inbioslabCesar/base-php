<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$dni = $_POST['dni'] ?? '';
$especialidad = $_POST['especialidad'] ?? '';
$descuento = $_POST['descuento'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;

if ($id && $nombre) {
    try {
        $stmt = $pdo->prepare("UPDATE convenios SET nombre=?, dni=?, especialidad=?, descuento=?, descripcion=? WHERE id=?");
        $stmt->execute([
            mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
            $dni,
            mb_convert_case($especialidad, MB_CASE_TITLE, "UTF-8"),
            $descuento,
            $descripcion,
            $id
        ]);
        $_SESSION['mensaje'] = "Convenio actualizado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al actualizar el convenio: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "Datos incompletos para actualizar el convenio.";
}

header('Location: dashboard.php?vista=convenios');
exit;
?>
