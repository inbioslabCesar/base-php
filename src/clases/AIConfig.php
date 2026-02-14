<?php
class AIConfig {
    private static $cfg;

    private static function load() {
        if (!self::$cfg) {
            $path = __DIR__ . '/../config/ai_config.php';
            self::$cfg = file_exists($path) ? (require $path) : ['enabled' => false];
        }
        return self::$cfg;
    }

    public static function isEnabled(): bool {
        $c = self::load();
        return !empty($c['enabled']);
    }

    public static function scope(): string {
        $c = self::load();
        return $c['scope'] ?? 'all_clients';
    }

    public static function provider(): string {
        $c = self::load();
        return $c['provider'] ?? 'anthropic';
    }

    public static function model(): string {
        $c = self::load();
        return $c['model'] ?? 'claude-haiku-4.5';
    }

    public static function apiKey(): ?string {
        $c = self::load();
        $envKey = $c['auth']['api_key_env'] ?? null;
        return $envKey ? getenv($envKey) ?: null : null;
    }

    public static function limits(): array {
        $c = self::load();
        return $c['limits'] ?? [];
    }
}
