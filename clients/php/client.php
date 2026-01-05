<?php
// Simple API client to create, send, check status, and download CPEs (boletas/invoices)
// Copy/paste ready for other projects.

$CFG = [
  'BASE_URL' => getenv('FACT_API_BASE_URL') ?: 'http://127.0.0.1:8000',
  'USER' => getenv('FACT_API_USER') ?: 'admin@sistema-sunat.com',
  'PASSWORD' => getenv('FACT_API_PASSWORD') ?: 'Admin123!@#',
  'COMPANY_ID' => (int)(getenv('FACT_COMPANY_ID') ?: 1),
  'BRANCH_ID' => (int)(getenv('FACT_BRANCH_ID') ?: 1),
  'SEND_METHOD' => getenv('FACT_SEND_METHOD') ?: 'individual',
  'TAX_MODE' => getenv('FACT_TAX_MODE') ?: 'exonerado', // gravado|exonerado
  'PDF_FORMAT' => getenv('FACT_PDF_FORMAT') ?: '80mm', // A4|80mm
];

function httpJson(string $method, string $url, ?string $token = null, $body = null, array $headers = []): array {
  $ch = curl_init($url);
  $h = array_merge(['Accept: application/json'], $headers);
  if ($token) { $h[] = 'Authorization: Bearer ' . $token; }
  if ($body !== null) {
    $h[] = 'Content-Type: application/json';
    $payload = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $body;
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  }
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => strtoupper($method),
    CURLOPT_HTTPHEADER => $h,
    CURLOPT_TIMEOUT => 30,
  ]);
  $resp = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($err) { throw new Exception('HTTP error: ' . $err); }
  $json = json_decode($resp, true) ?: [];
  if ($code >= 400) {
    $msg = $json['message'] ?? ('HTTP ' . $code);
    throw new Exception($msg);
  }
  return $json;
}

function login(array $CFG): string {
  $url = rtrim($CFG['BASE_URL'], '/') . '/api/auth/login';
  $data = ['email' => $CFG['USER'], 'username' => $CFG['USER'], 'password' => $CFG['PASSWORD']];
  $res = httpJson('POST', $url, null, $data);
  $token = $res['access_token'] ?? ($res['data']['access_token'] ?? null);
  if (!$token) { throw new Exception('No access token'); }
  return $token;
}

function buildPayload(array $CFG, array $opts = []): array {
  $gravado = ($CFG['TAX_MODE'] === 'gravado');
  $porc = $gravado ? 18 : 0;
  $afe = $gravado ? '10' : '20';
  $serie = $gravado ? 'F001' : 'B001'; // demo: empresas gravado → factura; exonerado → boleta
  return [
    'company_id' => $CFG['COMPANY_ID'],
    'branch_id' => $CFG['BRANCH_ID'],
    'metodo_envio' => $CFG['SEND_METHOD'],
    'serie' => $opts['serie'] ?? $serie,
    'fecha_emision' => date('Y-m-d'),
    'client' => [
      'tipo_documento' => $opts['tipo_documento'] ?? '1',
      'numero_documento' => $opts['numero_documento'] ?? '12345678',
      'razon_social' => $opts['razon_social'] ?? 'Cliente Demo',
    ],
    'detalles' => [[
      'codigo' => $opts['codigo'] ?? 'ITEM-001',
      'descripcion' => $opts['descripcion'] ?? 'Servicio Demo',
      'unidad' => 'NIU',
      'cantidad' => 1,
      'mto_valor_unitario' => (float)($opts['valor_unitario'] ?? 50.00),
      'porcentaje_igv' => $porc,
      'tip_afe_igv' => $afe,
    ]],
  ];
}

function createSendDownload(array $CFG): void {
  $token = login($CFG);
  $base = rtrim($CFG['BASE_URL'], '/');
  $gravado = ($CFG['TAX_MODE'] === 'gravado');
  $tipo = $gravado ? 'invoices' : 'boletas';
  // Crear
  $payload = buildPayload($CFG);
  $create = httpJson('POST', $base . '/api/v1/' . $tipo, $token, $payload);
  $id = $create['data']['id'] ?? $create['id'] ?? null;
  if (!$id) { throw new Exception('No remote id'); }
  echo "Created $tipo id=$id\n";
  // Enviar SUNAT
  $send = httpJson('POST', $base . '/api/v1/' . $tipo . '/' . $id . '/send-sunat', $token, null);
  $estado = strtolower($send['estado_sunat'] ?? ($send['sunat_status'] ?? ($send['status'] ?? 'enviado')));
  echo "Send status=$estado\n";
  // Estado (fallback al GET del recurso)
  $get = httpJson('GET', $base . '/api/v1/' . $tipo . '/' . $id, $token, null);
  $estado2 = strtolower($get['estado_sunat'] ?? ($get['sunat_status'] ?? ($get['status'] ?? $estado)));
  echo "Current status=$estado2\n";
  // Descargar PDF
  $pdf = $base . '/api/v1/' . $tipo . '/' . $id . '/download-pdf?format=' . urlencode($CFG['PDF_FORMAT']);
  echo "PDF: $pdf\n";
}

if (PHP_SAPI === 'cli') {
  try {
    createSendDownload($CFG);
  } catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
  }
}
