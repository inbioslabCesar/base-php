<?php

if (!function_exists('pdf_cot_log')) {
    function pdf_cot_log($message, $context = []) {
        $dirs = [
            __DIR__ . '/../../tmp/facturacion/logs',
            __DIR__ . '/../../../tmp/facturacion/logs',
        ];
        $payload = [
            'time' => date('c'),
            'endpoint' => 'cotizaciones/views/descargar_cotizacion.php',
            'message' => (string)$message,
            'context' => $context,
        ];
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (is_dir($dir)) {
                @file_put_contents($dir . '/pdf_cotizacion_errors.log', $line, FILE_APPEND);
                @file_put_contents($dir . '/hook_errors.log', $line, FILE_APPEND);
            }
        }
    }
}

$autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
if (!is_file($autoloadPath)) {
    pdf_cot_log('Bootstrap error: vendor/autoload.php no existe', ['path' => $autoloadPath]);
    http_response_code(500);
    echo 'Error de servidor: falta autoload.';
    exit;
}
require_once $autoloadPath;

$conexionPath = __DIR__ . '/../../conexion/conexion.php';
if (!is_file($conexionPath)) {
    pdf_cot_log('Bootstrap error: conexion.php no existe', ['path' => $conexionPath]);
    http_response_code(500);
    echo 'Error de servidor: falta conexión.';
    exit;
}
require_once $conexionPath;

$currencyPath = __DIR__ . '/../../config/currency.php';
if (!is_file($currencyPath)) {
    pdf_cot_log('Bootstrap error: currency.php no existe', ['path' => $currencyPath]);
    http_response_code(500);
    echo 'Error de servidor: falta configuración de moneda.';
    exit;
}
require_once $currencyPath;

set_exception_handler(function ($e) {
    pdf_cot_log('Excepción no controlada', [
        'class' => is_object($e) ? get_class($e) : 'unknown',
        'error' => is_object($e) && method_exists($e, 'getMessage') ? $e->getMessage() : 'sin mensaje',
        'trace' => is_object($e) && method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : '',
    ]);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'Error al generar PDF de cotización.';
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array((int)$err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        pdf_cot_log('Fatal shutdown error', $err);
    }
});

$logPdfError = static function (string $message, ?\Throwable $e = null): void {
    $payload = [
        'time' => date('c'),
        'endpoint' => 'cotizaciones/views/descargar_cotizacion.php',
        'message' => $message,
        'exception' => $e ? $e->getMessage() : null,
        'trace' => $e ? $e->getTraceAsString() : null,
    ];
    pdf_cot_log($message, $payload);
};

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

try {
    $currencyCfg = currency_get_config($pdo);
} catch (\Throwable $e) {
    $logPdfError('Fallo currency_get_config, se usa fallback', $e);
    $currencyCfg = [
        'code' => 'PEN',
        'symbol' => 'S/',
        'position' => 'prefix',
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ',',
    ];
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
if (!is_array($empresa)) {
    $empresa = [
        'logo' => '../uploads/empresa/logo_empresa.png',
        'nombre' => 'Empresa',
        'direccion' => '',
        'ruc' => '',
        'telefono' => '',
        'celular' => '',
        'email' => '',
        'dominio' => ($_SERVER['HTTP_HOST'] ?? ''),
    ];
}

// Logo dinámico de la empresa para el PDF (mPDF requiere URL absoluta confiable).
$logoDb = isset($empresa['logo']) ? (string)$empresa['logo'] : '';
if ($logoDb === '' || preg_match('/^data:image\//i', $logoDb)) {
    $logoDb = '../uploads/empresa/logo_empresa.png';
}

$logoEsAbsoluto = (bool)preg_match('#^https?://#i', $logoDb);
$logoRel = ltrim($logoDb, '/');
$logoRel = preg_replace('#^\.\./+#', '', $logoRel);

$logo = '';
if ($logoEsAbsoluto) {
    $logo = $logoDb;
} else {
    // Priorizar ruta física local para evitar fallos por subcarpetas/base URL en producción.
    $projectRoot = realpath(__DIR__ . '/../../..');
    if ($projectRoot === false) {
        $projectRoot = __DIR__ . '/../../..';
    }
    $srcRoot = realpath(__DIR__ . '/../..');
    if ($srcRoot === false) {
        $srcRoot = __DIR__ . '/../..';
    }

    $candidatos = [];
    if (strpos($logoRel, 'uploads/') === 0) {
        $candidatos[] = rtrim((string)$projectRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
    }
    $candidatos[] = rtrim((string)$srcRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);

    foreach ($candidatos as $path) {
        $real = realpath($path);
        if ($real !== false && is_file($real)) {
            $logo = $real;
            break;
        }
    }

    // Fallback URL pública (si no se encontró archivo local)
    if ($logo === '') {
        $esHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
        $hostHeader = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocolo = $esHttps ? 'https' : 'http';
        $logo = $protocolo . '://' . $hostHeader . '/' . ltrim($logoRel, '/');
    }
}
// Dominio de la empresa (usa el guardado en config_empresa; si falta, usa el host actual)
$dominioEmpresa = !empty($empresa['dominio']) ? $empresa['dominio'] : ($_SERVER['HTTP_HOST'] ?? '');

$logoHtml = '';
if ($logo !== '') {
    $logoHtml = '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" width="100" alt="Logo Empresa">';
}

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
            ' . $logoHtml . '
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
                <th>P. Unit. (' . htmlspecialchars((string)$currencyCfg['symbol']) . ')</th>
                <th>Cant.</th>
                <th>Subtotal (' . htmlspecialchars((string)$currencyCfg['symbol']) . ')</th>
            </tr>
        </thead>
        <tbody>';
foreach ($examenes as $examen) {
    $html .= '<tr>
        <td align="center">' . mayus($examen['nombre_examen']) . '</td>
        <td align="center">' . mayus($examen['preanalitica_cliente']) . '</td>
        <td align="center">' . money_format_local((float)$examen['precio_unitario'], $currencyCfg) . '</td>
        <td align="center">' . $examen['cantidad'] . '</td>
        <td align="center">' . money_format_local((float)$examen['subtotal'], $currencyCfg) . '</td>
    </tr>';
}
$html .= '
        </tbody>
    </table>
    <br>
    <strong >Total: ' . money_format_local((float)$cotizacion['total'], $currencyCfg) . '</strong>
</body>
</html>';

// ... (código para generar el PDF con mPDF)
try {
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->SetDisplayMode('fullpage');
    if (ob_get_length()) ob_end_clean();
    $mpdf->Output('cotizacion_' . $cotizacion['codigo'] . '.pdf', 'D');
    exit;
} catch (\Throwable $e) {
    $logPdfError('Fallo principal al generar PDF (intento con logo)', $e);
    // Fallback seguro: si falla por imagen/logo, generar sin logo para no romper producción.
    try {
        $htmlSinLogo = str_replace($logoHtml, '', $html);
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($htmlSinLogo);
        $mpdf->SetDisplayMode('fullpage');
        if (ob_get_length()) ob_end_clean();
        $mpdf->Output('cotizacion_' . $cotizacion['codigo'] . '.pdf', 'D');
        exit;
    } catch (\Throwable $e2) {
        $logPdfError('Fallo fallback al generar PDF (sin logo)', $e2);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo 'No se pudo generar la cotización PDF.';
        exit;
    }
}