<?php

function qr_verificacion_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function qr_verificacion_base64url_decode(string $data): ?string
{
    $norm = strtr($data, '-_', '+/');
    $pad = strlen($norm) % 4;
    if ($pad > 0) {
        $norm .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($norm, true);
    return $decoded === false ? null : $decoded;
}

function qr_verificacion_normalize_array($value)
{
    if (!is_array($value)) {
        return $value;
    }

    $isAssoc = array_keys($value) !== range(0, count($value) - 1);
    if ($isAssoc) {
        ksort($value);
    }

    foreach ($value as $k => $v) {
        $value[$k] = qr_verificacion_normalize_array($v);
    }

    return $value;
}

function qr_verificacion_resultados_fingerprint(int $cotizacionId, array $rows): string
{
    $compactRows = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $resultadosRaw = (string)($row['resultados'] ?? '');
        $resultados = [];
        if ($resultadosRaw !== '') {
            $tmp = json_decode($resultadosRaw, true);
            if (is_array($tmp)) {
                $resultados = $tmp;
            }
        }

        $imprimirExamen = !isset($resultados['imprimir_examen']) || (int)$resultados['imprimir_examen'] === 1;
        if (!$imprimirExamen) {
            continue;
        }

        $compactRows[] = [
            'id' => (int)($row['id'] ?? 0),
            'id_examen' => (int)($row['id_examen'] ?? 0),
            'estado' => (string)($row['estado'] ?? ''),
            'estado_validacion' => (string)($row['estado_validacion'] ?? ''),
            'fecha_ingreso' => (string)($row['fecha_ingreso'] ?? ''),
            'fecha_proceso_en' => (string)($row['fecha_proceso_en'] ?? ''),
            'fecha_validacion_en' => (string)($row['fecha_validacion_en'] ?? ''),
            'resultados' => qr_verificacion_normalize_array($resultados),
        ];
    }

    $payload = [
        'cotizacion_id' => $cotizacionId,
        'rows' => qr_verificacion_normalize_array($compactRows),
    ];

    return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function qr_verificacion_parse_unix_time($value): int
{
    if ($value === null) {
        return 0;
    }

    if (is_int($value)) {
        return $value > 0 ? $value : 0;
    }

    $raw = trim((string)$value);
    if ($raw === '') {
        return 0;
    }

    if (ctype_digit($raw)) {
        $ts = (int)$raw;
        return $ts > 0 ? $ts : 0;
    }

    $ts = strtotime($raw);
    return $ts !== false && $ts > 0 ? (int)$ts : 0;
}

function qr_verificacion_secrets(array $empresa = []): array
{
    $fromEnv = trim((string)getenv('RESULTADOS_QR_SECRET'));
    if ($fromEnv !== '') {
        $primary = $fromEnv;
    } else {
        $primary = '';
    }

    if ($primary === '' && defined('RESULTADOS_QR_SECRET')) {
        $fromConstant = trim((string)RESULTADOS_QR_SECRET);
        if ($fromConstant !== '') {
            $primary = $fromConstant;
        }
    }

    if ($primary === '') {
        $appKey = trim((string)getenv('APP_KEY'));
        if ($appKey !== '') {
            $primary = $appKey;
        }
    }

    if ($primary === '') {
        $dbHost = defined('DB_HOST') ? (string)DB_HOST : '';
        $dbName = defined('DB_NAME') ? (string)DB_NAME : '';
        $dbUser = defined('DB_USER') ? (string)DB_USER : '';
        $dbPass = defined('DB_PASS') ? (string)DB_PASS : '';
        $empresaRuc = trim((string)($empresa['ruc'] ?? ''));
        $primary = hash('sha256', $dbHost . '|' . $dbName . '|' . $dbUser . '|' . $dbPass . '|' . $empresaRuc . '|qr-verify-v1');
    }

    $previous = trim((string)getenv('RESULTADOS_QR_SECRET_PREVIOUS'));
    if ($previous === '' && defined('RESULTADOS_QR_SECRET_PREVIOUS')) {
        $previous = trim((string)RESULTADOS_QR_SECRET_PREVIOUS);
    }

    $previousValidUntilRaw = trim((string)getenv('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL'));
    if ($previousValidUntilRaw === '' && defined('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL')) {
        $previousValidUntilRaw = trim((string)RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL);
    }

    $previousValidUntil = qr_verificacion_parse_unix_time($previousValidUntilRaw);

    return [
        'primary' => $primary,
        'previous' => $previous,
        'previous_valid_until' => $previousValidUntil,
    ];
}

function qr_verificacion_secret(array $empresa = []): string
{
    $secrets = qr_verificacion_secrets($empresa);
    return (string)($secrets['primary'] ?? '');
}

function qr_verificacion_firmar_token(array $payload, array $empresa = []): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return '';
    }

    $payloadB64 = qr_verificacion_base64url_encode($json);
    $signature = hash_hmac('sha256', $payloadB64, qr_verificacion_secret($empresa), true);
    $sigB64 = qr_verificacion_base64url_encode($signature);

    return $payloadB64 . '.' . $sigB64;
}

function qr_verificacion_validar_token(string $token, array $empresa = []): array
{
    $parts = explode('.', trim($token));
    if (count($parts) !== 2) {
        return ['ok' => false, 'error' => 'Formato de token inválido'];
    }

    [$payloadB64, $sigB64] = $parts;
    if ($payloadB64 === '' || $sigB64 === '') {
        return ['ok' => false, 'error' => 'Token incompleto'];
    }

    $sigRaw = qr_verificacion_base64url_decode($sigB64);
    if ($sigRaw === null) {
        return ['ok' => false, 'error' => 'Firma ilegible'];
    }

    $payloadJson = qr_verificacion_base64url_decode($payloadB64);
    if ($payloadJson === null || $payloadJson === '') {
        return ['ok' => false, 'error' => 'Payload inválido'];
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return ['ok' => false, 'error' => 'Payload no válido'];
    }

    $version = (int)($payload['v'] ?? 0);
    $cotizacionId = (int)($payload['cid'] ?? 0);
    $fingerprint = trim((string)($payload['fp'] ?? ''));

    if ($version !== 1 || $cotizacionId <= 0 || $fingerprint === '') {
        return ['ok' => false, 'error' => 'Payload incompleto'];
    }

    $secrets = qr_verificacion_secrets($empresa);
    $expectedPrimary = hash_hmac('sha256', $payloadB64, (string)$secrets['primary'], true);
    $signatureValid = hash_equals($expectedPrimary, $sigRaw);

    if (!$signatureValid) {
        $previous = trim((string)($secrets['previous'] ?? ''));
        $previousValidUntil = (int)($secrets['previous_valid_until'] ?? 0);
        $iat = (int)($payload['iat'] ?? 0);
        $now = time();

        $canUsePrevious = $previous !== ''
            && $previousValidUntil > 0
            && $now <= $previousValidUntil
            && $iat > 0
            && $iat <= $previousValidUntil;

        if ($canUsePrevious) {
            $expectedPrevious = hash_hmac('sha256', $payloadB64, $previous, true);
            $signatureValid = hash_equals($expectedPrevious, $sigRaw);
        }
    }

    if (!$signatureValid) {
        return ['ok' => false, 'error' => 'Firma inválida'];
    }

    return [
        'ok' => true,
        'payload' => [
            'v' => $version,
            'cid' => $cotizacionId,
            'fp' => $fingerprint,
            'iat' => (int)($payload['iat'] ?? 0),
        ],
    ];
}

function qr_verificacion_mask_dni(string $dni): string
{
    $dni = trim($dni);
    if ($dni === '' || $dni === '--') {
        return '--';
    }
    $len = strlen($dni);
    if ($len <= 4) {
        return str_repeat('*', max(0, $len - 1)) . substr($dni, -1);
    }
    return str_repeat('*', $len - 4) . substr($dni, -4);
}
