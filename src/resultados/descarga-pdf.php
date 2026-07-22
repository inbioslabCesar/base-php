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
$resolveEmpresaAssetPath = static function (string $storedPath): string {
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return '';
    }
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));
    if (strpos($normalized, '../uploads/') === 0) {
        $normalized = substr($normalized, 3);
    }
    if (strpos($normalized, 'uploads/') === 0) {
        return rtrim(__DIR__ . '/../../', '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }
    return rtrim(__DIR__ . '/../', '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($storedPath, './'));
};
$logo = !empty($empresa['logo']) ? $resolveEmpresaAssetPath((string)$empresa['logo']) : '';
$firma = !empty($empresa['firma']) ? $resolveEmpresaAssetPath((string)$empresa['firma']) : '';
$items = obtenerItemsResultados($pdo, $rows);
$reporte = armarHtmlReporte($paciente, $referencia, $empresa, $items);

$mpdf = new Mpdf([
    // Reservar espacio estable para un header alto (logo + QR + datos de paciente)
    // y evitar solapes del contenido desde la segunda hoja.
    'margin_top' => 75,
    'margin_bottom' => 35,
    'margin_header' => 4,
    'margin_footer' => 4
]);

// Generar código QR con datos clave para el header
$qrText = 'Laboratorio: ' . ($empresa['nombre'] ?? 'INBIOSLAB')
    . ' | Resultado ID: ' . ($paciente['id'] ?? '')
    . ' | Paciente: ' . ($paciente['nombre'] ?? '')
    . ' | DNI: ' . ($paciente['dni'] ?? '')
    . ' | Fecha: ' . ($paciente['fecha'] ?? '');
$qrBase64 = '';
try {
    if (class_exists('Endroid\\QrCode\\QrCode')) {
        $qr = new \Endroid\QrCode\QrCode($qrText);
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qr);
        $qrBase64 = base64_encode($result->getString());
    }
} catch (\Exception $e) {}

$logo_html = $logo && file_exists($logo) ? '<img src="' . $logo . '" class="logo" style="max-height:86px;max-width:150px;">' : '';
$qr_html = $qrBase64 ? '<img src="data:image/png;base64,' . $qrBase64 . '" style="max-height:86px;max-width:86px;">' : '<div style="width:86px;height:86px;border:2px solid #222;text-align:center;line-height:86px;font-size:34px;">X</div>';
$direccion_html = '<div style="font-size:16px;font-weight:bold;color:#1a237e;line-height:1.2;">' . htmlspecialchars($empresa['nombre']) . '</div>'
    . '<div style="font-size:12px;color:#555;line-height:1.2;">' . htmlspecialchars($empresa['dominio'] ?? '') . '</div>'
    . '<div style="font-size:12px;color:#555;line-height:1.2;">RUC: ' . htmlspecialchars($empresa['ruc'] ?? '') . '</div>'
    . '<div style="font-size:12px;color:#555;line-height:1.2;">' . htmlspecialchars($empresa['direccion']) . '</div>'
    . '<div style="font-size:12px;color:#555;line-height:1.2;">Tel: ' . htmlspecialchars($empresa['telefono']) . ' | Cel: ' . htmlspecialchars($empresa['celular']) . '</div>';

$headerHtml = '
    <table style="width:100%;margin-bottom:0px;border-bottom:1px solid #e2e8f0;">
        <tr>
            <td style="width:120px;vertical-align:middle;text-align:left;padding:0;">' . $logo_html . '</td>
            <td style="width:50%;vertical-align:middle;text-align:center;padding:0;" colspan="1">' . $qr_html . '</td>
            <td style="width:120px;vertical-align:middle;text-align:right;padding:0;">' . $direccion_html . '</td>
        </tr>
    </table>
    <table class="datos-cliente-tabla" style="font-size:12px; line-height:1.2; margin:6px 0 10px 0;">
        <tr><td style="padding:1px 6px;"><strong>Paciente:</strong> ' . htmlspecialchars($paciente['nombre']) . '</td><td style="padding:1px 6px;"><strong>Código Paciente:</strong> ' . htmlspecialchars($paciente['codigo_cliente']) . '</td></tr>
        <tr><td style="padding:1px 6px;"><strong>DNI:</strong> ' . htmlspecialchars($paciente['dni']) . '</td><td style="padding:1px 6px;"><strong>Edad:</strong> ' . htmlspecialchars($paciente['edad']) . '   <strong>Sexo:</strong> ' . htmlspecialchars($paciente['sexo']) . '</td></tr>
        <tr><td colspan="2" style="padding:1px 6px;"><strong>Referencia:</strong> ' . htmlspecialchars($referencia) . '</td></tr>
        <tr><td colspan="2" style="padding:1px 6px;"><strong>Fecha:</strong> ' . htmlspecialchars($paciente['fecha']) . '</td></tr>
    </table>
    <div style="text-align:center; font-size:18px; font-weight:700; color:#1a237e; letter-spacing:1px; margin:4px 0 2px 0;">Reporte de Resultados</div>
';
$mpdf->SetHTMLHeader($headerHtml, 'O', true);
$mpdf->SetHTMLHeader($headerHtml, 'E', true);
$firma_html = $firma && file_exists($firma) ? '<img src="' . $firma . '" style="height:95px;"><br>' : '';
$mpdf->SetHTMLFooter('<div class="firma-footer">' . $firma_html . '<hr class="my-3" style="margin:8px 0;"><div style="font-size: 11px; color: #555; text-align:left;">Informe confidencial. Prohibida su reproducción total o parcial.<br><strong>Nota:</strong> Resultados fuera de los rangos referenciales se verán con un <strong>*</strong>.</div></div>');
$mpdf->WriteHTML($reporte['css'], \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($reporte['html'], \Mpdf\HTMLParserMode::HTML_BODY);
$nombrePaciente = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['-', 'a', 'e', 'i', 'o', 'u', 'n'], $paciente['nombre']));
$fechaReporte = date('d-m-Y', strtotime($paciente['fecha']));
$nombreArchivo = $nombrePaciente . '-' . $fechaReporte . '.pdf';
$pdfContent = $mpdf->Output('', 'S');

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($pdfContent));

echo $pdfContent;
exit;
