<?php
// descarga-pdf.php - versión simplificada y comentada
// Requisitos: composer install (mpdf/mpdf) y una conexión PDO válida en src/conexion.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php'; // composer autoload
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/conexion.php';

use Mpdf\Mpdf;

// Parámetro de entrada
$cotizacion_id = $_GET['cotizacion_id'] ?? null;
if (!$cotizacion_id) {
    http_response_code(400);
    echo 'Falta el parámetro cotizacion_id';
    exit;
}

// Ejemplo básico de consultas (adaptar tablas/columnas al esquema real)
// 1) Obtener datos de cotización (referencia, empresa, convenio)
$sqlCot = "SELECT c.id_empresa, c.id_convenio, c.referencia_personalizada, e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
           FROM cotizaciones c
           LEFT JOIN empresas e ON c.id_empresa = e.id
           LEFT JOIN convenios v ON c.id_convenio = v.id
           WHERE c.id = :cotizacion_id";
$stmtCot = $pdo->prepare($sqlCot);
$stmtCot->execute(['cotizacion_id' => $cotizacion_id]);
$cot = $stmtCot->fetch(PDO::FETCH_ASSOC);

// 2) Obtener resultados_examenes + cliente
$sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.id AS cliente_id, re.fecha_ingreso
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['cotizacion_id' => $cotizacion_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    http_response_code(404);
    echo 'No se encontraron resultados para la cotización.';
    exit;
}

$first = $rows[0];
$paciente = [
    'nombre' => trim($first['nombre'] . ' ' . $first['apellido']),
    'codigo_cliente' => $first['codigo_cliente'] ?? '',
    'dni' => $first['dni'] ?? '',
    'edad' => $first['edad'] ?? '',
    'sexo' => $first['sexo'] ?? '',
    'fecha' => $first['fecha_ingreso'] ?? ''
];

// Empresa/config
$stmt2 = $pdo->query("SELECT nombre, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1");
$empresa = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

$logo = !empty($empresa['logo']) ? __DIR__ . '/src/' . ltrim($empresa['logo'], './') : '';
$firma = !empty($empresa['firma']) ? __DIR__ . '/src/' . ltrim($empresa['firma'], './') : '';

// Armar HTML simple
$css = "body{font-family:Arial,Helvetica,sans-serif;font-size:12px;} .title{font-size:18px;font-weight:bold;text-align:center;margin-bottom:10px;} table{width:100%;border-collapse:collapse;} td, th{padding:6px;border:1px solid #ddd;}";

$header = "<div style='text-align:center;'><strong>" . htmlspecialchars($empresa['nombre'] ?? 'Laboratorio') . "</strong></div>";

$html = "<div>";
$html .= "<div class='title'>Reporte de Resultados</div>";
$html .= "<table><tr><td><strong>Paciente</strong></td><td>" . htmlspecialchars($paciente['nombre']) . "</td></tr>";
$html .= "<tr><td><strong>DNI</strong></td><td>" . htmlspecialchars($paciente['dni']) . "</td></tr>";
$html .= "<tr><td><strong>Fecha</strong></td><td>" . htmlspecialchars($paciente['fecha']) . "</td></tr></table><br>";

$html .= "<table><thead><tr><th>Prueba</th><th>Resultado (JSON)</th></tr></thead><tbody>";
foreach ($rows as $r) {
    $res = $r['resultados'] ?? '';
    $html .= "<tr><td>Examen ID: " . htmlspecialchars($r['id_examen']) . "</td><td>" . htmlspecialchars($res) . "</td></tr>";
}
$html .= "</tbody></table></div>";

// Generar PDF con mPDF
$mpdf = new Mpdf(['margin_top' => 20, 'margin_bottom' => 20]);
if ($logo && file_exists($logo)) {
    $mpdf->SetHTMLHeader('<div style="text-align:left"><img src="' . $logo . '" style="height:50px"></div>');
}
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$mpdf->Output('reporte-resultados.pdf', 'I');
exit;
