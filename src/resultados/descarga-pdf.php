<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';

use Mpdf\Mpdf;

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
if (!$cotizacion_id) {
    die('ID de cotización no proporcionado.');
}

// Obtener resultados y datos del paciente

// Traer datos de referencia de cotización (empresa/convenio/particular)
$sqlCot = "SELECT c.id_empresa, c.id_convenio, c.referencia_personalizada, e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
           FROM cotizaciones c
           LEFT JOIN empresas e ON c.id_empresa = e.id
           LEFT JOIN convenios v ON c.id_convenio = v.id
           WHERE c.id = :cotizacion_id";
$stmtCot = $pdo->prepare($sqlCot);
$stmtCot->execute(['cotizacion_id' => $cotizacion_id]);
$cot = $stmtCot->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.id AS cliente_id
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['cotizacion_id' => $cotizacion_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows || count($rows) === 0) {
    die('No se encontraron resultados para esta cotización.');
}

// Datos del paciente
$primer_row = $rows[0];
$paciente = [
    "nombre"         => trim($primer_row['nombre'] . ' ' . $primer_row['apellido']),
    "codigo_cliente" => $primer_row['codigo_cliente'] ?? "",
    "dni"            => $primer_row['dni'] ?? "",
    "edad"           => $primer_row['edad'],
    "sexo"           => $primer_row['sexo'],
    "fecha"          => $primer_row['fecha_ingreso'],
    "id"             => $primer_row['cliente_id']
];

// Lógica de referencia (empresa, convenio, particular)
$referencia = '';

// Verificar si hay una referencia personalizada en la cotización
$referencia_personalizada = !empty($cot['referencia_personalizada']) ? trim($cot['referencia_personalizada']) : '';

if (!empty($referencia_personalizada)) {
    // Si hay referencia personalizada, usarla
    $referencia = $referencia_personalizada;
} else {
    // Si no hay referencia personalizada, usar la lógica original
    if (!empty($cot['id_empresa']) && (!empty($cot['nombre_comercial']) || !empty($cot['razon_social']))) {
        $referencia = $cot['nombre_comercial'] ?: $cot['razon_social'];
    } elseif (!empty($cot['id_convenio']) && !empty($cot['nombre_convenio'])) {
        $referencia = $cot['nombre_convenio'];
    } else {
        $referencia = 'Particular';
    }
}

// Datos de la empresa
$sql3 = "SELECT nombre, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1";
$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$empresa = $stmt3->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    $empresa = [
        "nombre" => "",
        "direccion" => "",
        "telefono" => "",
        "celular" => "",
        "logo" => "",
        "firma" => ""
    ];
}

// Rutas absolutas para logo y firma
$logo = $empresa['logo'] ? __DIR__ . '/../' . ltrim($empresa['logo'], './') : '';
$firma = $empresa['firma'] ? __DIR__ . '/../' . ltrim($empresa['firma'], './') : '';
// Procesar resultados para armar la tabla
$items = [];
foreach ($rows as $row) {
    $sql2 = "SELECT nombre AS nombre_examen, adicional FROM examenes WHERE id = :id_examen";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(['id_examen' => $row['id_examen']]);
    $examen = $stmt2->fetch(PDO::FETCH_ASSOC);

    $adicional = $examen && $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
    $resultados_json = $row['resultados'] ? json_decode($row['resultados'], true) : [];

    // Solo imprimir si el examen fue marcado para imprimir
    if (!isset($resultados_json['imprimir_examen']) || !$resultados_json['imprimir_examen']) {
        continue;
    }

    // Normaliza valores numéricos (quita comas)
    foreach ($resultados_json as $k => $v) {
        if (is_string($v) && preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $v)) {
            $resultados_json[$k] = str_replace(',', '', $v);
        }
    }

    usort($adicional, function ($a, $b) {
        return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
    });

    $valores = [];
    $examen_items = [];
    foreach ($adicional as $item) {
        // Ignorar tipo "Campo" y otros no relevantes
        if (!in_array($item['tipo'], ['Parámetro', 'Título', 'Subtítulo'])) {
            continue;
        }
        $nombre = $item['nombre'];
        $valor = '';

        if ($item['tipo'] === 'Parámetro') {
            if (!empty($item['formula'])) {
                $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
                if ($valor === '' || $valor === null) {
                    $formula = $item['formula'];
                    $formula_eval = preg_replace_callback('/\[(.*?)\]/', function($matches) use ($valores) {
                        $param = trim($matches[1]);
                        return isset($valores[$param]) && is_numeric($valores[$param]) ? $valores[$param] : 0;
                    }, $formula);
                    try {
                        $valor = eval('return ' . $formula_eval . ';');
                        if (is_numeric($valor)) {
                            $valor = number_format($valor, 1, '.', '');
                        }
                    } catch (Throwable $e) {
                        $valor = '';
                    }
                }
            } else {
                $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
            }
            $valores[$nombre] = $valor;
            $examen_items[] = array_merge($item, [
                "prueba" => $nombre,
                "valor" => $valor,
                "tipo" => "Parámetro"
            ]);
        } else {
            $examen_items[] = array_merge($item, [
                "prueba" => $nombre
            ]);
        }
    }
    $items = array_merge($items, $examen_items);
}
// CSS profesional y encabezado de tabla más notorio y alto
$css = '
body, table, td, th {
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
}
.encabezado-tabla {
    width: 100%;
    border-bottom: 2px solid #eee;
    margin-bottom: 0px;
}
.logo {
    width: 110px;
}
.direccion {
    text-align: right;
    font-size: 13px;
    color: #555;
    vertical-align: top;
}
.datos-cliente-tabla {
    width: 100%;
    margin: 10px 0 15px 0;
    font-size: 13px;
    color: #333;
}
.datos-cliente-tabla td {
    padding: 2px 8px;
    vertical-align: top;
}
.titulo-reporte {
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 18px;
    color: #1a237e;
    letter-spacing: 1px;
}
.tabla-resultados {
    width: 100%;
    border-collapse: collapse;
    margin-top: 18px;
    font-size: 11px;
}
.tabla-resultados th {
    background: #d7e3fcff;
    font-size: 11px;
    color: #1a237e;
    font-weight: bold;
    border: none;
    text-align: left;
    height: 32px;
}
.tabla-resultados td {
    font-size: 11px;
    border: none;
    padding: 2px 8px;
    text-align: left;
    vertical-align: middle;
}
.referencia-list {
    margin: 0;
    padding-left: 16px;
    font-size: 0.97em;
    color: #222;
}
.firma-footer {
    text-align: right;
    margin-top: 45px;
}
.subtitulo {
    background: #e3e8f5 !important;
    color: #1a237e !important;
    font-weight: bold !important;
    border-radius: 6px;
}
';

// Header y footer del PDF
$mpdf = new Mpdf([
    'margin_top' => 62,
    'margin_bottom' => 35
]);

$logo_html = $logo && file_exists($logo) ? '<img src="' . $logo . '" class="logo">' : '';
$direccion_html = '
    <strong style="font-size:15px;color:#1a237e;">' . htmlspecialchars($empresa['nombre']) . '</strong><br>
    ' . htmlspecialchars($empresa['direccion']) . '<br>
    Tel: ' . htmlspecialchars($empresa['telefono']) . '<br>
    Celular: ' . htmlspecialchars($empresa['celular']);


$mpdf->SetHTMLHeader('
<table class="encabezado-tabla">
    <tr>
        <td style="width:60%">' . $logo_html . '</td>
        <td class="direccion" style="width:40%">' . $direccion_html . '</td>
    </tr>
</table>
<table class="datos-cliente-tabla">
    <tr>
        <td><strong>Paciente:</strong> ' . htmlspecialchars($paciente['nombre']) . '</td>
        <td><strong>Código Paciente:</strong> ' . htmlspecialchars($paciente['codigo_cliente']) . '</td>
    </tr>
    <tr>
        <td><strong>DNI:</strong> ' . htmlspecialchars($paciente['dni']) . '</td>
    <td><strong>Edad:</strong> ' . htmlspecialchars($paciente['edad']) . '   <strong>Sexo:</strong> ' . htmlspecialchars($paciente['sexo']) . '</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Referencia:</strong> ' . htmlspecialchars($referencia) . '</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Fecha:</strong> ' . htmlspecialchars($paciente['fecha']) . '</td>
    </tr>
</table>
', 'O', true);

$firma_html = $firma && file_exists($firma)
    ? '<img src="' . $firma . '" style="height:65px;"><br>'
    : '';

$mpdf->SetHTMLFooter('
    <div class="firma-footer">
        ' . $firma_html . '
        <hr class="my-3" style="margin:8px 0;">
        <div style="font-size: 11px; color: #555; text-align:left;">
            Informe confidencial. Prohibida su reproducción total o parcial.
        </div>
    </div>
');
// Armar la tabla de resultados
$html = '
<div>
    <div class="titulo-reporte">
        Reporte de Resultados
    </div>
    <table class="tabla-resultados">
        <thead>
            <tr>
                <th>Prueba</th>
                <th>Metodología</th>
                <th>Resultado</th>
                <th>Unidades</th>
                <th>Valores de Referencia</th>
            </tr>
        </thead>
        <tbody>
';

$sinDecimales = ['R_GLOBULOS_BLANCOS', 'PLAQUETAS'];

foreach ($items as $item) {
    if ($item['tipo'] === "Título") {
        $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
        $color_texto = $item['color_texto'] ?? "#1a237e";
        $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
        $html .= '<tr class="subtitulo">
            <td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';border-radius:6px;text-align:center;">'
            . htmlspecialchars($item['prueba']) .
            '</td>
        </tr>';
    } elseif ($item['tipo'] === "Subtítulo") {
        $color_fondo = $item['color_fondo'] ?? "#e3e8f5";
        $color_texto = $item['color_texto'] ?? "#1a237e";
        $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
        $html .= '<tr class="subtitulo">
            <td colspan="5" style="background:' . htmlspecialchars($color_fondo) . ';color:' . htmlspecialchars($color_texto) . ';font-weight:' . $font_weight . ';border-radius:6px;">'
            . htmlspecialchars($item['prueba']) .
            '</td>
        </tr>';
    } elseif ($item['tipo'] === "Parámetro") {
        // Referencias
        $referencias = isset($item['referencias']) && is_array($item['referencias'])
            ? $item['referencias']
            : (isset($item['referencia']) ? $item['referencia'] : []);
        $refHTML = '';
        if (is_array($referencias)) {
            $refHTML = '<ul class="referencia-list">';
            foreach ($referencias as $ref) {
                if (is_array($ref) && isset($ref['desc'])) {
                    $refHTML .= '<li><strong>' . htmlspecialchars($ref['desc']) . '</strong> ' . htmlspecialchars($ref['valor']) . '</li>';
                } else {
                    $refHTML .= '<li>' . htmlspecialchars(is_array($ref) && isset($ref['valor']) ? $ref['valor'] : $ref) . '</li>';
                }
            }
            $refHTML .= '</ul>';
        } elseif ($referencias) {
            $refHTML = htmlspecialchars($referencias);
        }
        // Formatea resultado
        $valorFormateado = $item['valor'];
        $font_weight = !empty($item['negrita']) ? 'bold' : 'normal';
        if (in_array($item['prueba'], $sinDecimales) && is_numeric(str_replace(',', '', $valorFormateado))) {
            $valorFormateado = number_format(floatval(str_replace(',', '', $valorFormateado)), 0, '', ',');
        } elseif ($valorFormateado !== "" && !is_null($valorFormateado) && is_numeric($valorFormateado)) {
            $valorFormateado = number_format($valorFormateado, 1, '.', '');
        }
        // Si es array (por ejemplo, checkboxes múltiples), mostrar cada valor en una fila con negrita si corresponde
        if (is_array($valorFormateado)) {
            foreach ($valorFormateado as $valorSel) {
                if ($valorSel !== '' && $valorSel !== null) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight:' . $font_weight . '">' . htmlspecialchars($item['prueba']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
                    $html .= '<td>' . htmlspecialchars($valorSel) . '</td>';
                    $html .= '<td>' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
                    $html .= '<td>' . $refHTML . '</td>';
                    $html .= '</tr>';
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td style="font-weight:' . $font_weight . '">' . htmlspecialchars($item['prueba']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['metodologia'] ?? "") . '</td>';
            $html .= '<td>' . htmlspecialchars($valorFormateado) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['unidad'] ?? "") . '</td>';
            $html .= '<td>' . $refHTML . '</td>';
            $html .= '</tr>';
        }
    }
}

$html .= '
        </tbody>
    </table>
</div>
';

// Aplicar CSS y generar PDF
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$mpdf->Output('reporte-resultados.pdf', 'I');
exit;
