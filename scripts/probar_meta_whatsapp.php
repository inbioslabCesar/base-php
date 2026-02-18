<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script debe ejecutarse por CLI.\n";
    exit(1);
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

$phoneNumberId = trim((string)(getenv('META_WHATSAPP_PHONE_NUMBER_ID') ?: ''));
$token = trim((string)(getenv('META_WHATSAPP_TOKEN') ?: ''));
$countryCode = preg_replace('/\D+/', '', (string)(getenv('META_WHATSAPP_COUNTRY_CODE') ?: '51'));

if ($phoneNumberId === '' || $token === '') {
    echo "Faltan credenciales en scripts/.whatsapp.env\n";
    echo "META_WHATSAPP_PHONE_NUMBER_ID=...\n";
    echo "META_WHATSAPP_TOKEN=...\n";
    exit(1);
}

$show = function ($text) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
};

$show('Validando credenciales con Meta Graph API...');
$checkUrl = 'https://graph.facebook.com/v21.0/' . rawurlencode($phoneNumberId) . '?fields=id,display_phone_number,verified_name';

$ch = curl_init($checkUrl);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$resp = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0) {
    $show('Error cURL: ' . $error);
    exit(1);
}
if ($status < 200 || $status >= 300) {
    $show('Meta respondió HTTP ' . $status . ': ' . substr((string)$resp, 0, 500));
    exit(1);
}

$data = json_decode((string)$resp, true);
$show('Credenciales OK. Número Meta: ' . (string)($data['display_phone_number'] ?? 'N/D') . ' - Nombre: ' . (string)($data['verified_name'] ?? 'N/D'));

$to = $argv[1] ?? '';
if ($to === '') {
    $show('Sin número destino para prueba. Uso: php scripts/probar_meta_whatsapp.php 945241682');
    exit(0);
}

$to = preg_replace('/\D+/', '', (string)$to);
if ($countryCode !== '' && strpos($to, $countryCode) !== 0 && strlen($to) <= 11) {
    $to = $countryCode . ltrim($to, '0');
}

$message = 'Prueba de conexión Meta WhatsApp desde INBIOSLAB. Hora: ' . date('Y-m-d H:i:s');
$sendUrl = 'https://graph.facebook.com/v21.0/' . rawurlencode($phoneNumberId) . '/messages';
$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $to,
    'type' => 'text',
    'text' => ['body' => $message],
];

$ch = curl_init($sendUrl);
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

if ($errno !== 0) {
    $show('Error cURL al enviar: ' . $error);
    exit(1);
}
if ($status < 200 || $status >= 300) {
    $show('Error enviando WhatsApp. HTTP ' . $status . ': ' . substr((string)$resp, 0, 500));
    exit(1);
}

$show('Mensaje de prueba enviado correctamente a ' . $to);
$show('Respuesta Meta: ' . substr((string)$resp, 0, 500));
