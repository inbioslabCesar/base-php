<?php
require_once __DIR__ . '/ui_theme.php';

if (!function_exists('app_operacion_normalizar_modo')) {
    function app_operacion_normalizar_modo($valor): string
    {
        $valor = strtoupper(trim((string)$valor));
        if (in_array($valor, ['PARTICULAR', 'SIS', 'MIXTO'], true)) {
            return $valor;
        }

        return 'PARTICULAR';
    }
}

if (!function_exists('app_operacion_normalizar_bool')) {
    function app_operacion_normalizar_bool($valor, bool $default = true): bool
    {
        if ($valor === null || $valor === '') {
            return $default;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        $valor = strtolower(trim((string)$valor));
        if (in_array($valor, ['1', 'true', 'yes', 'on', 'si', 'sí'], true)) {
            return true;
        }

        if (in_array($valor, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }
}

if (!function_exists('app_database_has_table')) {
    function app_database_has_table(PDO $pdo, string $tableName): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $stmt->execute([$tableName]);

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('app_database_has_column')) {
    function app_database_has_column(PDO $pdo, string $tableName, string $columnName): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$tableName, $columnName]);

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('app_operacion_context')) {
    function app_operacion_context(?PDO $pdo = null): array
    {
        static $cache = [];
        $cacheKey = $pdo instanceof PDO ? 'pdo_' . spl_object_id($pdo) : 'no_pdo';

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $modoOperativo = app_operacion_normalizar_modo(getenv('MODO_OPERATIVO') ?: getenv('APP_MODO_OPERATIVO') ?: 'PARTICULAR');
        $portalPublicoEnable = app_operacion_normalizar_bool(getenv('PORTAL_PUBLICO_ENABLE') ?: getenv('APP_PORTAL_PUBLICO_ENABLE'), true);
        $configEmpresa = null;

        if ($pdo instanceof PDO) {
            try {
                $configEmpresa = ui_theme_fetch_company_config($pdo);
                if (is_array($configEmpresa)) {
                    if (array_key_exists('modo_operativo', $configEmpresa)) {
                        $modoOperativo = app_operacion_normalizar_modo($configEmpresa['modo_operativo']);
                    }
                    if (array_key_exists('portal_publico_enable', $configEmpresa)) {
                        $portalPublicoEnable = app_operacion_normalizar_bool($configEmpresa['portal_publico_enable'], true);
                    }
                }
            } catch (Throwable $e) {
            }
        }

        $context = [
            'modo_operativo' => $modoOperativo,
            'portal_publico_enable' => $portalPublicoEnable,
            'es_particular' => ($modoOperativo === 'PARTICULAR'),
            'es_sis' => ($modoOperativo === 'SIS'),
            'es_mixto' => ($modoOperativo === 'MIXTO'),
            'config_empresa' => $configEmpresa,
        ];

        $cache[$cacheKey] = $context;

        return $context;
    }
}

if (!function_exists('app_operacion_context_is_sis')) {
    function app_operacion_context_is_sis(?array $context = null): bool
    {
        return $context !== null && !empty($context['es_sis']);
    }
}
