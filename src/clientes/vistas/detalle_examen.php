<?php
function mostrarCampo($etiqueta, $valor) {
    if (!empty($valor)) {
        echo '<p><b>' . htmlspecialchars($etiqueta) . ':</b> ' . ucfirst(strtolower($valor)) . '</p>';
    }
}
?>

<div class="container mt-4">
    <h2><?= ucfirst(strtolower($examen['nombre'])) ?></h2>
    <p><b>Precio:</b> <?= htmlspecialchars($examen['precio_publico']) ?> </p>
    <?php mostrarCampo('Descripción', $examen['descripcion']); ?>
    <?php mostrarCampo('Área', $examen['area']); ?>
    <?php mostrarCampo('Metodología', $examen['metodologia']); ?>
    <?php mostrarCampo('Tiempo de Respuesta', $examen['tiempo_respuesta']); ?>
    <?php mostrarCampo('Preanalítica Cliente', $examen['preanalitica_cliente']); ?>
    <?php mostrarCampo('Preanalítica Referencias', $examen['preanalitica_referencias']); ?>
    <?php mostrarCampo('Tipo de Muestra', $examen['tipo_muestra']); ?>
    <?php mostrarCampo('Tipo de Tubo (color)/Recipiente', $examen['tipo_tubo']); ?>
    <?php mostrarCampo('Observaciones', $examen['observaciones']); ?>
</div>
