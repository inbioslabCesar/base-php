<?php
class FacturacionService {
    private $pdo;
    private $auth;
    private $storageDir;
    private $statusPath;
    private $config;
    private $columnsCache = [];

    public function __construct(PDO $pdo, FacturacionAuthService $auth, string $storageDir = null) {
        $this->pdo = $pdo;
        $this->auth = $auth;
        $this->storageDir = $storageDir ?: __DIR__ . '/../../tmp/facturacion';
        if (!is_dir($this->storageDir)) { @mkdir($this->storageDir, 0777, true); }
        if (!is_dir($this->storageDir . '/payloads')) { @mkdir($this->storageDir . '/payloads', 0777, true); }
        if (!is_dir($this->storageDir . '/logs')) { @mkdir($this->storageDir . '/logs', 0777, true); }
        $this->statusPath = $this->storageDir . '/status.json';
        if (!file_exists($this->statusPath)) { file_put_contents($this->statusPath, json_encode([])); }
        $this->config = require __DIR__ . '/../config/facturacion_config.php';
    }

    public function emitirComprobante(int $cotizacionId, array $opciones = []): array {
        // Guardar reemisión: si ya existe estado y/o ID remoto, evitar duplicados
        $existing = $this->getStatus($cotizacionId);
        if ($existing) {
            $curr = strtolower((string)($existing['status'] ?? ''));
            $hasRemote = !empty($existing['remote_id']);
            // Si ya está aceptado, no volver a crear/enviar
            if ($curr === 'aceptado') {
                return $existing + ['already_emitted' => true];
            }
            // Si existe ID remoto (cualquier estado distinto de aceptado), intentar reenvío y NO recrear
            if ($hasRemote) {
                try {
                    $re = $this->reintentarEnvio($cotizacionId);
                    if ($re) { return $re + ['reintento' => true]; }
                    return $existing + ['error' => 'No se pudo reintentar el envío a SUNAT (sin respuesta del reintento).', 'reintento_failed' => true];
                } catch (Throwable $e) {
                    $this->log('resend_error', ['cotizacion_id' => $cotizacionId, 'message' => $e->getMessage()]);
                    return $existing + ['error' => 'No se pudo reintentar el envío a SUNAT: ' . $e->getMessage(), 'reintento_failed' => true];
                }
            }
        }
        $cot = $this->getCotizacion($cotizacionId);
        if (!$cot) throw new Exception('Cotización no encontrada');
        $det = $this->getDetalles($cotizacionId);
        $empresaId = (int)($cot['id_empresa'] ?? 0);
        $token = null;
        // Siempre intentar obtener token usando emisora por defecto si no hay empresa asociada
        try { $token = $this->auth->getToken($empresaId ?: 0); } catch (Throwable $e) { $this->log('auth_error', ['cotizacion_id' => $cotizacionId, 'message' => $e->getMessage()]); }
        $payload = $this->buildPayload($cot, $det, $opciones);
        $this->persistPayload($cotizacionId, $payload);
        $result = ['status' => 'pendiente', 'cotizacion_id' => $cotizacionId, 'token_present' => (bool)$token];
        $this->setStatus($cotizacionId, $result['status'], ['token_present' => (bool)$token, 'empresa_id' => ($empresaId ?: 0)]);
        if ($token) {
            // Intentar crear y enviar de forma síncrona (placeholder basado en configuración)
            try {
                $res = $this->createAndSendRemote($cotizacionId, $payload, $token);
                $result = $res + ['cotizacion_id' => $cotizacionId];
                $extra = [
                    'token_present' => true,
                    'remote_id' => $result['remote_id'] ?? null,
                    'tipo' => $result['tipo'] ?? null,
                    'empresa_id' => ($empresaId ?: 0),
                ];
                if (isset($res['sunat_message'])) { $extra['sunat_message'] = $res['sunat_message']; }
                if (isset($res['sunat_code'])) { $extra['sunat_code'] = $res['sunat_code']; }
                if (isset($res['xml_path'])) { $extra['xml_path'] = $res['xml_path']; }
                if (isset($res['cdr_path'])) { $extra['cdr_path'] = $res['cdr_path']; }
                if (isset($res['pdf_path'])) { $extra['pdf_path'] = $res['pdf_path']; }
                $this->setStatus($cotizacionId, $result['status'], $extra);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $this->log('send_error', ['cotizacion_id' => $cotizacionId, 'message' => $msg]);
                // Guardar el error en el estado para que la UI pueda mostrarlo
                $this->setStatus($cotizacionId, 'pendiente', ['token_present' => true, 'empresa_id' => ($empresaId ?: 0), 'last_error' => $msg]);
                $result['error'] = $msg;
            }
        }
        return $result;
    }

    // Placeholder: envío al API externo y a SUNAT (síncrono)
    public function enviarASunat(int $cotizacionId): array {
        $st = $this->getStatus($cotizacionId);
        if (!$st) { throw new Exception('Sin estado previo. Emita primero el comprobante.'); }
        $this->setStatus($cotizacionId, 'enviado');
        // Integrar aquí llamada real al API: create + send, actualizar según respuesta
        return ['status' => 'enviado', 'cotizacion_id' => $cotizacionId];
    }

    public function getStatus(int $cotizacionId): ?array {
        $all = $this->readStatusAll();
        $key = (string)$cotizacionId;
        return isset($all[$key]) ? $all[$key] : null;
    }

    // Consulta el API externo para sincronizar el estado (aceptado/observado/rechazado)
    public function refreshRemoteStatus(int $cotizacionId): ?array {
        $st = $this->getStatus($cotizacionId);
        if (!$st || empty($st['remote_id']) || empty($st['tipo'])) {
            return $st ?: null;
        }
        $empresaId = (int)($st['empresa_id'] ?? 0);
        $token = null;
        try { $token = $this->auth->getToken($empresaId); } catch (Throwable $e) {
            $this->log('auth_error_refresh', ['cotizacion_id' => $cotizacionId, 'message' => $e->getMessage()]);
            return $st; // no cambia
        }
        $base = rtrim($this->config['base_url'] ?? '', '/');
            $routeKey = 'status_' . $st['tipo']; // status_boleta | status_factura
            $route = $this->config['routes'][$routeKey] ?? null;
            $resp = null;
            try {
                if ($route) {
                    $url = $base . str_replace('{id}', $st['remote_id'], $route);
                    $resp = $this->curlJson('GET', $url, null, $token, [
                        'stage' => 'status', 'cotizacion_id' => $cotizacionId, 'tipo' => $st['tipo'], 'remote_id' => $st['remote_id']
                    ]);
                }
            } catch (Throwable $e) {
                // Si la ruta status no existe, intentaremos GET del recurso
                $this->log('status_error', ['cotizacion_id' => $cotizacionId, 'message' => $e->getMessage()]);
            }
            // Fallback: GET del recurso si no hay status
            if (!$resp) {
                $getKey = 'get_' . $st['tipo']; // get_boleta | get_factura
                $getRoute = $this->config['routes'][$getKey] ?? null;
                if ($getRoute) {
                    try {
                        $getUrl = $base . str_replace('{id}', $st['remote_id'], $getRoute);
                        $resp = $this->curlJson('GET', $getUrl, null, $token, [
                            'stage' => 'status', 'cotizacion_id' => $cotizacionId, 'tipo' => $st['tipo'], 'remote_id' => $st['remote_id']
                        ]);
                    } catch (Throwable $e2) {
                        $this->log('status_error', ['cotizacion_id' => $cotizacionId, 'message' => $e2->getMessage()]);
                    }
                }
            }
            if ($resp) {
                $status = $resp['status'] ?? $resp['sunat_status'] ?? $resp['estado_sunat'] ?? ($resp['data']['status'] ?? null);
                $status = is_string($status) ? strtolower($status) : null;
                if (!in_array($status, ['aceptado','observado','rechazado','enviado','pendiente'])) {
                    if (!empty($resp['accepted']) || !empty(($resp['data']['accepted'] ?? null))) { $status = 'aceptado'; }
                }
                // Detectar aceptación por presencia de rutas de archivos
                $data = (isset($resp['data']) && is_array($resp['data'])) ? $resp['data'] : $resp;
                if (($data['xml_path'] ?? null) || ($data['cdr_path'] ?? null)) { $status = 'aceptado'; }
                if ($status) {
                    $extra = $st;
                    $extra['sunat_code'] = $resp['sunat_code'] ?? ($resp['data']['sunat_code'] ?? null);
                    $extra['sunat_message'] = $resp['sunat_message'] ?? ($resp['data']['sunat_message'] ?? ($resp['message'] ?? null));
                    $extra['xml_path'] = $data['xml_path'] ?? ($extra['xml_path'] ?? null);
                    $extra['cdr_path'] = $data['cdr_path'] ?? ($extra['cdr_path'] ?? null);
                    $extra['pdf_path'] = $data['pdf_path'] ?? ($extra['pdf_path'] ?? null);
                    $this->setStatus($cotizacionId, $status, $extra);
                    return $this->getStatus($cotizacionId);
                }
            }
        return $st;
    }

    // Reintentar el envío a SUNAT para un comprobante ya creado
    public function reintentarEnvio(int $cotizacionId): ?array {
        $st = $this->getStatus($cotizacionId);
        if (!$st || empty($st['remote_id']) || empty($st['tipo'])) { return null; }
        $empresaId = (int)($st['empresa_id'] ?? 0);
        $token = $this->auth->getToken($empresaId);
        $base = rtrim($this->config['base_url'] ?? '', '/');
        $routeSend = $this->config['routes']['send_' . $st['tipo']] ?? null;
        if (!$routeSend) { return null; }
        $urlSend = $base . str_replace('{id}', $st['remote_id'], $routeSend);
        $resp = $this->curlJson('POST', $urlSend, null, $token, ['cotizacion_id' => $cotizacionId, 'stage' => 'send', 'tipo' => $st['tipo'], 'remote_id' => $st['remote_id']]);
        $data = (isset($resp['data']) && is_array($resp['data'])) ? $resp['data'] : $resp;
        $status = strtolower($resp['status'] ?? $resp['sunat_status'] ?? $resp['estado_sunat'] ?? 'enviado');
        if (($data['xml_path'] ?? null) || ($data['cdr_path'] ?? null)) { $status = 'aceptado'; }
        $extra = $st;
        $extra['sunat_message'] = $data['sunat_message'] ?? ($resp['message'] ?? null);
        $extra['xml_path'] = $data['xml_path'] ?? ($extra['xml_path'] ?? null);
        $extra['cdr_path'] = $data['cdr_path'] ?? ($extra['cdr_path'] ?? null);
        $extra['pdf_path'] = $data['pdf_path'] ?? ($extra['pdf_path'] ?? null);
        $this->setStatus($cotizacionId, $status, $extra);
        return $this->getStatus($cotizacionId);
    }

    public function setStatus(int $cotizacionId, string $status, array $extra = []): void {
        $allowed = ['pendiente','enviado','aceptado','rechazado','observado'];
        if (!in_array($status, $allowed)) { $status = 'pendiente'; }
        $all = $this->readStatusAll();
        $all[(string)$cotizacionId] = ['status' => $status, 'updated_at' => date('c')] + $extra;
        $this->writeStatusAll($all);
    }

    private function getCotizacion(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni, cl.codigo_cliente, cl.tipo_documento AS tipo_documento_cliente, emp.ruc AS ruc_empresa, emp.razon_social AS razon_social_empresa, emp.nombre_comercial AS nombre_comercial_empresa FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id LEFT JOIN empresas emp ON c.id_empresa = emp.id WHERE c.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getDetalles(int $id): array {
        $stmt = $this->pdo->prepare("SELECT cd.*, e.nombre AS nombre_examen FROM cotizaciones_detalle cd LEFT JOIN examenes e ON cd.id_examen = e.id WHERE cd.id_cotizacion = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildPayload(array $cot, array $det, array $opciones): array {
        // Emisor (empresa + sucursal) requerido por el API
        $companyId = $this->config['defaults']['company_id'] ?? null;
        $branchId = $this->config['defaults']['branch_id'] ?? null;
        $metodoEnvio = $this->config['defaults']['metodo_envio'] ?? 'individual';
        $moneda = (string)($opciones['moneda'] ?? ($this->config['defaults']['moneda'] ?? 'PEN'));
        $formaPagoTipo = (string)($opciones['forma_pago_tipo'] ?? ($this->config['defaults']['forma_pago_tipo'] ?? 'Contado'));
        $porcIgv = (float)($this->config['defaults']['porcentaje_igv'] ?? 18);
        $tipAfeIgv = (string)($this->config['defaults']['tip_afe_igv'] ?? '10');
        $unidad = (string)($this->config['defaults']['unidad'] ?? 'NIU');
        // Modo de impuestos: gravado (IGV 18%) o exonerado
        $modoImpuestos = $this->config['defaults']['modo_impuestos'] ?? 'gravado';
        if ($modoImpuestos === 'exonerado') {
            $porcIgv = 0.0;
            $tipAfeIgv = '20'; // Exonerado - Operación Onerosa (Catálogo 07)
        }

        // Tipo de comprobante
        // Compatibilidad: si existe `comprobante_tipo`, usarlo; si no, inferir por `id_empresa`.
        $tipo = null;
        if (isset($cot['comprobante_tipo']) && is_string($cot['comprobante_tipo'])) {
            $t = strtolower(trim($cot['comprobante_tipo']));
            if (in_array($t, ['boleta', 'factura'], true)) { $tipo = $t; }
        }
        if (!$tipo) {
            $tipo = !empty($cot['id_empresa']) ? 'factura' : 'boleta';
        }

        // Cliente (receptor)
        $esEmpresa = !empty($cot['id_empresa']);
        if ($tipo === 'factura') {
            $tipoDocCliente = '6'; // RUC
            if ($esEmpresa) {
                $numeroDoc = (string)($cot['ruc_empresa'] ?? '');
                $razonSocial = (string)($cot['razon_social_empresa'] ?? ($cot['nombre_comercial_empresa'] ?? ''));
                if ($razonSocial === '') {
                    $razonSocial = trim(($cot['nombre_cliente'] ?? '') . ' ' . ($cot['apellido_cliente'] ?? ''));
                }
            } else {
                // Factura sin empresa: usar datos guardados en cotización (si existen columnas receptor_*)
                $numeroDoc = (string)($cot['receptor_numero_documento'] ?? '');
                $razonSocial = (string)($cot['receptor_razon_social'] ?? '');
                if ($razonSocial === '') {
                    $razonSocial = trim(($cot['nombre_cliente'] ?? '') . ' ' . ($cot['apellido_cliente'] ?? ''));
                }
            }
        } else {
            // Boleta: por defecto DNI
            $tipoDocCliente = '1'; // DNI
            $numeroDoc = (string)($cot['dni'] ?? '');
            $razonSocial = trim(($cot['nombre_cliente'] ?? '') . ' ' . ($cot['apellido_cliente'] ?? ''));
        }

        // Detalles
        $detalles = [];
        foreach ($det as $d) {
            $precio = $d['precio_unitario'] ?? $d['precio'] ?? $d['precio_publico'] ?? 0;
            $qty = (int)($d['cantidad'] ?? 1);
            $precioBruto = round((float)$precio, 2);
            $valorUnitario = round($precioBruto / (1 + ($porcIgv/100)), 2); // sin IGV
            $codigo = isset($d['id_examen']) ? ('EX-' . $d['id_examen']) : (isset($d['codigo']) ? (string)$d['codigo'] : 'ITEM-1');
            $detalles[] = [
                'codigo' => $codigo,
                'descripcion' => $d['nombre_examen'] ?? '',
                'unidad' => $unidad,
                'cantidad' => $qty,
                'mto_valor_unitario' => $valorUnitario,
                'porcentaje_igv' => $porcIgv,
                'tip_afe_igv' => $tipAfeIgv,
            ];
        }

        // Serie según tipo de comprobante
        $serie = ($tipo === 'factura') ? 'F001' : 'B001';

        $payload = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'metodo_envio' => $opciones['metodo_envio'] ?? $metodoEnvio,
            'moneda' => $moneda,
            'forma_pago_tipo' => $formaPagoTipo,
            'serie' => $opciones['serie'] ?? $serie,
            'numero' => null,
            'fecha_emision' => date('Y-m-d'),
            'client' => [
                'tipo_documento' => $tipoDocCliente,
                'numero_documento' => $numeroDoc,
                'razon_social' => $razonSocial,
            ],
            'detalles' => $detalles,
            'origen' => ['cotizacion_id' => (int)$cot['id']],
        ];
        return $payload;
    }

    private function createAndSendRemote(int $cotizacionId, array $payload, string $token): array {
        $tipo = $this->inferTipo($cotizacionId);
        $base = rtrim($this->config['base_url'] ?? '', '/');
        $routeCreate = $this->config['routes']['create_' . $tipo] ?? null;
        $routeSend = $this->config['routes']['send_' . $tipo] ?? null;
        if (!$routeCreate || !$routeSend) { throw new Exception('Rutas de API no configuradas'); }
        // Crear
        $urlCreate = $base . $routeCreate;
        $respCreate = $this->curlJson('POST', $urlCreate, $payload, $token, ['cotizacion_id' => $cotizacionId, 'stage' => 'create', 'tipo' => $tipo]);
        $remoteId = $respCreate['id'] ?? $respCreate['data']['id'] ?? null;
        if (!$remoteId) { throw new Exception('No se obtuvo ID remoto'); }
        // Enviar a SUNAT
        $urlSend = $base . str_replace('{id}', $remoteId, $routeSend);
        $respSend = $this->curlJson('POST', $urlSend, null, $token, ['cotizacion_id' => $cotizacionId, 'stage' => 'send', 'tipo' => $tipo, 'remote_id' => $remoteId]);
        // Considerar distintas claves posibles: status, sunat_status, estado_sunat
        $status = $respSend['status'] ?? $respSend['sunat_status'] ?? $respSend['estado_sunat'] ?? 'enviado';
        if (is_string($status)) {
            $status = strtolower($status);
            if (in_array($status, ['aceptada'])) { $status = 'aceptado'; }
            if (in_array($status, ['observada'])) { $status = 'observado'; }
            if (in_array($status, ['rechazada'])) { $status = 'rechazado'; }
        }
        // Si la respuesta contiene rutas de archivos, considerar aceptado
        $data = (isset($respSend['data']) && is_array($respSend['data'])) ? $respSend['data'] : $respSend;
        if (($data['xml_path'] ?? null) || ($data['cdr_path'] ?? null)) { $status = 'aceptado'; }
        $res = ['status' => $status, 'remote_id' => $remoteId, 'tipo' => $tipo];
        $res['sunat_message'] = $data['sunat_message'] ?? ($respSend['message'] ?? null);
        $res['sunat_code'] = $data['sunat_code'] ?? null;
        $res['xml_path'] = $data['xml_path'] ?? null;
        $res['cdr_path'] = $data['cdr_path'] ?? null;
        $res['pdf_path'] = $data['pdf_path'] ?? null;
        return $res;
    }

    private function inferTipo(int $cotizacionId): string {
        if ($this->hasCotizacionesColumn('comprobante_tipo')) {
            $stmt = $this->pdo->prepare('SELECT comprobante_tipo, id_empresa FROM cotizaciones WHERE id = ?');
            $stmt->execute([$cotizacionId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $t = strtolower(trim((string)($row['comprobante_tipo'] ?? '')));
                if (in_array($t, ['boleta', 'factura'], true)) { return $t; }
                $idEmpresa = (int)($row['id_empresa'] ?? 0);
                return $idEmpresa > 0 ? 'factura' : 'boleta';
            }
            return 'boleta';
        }
        $stmt = $this->pdo->prepare('SELECT id_empresa FROM cotizaciones WHERE id = ?');
        $stmt->execute([$cotizacionId]);
        $idEmpresa = (int)($stmt->fetchColumn());
        return $idEmpresa > 0 ? 'factura' : 'boleta';
    }

    private function hasCotizacionesColumn(string $column): bool {
        if (array_key_exists($column, $this->columnsCache)) {
            return (bool)$this->columnsCache[$column];
        }
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM cotizaciones LIKE ?");
            $stmt->execute([$column]);
            $ok = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            $this->columnsCache[$column] = $ok;
            return $ok;
        } catch (Throwable $e) {
            $this->columnsCache[$column] = false;
            return false;
        }
    }

    private function persistPayload(int $id, array $payload): void {
        $path = $this->storageDir . '/payloads/cotizacion_' . $id . '.json';
        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function log(string $type, array $data): void {
        $line = json_encode(['type' => $type, 'time' => date('c')] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($this->storageDir . '/logs/facturacion.log', $line, FILE_APPEND);
    }

    private function readStatusAll(): array {
        $c = @file_get_contents($this->statusPath);
        return $c ? (json_decode($c, true) ?: []) : [];
    }

    private function writeStatusAll(array $data): void {
        file_put_contents($this->statusPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function curlJson(string $method, string $url, $body, string $token, array $ctx = []): array {
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        if ($token) { $headers[] = 'Authorization: Bearer ' . $token; }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $body;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($resp, true);
        // Log de llamada
        $this->log('api_call', [
            'stage' => $ctx['stage'] ?? null,
            'cotizacion_id' => $ctx['cotizacion_id'] ?? null,
            'tipo' => $ctx['tipo'] ?? null,
            'remote_id' => $ctx['remote_id'] ?? null,
            'method' => strtoupper($method),
            'url' => $url,
            'code' => $code,
            'error' => $err,
            'response' => is_string($resp) ? substr($resp, 0, 2000) : null
        ]);
        if ($err || $code >= 400) {
            // Intentar extraer detalles
            $details = '';
            if (is_array($json)) {
                if (isset($json['message'])) { $details = $json['message']; }
                if (isset($json['errors']) && is_array($json['errors'])) {
                    $first = reset($json['errors']);
                    if (is_array($first)) { $details .= ' | ' . implode('; ', $first); }
                    elseif (is_string($first)) { $details .= ' | ' . $first; }
                }
            }
            throw new Exception('Error HTTP ' . $code . ' en ' . $url . ($details ? (' - ' . $details) : ''));
        }
        return $json ?: [];
    }
}
