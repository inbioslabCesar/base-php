<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php'; // Ruta a mPDF (ajústala según tu proyecto)

$id_cotizacion = $_GET['id'] ?? null;
$id_cliente = $_SESSION['id_cliente'] ?? null;

if (!$id_cotizacion || !$id_cliente) {
    die("Cotización o cliente no válido.");
}

// Obtener cabecera
$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ? AND id_cliente = ?");
$stmt->execute([$id_cotizacion, $id_cliente]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion || $cotizacion['estado_pago'] !== 'pagado') {
    die("Solo puede descargar cotizaciones pagadas.");
}

// Obtener detalles
$stmt = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = ?");
$stmt->execute([$id_cotizacion]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar HTML para el PDF
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .logo { width: 120px; }
    .titulo { font-size: 18px; font-weight: bold; }
    .tabla { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .tabla th, .tabla td { border: 1px solid #333; padding: 5px; }
    .tabla th { background: #eee; }
</style>
<table width="100%">
    <tr>
        <td><img src="../../images/inbioslab-logo.png" class="logo"></td>
        <td align="right">
            <div class="titulo">Cotización de Exámenes</div>
            <strong>Código:</strong> ' . htmlspecialchars($cotizacion['codigo']) . '<br>
            <strong>Fecha:</strong> ' . htmlspecialchars(date('d/m/Y H:i', strtotime($cotizacion['fecha']))) . '
        </td>
    </tr>
</table>
<hr>
<strong>Cliente:</strong> ' . $_SESSION['nombre'] . '<br>
<strong>Observaciones:</strong> ' . nl2br(htmlspecialchars($cotizacion['observaciones'] ?? '')) . '<br>
<br>
<table class="tabla">
    <tr>
        <th>Examen</th>
        <th>Precio Unitario</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
    </tr>';
foreach ($detalles as $det) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($det['nombre_examen']) . '</td>
        <td>S/ ' . number_format($det['precio_unitario'], 2) . '</td>
        <td>' . htmlspecialchars($det['cantidad']) . '</td>
        <td>S/ ' . number_format($det['subtotal'], 2) . '</td>
    </tr>';
}
$html .= '
    <tr>
        <td colspan="3" align="right"><strong>Total</strong></td>
        <td><strong>S/ ' . number_format($cotizacion['total'], 2) . '</strong></td>
    </tr>
</table>
<br>
<small>INBIOSLAB - Laboratorio Clínico. Dirección: [Tu dirección]. Tel: [Tu teléfono]. RUC: [Tu RUC]</small>
';

// Generar PDF
$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('Cotizacion_' . $cotizacion['codigo'] . '.pdf', 'D');
exit;
