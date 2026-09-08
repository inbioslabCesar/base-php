<?php
// Prueba rapida de rotacion de claves para QR de resultados.
// Ejecutar: php scripts/test_qr_secret_rotation.php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/resultados/qr_verificacion_utils.php';

$now = time();
$newSecret = 'ROTATE_NEW_SECRET_2026_09_08';
$oldSecret = 'ROTATE_OLD_SECRET_2026_08_31';

$payload = [
    'v' => 1,
    'cid' => 9999,
    'fp' => hash('sha256', 'fingerprint-demo'),
    'iat' => $now,
];

$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$payloadB64 = qr_verificacion_base64url_encode((string)$payloadJson);

$buildToken = static function (string $secret) use ($payloadB64): string {
    $sig = hash_hmac('sha256', $payloadB64, $secret, true);
    return $payloadB64 . '.' . qr_verificacion_base64url_encode($sig);
};

$tokenOld = $buildToken($oldSecret);
$tokenNew = $buildToken($newSecret);

putenv('RESULTADOS_QR_SECRET=' . $newSecret);
putenv('RESULTADOS_QR_SECRET_PREVIOUS=' . $oldSecret);
putenv('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL=' . ($now + 3600));

$r1 = qr_verificacion_validar_token($tokenNew, []);
$r2 = qr_verificacion_validar_token($tokenOld, []);

putenv('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL=' . ($now - 60));
$r3 = qr_verificacion_validar_token($tokenOld, []);

$ok1 = !empty($r1['ok']);
$ok2 = !empty($r2['ok']);
$ok3 = !empty($r3['ok']);

$allGood = $ok1 && $ok2 && !$ok3;

echo "\n=== QR SECRET ROTATION TEST ===\n";
echo 'New key token valid: ' . ($ok1 ? 'YES' : 'NO') . "\n";
echo 'Old key token valid in window: ' . ($ok2 ? 'YES' : 'NO') . "\n";
echo 'Old key token after expiry: ' . ($ok3 ? 'YES (UNEXPECTED)' : 'NO (EXPECTED)') . "\n";
echo 'Overall result: ' . ($allGood ? 'PASS' : 'FAIL') . "\n\n";

if (!$allGood) {
    echo "Details:\n";
    echo 'r1=' . json_encode($r1, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo 'r2=' . json_encode($r2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo 'r3=' . json_encode($r3, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

exit(0);
