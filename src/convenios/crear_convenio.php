<?php
require_once __DIR__ . '/../conexion/conexion.php';

$nombre = $_POST['nombre'] ?? '';
$dni = $_POST['dni'] ?? '';
$especialidad = $_POST['especialidad'] ?? '';
$descuento = $_POST['descuento'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;

if ($nombre) {
    try {
        $stmt = $pdo->prepare("INSERT INTO convenios (nombre, dni, especialidad, descuento, descripcion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
            $dni,
            mb_convert_case($especialidad, MB_CASE_TITLE, "UTF-8"),
            $descuento,
            $descripcion
        ]);
        $_SESSION['mensaje'] = "Convenio creado exitosamente.";
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al crear el convenio: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "El nombre es obligatorio.";
}

header('Location: dashboard.php?vista=convenios');
exit;
?>
