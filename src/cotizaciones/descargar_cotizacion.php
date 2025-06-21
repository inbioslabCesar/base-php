<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';

// Ajustar zona horaria a Perú
date_default_timezone_set('America/Lima');

if (!isset($_GET['id'])) {
    die('ID de cotización no proporcionado.');
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
$stmt_detalle = $pdo->prepare(
    "SELECT d.*, e.preanalitica_cliente
     FROM cotizaciones_detalle d
     LEFT JOIN examenes e ON d.id_examen = e.id
     WHERE d.id_cotizacion = :id_cotizacion"
);
$stmt_detalle->execute([':id_cotizacion' => $id]);
$examenes = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

// Traer datos de la empresa
$stmtEmpresa = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
$logo = '../images/empresa/logo_empresa.png'; // Ruta relativa

$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 110px; }
        .empresa-datos { text-align: right; font-size: 11px; }
        .tabla { border-collapse: collapse; width: 100%; }
        .tabla th, .tabla td { border: 1px solid #ccc; padding: 5px; }
        .tabla th { background: #f5f5f5; }
        .total { text-align:right; font-weight:bold; }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <img src="' . $logo . '" width="100" alt="Logo Empresa">
        </td>
        <td class="empresa-datos">
            <strong>' . htmlspecialchars($empresa['nombre'] ?? '') . '</strong><br>
            RUC: ' . htmlspecialchars($empresa['ruc'] ?? '') . '<br>
            Dirección: ' . htmlspecialchars($empresa['direccion'] ?? '') . '<br>
            Teléfono: ' . htmlspecialchars($empresa['telefono'] ?? '') . '<br>
            Celular: ' . htmlspecialchars($empresa['celular'] ?? '') . '<br>
            Email: ' . htmlspecialchars($empresa['email'] ?? '') . '
        </td>
    </tr>
</table>
<h2 style="text-align:center;">Cotización de Exámenes</h2>
<div class="datos">
    <strong>Código:</strong> ' . htmlspecialchars($cotizacion['codigo'] ?? '') . '<br>
    <strong>Cliente:</strong> ' . htmlspecialchars(($cotizacion['nombre_cliente'] ?? '') . ' ' . ($cotizacion['apellido_cliente'] ?? '')). '<br>
    <strong>DNI:</strong> ' . htmlspecialchars($cotizacion['dni_cliente'] ?? '') . '<br>
    <strong>Fecha y hora:</strong> ' . htmlspecialchars(date('d/m/Y H:i', strtotime($cotizacion['fecha'] ?? ''))) . '<br>
    <strong>Estado:</strong> ' . htmlspecialchars($cotizacion['estado_pago'] ?? '') . '<br>
    <strong>Rol Creador:</strong> ' . htmlspecialchars($cotizacion['rol_creador'] ?? '') . '
</div>
<h4>Exámenes Cotizados</h4>
<table class="tabla">
    <thead>
        <tr>
            <th>Nombre del Examen</th>
            <th>Condiciones del paciente</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>';
foreach ($examenes as $examen) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($examen['nombre_examen'] ?? '') . '</td>
            <td>' . htmlspecialchars($examen['preanalitica_cliente'] ?? '') . '</td>
            <td>S/ ' . number_format($examen['precio_unitario'] ?? 0, 2) . '</td>
            <td>' . htmlspecialchars($examen['cantidad'] ?? '') . '</td>
            <td>S/ ' . number_format($examen['subtotal'] ?? 0, 2) . '</td>
        </tr>';
}
$html .= '
    </tbody>
</table>
<p class="total">Total: S/ ' . number_format($cotizacion['total'] ?? 0, 2) . '</p>
</body>
</html>
';

$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../tmp']);
$mpdf->WriteHTML($html);
$mpdf->Output('cotizacion_' . ($cotizacion['codigo'] ?? 'descarga') . '.pdf', 'D');
