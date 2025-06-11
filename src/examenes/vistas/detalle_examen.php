<?php
function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>
<div class="modal-header">
    <h5 class="modal-title" id="modalLabel<?= $examen_detalle['id'] ?? '' ?>">Detalle del Examen</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
<div class="modal-body">
    <strong>ID:</strong> <?= htmlspecialchars($examen_detalle['id'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Código:</strong> <?= htmlspecialchars($examen_detalle['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Nombre:</strong> <?= htmlspecialchars(capitalizar($examen_detalle['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Descripción:</strong> <?= nl2br(htmlspecialchars($examen_detalle['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
    <strong>Área:</strong> <?= htmlspecialchars(capitalizar($examen_detalle['area'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Metodología:</strong> <?= htmlspecialchars(capitalizar($examen_detalle['metodologia'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Tiempo Respuesta:</strong> <?= htmlspecialchars($examen_detalle['tiempo_respuesta'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Preanalítica Cliente:</strong> <?= htmlspecialchars($examen_detalle['preanalitica_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Preanalítica Referencias:</strong> <?= htmlspecialchars($examen_detalle['preanalitica_referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Tipo de Muestra:</strong> <?= htmlspecialchars($examen_detalle['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Tipo de Tubo:</strong> <?= htmlspecialchars($examen_detalle['tipo_tubo'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($examen_detalle['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
    <strong>Precio Público:</strong> <?= htmlspecialchars($examen_detalle['precio_publico'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Adicional:</strong> <?= nl2br(htmlspecialchars($examen_detalle['adicional'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
    <strong>Vigente:</strong> <?= isset($examen_detalle['vigente']) && $examen_detalle['vigente'] ? 'Sí' : 'No' ?><br>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
