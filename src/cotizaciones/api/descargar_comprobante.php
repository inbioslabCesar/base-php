<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../../facturacion/FacturacionService.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : '';
$allow = ['xml','pdf','cdr'];
if ($id <= 0 || !in_array($tipo, $allow)) {
    http_response_code(400);
    echo 'Parámetros inválidos';
    exit;
}
$svc = new FacturacionService($pdo, new FacturacionAuthService());
$config = require __DIR__ . '/../../config/facturacion_config.php';

// Intentar refrescar estado antes de descargar
$st = $svc->refreshRemoteStatus($id);
if (!$st) { $st = $svc->getStatus($id); }

$remoteId = is_array($st) ? ($st['remote_id'] ?? null) : null;
$tipoDoc = is_array($st) ? ($st['tipo'] ?? null) : null; // 'boleta' | 'factura'

// Si hay ID remoto, proxy al API
if ($remoteId && $tipoDoc) {
    $base = rtrim($config['base_url'] ?? '', '/');
    $routeKey = 'download_' . $tipo . '_' . $tipoDoc; // download_xml_boleta, download_cdr_boleta, download_pdf_boleta
    $route = $config['routes'][$routeKey] ?? null;

    if ($route) {
        $url = $base . str_replace('{id}', $remoteId, $route);

        // PDF: aplicar formato dinámico (por defecto config)
        if ($tipo === 'pdf') {
            $formato = isset($_GET['formato']) ? trim($_GET['formato']) : ($config['defaults']['pdf_format'] ?? 'A4');
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'format=' . urlencode($formato);
        }

        $token = (new FacturacionAuthService())->getToken((int)($st['empresa_id'] ?? 0));
        $headers = ['Authorization: Bearer ' . $token];
        if ($tipo === 'xml') { $headers[] = 'Accept: application/xml'; }
        if ($tipo === 'pdf') { $headers[] = 'Accept: application/pdf'; }
        if ($tipo === 'cdr') { $headers[] = 'Accept: application/zip'; }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($code >= 200 && $code < 300 && $data !== false && $data !== '') {
            $mime = 'application/octet-stream';
            if ($tipo === 'xml') $mime = 'application/xml';
            if ($tipo === 'pdf') $mime = 'application/pdf';
            if ($tipo === 'cdr') $mime = 'application/zip';
            $ext = $tipo === 'cdr' ? 'zip' : $tipo;
            $filename = 'comprobante_' . $tipo . '_' . $id . '.' . $ext;

            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $data;
            exit;
        }

        // Si el PDF no existe en el API, intentar generarlo y volver a descargar
        if ($tipo === 'pdf' && $code === 404) {
            $genUrl = $base . str_replace('{id}', $remoteId, $config['routes']['generate_pdf_' . $tipoDoc] ?? ('/api/v1/' . ($tipoDoc === 'boleta' ? 'boletas' : 'invoices') . '/{id}/generate-pdf'));
            $genUrl = str_replace('{id}', $remoteId, $genUrl);
            $chGen = curl_init($genUrl);
            curl_setopt_array($chGen, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
                CURLOPT_TIMEOUT => 60,
            ]);
            curl_exec($chGen);
            curl_close($chGen);

            // Reintento de descarga
            $chRetry = curl_init($url);
            curl_setopt_array($chRetry, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 60,
            ]);
            $dataRetry = curl_exec($chRetry);
            $codeRetry = curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
            curl_close($chRetry);

            if ($codeRetry >= 200 && $codeRetry < 300 && $dataRetry !== false && $dataRetry !== '') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="comprobante_pdf_' . $id . '.pdf"');
                echo $dataRetry;
                exit;
            }
        }

        // Log descarga fallida
        $dlLog = __DIR__ . '/../../tmp/facturacion/logs/downloads.log';
        @file_put_contents($dlLog, json_encode([
            'time' => date('c'),
            'cotizacion_id' => $id,
            'tipo' => $tipo,
            'tipo_doc' => $tipoDoc,
            'remote_id' => $remoteId,
            'url' => $url,
            'code' => $code,
            'error' => $err,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}
// Fallback local
$baseLocal = __DIR__ . '/../../tmp/facturacion';
$pathLocal = null;
if ($tipo === 'xml') $pathLocal = $baseLocal . '/payloads/cotizacion_' . $id . '.json'; // temporal
if ($tipo === 'pdf') $pathLocal = $baseLocal . '/payloads/cotizacion_' . $id . '.pdf';
if ($tipo === 'cdr') $pathLocal = $baseLocal . '/payloads/cotizacion_' . $id . '.zip';
if ($pathLocal && file_exists($pathLocal)) {
    $mime = 'application/octet-stream';
    if ($tipo === 'xml') $mime = 'application/json';
    if ($tipo === 'pdf') $mime = 'application/pdf';
    if ($tipo === 'cdr') $mime = 'application/zip';
    $ext = $tipo === 'cdr' ? 'zip' : $tipo;
    $filename = 'comprobante_' . $tipo . '_' . $id . '.' . $ext;
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($pathLocal);
    exit;
}
http_response_code(404);
$estado = is_array($st) ? (strtoupper($st['status'] ?? 'SIN_ESTADO')) : 'SIN_ESTADO';
if ($tipo === 'pdf') {
    echo 'PDF no disponible. Estado actual: ' . $estado . '.';
} else {
    echo 'Archivo no disponible. Estado actual: ' . $estado . '. Para XML/CDR el documento debe estar ACEPTADO por SUNAT.';
}
