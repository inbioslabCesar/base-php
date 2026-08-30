<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/operacion_context.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../usuarios/funciones/usuarios_privilegios.php';
// Agregar estos dos require para los componentes:
require_once __DIR__ . '/resultados_pdf_datos.php';
require_once __DIR__ . '/resultados_pdf_html.php';
use Mpdf\Mpdf;

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
if (!$cotizacion_id) {
    die('ID de cotización no proporcionado.');
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual === 'servicio') {
    require_once __DIR__ . '/../servicios/funciones/servicios_schema.php';
    try {
        servicios_asegurar_tabla($pdo);
    } catch (Throwable $e) {
        die('No se pudo validar permisos del servicio.');
    }

    $servicioId = (int)($_SESSION['servicio_id'] ?? 0);
    if ($servicioId <= 0) {
        die('Acceso no autorizado.');
    }

    $stmtPermiso = $pdo->prepare("SELECT COUNT(*)
        FROM cotizaciones c
        INNER JOIN servicio_cliente sc ON sc.cliente_id = c.id_cliente
        WHERE c.id = ? AND sc.servicio_id = ?");
    $stmtPermiso->execute([(int)$cotizacion_id, $servicioId]);
    if ((int)$stmtPermiso->fetchColumn() <= 0) {
        die('No tienes permiso para descargar este resultado.');
    }

    try {
        $stmtClienteCot = $pdo->prepare("SELECT id_cliente FROM cotizaciones WHERE id = ? LIMIT 1");
        $stmtClienteCot->execute([(int)$cotizacion_id]);
        $clienteIdAudit = (int)($stmtClienteCot->fetchColumn() ?: 0);

        $ipAudit = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        if (strpos($ipAudit, ',') !== false) {
            $ipAudit = trim(explode(',', $ipAudit)[0]);
        }
        $uaAudit = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $emailAudit = substr((string)($_SESSION['email'] ?? ''), 0, 190);

        $stmtAudit = $pdo->prepare("INSERT INTO servicio_descargas_auditoria
            (servicio_id, cotizacion_id, cliente_id, usuario_email, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAudit->execute([
            $servicioId,
            (int)$cotizacion_id,
            $clienteIdAudit > 0 ? $clienteIdAudit : null,
            $emailAudit !== '' ? $emailAudit : null,
            $ipAudit !== '' ? $ipAudit : null,
            $uaAudit !== '' ? $uaAudit : null,
        ]);
    } catch (Throwable $e) {
        // No bloquear la descarga por errores de auditoria.
    }
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
$esSisCotizacion = ((int)($cot['es_sis'] ?? 0) === 1);
$tipoSeguro = $esSisCotizacion ? 'SIS' : '';
$referencia_personalizada = !empty($cot['referencia_personalizada']) ? trim($cot['referencia_personalizada']) : '';
if ($esSisCotizacion) {
    $referencia = 'SIS';
} elseif (!empty($referencia_personalizada)) {
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

$operacionContexto = function_exists('app_operacion_context') ? app_operacion_context($pdo) : ['es_particular' => true];
$esModoParticular = !empty($operacionContexto['es_particular']);

$firmaProfesionalPath = '';
$firmaProfesionalNombre = '';
$firmaProfesionalCargo = '';
$firmaProfesionalColegiatura = '';
$firmaProfesionalCtmp = '';
$firmaProfesionalTurno = '';
$hayProfesionalResponsable = false;
$privUsarFirmaSelloProfesional = true;
$privMostrarProfesionalCabecera = true;
$privMostrarTurnoCabecera = true;

$normalizarRegistroProfesional = static function (string $valor): string {
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }
    $valor = preg_replace('/^(c\.?t\.?m\.?p\.?|ctmp|colegiatura|cmp)\s*[:#-]?\s*/iu', '', $valor) ?? $valor;
    return trim($valor);
};

try {
    $tieneColFirmaUsuario = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'firma'")->fetch(PDO::FETCH_ASSOC);
    if ($tieneColFirmaUsuario) {
        $tieneColColegiatura = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'colegiatura_numero'")->fetch(PDO::FETCH_ASSOC);
        $tieneColCtmp = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'ctmp_numero'")->fetch(PDO::FETCH_ASSOC);
        $tieneColPrivilegios = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'privilegios_json'")->fetch(PDO::FETCH_ASSOC);
        $tieneColRolUsuario = (bool)$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch(PDO::FETCH_ASSOC);
        $tieneColIdTurno = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'id_turno'")->fetch(PDO::FETCH_ASSOC);
        $tablaTurnosExiste = (bool)$pdo->query("SHOW TABLES LIKE 'laboratorio_turnos'")->fetchColumn();

        $selectColegiatura = $tieneColColegiatura ? "u.colegiatura_numero AS colegiatura_numero" : "NULL AS colegiatura_numero";
        $selectCtmp = $tieneColCtmp ? "u.ctmp_numero AS ctmp_numero" : "NULL AS ctmp_numero";
        $selectPrivilegios = $tieneColPrivilegios ? "u.privilegios_json AS privilegios_json" : "NULL AS privilegios_json";
        $selectRol = $tieneColRolUsuario ? "u.rol AS rol_usuario" : "NULL AS rol_usuario";
        $selectTurno = ($tieneColIdTurno && $tablaTurnosExiste)
            ? "lt.id AS turno_id, lt.abierto_en AS turno_abierto_en, lt.cerrado_en AS turno_cerrado_en"
            : "NULL AS turno_id, NULL AS turno_abierto_en, NULL AS turno_cerrado_en";
        $joinTurno = ($tieneColIdTurno && $tablaTurnosExiste)
            ? "LEFT JOIN laboratorio_turnos lt ON lt.id = re.id_turno"
            : "";
        $joinUsuario = ($tieneColIdTurno && $tablaTurnosExiste)
            ? "INNER JOIN usuarios u ON u.id = COALESCE(lt.usuario_id, re.id_laboratorista)"
            : "INNER JOIN usuarios u ON u.id = re.id_laboratorista";
        $whereTurno = $tieneColIdTurno ? " OR (re.id_turno IS NOT NULL AND re.id_turno > 0)" : "";
        $ordenTurno = $tieneColIdTurno ? ", COALESCE(re.id_turno, 0) DESC" : "";

        $stmtFirmaProf = $pdo->prepare("SELECT u.firma, u.nombre, u.apellido, u.profesion, u.cargo,
            {$selectColegiatura}, {$selectCtmp}, {$selectPrivilegios}, {$selectRol}, {$selectTurno}
            FROM resultados_examenes re
            {$joinTurno}
            {$joinUsuario}
            WHERE re.id_cotizacion = ?
              AND (re.id_laboratorista IS NOT NULL AND re.id_laboratorista > 0 {$whereTurno})
            ORDER BY CASE WHEN re.estado = 'completado' THEN 0 ELSE 1 END{$ordenTurno}, re.id DESC
            LIMIT 1");
        $stmtFirmaProf->execute([(int)$cotizacion_id]);
        $prof = $stmtFirmaProf->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($prof) {
            $hayProfesionalResponsable = true;
            $firmaProfesionalPath = !empty($prof['firma']) ? $resolveEmpresaAssetPath((string)$prof['firma']) : '';
            $firmaProfesionalNombre = trim((string)($prof['nombre'] ?? '') . ' ' . (string)($prof['apellido'] ?? ''));
            $firmaProfesionalCargo = trim((string)($prof['profesion'] ?? '') !== '' ? (string)$prof['profesion'] : (string)($prof['cargo'] ?? ''));
            $firmaProfesionalColegiatura = $normalizarRegistroProfesional((string)($prof['colegiatura_numero'] ?? ''));
            $firmaProfesionalCtmp = $normalizarRegistroProfesional((string)($prof['ctmp_numero'] ?? ''));

            $turnoId = (int)($prof['turno_id'] ?? 0);
            if ($turnoId > 0) {
                $firmaProfesionalTurno = 'Turno #' . $turnoId;
            }

            if ($esModoParticular) {
                $rolFirma = strtolower(trim((string)($prof['rol_usuario'] ?? '')));
                $privilegiosFirma = usuarios_privilegios_desde_json(
                    (string)($prof['privilegios_json'] ?? ''),
                    $rolFirma,
                    false
                );
                $privUsarFirmaSelloProfesional = !empty($privilegiosFirma['resultados_pdf_firma_profesional']);
                $privMostrarProfesionalCabecera = !empty($privilegiosFirma['resultados_pdf_header_profesional']);
                $privMostrarTurnoCabecera = !empty($privilegiosFirma['resultados_pdf_header_turno']);
            }
        }
    }
} catch (Throwable $e) {
    $firmaProfesionalPath = '';
}
$items = obtenerItemsResultados($pdo, $rows);
$reporte = armarHtmlReporte($paciente, $referencia, $empresa, $items);

$solicitanteNombre = trim((string)($cot['profesional_solicitante_nombre'] ?? ''));
$solicitanteTipo = trim((string)($cot['profesional_solicitante_tipo'] ?? ''));
$solicitanteRegistro = trim((string)($cot['profesional_solicitante_registro'] ?? ''));

$mostrarNombreProfesional = $firmaProfesionalNombre !== '' && (!$esModoParticular || $privMostrarProfesionalCabecera);
$mostrarTurnoProfesional = $firmaProfesionalTurno !== '' && (!$esModoParticular || $privMostrarTurnoCabecera);
$mostrarFilaProfesional = ($mostrarNombreProfesional || $mostrarTurnoProfesional);
$mostrarFilaSolicitante = ($solicitanteNombre !== '' || $solicitanteTipo !== '' || $solicitanteRegistro !== '');
$mostrarFilaSeguro = !empty($tipoSeguro);

// Ajuste dinamico del margen superior segun filas reales del header para evitar huecos grandes.
$filasHeaderDatos = 4; // Paciente, DNI/Edad/Sexo, Referencia, Fecha.
if ($mostrarFilaProfesional) {
    $filasHeaderDatos++;
}
if ($mostrarFilaSolicitante) {
    $filasHeaderDatos++;
}
if ($mostrarFilaSeguro) {
    $filasHeaderDatos++;
}
$headerCompacto = !$mostrarFilaProfesional && !$mostrarFilaSolicitante && !$mostrarFilaSeguro;
$marginTopPdf = (int)ceil(51 + ($filasHeaderDatos * 3.3));
if ($headerCompacto) {
    $marginTopPdf -= 3;
}
$marginTopPdf = (int)max(61, min(78, $marginTopPdf));

$mpdf = new Mpdf([
    // Reservar espacio estable para un header alto (logo + QR + datos de paciente)
    // y evitar solapes del contenido desde la segunda hoja.
    'margin_top' => $marginTopPdf,
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

$profesionalHeaderHtml = '';
if ($mostrarFilaProfesional) {
    $profesionalHeaderHtml = '<tr><td colspan="2" style="padding:1px 6px; text-align:left;">';
    if ($mostrarNombreProfesional) {
        $profesionalHeaderHtml .= '<strong>Profesional:</strong> ' . htmlspecialchars($firmaProfesionalNombre, ENT_QUOTES, 'UTF-8') . '<br>';
    }
    if ($mostrarTurnoProfesional) {
        $profesionalHeaderHtml .= '<strong>Turno:</strong> ' . htmlspecialchars($firmaProfesionalTurno, ENT_QUOTES, 'UTF-8');
    }
    $profesionalHeaderHtml .= '</td></tr>';
}

$solicitanteHeaderHtml = '';
if ($mostrarFilaSolicitante) {
    $partesSolicitante = [];
    if ($solicitanteNombre !== '') {
        $partesSolicitante[] = $solicitanteNombre;
    }
    if ($solicitanteTipo !== '') {
        $partesSolicitante[] = $solicitanteTipo;
    }
    if ($solicitanteRegistro !== '') {
        $partesSolicitante[] = 'Reg: ' . $solicitanteRegistro;
    }
    $solicitanteHeaderHtml = '<tr><td colspan="2" style="padding:1px 6px;"><strong>Solicitado por:</strong> '
        . htmlspecialchars(implode(' | ', $partesSolicitante), ENT_QUOTES, 'UTF-8')
        . '</td></tr>';
}

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
        ' . $profesionalHeaderHtml . '
        ' . $solicitanteHeaderHtml . '
        <tr><td colspan="2" style="padding:1px 6px;"><strong>Referencia:</strong> ' . htmlspecialchars($referencia) . '</td></tr>
        ' . (!empty($tipoSeguro) ? '<tr><td colspan="2" style="padding:1px 6px;"><strong>Tipo de seguro:</strong> ' . htmlspecialchars($tipoSeguro) . '</td></tr>' : '') . '
        <tr><td colspan="2" style="padding:1px 6px;"><strong>Fecha:</strong> ' . htmlspecialchars($paciente['fecha']) . '</td></tr>
    </table>
';
$mpdf->SetHTMLHeader($headerHtml, 'O', true);
$mpdf->SetHTMLHeader($headerHtml, 'E', true);
$usarFirmaSelloProfesional = $hayProfesionalResponsable && (!$esModoParticular || $privUsarFirmaSelloProfesional);
$firmaFooterPath = '';
if ($usarFirmaSelloProfesional) {
    $firmaFooterPath = ($firmaProfesionalPath !== '' && file_exists($firmaProfesionalPath)) ? $firmaProfesionalPath : '';
} else {
    $firmaFooterPath = ($firma !== '' && file_exists($firma)) ? $firma : '';
}
$firma_html = $firmaFooterPath && file_exists($firmaFooterPath)
    ? '<img src="' . $firmaFooterPath . '" style="height:95px; display:block; margin:0 auto -18px auto;">'
    : '';

$selloHtml = '';
if ($usarFirmaSelloProfesional && $firmaProfesionalNombre !== '') {
    $selloTitulo = htmlspecialchars(strtoupper($firmaProfesionalNombre), ENT_QUOTES, 'UTF-8');
    $selloCargoRaw = trim((string)($firmaProfesionalCargo !== '' ? $firmaProfesionalCargo : 'Profesional Responsable'));
    $selloCargo = htmlspecialchars(strtoupper($selloCargoRaw), ENT_QUOTES, 'UTF-8');
    $registroLinea = '';
    $esTecnologoMedico = stripos($selloCargoRaw, 'tecnolog') !== false;
    if ($firmaProfesionalCtmp !== '') {
        $registroLinea = 'C.T.M.P. ' . $firmaProfesionalCtmp;
    } elseif (!$esTecnologoMedico && $firmaProfesionalColegiatura !== '') {
        $registroLinea = 'Colegiatura ' . $firmaProfesionalColegiatura;
    }

    $selloHtml = '<div style="display:block;width:100%;text-align:center !important;color:#111;font-size:10px;line-height:1.0;margin-top:-10px;">'
        . '<div style="width:210px;border-top:1.6px solid #1f2937;margin:0 auto 2px auto;"></div>'
        . '<div style="font-weight:700;text-align:center !important;">' . $selloTitulo . '</div>'
        . '<div style="font-weight:700;text-align:center !important;">' . $selloCargo . '</div>'
        . ($registroLinea !== '' ? '<div style="font-weight:700;text-align:center !important;">' . htmlspecialchars($registroLinea, ENT_QUOTES, 'UTF-8') . '</div>' : '')
        . ($firma_html === '' ? '<div style="font-size:9px;color:#b91c1c;text-align:center !important;">Firma no registrada</div>' : '')
        . '</div>';
}
$bloqueFirmaSello = '';
if ($firma_html !== '' || $selloHtml !== '') {
    $bloqueFirmaSello = '<table style="width:240px; border-collapse:collapse; margin:0 auto; padding:0;">'
        . '<tr><td style="text-align:center; padding:0;">' . $firma_html . '</td></tr>'
        . '<tr><td style="text-align:center; padding:0;">' . $selloHtml . '</td></tr>'
        . '</table>';
}
$footerTop = '<table style="width:100%; border-collapse:collapse; margin:0; padding:0;">'
    . '<tr>'
    . '<td style="width:58%; padding:0;"></td>'
    . '<td style="width:42%; text-align:right; vertical-align:bottom; padding:0;">' . $bloqueFirmaSello . '</td>'
    . '</tr>'
    . '</table>';

$mpdf->SetHTMLFooter('<div class="firma-footer">' . $footerTop . '<hr class="my-3" style="margin:8px 0;"><div style="font-size: 11px; color: #555; text-align:left;">Informe confidencial. Prohibida su reproducción total o parcial.<br><strong>Nota:</strong> Resultados fuera de los rangos referenciales se verán con un <strong>*</strong>.</div></div>');
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
