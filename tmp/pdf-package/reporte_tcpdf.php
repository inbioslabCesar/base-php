<?php
// reporte_tcpdf.php - ejemplo mínimo usando TCPDF
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/conexion.php';

// Asegúrate de tener tecnickcom/tcpdf instalado via composer

// Datos de ejemplo (en integración, reemplazar por consultas)
$paciente = ['nombre' => 'Juan Pérez', 'dni' => '12345678', 'fecha' => date('Y-m-d')];

// Cargar TCPDF
use TCPDF;

class MYPDF extends TCPDF {
    public function Header() {
        // Header simple
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 15, 'Informe - Laboratorio', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }
}

$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Laboratorio');
$pdf->SetTitle('Reporte TCPDF');
$pdf->AddPage();

$html = '<h2>Reporte de Resultados</h2>';
$html .= '<p><strong>Paciente:</strong> ' . htmlspecialchars($paciente['nombre']) . '</p>';
$html .= '<p><strong>DNI:</strong> ' . htmlspecialchars($paciente['dni']) . '</p>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('reporte-tcpdf.pdf', 'I');
