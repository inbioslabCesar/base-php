<?php
// Configuracion de apiperu.net para consulta de DNI/RUC (Authorization: Bearer).
// Token: variable de entorno APISPERU_TOKEN o archivo local no versionado apisperu_token.local.php.

$token = (string)getenv('APISPERU_TOKEN');
$tokenLocalPath = __DIR__ . '/apisperu_token.local.php';
if ($token === '' && file_exists($tokenLocalPath)) {
    $token = trim((string)(require $tokenLocalPath));
}

return [
    'base_url' => 'https://apiperu.net/consulta',
    'token' => $token,
    'timeout_seconds' => 8,
];
