<?php

if (!function_exists('usuarios_privilegios_catalogo')) {
    function usuarios_privilegios_catalogo(bool $esSis = false): array
    {
        $catalogo = [
            'menu_admin' => ['label' => 'Ver Panel Admin', 'group' => 'Menú lateral'],
            'menu_usuarios' => ['label' => 'Ver Usuarios', 'group' => 'Menú lateral'],
            'menu_pacientes' => ['label' => 'Ver Pacientes', 'group' => 'Menú lateral'],
            'menu_empresas' => ['label' => 'Ver Empresas', 'group' => 'Menú lateral'],
            'menu_convenios' => ['label' => 'Ver Convenios', 'group' => 'Menú lateral'],
            'menu_servicios' => ['label' => 'Ver Servicios', 'group' => 'Menú lateral'],
            'menu_examenes' => ['label' => 'Ver Exámenes', 'group' => 'Menú lateral'],
            'menu_estadisticas' => ['label' => 'Ver Estadística', 'group' => 'Menú lateral'],
            'menu_inventario' => ['label' => 'Ver Inventario', 'group' => 'Menú lateral'],
            'menu_cotizaciones' => ['label' => 'Ver Cotizaciones', 'group' => 'Menú lateral'],
            'menu_contabilidad' => ['label' => 'Ver Contabilidad', 'group' => 'Menú lateral'],
            'menu_resultados' => ['label' => 'Ver Resultados', 'group' => 'Menú lateral'],
            'menu_turnos' => ['label' => 'Ver Turnos de Laboratorio', 'group' => 'Menú lateral'],

            'examenes_crear' => ['label' => 'Crear exámenes', 'group' => 'Exámenes'],
            'examenes_editar' => ['label' => 'Editar exámenes', 'group' => 'Exámenes'],
            'examenes_eliminar' => ['label' => 'Eliminar exámenes', 'group' => 'Exámenes'],

            'cotizaciones_ver' => ['label' => 'Ver cotizaciones', 'group' => 'Cotizaciones'],
            'cotizaciones_crear' => ['label' => 'Crear cotizaciones', 'group' => 'Cotizaciones'],
            'cotizaciones_editar' => ['label' => 'Editar / agregar / quitar cotizaciones', 'group' => 'Cotizaciones'],
            'cotizaciones_eliminar' => ['label' => 'Eliminar cotizaciones', 'group' => 'Cotizaciones'],
            'cotizaciones_pagar' => ['label' => 'Registrar pagos', 'group' => 'Cotizaciones'],

            'resultados_ver' => ['label' => 'Ver resultados', 'group' => 'Resultados'],
            'resultados_comparar' => ['label' => 'Comparar resultados', 'group' => 'Resultados'],
            'resultados_editar' => ['label' => 'Editar y guardar resultados', 'group' => 'Resultados'],
            'resultados_pdf_firma_profesional' => ['label' => 'PDF: usar firma y sello profesional', 'group' => 'Resultados'],
            'resultados_pdf_header_profesional' => ['label' => 'PDF: mostrar profesional en cabecera', 'group' => 'Resultados'],
            'resultados_pdf_header_turno' => ['label' => 'PDF: mostrar turno en cabecera', 'group' => 'Resultados'],
        ];

        if ($esSis) {
            foreach (['menu_empresas', 'menu_convenios', 'menu_contabilidad', 'cotizaciones_pagar', 'resultados_pdf_firma_profesional', 'resultados_pdf_header_profesional', 'resultados_pdf_header_turno'] as $claveExcluida) {
                unset($catalogo[$claveExcluida]);
            }
        }

        return $catalogo;
    }
}

if (!function_exists('usuarios_privilegios_todos_los_keys')) {
    function usuarios_privilegios_todos_los_keys(bool $esSis = false): array
    {
        return array_keys(usuarios_privilegios_catalogo($esSis));
    }
}

if (!function_exists('usuarios_privilegios_defecto_por_rol')) {
    function usuarios_privilegios_defecto_por_rol(string $rol, bool $esSis = false): array
    {
        $rol = strtolower(trim($rol));
        $todos = array_fill_keys(usuarios_privilegios_todos_los_keys($esSis), false);

        if ($rol === 'admin') {
            foreach ($todos as $key => $_) {
                $todos[$key] = true;
            }
            return $todos;
        }

        if ($rol === 'recepcionista') {
            $permisos = ['menu_cotizaciones', 'menu_pacientes', 'menu_estadisticas', 'menu_inventario', 'menu_contabilidad', 'examenes_crear', 'examenes_editar', 'cotizaciones_ver', 'cotizaciones_crear', 'cotizaciones_editar', 'cotizaciones_pagar', 'resultados_comparar'];
            if ($esSis) {
                $permisos = array_values(array_diff($permisos, ['menu_contabilidad', 'cotizaciones_pagar']));
            }
            foreach ($permisos as $key) {
                $todos[$key] = true;
            }
            return $todos;
        }

        if ($rol === 'laboratorista') {
            foreach (['menu_examenes', 'menu_cotizaciones', 'menu_resultados', 'menu_turnos', 'examenes_editar', 'cotizaciones_ver', 'resultados_ver', 'resultados_comparar', 'resultados_editar', 'resultados_pdf_firma_profesional', 'resultados_pdf_header_profesional', 'resultados_pdf_header_turno'] as $key) {
                $todos[$key] = true;
            }
            return $todos;
        }

        return $todos;
    }
}

if (!function_exists('usuarios_privilegios_normalizar_entrada')) {
    function usuarios_privilegios_normalizar_entrada(string $rol, $raw, bool $esSis = false): array
    {
        $defaults = usuarios_privilegios_defecto_por_rol($rol, $esSis);
        $keys = usuarios_privilegios_todos_los_keys($esSis);
        $result = $defaults;

        if ($rol === 'admin') {
            foreach ($result as $key => $_) {
                $result[$key] = true;
            }
            return $result;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $raw = $decoded;
            }
        }

        if (!is_array($raw)) {
            return $result;
        }

        if ($raw === []) {
            return array_fill_keys($keys, false);
        }

        $esListaSimple = array_keys($raw) === range(0, count($raw) - 1);
        if ($esListaSimple) {
            $selected = array_fill_keys($keys, false);
            if ($raw === []) {
                return $defaults;
            }
            foreach ($raw as $key) {
                $key = (string)$key;
                if (in_array($key, $keys, true)) {
                    $selected[$key] = true;
                }
            }
            return $selected;
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $value = $raw[$key];
            $result[$key] = in_array((string)$value, ['1', 'true', 'yes', 'on'], true) || $value === 1 || $value === true;
        }

        return $result;
    }
}

if (!function_exists('usuarios_privilegios_serializar')) {
    function usuarios_privilegios_serializar(string $rol, $raw, bool $esSis = false): string
    {
        $privilegios = usuarios_privilegios_normalizar_entrada($rol, $raw, $esSis);

        return json_encode($privilegios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('usuarios_privilegios_desde_json')) {
    function usuarios_privilegios_desde_json(?string $json, string $rol, bool $esSis = false): array
    {
        $json = trim((string)$json);
        if ($json === '') {
            return usuarios_privilegios_defecto_por_rol($rol, $esSis);
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return usuarios_privilegios_defecto_por_rol($rol, $esSis);
        }

        return usuarios_privilegios_normalizar_entrada($rol, $decoded, $esSis);
    }
}

if (!function_exists('usuarios_tiene_privilegio')) {
    function usuarios_tiene_privilegio(array $privilegios, string $clave): bool
    {
        return !empty($privilegios[$clave]);
    }
}

if (!function_exists('usuarios_privilegios_usuario_actual')) {
    function usuarios_privilegios_usuario_actual(PDO $pdo): array
    {
        static $cache = [];
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        $rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));
        $esSis = false;
        try {
            $esSis = !empty(app_operacion_context($pdo)['es_sis']);
        } catch (Throwable $e) {
        }
        $cacheKey = $usuarioId . '|' . $rol . '|' . (int)$esSis;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $privilegios = usuarios_privilegios_defecto_por_rol($rol, $esSis);

        if ($usuarioId > 0) {
            try {
                $stmt = $pdo->prepare('SELECT privilegios_json, rol FROM usuarios WHERE id = ? LIMIT 1');
                $stmt->execute([$usuarioId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $rolDb = strtolower(trim((string)($row['rol'] ?? $rol)));
                $privilegios = usuarios_privilegios_desde_json((string)($row['privilegios_json'] ?? ''), $rolDb, $esSis);
                $_SESSION['privilegios'] = $privilegios;
            } catch (Throwable $e) {
            }
        }

        $cache[$cacheKey] = $privilegios;
        return $privilegios;
    }
}

if (!function_exists('usuarios_tiene_privilegio_actual')) {
    function usuarios_tiene_privilegio_actual(PDO $pdo, string $clave): bool
    {
        $privilegios = usuarios_privilegios_usuario_actual($pdo);
        return usuarios_tiene_privilegio($privilegios, $clave);
    }
}
