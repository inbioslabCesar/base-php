<?php
// Acción: genera el PDF de la cotización express. Recalcula todo en el servidor; no escribe en BD.
require_once __DIR__ . '/../funciones/cotizador_express_servicio.php';

date_default_timezone_set('America/Lima');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

$conPrecios = cotExpContexto($pdo)['muestra_precios'];
$calc = cotExpCalcular(
    $pdo,
    $conPrecios,
    json_decode((string)($_POST['items'] ?? '[]'), true),
    (string)($_POST['perfil'] ?? 'particular'),
    (float)($_POST['desc_extra'] ?? 0)
);
if (empty($calc['lineas'])) {
    http_response_code(400);
    echo 'Agrega al menos un examen a la cotización.';
    exit;
}

$vigencia = max(1, min(90, (int)($_POST['vigencia'] ?? 7)));
$perfil = $calc['perfil'];

$html = cotExpPdfHtml($pdo, $calc + [
    'con_precios' => $conPrecios,
    'destinatario' => mb_substr(trim((string)($_POST['destinatario'] ?? '')), 0, 120),
    'tarifa_texto' => $perfil ? ucfirst($perfil['tipo']) . ' ' . $perfil['nombre'] : '',
    'vence' => date('d/m/Y', strtotime('+' . $vigencia . ' days')),
    'fecha' => date('d/m/Y'),
    'folio' => preg_match('/^EXP-\d{4}-\d{5}$/', (string)($_POST['folio'] ?? '')) ? $_POST['folio'] : '',
    'incluir_indicaciones' => ($_POST['indicaciones'] ?? '1') === '1',
]);

cotExpPdfSalida($html, ($conPrecios ? 'Cotizacion_' : 'Informacion_') . date('Ymd_His') . '.pdf');
exit;
