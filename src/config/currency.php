<?php

if (!function_exists('currency_default_config')) {
    function currency_default_config(): array
    {
        return [
            'code' => 'PEN',
            'symbol' => 'S/',
            'position' => 'prefix',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ];
    }
}

if (!function_exists('currency_normalize_config')) {
    function currency_normalize_config(array $raw): array
    {
        $defaults = currency_default_config();

        $code = strtoupper(trim((string)($raw['moneda_codigo'] ?? $defaults['code'])));
        if ($code === '') {
            $code = $defaults['code'];
        }

        $symbol = trim((string)($raw['moneda_simbolo'] ?? $defaults['symbol']));
        if ($symbol === '') {
            $symbol = $defaults['symbol'];
        }

        $position = strtolower(trim((string)($raw['moneda_posicion'] ?? $defaults['position'])));
        if (!in_array($position, ['prefix', 'suffix'], true)) {
            $position = $defaults['position'];
        }

        $decimals = (int)($raw['moneda_decimales'] ?? $defaults['decimals']);
        if ($decimals < 0 || $decimals > 4) {
            $decimals = $defaults['decimals'];
        }

        $decimalSeparator = (string)($raw['moneda_separador_decimal'] ?? $defaults['decimal_separator']);
        $decimalSeparator = $decimalSeparator !== '' ? mb_substr($decimalSeparator, 0, 1) : $defaults['decimal_separator'];

        $thousandsSeparator = (string)($raw['moneda_separador_miles'] ?? $defaults['thousands_separator']);
        $thousandsSeparator = $thousandsSeparator !== '' ? mb_substr($thousandsSeparator, 0, 1) : $defaults['thousands_separator'];

        if ($decimalSeparator === $thousandsSeparator) {
            $decimalSeparator = $defaults['decimal_separator'];
            $thousandsSeparator = $defaults['thousands_separator'];
        }

        return [
            'code' => $code,
            'symbol' => $symbol,
            'position' => $position,
            'decimals' => $decimals,
            'decimal_separator' => $decimalSeparator,
            'thousands_separator' => $thousandsSeparator,
        ];
    }
}

if (!function_exists('currency_get_config')) {
    function currency_get_config(?PDO $pdo = null): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $config = currency_default_config();

        try {
            if (!$pdo && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $pdo = $GLOBALS['pdo'];
            }

            if ($pdo instanceof PDO) {
                $stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                if (is_array($row)) {
                    $config = currency_normalize_config($row);
                }
            }
        } catch (Throwable $e) {
            $config = currency_default_config();
        }

        $cache = $config;
        return $cache;
    }
}

if (!function_exists('money_format_local')) {
    function money_format_local(float $amount, ?array $currency = null): string
    {
        $cfg = $currency ?? currency_get_config();

        $number = number_format(
            (float)$amount,
            (int)$cfg['decimals'],
            (string)$cfg['decimal_separator'],
            (string)$cfg['thousands_separator']
        );

        if (($cfg['position'] ?? 'prefix') === 'suffix') {
            return $number . ' ' . (string)$cfg['symbol'];
        }

        return (string)$cfg['symbol'] . ' ' . $number;
    }
}
