<?php

function documento_lookup_normalize(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function documento_lookup_tipo(string $documento): string
{
    $len = strlen($documento);
    if ($len === 8) {
        return 'dni';
    }
    if ($len === 11) {
        return 'ruc';
    }
    return 'otro';
}

function documento_lookup_load_config(): array
{
    $cfgPath = __DIR__ . '/../config/apisperu_config.php';
    $cfg = file_exists($cfgPath) ? require $cfgPath : [];

    return [
        'base_url' => isset($cfg['base_url']) ? (string)$cfg['base_url'] : 'https://dniruc.apisperu.com/api/v1',
        'token' => isset($cfg['token']) ? (string)$cfg['token'] : '',
        'timeout_seconds' => isset($cfg['timeout_seconds']) ? (int)$cfg['timeout_seconds'] : 8,
    ];
}

function documento_lookup_buscar_local(PDO $pdo, string $scope, string $documento): ?array
{
    if ($scope === 'empresa') {
        $stmt = $pdo->prepare('SELECT id, ruc, razon_social, nombre_comercial, direccion, telefono, email, representante, convenio, estado FROM empresas WHERE ruc = ? LIMIT 1');
        $stmt->execute([$documento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'entity' => 'empresa',
                'id' => (int)$row['id'],
                'documento' => (string)$row['ruc'],
                'tipo_documento' => 'ruc',
                'nombres' => '',
                'apellido_paterno' => '',
                'apellido_materno' => '',
                'razon_social' => (string)($row['razon_social'] ?? ''),
                'nombre_comercial' => (string)($row['nombre_comercial'] ?? ''),
                'direccion' => (string)($row['direccion'] ?? ''),
                'telefono' => (string)($row['telefono'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'raw' => $row,
            ];
        }
        return null;
    }

    if ($scope === 'convenio') {
        $stmt = $pdo->prepare('SELECT id, nombre, dni, especialidad, descripcion, email FROM convenios WHERE dni = ? LIMIT 1');
        $stmt->execute([$documento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'entity' => 'convenio',
                'id' => (int)$row['id'],
                'documento' => (string)$row['dni'],
                'tipo_documento' => documento_lookup_tipo((string)$row['dni']) === 'ruc' ? 'ruc' : 'dni',
                'nombres' => (string)($row['nombre'] ?? ''),
                'apellido_paterno' => '',
                'apellido_materno' => '',
                'razon_social' => documento_lookup_tipo((string)$row['dni']) === 'ruc' ? (string)($row['nombre'] ?? '') : '',
                'nombre_comercial' => '',
                'direccion' => '',
                'telefono' => '',
                'email' => (string)($row['email'] ?? ''),
                'raw' => $row,
            ];
        }
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, codigo_cliente, nombre, apellido, dni, tipo_documento, email, telefono, direccion, procedencia, sexo, fecha_nacimiento FROM clientes WHERE dni = ? LIMIT 1');
    $stmt->execute([$documento]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $tipoDoc = (string)($row['tipo_documento'] ?? 'dni');
        $tipoNormalizado = $tipoDoc === 'ruc' ? 'ruc' : (documento_lookup_tipo((string)$row['dni']) === 'ruc' ? 'ruc' : 'dni');

        return [
            'entity' => 'cliente',
            'id' => (int)$row['id'],
            'documento' => (string)$row['dni'],
            'tipo_documento' => $tipoNormalizado,
            'nombres' => (string)($row['nombre'] ?? ''),
            'apellido_paterno' => (string)($row['apellido'] ?? ''),
            'apellido_materno' => '',
            'razon_social' => $tipoNormalizado === 'ruc' ? trim((string)($row['nombre'] ?? '') . ' ' . (string)($row['apellido'] ?? '')) : '',
            'nombre_comercial' => '',
            'direccion' => (string)($row['direccion'] ?? ''),
            'telefono' => (string)($row['telefono'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'raw' => $row,
        ];
    }

    return null;
}

function documento_lookup_call_api(string $tipo, string $documento): array
{
    $cfg = documento_lookup_load_config();
    $token = trim((string)$cfg['token']);
    if ($token === '') {
        return [
            'ok' => false,
            'message' => 'Token APISPERU no configurado (APISPERU_TOKEN).',
            'http_status' => 500,
            'raw' => null,
        ];
    }

    $base = rtrim((string)$cfg['base_url'], '/');
    $url = $base . '/' . $tipo . '/' . rawurlencode($documento) . '?token=' . rawurlencode($token);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => max(3, (int)$cfg['timeout_seconds']),
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return [
            'ok' => false,
            'message' => $error !== '' ? $error : 'No se pudo consultar APISPERU.',
            'http_status' => 502,
            'raw' => null,
        ];
    }

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        return [
            'ok' => false,
            'message' => 'Respuesta no valida de APISPERU.',
            'http_status' => 502,
            'raw' => ['response' => $resp],
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = (string)($json['message'] ?? $json['error'] ?? 'Error de APISPERU.');
        return [
            'ok' => false,
            'message' => $msg,
            'http_status' => $httpCode > 0 ? $httpCode : 502,
            'raw' => $json,
        ];
    }

    if ($tipo === 'dni') {
        $nombres = trim((string)($json['nombres'] ?? ''));
        $apPat = trim((string)($json['apellidoPaterno'] ?? ''));
        $apMat = trim((string)($json['apellidoMaterno'] ?? ''));

        return [
            'ok' => true,
            'http_status' => 200,
            'message' => 'Consulta DNI exitosa.',
            'data' => [
                'documento' => (string)($json['dni'] ?? $documento),
                'tipo_documento' => 'dni',
                'nombres' => $nombres,
                'apellido_paterno' => $apPat,
                'apellido_materno' => $apMat,
                'razon_social' => '',
                'nombre_comercial' => '',
                'direccion' => '',
                'raw' => $json,
            ],
        ];
    }

    $razon = trim((string)($json['razonSocial'] ?? ''));
    $comercial = trim((string)($json['nombreComercial'] ?? ''));
    $direccion = trim((string)($json['direccion'] ?? ''));
    if ($direccion === '') {
        $direccion = trim(implode(' ', array_filter([
            (string)($json['direccion_'] ?? ''),
            (string)($json['distrito'] ?? ''),
            (string)($json['provincia'] ?? ''),
            (string)($json['departamento'] ?? ''),
        ])));
    }

    return [
        'ok' => true,
        'http_status' => 200,
        'message' => 'Consulta RUC exitosa.',
        'data' => [
            'documento' => (string)($json['ruc'] ?? $documento),
            'tipo_documento' => 'ruc',
            'nombres' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'razon_social' => $razon,
            'nombre_comercial' => $comercial,
            'direccion' => $direccion,
            'raw' => $json,
        ],
    ];
}

function documento_lookup_consultar(PDO $pdo, string $scope, string $documento): array
{
    $doc = documento_lookup_normalize($documento);
    $tipo = documento_lookup_tipo($doc);

    if ($doc === '') {
        return [
            'ok' => false,
            'status' => 'documento_vacio',
            'message' => 'Documento vacio.',
            'data' => null,
        ];
    }

    if ($tipo === 'otro') {
        return [
            'ok' => false,
            'status' => 'formato_no_soportado',
            'message' => 'Solo se permite DNI (8) o RUC (11).',
            'data' => null,
        ];
    }

    $local = documento_lookup_buscar_local($pdo, $scope, $doc);
    if ($local) {
        return [
            'ok' => true,
            'status' => 'encontrado_bd',
            'source' => 'bd',
            'message' => 'Documento encontrado en base local.',
            'data' => $local,
        ];
    }

    $api = documento_lookup_call_api($tipo, $doc);
    if (!empty($api['ok'])) {
        return [
            'ok' => true,
            'status' => 'encontrado_api',
            'source' => 'apis_peru',
            'message' => (string)($api['message'] ?? 'Consulta exitosa.'),
            'data' => $api['data'] ?? null,
        ];
    }

    return [
        'ok' => false,
        'status' => 'no_encontrado',
        'source' => 'apis_peru',
        'message' => (string)($api['message'] ?? 'No encontrado.'),
        'http_status' => (int)($api['http_status'] ?? 404),
        'data' => $api['raw'] ?? null,
    ];
}
