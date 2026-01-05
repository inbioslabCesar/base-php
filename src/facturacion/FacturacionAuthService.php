<?php
class FacturacionAuthService {
    private $storagePath;
    private $config;
    private $logPath;

    public function __construct(string $storageDir = null) {
        $dir = $storageDir ?: __DIR__ . '/../../tmp';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $this->storagePath = $dir . '/api_tokens.json';
        if (!file_exists($this->storagePath)) { file_put_contents($this->storagePath, json_encode([])); }
        $this->config = require __DIR__ . '/../config/facturacion_config.php';
        $this->logPath = $dir . '/auth.log';
    }

    public function getToken(int $empresaId): string {
        $tokens = $this->readAll();
        $key = (string)$empresaId;
        if (isset($tokens[$key])) {
            $t = $tokens[$key];
            if (!empty($t['access_token']) && !empty($t['expires_at']) && strtotime($t['expires_at']) > time() + 60) {
                return $t['access_token'];
            }
        }
        $new = $this->login($empresaId);
        if (is_array($new) && !empty($new['access_token'])) {
            $tokens[$key] = $new;
            $this->writeAll($tokens);
            return $new['access_token'];
        }
        throw new Exception('Token no disponible');
    }

    private function login(int $empresaId): ?array {
        $base = rtrim($this->config['base_url'] ?? '', '/');
        $loginPath = $this->config['routes']['login'] ?? '/api/auth/login';
        $url = $base . $loginPath;
        $credentials = $this->config['auth'] ?? [];
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return null;
        }
        // Enviar ambos campos 'username' y 'email' por compatibilidad
        $payload = json_encode([
            'username' => $credentials['username'],
            'email' => $credentials['username'],
            'password' => $credentials['password']
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','Accept: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->log(['step' => 'login_call', 'url' => $url, 'code' => $code, 'error' => $err, 'response' => $resp]);
        if ($err || $code >= 400) { return null; }
        $data = json_decode($resp, true) ?: [];
        $token = $data['access_token'] ?? $data['token'] ?? $data['plainTextToken'] ?? null;
        if (!$token && isset($data['data']) && is_array($data['data'])) {
            $token = $data['data']['access_token'] ?? $data['data']['token'] ?? null;
        }
        $this->log(['step' => 'login_parse', 'has_token' => (bool)$token, 'keys' => array_keys($data)]);
        if (!$token) { return null; }
        $ttl = intval($this->config['token_ttl_seconds'] ?? 3600);
        $expiresAt = date('c', time() + max(60, $ttl));
        $result = [
            'access_token' => $token,
            'expires_at' => $expiresAt,
            'empresa_id' => $empresaId,
        ];
        $this->log(['step' => 'login_store', 'empresa_id' => $empresaId]);
        return $result;
    }

    private function readAll(): array {
        $c = @file_get_contents($this->storagePath);
        return $c ? (json_decode($c, true) ?: []) : [];
    }

    private function writeAll(array $data): void {
        file_put_contents($this->storagePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function log(array $row): void {
        $line = json_encode(['time' => date('c')] + $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($this->logPath, $line, FILE_APPEND);
    }
}
