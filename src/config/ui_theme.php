<?php

if (!function_exists('ui_theme_predefined')) {
    function ui_theme_predefined(): array
    {
        return [
            'corporativo_azul' => [
                'label' => 'Corporativo Azul',
                'primary' => '#1f4f82',
                'secondary' => '#e9f3fb',
                'footer' => '#173a60',
                'button' => '#2f74bd',
                'text' => '#1c2a3b',
            ],
            'turquesa_clinico' => [
                'label' => 'Turquesa Clinico',
                'primary' => '#1f6f8b',
                'secondary' => '#e7f8fb',
                'footer' => '#174f63',
                'button' => '#28a9b8',
                'text' => '#173243',
            ],
            'verde_salud' => [
                'label' => 'Verde Salud',
                'primary' => '#1f6b57',
                'secondary' => '#eaf8f3',
                'footer' => '#174d3f',
                'button' => '#2f9f7e',
                'text' => '#1e3b34',
            ],
            'gris_profesional' => [
                'label' => 'Gris Profesional',
                'primary' => '#374151',
                'secondary' => '#f2f4f7',
                'footer' => '#1f2937',
                'button' => '#4b5563',
                'text' => '#1f2937',
            ],
            'noche_medica' => [
                'label' => 'Noche Medica',
                'primary' => '#1f2a44',
                'secondary' => '#edf1fb',
                'footer' => '#131b2d',
                'button' => '#2f4f8f',
                'text' => '#1a2236',
            ],
        ];
    }
}

if (!function_exists('ui_theme_normalize_host')) {
    function ui_theme_normalize_host(string $host): string
    {
        $h = strtolower(trim($host));
        if ($h === '') {
            return '';
        }
        $h = preg_replace('#^https?://#i', '', $h) ?? $h;
        $h = preg_replace('#/.*$#', '', $h) ?? $h;
        $h = preg_replace('#:\\d+$#', '', $h) ?? $h;
        if (strpos($h, 'www.') === 0) {
            $h = substr($h, 4);
        }
        return trim($h);
    }
}

if (!function_exists('ui_theme_detect_request_host')) {
    function ui_theme_detect_request_host(): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        return ui_theme_normalize_host($host);
    }
}

if (!function_exists('ui_theme_fetch_company_config')) {
    function ui_theme_fetch_company_config(PDO $pdo, ?int $forcedId = null, ?string $host = null): array
    {
        $rows = [];
        try {
            $stmt = $pdo->query('SELECT * FROM config_empresa ORDER BY id ASC');
            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            $rows = [];
        }

        if (empty($rows)) {
            return [];
        }

        if ($forcedId !== null && $forcedId > 0) {
            foreach ($rows as $row) {
                if ((int)($row['id'] ?? 0) === $forcedId) {
                    return $row;
                }
            }
        }

        $targetHost = ui_theme_normalize_host((string)($host ?? ui_theme_detect_request_host()));
        if ($targetHost !== '') {
            foreach ($rows as $row) {
                $rowHost = ui_theme_normalize_host((string)($row['dominio'] ?? ''));
                if ($rowHost !== '' && $rowHost === $targetHost) {
                    return $row;
                }
            }
        }

        return $rows[0];
    }
}

if (!function_exists('ui_theme_is_hex_color')) {
    function ui_theme_is_hex_color(string $value): bool
    {
        return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', trim($value));
    }
}

if (!function_exists('ui_theme_normalize_hex')) {
    function ui_theme_normalize_hex(string $value, string $fallback): string
    {
        $v = trim($value);
        if (ui_theme_is_hex_color($v)) {
            return strtolower($v);
        }
        return strtolower($fallback);
    }
}

if (!function_exists('ui_theme_adjust_brightness')) {
    function ui_theme_adjust_brightness(string $hex, int $delta): string
    {
        $hex = ltrim(ui_theme_normalize_hex($hex, '#000000'), '#');
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $delta));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $delta));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $delta));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}

if (!function_exists('ui_theme_text_for_bg')) {
    function ui_theme_text_for_bg(string $hexColor): string
    {
        $hex = ltrim(ui_theme_normalize_hex($hexColor, '#000000'), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
        return $luminance > 0.58 ? '#1f2937' : '#f9fbff';
    }
}

if (!function_exists('ui_theme_get_active')) {
    function ui_theme_get_active(PDO $pdo, ?array $companyConfig = null): array
    {
        $presets = ui_theme_predefined();

        $cfg = is_array($companyConfig) ? $companyConfig : ui_theme_fetch_company_config($pdo);

        $activeKey = strtolower(trim((string)($cfg['tema_ui_activo'] ?? '')));
        $base = $presets[$activeKey] ?? [
            'label' => 'Personalizado',
            'primary' => '#0d6efd',
            'secondary' => '#f8f9fa',
            'footer' => '#343a40',
            'button' => '#198754',
            'text' => '#212529',
        ];

        $primary = ui_theme_normalize_hex((string)($cfg['color_principal'] ?? ''), $base['primary']);
        $secondary = ui_theme_normalize_hex((string)($cfg['color_secundario'] ?? ''), $base['secondary']);
        $footer = ui_theme_normalize_hex((string)($cfg['color_footer'] ?? ''), $base['footer']);
        $button = ui_theme_normalize_hex((string)($cfg['color_botones'] ?? ''), $base['button']);
        $text = ui_theme_normalize_hex((string)($cfg['color_texto'] ?? ''), $base['text']);

        $navbarText = ui_theme_text_for_bg($primary);
        $sidebarText = ui_theme_text_for_bg($primary);
        $footerText = ui_theme_text_for_bg($footer);
        $buttonText = ui_theme_text_for_bg($button);

        return [
            'company_id' => (int)($cfg['id'] ?? 0),
            'company_domain' => (string)($cfg['dominio'] ?? ''),
            'active_key' => $activeKey,
            'active_label' => $base['label'],
            'primary' => $primary,
            'secondary' => $secondary,
            'footer' => $footer,
            'button' => $button,
            'text' => $text,
            'navbar_bg' => $primary,
            'navbar_text' => $navbarText,
            'navbar_hover_bg' => ui_theme_adjust_brightness($primary, -18),
            'sidebar_bg' => $primary,
            'sidebar_text' => $sidebarText,
            'sidebar_hover_bg' => ui_theme_adjust_brightness($primary, -18),
            'footer_bg' => $footer,
            'footer_text' => $footerText,
            'button_bg' => $button,
            'button_text' => $buttonText,
            'card_bg' => '#ffffff',
            'border' => ui_theme_adjust_brightness($secondary, -28),
            'body_bg' => $secondary,
        ];
    }
}
