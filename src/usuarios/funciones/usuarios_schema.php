<?php

if (!function_exists('usuarios_asegurar_columnas_profesional')) {
    function usuarios_asegurar_columnas_profesional(PDO $pdo): void
    {
        static $aplicado = false;
        if ($aplicado) {
            return;
        }
        $aplicado = true;

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM usuarios");
            $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $existentes = [];
            foreach ($cols as $col) {
                $nombre = isset($col['Field']) ? (string)$col['Field'] : '';
                if ($nombre !== '') {
                    $existentes[] = $nombre;
                }
            }

            if (!in_array('firma', $existentes, true)) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN firma VARCHAR(255) NULL AFTER profesion");
            }
            if (!in_array('colegiatura_numero', $existentes, true)) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN colegiatura_numero VARCHAR(60) NULL AFTER profesion");
            }
            if (!in_array('ctmp_numero', $existentes, true)) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN ctmp_numero VARCHAR(60) NULL AFTER colegiatura_numero");
            }
            if (!in_array('privilegios_json', $existentes, true)) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN privilegios_json TEXT NULL AFTER rol");
            }
        } catch (Throwable $e) {
            // Mantener compatibilidad en entornos donde no se pueda alterar esquema.
        }
    }
}

if (!function_exists('usuarios_tiene_columna')) {
    function usuarios_tiene_columna(PDO $pdo, string $columna): bool
    {
        static $cache = [];
        if (array_key_exists($columna, $cache)) {
            return (bool)$cache[$columna];
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM usuarios LIKE ?");
            $stmt->execute([$columna]);
            $cache[$columna] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache[$columna] = false;
        }
        return (bool)$cache[$columna];
    }
}

if (!function_exists('usuarios_tiene_columna_firma')) {
    function usuarios_tiene_columna_firma(PDO $pdo): bool
    {
        return usuarios_tiene_columna($pdo, 'firma');
    }
}
