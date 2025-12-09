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
$stmt = $pdo->prepare("SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni AS dni_cliente
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

$html = '
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    .datos-empresa { margin-bottom: 10px; }
    .datos-cliente, .datos-cotizacion { margin-bottom: 8px; }
    .tabla-campos { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .tabla-campos td { padding: 2px 6px; }
    .tabla-examenes { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .tabla-examenes th, .tabla-examenes td { border: 1px solid #ccc; padding: 4px; }
    .tabla-examenes th { background: #f4f4f4; }
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
            Email: ' . $empresa['email'] . '
        </td>
    </tr>
</table>

    <div class="datos-cliente">
        <strong>Cliente:</strong> ' . mayus($cotizacion['nombre_cliente'] . ' ' . $cotizacion['apellido_cliente']) . '<br>
        <strong>DNI :</strong> ' . htmlspecialchars($cotizacion['dni_cliente']) . '<br>
        <strong>Cotización:</strong> ' . htmlspecialchars($cotizacion['codigo']) . '<br>
        <strong>Fecha:</strong> ' . htmlspecialchars($cotizacion['fecha']) . '<br>
        ';
        // Mostrar condición: Particular, Empresa o Convenio
        $tipo = $cotizacion['tipo_usuario'] ?? '';
        if ($tipo === 'empresa' && !empty($cotizacion['id_empresa'])) {
            $stmtEmp = $pdo->prepare("SELECT nombre_comercial, razon_social FROM empresas WHERE id = ?");
            $stmtEmp->execute([$cotizacion['id_empresa']]);
            $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
            $html .= '<strong>Condición:</strong> Empresa: ' . htmlspecialchars($emp['nombre_comercial'] ?? $emp['razon_social'] ?? '') . '<br>';
        } elseif ($tipo === 'convenio' && !empty($cotizacion['id_convenio'])) {
            $stmtConv = $pdo->prepare("SELECT nombre FROM convenios WHERE id = ?");
            $stmtConv->execute([$cotizacion['id_convenio']]);
            $conv = $stmtConv->fetch(PDO::FETCH_ASSOC);
            $html .= '<strong>Condición:</strong> Convenio: ' . htmlspecialchars($conv['nombre'] ?? '') . '<br>';
        } else {
            $html .= '<strong>Condición:</strong> Particular<br>';
        }
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
                <th>Condición Cliente</th>
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