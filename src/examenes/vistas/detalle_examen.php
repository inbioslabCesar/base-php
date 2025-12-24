<?php
function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}

$vigenteBadge = (isset($examen_detalle['vigente']) && $examen_detalle['vigente'])
    ? '<span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-green-700 text-xs font-semibold">Sí</span>'
    : '<span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-red-700 text-xs font-semibold">No</span>';
$obs = trim($examen_detalle['observaciones'] ?? '');
?>
<div class="modal-header bg-indigo-600 text-white">
    <h5 class="modal-title" id="modalLabel<?= $examen_detalle['id'] ?? '' ?>">Detalle del Examen</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
<div class="modal-body p-0">
    <div class="p-6">
        <div class="mb-4">
            <div class="text-sm text-slate-500">Código</div>
            <div class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($examen_detalle['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-sm text-slate-500 mt-2">Nombre</div>
            <div class="text-base font-medium text-slate-800"><?= htmlspecialchars(capitalizar($examen_detalle['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="font-semibold text-slate-600">ID</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Área</dt>
                <dd class="text-slate-800"><?= htmlspecialchars(capitalizar($examen_detalle['area'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Metodología</dt>
                <dd class="text-slate-800"><?= htmlspecialchars(capitalizar($examen_detalle['metodologia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Tiempo Respuesta</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['tiempo_respuesta'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-semibold text-slate-600">Descripción</dt>
                <dd class="text-slate-800"><?= nl2br(htmlspecialchars($examen_detalle['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Preanalítica Cliente</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['preanalitica_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Preanalítica Referencias</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['preanalitica_referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Tipo de Muestra</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Tipo de Tubo</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['tipo_tubo'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <?php if ($obs !== ''): ?>
            <div class="md:col-span-2">
                <dt class="font-semibold text-slate-600">Observaciones</dt>
                <dd class="text-slate-800"><?= nl2br(htmlspecialchars($obs, ENT_QUOTES, 'UTF-8')) ?></dd>
            </div>
            <?php endif; ?>
            <div>
                <dt class="font-semibold text-slate-600">Precio Público</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($examen_detalle['precio_publico'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-600">Vigente</dt>
                <dd class="text-slate-800"><?= $vigenteBadge ?></dd>
            </div>
        </dl>
    </div>
</div>
<div class="modal-footer bg-gray-50">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
