<?php
// Inicializar variables en modo edición o creación
$esEdicion = isset($_SESSION['promocion_editar']);
if ($esEdicion) {
    $p = $_SESSION['promocion_editar'];
    $id = $p['id'];
    $titulo = $p['titulo'];
    $descripcion = $p['descripcion'];
    $precio_promocional = $p['precio_promocional'];
    $fecha_inicio = $p['fecha_inicio'];
    $fecha_fin = $p['fecha_fin'];
    $imagen = $p['imagen'];
    unset($_SESSION['promocion_editar']); // Limpia la sesión
} else {
    $titulo = $descripcion = $precio_promocional = $fecha_inicio = $fecha_fin = $imagen = '';
}
?>

<form action="promociones/<?php echo $esEdicion ? "editar_promocion.php?id=$id" : "crear_promocion.php"; ?>" method="POST" enctype="multipart/form-data">
    <!-- Campos del formulario -->
    <input type="text" name="titulo" value="<?php echo htmlspecialchars($titulo); ?>" required>
    <textarea name="descripcion"><?php echo htmlspecialchars($descripcion); ?></textarea>
    <input type="number" name="precio_promocional" value="<?php echo htmlspecialchars($precio_promocional); ?>" required>
    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
    <input type="file" name="imagen" accept="image/*">
    <?php if ($esEdicion && $imagen): ?>
        <img src="../images/<?php echo htmlspecialchars($imagen); ?>" width="100">
    <?php endif; ?>
    <button type="submit"><?php echo $esEdicion ? "Actualizar" : "Crear"; ?> Promoción</button>
</form>
