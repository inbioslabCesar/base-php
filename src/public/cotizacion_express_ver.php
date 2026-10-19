<?php
// Enlace público (sin login) a una cotización express, identificado por un token aleatorio de 40 caracteres.
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../cotizador_express/funciones/cotizador_express_servicio.php';

date_default_timezone_set('America/Lima');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$pagina = static function (string $titulo, string $mensaje, int $codigo) use ($pdo): void {
    $emp = cotExpEmpresa($pdo);
    http_response_code($codigo);
    $contacto = trim(implode(' · ', array_filter([$emp['telefono'], $emp['celular']])));
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow"><title>' . htmlspecialchars($titulo) . '</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f0fdfa;display:grid;place-items:center;min-height:100vh;margin:0}'
        . '.c{max-width:440px;background:#fff;border-radius:16px;padding:32px;box-shadow:0 10px 30px rgba(0,0,0,.08);text-align:center}'
        . 'h1{font-size:1.3rem;color:#0f766e}p{color:#475569}</style></head><body><div class="c"><h1>'
        . htmlspecialchars($titulo) . '</h1><p>' . htmlspecialchars($mensaje) . '</p><p><b>' . htmlspecialchars($emp['nombre']) . '</b>'
        . ($contacto !== '' ? '<br>' . htmlspecialchars($contacto) : '') . '</p></div></body></html>';
    exit;
};

$token = (string)($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{40}$/', $token) || !cotExpHistorialDisponible($pdo)) {
    $pagina('Cotización no disponible', 'El enlace no es válido.', 404);
}

$stmt = $pdo->prepare('SELECT * FROM cotizaciones_express WHERE token = ? LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['estado'] === 'anulada') {
    $pagina('Cotización no disponible', 'Esta cotización ya no está disponible. Comuníquese con el laboratorio.', 404);
}
if (cotExpEstadoEfectivo($row) === 'vencida') {
    $pagina('Cotización vencida', 'Esta cotización venció el ' . date('d/m/Y', strtotime($row['vence_en'])) . '. Solicite una nueva y con gusto se la enviamos.', 410);
}

$stmtIt = $pdo->prepare('SELECT examen_id, nombre_examen, cantidad, precio_unitario, subtotal FROM cotizaciones_express_items WHERE express_id = ? ORDER BY id');
$stmtIt->execute([(int)$row['id']]);

$info = [];
foreach (cotExpCatalogo($pdo, false) as $ex) {
    $info[$ex['id']] = $ex;
}
$lineas = [];
foreach ($stmtIt->fetchAll(PDO::FETCH_ASSOC) as $it) {
    $ex = $info[(int)$it['examen_id']] ?? [];
    $lineas[] = [
        'id' => (int)$it['examen_id'],
        'nombre' => (string)$it['nombre_examen'],
        'tipo_muestra' => (string)($ex['tipo_muestra'] ?? ''),
        'tiempo_respuesta' => (string)($ex['tiempo_respuesta'] ?? ''),
        'preanalitica_cliente' => (string)($ex['preanalitica_cliente'] ?? ''),
        'cant' => (int)$it['cantidad'],
        'unit' => (float)$it['precio_unitario'],
        'sub' => (float)$it['subtotal'],
    ];
}

$html = cotExpPdfHtml($pdo, [
    'con_precios' => true,
    'lineas' => $lineas,
    'subtotal' => (float)$row['subtotal'],
    'desc_pct' => (float)$row['descuento_extra'],
    'desc_monto' => round((float)$row['subtotal'] - (float)$row['total'], 2),
    'total' => (float)$row['total'],
    'destinatario' => (string)$row['destinatario_nombre'],
    'tarifa_texto' => $row['tarifa_tipo'] !== 'particular' ? ucfirst((string)$row['tarifa_tipo']) . ' ' . $row['tarifa_nombre'] : '',
    'vence' => date('d/m/Y', strtotime($row['vence_en'])),
    'fecha' => date('d/m/Y', strtotime($row['creado_en'])),
    'folio' => (string)$row['folio'],
    'incluir_indicaciones' => (int)$row['incluir_indicaciones'] === 1,
]);

cotExpPdfSalida($html, (string)$row['folio'] . '.pdf');
exit;
