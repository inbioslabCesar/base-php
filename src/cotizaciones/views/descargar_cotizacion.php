<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../conexion/conexion.php';

// Ajustar zona horaria a Perú
date_default_timezone_set('America/Lima');

if (!isset($_GET['id'])) {
    die('ID de cotización no proporcionado.');
}
$id_cotizacion = $_GET['id'] ?? null;
if (!$id_cotizacion) {
    die("No se recibió el ID de la cotización.");
}

function mayus($texto) {
    return ucwords(strtolower($texto));
}

$id = intval($_GET['id']);

// Traer datos generales de la cotización y cliente (con DNI)
$stmt = $pdo->prepare("SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni AS dni_cliente, cl.codigo_cliente AS codigo_cliente
    FROM cotizaciones c 
    LEFT JOIN clientes cl ON c.id_cliente = cl.id 
    WHERE c.id = :id");
$stmt->execute([':id' => $id]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    die('Cotización no encontrada.');
}

// Traer detalle de exámenes con condiciones del paciente
$stmt = $pdo->prepare("
    SELECT cd.*, e.preanalitica_cliente, e.nombre AS nombre_examen
    FROM cotizaciones_detalle cd
    LEFT JOIN examenes e ON cd.id_examen = e.id
    WHERE cd.id_cotizacion = ?
");
$stmt->execute([$id_cotizacion]);
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traer datos de la empresa
$stmtEmpresa = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
$logo = BASE_URL . 'images/empresa/logo_empresa.png'; // Ruta pública para mPDF
// Dominio de la empresa (usa el guardado en config_empresa; si falta, usa el host actual)
$dominioEmpresa = !empty($empresa['dominio']) ? $empresa['dominio'] : ($_SERVER['HTTP_HOST'] ?? '');

$html = '
<html>
<head>
<style>
    /* Reduce fuentes y espacios para PDF compacto */
    body { font-family: Arial, sans-serif; font-size: 10pt; line-height:1.1 }
    .datos-empresa { margin-bottom: 6px; }
    .datos-cliente, .datos-cotizacion { margin-bottom: 6px; font-size:10pt }
    .datos-cliente-table { width:100%; border-collapse: collapse; margin-bottom:6px }
    .datos-cliente-table td { vertical-align: top; padding: 2px 6px; }
    .label-small { font-weight:700; padding-right:6px; }
    .tabla-campos { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .tabla-campos td { padding: 3px 6px; }
    .tabla-examenes { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .tabla-examenes th, .tabla-examenes td { border: 1px solid #bbb; padding: 6px 6px; font-size:10pt }
    .tabla-examenes th { background: #f4f4f4; font-size:10pt }
    .tabla-examenes td { padding-top:4px; padding-bottom:4px }
    /* Ajustes para que las filas ocupen menos espacio */
    .tabla-examenes td, .tabla-examenes th { line-height:1.05 }
</style>
</head>
<body>
    <table width="100%" style="margin-bottom:12px;">
    <tr>
        <td width="40%" valign="top">
            <img src="' . $logo . '" width="100" alt="Logo Empresa">
        </td>
        <td width="60%" valign="top" align="right" style="font-size:11pt;">
            <strong>' . ($empresa['nombre']) . '</strong><br>
            ' . mayus($empresa['direccion']) . '<br>
            RUC: ' . $empresa['ruc'] . '<br>
            Tel: ' . $empresa['telefono'] . '<br>
            Celular: ' . $empresa['celular'] . '<br>
            Email: ' . $empresa['email'] . '<br>
            ' . htmlspecialchars($dominioEmpresa) . '
        </td>
    </tr>
</table>

    <div class="datos-cliente">
        <!-- Tabla en dos columnas para datos del paciente -->
        <table class="datos-cliente-table">
            <tr>
                <td style="width:50%">
                    <div><span class="label-small">Paciente:</span> ' . mayus($cotizacion['nombre_cliente'] . ' ' . $cotizacion['apellido_cliente']) . '</div>
                    <div><span class="label-small">DNI:</span> ' . htmlspecialchars($cotizacion['dni_cliente']) . '</div>
                    <div><span class="label-small">Cód. Paciente:</span> ' . htmlspecialchars($cotizacion['codigo_cliente'] ?? '') . '</div>
                </td>
                <td style="width:50%">
                    <div><span class="label-small">Cotización:</span> ' . htmlspecialchars($cotizacion['codigo']) . '</div>
                    <div><span class="label-small">Fecha:</span> ' . htmlspecialchars($cotizacion['fecha']) . '</div>
                    <div>
                        <span class="label-small">Referencia:</span>
                        ';
                        // Mostrar referencia: Particular, Empresa o Convenio
                        $tipo = $cotizacion['tipo_usuario'] ?? '';
                        if ($tipo === 'empresa' && !empty($cotizacion['id_empresa'])) {
                            $stmtEmp = $pdo->prepare("SELECT nombre_comercial, razon_social FROM empresas WHERE id = ?");
                            $stmtEmp->execute([$cotizacion['id_empresa']]);
                            $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
                            $html .= 'Empresa: ' . htmlspecialchars($emp['nombre_comercial'] ?? $emp['razon_social'] ?? '');
                        } elseif ($tipo === 'convenio' && !empty($cotizacion['id_convenio'])) {
                            $stmtConv = $pdo->prepare("SELECT nombre FROM convenios WHERE id = ?");
                            $stmtConv->execute([$cotizacion['id_convenio']]);
                            $conv = $stmtConv->fetch(PDO::FETCH_ASSOC);
                            $html .= 'Convenio: ' . htmlspecialchars($conv['nombre'] ?? '');
                        } else {
                            $html .= 'Particular';
                        }
        $html .= '    </div>';
        $html .= '    </td>';
        $html .= '    </tr>';
        $html .= '    </table>';
    $html .= "</div>\n";

    $html .= '<table class="tabla-campos">'
        . '<tr>'
        . '<td><strong>Lugar de toma:</strong></td>'
        . '<td>' . mayus($cotizacion['tipo_toma'] ?? 'No asignado') . '</td>'
        . '<td><strong>Dirección de toma:</strong></td>'
        . '<td>' . mayus($cotizacion['direccion_toma'] ?? 'No asignada') . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td><strong>Fecha de toma:</strong></td>'
        . '<td>' . htmlspecialchars($cotizacion['fecha_toma'] ?? 'No asignada') . '</td>'
        . '<td><strong>Hora de toma:</strong></td>'
        . '<td>' . htmlspecialchars($cotizacion['hora_toma'] ?? 'No asignada') . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td><strong>Rol creador:</strong></td>'
        . '<td>' . mayus($cotizacion['rol_creador'] ?? 'No asignado') . '</td>'
        . '<td><strong>Observaciones:</strong></td>'
        . '<td>' . mayus($cotizacion['observaciones'] ?? 'No asignadas') . '</td>'
    . '</tr>'
    . '</table>';

// ... (tabla de exámenes cotizados)
$html .= '
    <table class="tabla-examenes">
        <thead>
            <tr>
                <th>Examen</th>
                <th>Condición Paciente</th>
                <th>P. Unit. (S/.)</th>
                <th>Cant.</th>
                <th>Subtotal (S/.)</th>
            </tr>
        </thead>
        <tbody>';
foreach ($examenes as $examen) {
    $html .= '<tr>
        <td align="center">' . mayus($examen['nombre_examen']) . '</td>
        <td align="center">' . mayus($examen['preanalitica_cliente']) . '</td>
        <td align="center">' . number_format($examen['precio_unitario'], 2) . '</td>
        <td align="center">' . $examen['cantidad'] . '</td>
        <td align="center">' . number_format($examen['subtotal'], 2) . '</td>
    </tr>';
}
$html .= '
        </tbody>
    </table>
    <br>
    <strong >Total: S/. ' . number_format($cotizacion['total'], 2) . '</strong>
</body>
</html>';

// ... (código para generar el PDF con mPDF)
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->SetDisplayMode('fullpage');
if (ob_get_length()) ob_end_clean();
$mpdf->Output('cotizacion_' . $cotizacion['codigo'] . '.pdf', 'D');
exit;