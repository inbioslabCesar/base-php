<?php
if (PHP_SAPI === 'cli') {
    echo "Este endpoint debe ejecutarse via HTTP.\n";
    exit(1);
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$loadSimpleEnv = function ($path) {
    if (!is_file($path)) {
        return;
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
};

$loadSimpleEnv(__DIR__ . '/.whatsapp.env');

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$to = preg_replace('/\D+/', '', (string)($data['to'] ?? ''));
$message = trim((string)($data['message'] ?? ''));

$sendMode = strtolower(trim((string)(getenv('META_WHATSAPP_SEND_MODE') ?: 'text')));
$templateName = trim((string)(getenv('META_WHATSAPP_TEMPLATE_NAME') ?: 'hello_world'));
$templateLang = trim((string)(getenv('META_WHATSAPP_TEMPLATE_LANG') ?: 'en_US'));
$templateUseParams = (int)(getenv('META_WHATSAPP_TEMPLATE_USE_PARAMS') ?: 0) === 1;
$templateFallbackName = trim((string)(getenv('META_WHATSAPP_TEMPLATE_FALLBACK_NAME') ?: 'hello_world'));
$templateFallbackLang = trim((string)(getenv('META_WHATSAPP_TEMPLATE_FALLBACK_LANG') ?: 'en_US'));
$templateParams = $data['template_params'] ?? [];
if (!is_array($templateParams)) {
    $templateParams = [];
}

if ($to === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'missing_to']);
    exit;
}

if ($sendMode !== 'template' && $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'missing_message']);
    exit;
}

$countryCode = preg_replace('/\D+/', '', (string)(getenv('META_WHATSAPP_COUNTRY_CODE') ?: '51'));
if ($countryCode !== '' && strpos($to, $countryCode) !== 0 && strlen($to) <= 11) {
    $to = $countryCode . ltrim($to, '0');
}

$phoneNumberId = trim((string)(getenv('META_WHATSAPP_PHONE_NUMBER_ID') ?: ''));
$token = trim((string)(getenv('META_WHATSAPP_TOKEN') ?: ''));

$logPath = __DIR__ . '/../tmp/whatsapp_webhook.log';
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

$logRow = [
    'at' => date('Y-m-d H:i:s'),
    'to' => $to,
    'send_mode' => $sendMode,
    'template_name' => $sendMode === 'template' ? $templateName : '',
    'template_lang' => $sendMode === 'template' ? $templateLang : '',
    'result_id' => (int)($data['result_id'] ?? 0),
    'cotizacion_id' => (int)($data['cotizacion_id'] ?? 0),
    'status' => (string)($data['status'] ?? ''),
];

if ($phoneNumberId === '' || $token === '') {
    $logRow['sent'] = false;
    $logRow['reason'] = 'meta_credentials_missing';
    @file_put_contents($logPath, json_encode($logRow, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'forwarded' => false,
        'message' => 'Webhook recibido, faltan credenciales META_WHATSAPP_PHONE_NUMBER_ID y/o META_WHATSAPP_TOKEN',
        'to' => $to,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = 'https://graph.facebook.com/v21.0/' . rawurlencode($phoneNumberId) . '/messages';
$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $to,
];

if ($sendMode === 'template') {
    $payload['type'] = 'template';
    $payload['template'] = [
        'name' => $templateName,
        'language' => ['code' => $templateLang],
    ];
    if ($templateUseParams && !empty($templateParams)) {
        $params = [];
        foreach ($templateParams as $param) {
            $paramText = trim((string)$param);
            if ($paramText === '') {
                continue;
            }
            $params[] = [
                'type' => 'text',
                'text' => $paramText,
            ];
        }
        if (!empty($params)) {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => $params,
                ],
            ];
        }
    }
} else {
    $payload['type'] = 'text';
    $payload['text'] = [
        'body' => $message,
    ];
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);

$resp = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($sendMode === 'template' && $status >= 400) {
    $providerError = json_decode((string)$resp, true);
    $providerCode = (int)($providerError['error']['code'] ?? 0);
    $templateMissing = $providerCode === 132001;
    $canFallback = $templateMissing
        && $templateFallbackName !== ''
        && !($templateFallbackName === $templateName && $templateFallbackLang === $templateLang);

    if ($canFallback) {
        $fallbackPayload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateFallbackName,
                'language' => ['code' => $templateFallbackLang],
            ],
        ];

        $ch2 = curl_init($url);
        curl_setopt_array($ch2, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp2 = curl_exec($ch2);
        $errno2 = curl_errno($ch2);
        $error2 = curl_error($ch2);
        $status2 = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($errno2 === 0 && $status2 >= 200 && $status2 < 300) {
            $resp = $resp2;
            $status = $status2;
            $logRow['fallback_used'] = true;
            $logRow['fallback_from_template'] = $templateName . ':' . $templateLang;
            $logRow['fallback_to_template'] = $templateFallbackName . ':' . $templateFallbackLang;
            $logRow['template_name'] = $templateFallbackName;
            $logRow['template_lang'] = $templateFallbackLang;
        } else {
            $logRow['fallback_attempted'] = true;
            $logRow['fallback_error'] = $errno2 !== 0
                ? ('curl_' . $errno2 . ': ' . $error2)
                : ('http_' . $status2 . ': ' . substr((string)$resp2, 0, 300));
        }
    }
}

if ($errno !== 0 || $status < 200 || $status >= 300) {
    $logRow['sent'] = false;
    $logRow['reason'] = $errno !== 0 ? ('curl_' . $errno . ': ' . $error) : ('http_' . $status . ': ' . substr((string)$resp, 0, 300));
    @file_put_contents($logPath, json_encode($logRow, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'forwarded' => false,
        'error' => $logRow['reason'],
        'to' => $to,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$logRow['sent'] = true;
$logRow['provider_status'] = $status;
$providerData = json_decode((string)$resp, true);
if (is_array($providerData)) {
    $logRow['provider_message_id'] = (string)($providerData['messages'][0]['id'] ?? '');
    $logRow['provider_message_status'] = (string)($providerData['messages'][0]['message_status'] ?? '');
}
$logRow['provider_response'] = substr((string)$resp, 0, 500);
@file_put_contents($logPath, json_encode($logRow, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

echo json_encode([
    'ok' => true,
    'forwarded' => true,
    'provider_status' => $status,
    'to' => $to,
], JSON_UNESCAPED_UNICODE);
