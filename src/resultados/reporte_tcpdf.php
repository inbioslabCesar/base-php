<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents(__DIR__ . '/debug_tcpdf.txt', 'Se ejecutó reporte_tcpdf.php: ' . date('Y-m-d H:i:s'));
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../conexion/conexion.php'; // $pdo disponible
require_once __DIR__ . '/../../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Recibe el id de cotización
$cotizacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($cotizacion_id <= 0) {
    die('ID de cotización no válido');
}

// 1. Consulta resultados y datos del cliente
$sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.id AS cliente_id
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['cotizacion_id' => $cotizacion_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    die('No se encontraron resultados para esta cotización.');
}

// 2. Consulta datos de empresa
$sql3 = "SELECT nombre, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1";
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$empresa = $stmt3->fetch(PDO::FETCH_ASSOC);

// 3. Rutas absolutas para imágenes
$logo_path = __DIR__ . '/../../images/empresa/' . $empresa['logo'];
$firma_path = __DIR__ . '/../../images/empresa/' . $empresa['firma'];

// 4. Prepara los datos del paciente y empresa para el encabezado
$paciente = $rows[0];
$paciente_info = "Paciente: {$paciente['nombre']} {$paciente['apellido']}   DNI: {$paciente['dni']}   Edad: {$paciente['edad']}   Sexo: {$paciente['sexo']}";
$empresa_info = "{$empresa['nombre']}\nDirección: {$empresa['direccion']}\nTel: {$empresa['telefono']} Cel: {$empresa['celular']}";
// Clase personalizada para encabezado y pie de página
class MYPDF extends TCPDF {
    public $empresa, $paciente, $logo_path, $firma_path;
    public function Header() {
        // Logo
        if (file_exists($this->logo_path)) {
            $this->Image($this->logo_path, 10, 10, 30, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        // Título
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Reporte de Resultados', 0, 1, 'C');
        $this->SetFont('helvetica', '', 9);
        // Empresa
        $this->MultiCell(0, 5, $this->empresa, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
        // Paciente
        $this->MultiCell(0, 5, $this->paciente, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
        $this->Ln(2);
    }
    public function Footer() {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, '(L) Laboratorio de Referencia | * Resultados fuera de rango | ** Muestra remitida', 0, false, 'C');
        // Firma
        if (file_exists($this->firma_path)) {
            $this->Image($this->firma_path, 160, 270, 30, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        // Número de página
        $this->SetY(-15);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'R');
    }
}

// Construcción de la tabla HTML de resultados
$html = '<table border="1" cellpadding="4">
    <thead>
        <tr style="background-color:#f4f6fa;">
            <th width="28%">Prueba</th>
            <th width="18%">Metodología</th>
            <th width="14%">Resultado</th>
            <th width="12%">Unidades</th>
            <th width="28%">Valores de Referencia</th>
        </tr>
    </thead>
    <tbody>';

foreach ($rows as $row) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['nombre_examen'] ?? $row['prueba'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['metodologia'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['resultado'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['unidad'] ?? '') . '</td>
        <td>' . htmlspecialchars($row['referencia'] ?? '') . '</td>
    </tr>';
}

$html .= '</tbody></table>';
// Instancia y configuración de TCPDF personalizada
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Pasa los datos de encabezado y rutas de imágenes a la clase
$pdf->empresa = $empresa_info;
$pdf->paciente = $paciente_info;
$pdf->logo_path = $logo_path;
$pdf->firma_path = $firma_path;

// Configuración básica
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($empresa['nombre']);
$pdf->SetTitle('Reporte de Resultados');
$pdf->SetMargins(10, 45, 10); // Izquierda, Arriba, Derecha
$pdf->SetHeaderMargin(15);
$pdf->SetFooterMargin(20);
$pdf->SetAutoPageBreak(TRUE, 25); // Espacio para el pie de página

$pdf->AddPage();

// Escribe la tabla HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Salida del PDF al navegador
$pdf->Output('reporte-resultados.pdf', 'I'); // 'I' para mostrar en navegador, 'D' para descargar

exit;
