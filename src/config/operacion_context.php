<?php
require_once __DIR__ . '/ui_theme.php';
require_once __DIR__ . '/../resultados/servicios/EdadPacienteService.php';

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

// ---------------------------------------------------------------------------
// Códigos prestacionales SIS por curso de vida (301-306) y gestantes.
// El PHP es la fuente de verdad: el JS del formulario solo muestra una vista previa.
// Toda lectura/escritura tolera que la migración sql/2026_10_18_codigos_prestacionales_sis no esté aplicada.
// ---------------------------------------------------------------------------

if (!function_exists('sis_prestacional_columnas_cotizacion')) {
    function sis_prestacional_columnas_cotizacion(): array
    {
        return ['codigo_prestacional_id', 'codigo_prestacional', 'edad_paciente_meses', 'es_gestante', 'edad_gestacional_semanas', 'edad_gestacional_dias', 'fpp'];
    }
}

if (!function_exists('sis_prestacional_columnas_ok')) {
    function sis_prestacional_columnas_ok(PDO $pdo): bool
    {
        static $cache = [];
        $key = spl_object_id($pdo);
        if (!isset($cache[$key])) {
            $cache[$key] = true;
            foreach (sis_prestacional_columnas_cotizacion() as $col) {
                if (!app_database_has_column($pdo, 'cotizaciones', $col)) {
                    $cache[$key] = false;
                    break;
                }
            }
        }

        return $cache[$key];
    }
}

if (!function_exists('sis_prestacional_catalogo_defecto')) {
    // Respaldo cuando la tabla aún no existe: mismos valores que la semilla de la migración.
    function sis_prestacional_catalogo_defecto(): array
    {
        $base = [
            ['301', 'Niño', 'Etapa de vida niño: 0 a 11 años, 11 meses, 29 días', 'EDAD', 0, 143, 'bi-emoji-smile', '#0d6efd'],
            ['302', 'Adolescente', 'Etapa de vida adolescente: 12 a 17 años, 11 meses, 29 días', 'EDAD', 144, 215, 'bi-person-arms-up', '#6f42c1'],
            ['303', 'Joven', 'Etapa de vida joven: 18 a 29 años, 11 meses, 29 días', 'EDAD', 216, 359, 'bi-person', '#198754'],
            ['304', 'Adulto', 'Etapa de vida adulto: 30 a 59 años, 11 meses, 29 días', 'EDAD', 360, 719, 'bi-person-workspace', '#fd7e14'],
            ['305', 'Adulto mayor', 'Etapa de vida adulto mayor: 60 años a más', 'EDAD', 720, null, 'bi-person-wheelchair', '#6c757d'],
            ['306', 'Gestante', 'Atención prenatal / gestante', 'GESTANTE', null, null, 'bi-gender-female', '#d63384'],
        ];
        $filas = [];
        foreach ($base as $i => $b) {
            $filas[] = [
                'id' => 0,
                'codigo' => $b[0],
                'nombre' => $b[1],
                'descripcion_minsa' => $b[2],
                'tipo_regla' => $b[3],
                'edad_min_meses' => $b[4],
                'edad_max_meses' => $b[5],
                'icono_clase' => $b[6],
                'icono_ruta' => null,
                'color' => $b[7],
                'orden' => $i + 1,
                'activo' => 1,
            ];
        }

        return $filas;
    }
}

if (!function_exists('sis_prestacional_catalogo')) {
    function sis_prestacional_catalogo(PDO $pdo, bool $soloActivos = true): array
    {
        if (!app_database_has_table($pdo, 'sis_codigos_prestacionales')) {
            return sis_prestacional_catalogo_defecto();
        }

        try {
            $sql = 'SELECT * FROM sis_codigos_prestacionales' . ($soloActivos ? ' WHERE activo = 1' : '') . ' ORDER BY orden, codigo';
            $filas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return sis_prestacional_catalogo_defecto();
        }

        return $filas ?: sis_prestacional_catalogo_defecto();
    }
}

if (!function_exists('sis_prestacional_icono_url')) {
    // Solo imágenes (png/webp/jpg) se pueden mostrar en <img>; un PDF se enlaza pero no se incrusta.
    function sis_prestacional_icono_url(?string $ruta): ?string
    {
        $ruta = trim((string)$ruta);
        if ($ruta === '' || !preg_match('/\.(png|webp|jpe?g)$/i', $ruta)) {
            return null;
        }

        return (defined('BASE_URL') ? BASE_URL : '') . '../' . ltrim(str_replace('\\', '/', $ruta), '/');
    }
}

if (!function_exists('sis_prestacional_icono_html')) {
    function sis_prestacional_icono_html(array $fila, int $tam = 28): string
    {
        $url = sis_prestacional_icono_url($fila['icono_ruta'] ?? null);
        $color = htmlspecialchars((string)(($fila['color'] ?? '') ?: '#6c757d'), ENT_QUOTES, 'UTF-8');
        if ($url !== null) {
            return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" width="' . $tam . '" height="' . $tam . '" style="object-fit:contain;">';
        }
        $clase = htmlspecialchars((string)(($fila['icono_clase'] ?? '') ?: 'bi-shield-check'), ENT_QUOTES, 'UTF-8');

        return '<i class="bi ' . $clase . '" style="font-size:' . $tam . 'px;color:' . $color . ';"></i>';
    }
}

if (!function_exists('sis_prestacional_edad_meses')) {
    // Meses completos del paciente a la fecha de referencia; null si no hay fecha de nacimiento ni edad utilizable.
    function sis_prestacional_edad_meses(PDO $pdo, int $idCliente, ?string $fechaRef = null): ?int
    {
        $hasRefValor = app_database_has_column($pdo, 'clientes', 'edad_referida_valor');
        $hasRefFecha = app_database_has_column($pdo, 'clientes', 'edad_referida_fecha');
        $sql = 'SELECT fecha_nacimiento, edad, '
            . ($hasRefValor ? 'edad_referida_valor' : 'NULL AS edad_referida_valor') . ', '
            . ($hasRefFecha ? 'edad_referida_fecha' : 'NULL AS edad_referida_fecha')
            . ' FROM clientes WHERE id = ? LIMIT 1';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idCliente]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return null;
        }

        $edad = EdadPacienteService::resolverEdadParaEvento(
            $row['fecha_nacimiento'] ?? null,
            $fechaRef ?: date('Y-m-d H:i:s'),
            ($row['edad_referida_valor'] ?? null) !== null && (string)$row['edad_referida_valor'] !== ''
                ? (string)$row['edad_referida_valor']
                : ($row['edad'] ?? null),
            $row['edad_referida_fecha'] ?? null
        );
        if ($edad['edad_anios'] === null) {
            return null;
        }

        return ((int)$edad['edad_anios'] * 12) + (int)($edad['edad_meses'] ?? 0);
    }
}

if (!function_exists('sis_codigo_prestacional_resolver')) {
    // Devuelve la fila del catálogo que corresponde (306 si es gestante y está activo; si no, por edad) o null.
    function sis_codigo_prestacional_resolver(PDO $pdo, ?int $edadMeses, bool $esGestante): ?array
    {
        $catalogo = sis_prestacional_catalogo($pdo, true);

        if ($esGestante) {
            foreach ($catalogo as $fila) {
                if (($fila['tipo_regla'] ?? '') === 'GESTANTE') {
                    return $fila;
                }
            }
        }

        if ($edadMeses === null || $edadMeses < 0) {
            return null;
        }

        foreach ($catalogo as $fila) {
            if (($fila['tipo_regla'] ?? '') !== 'EDAD') {
                continue;
            }
            $min = $fila['edad_min_meses'] !== null ? (int)$fila['edad_min_meses'] : 0;
            $max = $fila['edad_max_meses'] !== null ? (int)$fila['edad_max_meses'] : PHP_INT_MAX;
            if ($edadMeses >= $min && $edadMeses <= $max) {
                return $fila;
            }
        }

        return null;
    }
}

if (!function_exists('sis_prestacional_preparar')) {
    /**
     * Valida los datos de gestante del POST y calcula el código prestacional.
     * Retorna ['ok' => bool, 'error' => string|null, 'datos' => columna => valor, 'fila' => catálogo|null].
     */
    function sis_prestacional_preparar(PDO $pdo, int $idCliente, ?string $fechaRef, array $post): array
    {
        $esGestante = !empty($post['es_gestante']);
        $semanas = null;
        $dias = null;
        $fpp = null;

        if ($esGestante) {
            $semRaw = trim((string)($post['edad_gestacional_semanas'] ?? ''));
            $diasRaw = trim((string)($post['edad_gestacional_dias'] ?? ''));
            $fppRaw = trim((string)($post['fpp'] ?? ''));
            $fppDate = DateTime::createFromFormat('Y-m-d', $fppRaw);

            $semanasOk = ctype_digit($semRaw) && (int)$semRaw >= 1 && (int)$semRaw <= 42;
            $diasOk = $diasRaw === '' || (ctype_digit($diasRaw) && (int)$diasRaw <= 6);
            $fppOk = $fppDate instanceof DateTime && $fppDate->format('Y-m-d') === $fppRaw;
            if (!$semanasOk || !$diasOk || !$fppOk) {
                return ['ok' => false, 'error' => 'gestacion_invalida', 'datos' => [], 'fila' => null];
            }

            $semanas = (int)$semRaw;
            $dias = $diasRaw === '' ? 0 : (int)$diasRaw;
            $fpp = $fppRaw;
        }

        $edadMeses = sis_prestacional_edad_meses($pdo, $idCliente, $fechaRef);
        $fila = sis_codigo_prestacional_resolver($pdo, $edadMeses, $esGestante);

        return [
            'ok' => true,
            'error' => null,
            'datos' => [
                'codigo_prestacional_id' => ($fila && (int)($fila['id'] ?? 0) > 0) ? (int)$fila['id'] : null,
                'codigo_prestacional' => $fila['codigo'] ?? null,
                'edad_paciente_meses' => $edadMeses,
                'es_gestante' => $esGestante ? 1 : 0,
                'edad_gestacional_semanas' => $semanas,
                'edad_gestacional_dias' => $dias,
                'fpp' => $fpp,
            ],
            'fila' => $fila,
        ];
    }
}

if (!function_exists('sis_prestacional_persistir')) {
    function sis_prestacional_persistir(PDO $pdo, int $idCotizacion, array $datos): bool
    {
        if ($idCotizacion <= 0 || !sis_prestacional_columnas_ok($pdo)) {
            return false;
        }

        $set = [];
        $params = [];
        foreach (sis_prestacional_columnas_cotizacion() as $col) {
            if (array_key_exists($col, $datos)) {
                $set[] = $col . ' = ?';
                $params[] = $datos[$col];
            }
        }
        if (empty($set)) {
            return false;
        }
        $params[] = $idCotizacion;

        try {
            $pdo->prepare('UPDATE cotizaciones SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
            return true;
        } catch (Throwable $e) {
            error_log('sis_prestacional_persistir: ' . $e->getMessage());
            return false;
        }
    }
}
