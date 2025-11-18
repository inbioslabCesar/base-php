<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';
// Agregar estos dos require para los componentes:
require_once __DIR__ . '/resultados_pdf_datos.php';
require_once __DIR__ . '/resultados_pdf_html.php';
use Mpdf\Mpdf;

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
if (!$cotizacion_id) {
    die('ID de cotización no proporcionado.');
}

$cot = obtenerDatosCotizacion($pdo, $cotizacion_id);
$rows = obtenerResultadosExamenes($pdo, $cotizacion_id);
if (!$rows || count($rows) === 0) {
    die('No se encontraron resultados para esta cotización.');
}
$primer_row = $rows[0];
$paciente = [
    "nombre"         => trim($primer_row['nombre'] . ' ' . $primer_row['apellido']),
    "codigo_cliente" => $primer_row['codigo_cliente'] ?? "",
    "dni"            => ($primer_row['tipo_documento'] ?? '') === 'sin_dni' ? '--' : ($primer_row['dni'] ?? ""),
    "edad"           => $primer_row['edad'],
    "sexo"           => $primer_row['sexo'],
    "fecha"          => $primer_row['fecha_ingreso'],
    "id"             => $primer_row['cliente_id']
];
$referencia = '';
$referencia_personalizada = !empty($cot['referencia_personalizada']) ? trim($cot['referencia_personalizada']) : '';
if (!empty($referencia_personalizada)) {
    $referencia = $referencia_personalizada;
} else {
    if (!empty($cot['id_empresa']) && (!empty($cot['nombre_comercial']) || !empty($cot['razon_social']))) {
        $referencia = $cot['nombre_comercial'] ?: $cot['razon_social'];
    } elseif (!empty($cot['id_convenio']) && !empty($cot['nombre_convenio'])) {
        $referencia = $cot['nombre_convenio'];
    } else {
        $referencia = 'Particular';
    }
}
$empresa = obtenerDatosEmpresa($pdo);
$logo = $empresa['logo'] ? __DIR__ . '/../' . ltrim($empresa['logo'], './') : '';
$firma = $empresa['firma'] ? __DIR__ . '/../' . ltrim($empresa['firma'], './') : '';
$items = obtenerItemsResultados($pdo, $rows);
$reporte = armarHtmlReporte($paciente, $referencia, $empresa, $items);

$mpdf = new Mpdf([
    'margin_top' => 62,
    'margin_bottom' => 35
]);
$logo_html = $logo && file_exists($logo) ? '<img src="' . $logo . '" class="logo">' : '';
$direccion_html = '<strong style="font-size:15px;color:#1a237e;">' . htmlspecialchars($empresa['nombre']) . '</strong><br>' . htmlspecialchars($empresa['direccion']) . '<br>Tel: ' . htmlspecialchars($empresa['telefono']) . '<br>Celular: ' . htmlspecialchars($empresa['celular']);
$mpdf->SetHTMLHeader('<table class="encabezado-tabla"><tr><td style="width:60%">' . $logo_html . '</td><td class="direccion" style="width:40%">' . $direccion_html . '</td></tr></table><table class="datos-cliente-tabla"><tr><td><strong>Paciente:</strong> ' . htmlspecialchars($paciente['nombre']) . '</td><td><strong>Código Paciente:</strong> ' . htmlspecialchars($paciente['codigo_cliente']) . '</td></tr><tr><td><strong>DNI:</strong> ' . htmlspecialchars($paciente['dni']) . '</td><td><strong>Edad:</strong> ' . htmlspecialchars($paciente['edad']) . '   <strong>Sexo:</strong> ' . htmlspecialchars($paciente['sexo']) . '</td></tr><tr><td colspan="2"><strong>Referencia:</strong> ' . htmlspecialchars($referencia) . '</td></tr><tr><td colspan="2"><strong>Fecha:</strong> ' . htmlspecialchars($paciente['fecha']) . '</td></tr></table>', 'O', true);
$firma_html = $firma && file_exists($firma) ? '<img src="' . $firma . '" style="height:65px;"><br>' : '';
$mpdf->SetHTMLFooter('<div class="firma-footer">' . $firma_html . '<hr class="my-3" style="margin:8px 0;"><div style="font-size: 11px; color: #555; text-align:left;">Informe confidencial. Prohibida su reproducción total o parcial.</div></div>');
$mpdf->WriteHTML($reporte['css'], \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($reporte['html'], \Mpdf\HTMLParserMode::HTML_BODY);
$nombrePaciente = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['-', 'a', 'e', 'i', 'o', 'u', 'n'], $paciente['nombre']));
$fechaReporte = date('d-m-Y', strtotime($paciente['fecha']));
$nombreArchivo = $nombrePaciente . '-' . $fechaReporte . '.pdf';
$mpdf->Output($nombreArchivo, 'I');
exit;
