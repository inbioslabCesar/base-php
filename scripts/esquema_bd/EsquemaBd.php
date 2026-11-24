<?php

/**
 * Lleva una BD de cualquier versión al esquema de referencia (generado desde desarrollo).
 *
 * Solo CREA lo que falta: tablas, columnas, índices, claves foráneas y semillas de catálogo.
 * Nunca elimina ni modifica lo existente: las diferencias en columnas/índices ya existentes
 * se informan como "info" para decisión manual. Toda acción se decide mirando la BD real,
 * por eso sirve igual para una BD muy atrasada o casi al día, en MySQL o MariaDB.
 */
class EsquemaBd
{
    public const FORMATO = 1;

    // Tablas que no se replican a los clientes: respaldos puntuales de desarrollo (backup_*, *_backup, *_backup_*).
    public const TABLAS_EXCLUIDAS_REGEX = '/(^|_)backup(_|$)/i';

    // Catálogos cuyos registros iniciales se copian cuando faltan (por clave natural). Nunca se actualizan.
    public const SEMILLAS = [
        'sis_codigos_prestacionales' => [
            'clave' => ['codigo'],
            'excluir' => ['id', 'icono_ruta', 'created_at', 'updated_at', 'creado_en', 'actualizado_en'],
        ],
    ];

    public static function raiz(): string
    {
        return dirname(__DIR__, 2);
    }

    // --clave=valor y --bandera → ['clave' => 'valor', 'bandera' => true]
    public static function parsearArgumentos(array $argv): array
    {
        $r = [];
        foreach (array_slice($argv, 1) as $arg) {
            if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/i', $arg, $m)) {
                $r[$m[1]] = isset($m[2]) ? $m[2] : true;
            }
        }

        return $r;
    }

    // Aplica --host/--puerto/--bd/--usuario/--clave sobre la config de la empresa (para túnel, acceso remoto o pruebas).
    public static function aplicarSobrescrituras(array $cfg, array $args): array
    {
        $mapa = ['host' => 'host', 'puerto' => 'port', 'bd' => 'name', 'usuario' => 'user', 'clave' => 'pass'];
        foreach ($mapa as $opcion => $clave) {
            if (isset($args[$opcion]) && $args[$opcion] !== true) {
                $cfg[$clave] = (string)$args[$opcion];
            }
        }

        return $cfg;
    }

    public static function rutaReferenciaDefecto(): string
    {
        return self::raiz() . '/sql/esquema_referencia/esquema_referencia.json';
    }

    // ------------------------------------------------------------------
    // Conexión
    // ------------------------------------------------------------------

    public static function listarEmpresas(): array
    {
        $slugs = [];
        foreach (glob(self::raiz() . '/src/config/empresas/*.php') ?: [] as $archivo) {
            $slugs[] = basename($archivo, '.php');
        }
        sort($slugs);

        return $slugs;
    }

    // Lee DB_HOST/NAME/USER/PASS de src/config/empresas/{slug}.php sin ejecutarlo (los define() chocarían entre empresas).
    public static function cargarConfigEmpresa(string $slug): array
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            throw new InvalidArgumentException("Empresa inválida: {$slug}");
        }
        $ruta = self::raiz() . "/src/config/empresas/{$slug}.php";
        if (!is_file($ruta)) {
            throw new RuntimeException("No existe la configuración de la empresa '{$slug}'.");
        }

        $cfg = [];
        $patron = '/define\(\s*\'DB_(HOST|NAME|USER|PASS)\'\s*,\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*\)/';
        if (preg_match_all($patron, (string)file_get_contents($ruta), $coincidencias, PREG_SET_ORDER)) {
            foreach ($coincidencias as $m) {
                $cfg[strtolower($m[1])] = str_replace(['\\\\', "\\'"], ['\\', "'"], $m[2]);
            }
        }
        foreach (['host', 'name', 'user', 'pass'] as $clave) {
            if (!array_key_exists($clave, $cfg)) {
                throw new RuntimeException("Falta DB_" . strtoupper($clave) . " en {$slug}.php");
            }
        }

        return $cfg;
    }

    public static function conectar(array $cfg): PDO
    {
        $dsn = 'mysql:host=' . $cfg['host']
            . (!empty($cfg['port']) ? ';port=' . (int)$cfg['port'] : '')
            . ';dbname=' . $cfg['name'] . ';charset=utf8mb4';

        return new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function versionServidor(PDO $pdo): string
    {
        return (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    }

    public static function esMariaDb(PDO $pdo): bool
    {
        return stripos(self::versionServidor($pdo), 'mariadb') !== false;
    }

    // ------------------------------------------------------------------
    // Lectura de estructura (information_schema)
    // ------------------------------------------------------------------

    public static function leerEstructura(PDO $pdo): array
    {
        $maria = self::esMariaDb($pdo);
        $db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        $consulta = static function (string $sql) use ($pdo, $db): array {
            $st = $pdo->prepare($sql);
            $st->execute([$db]);

            return array_map(static function (array $fila): array {
                return array_change_key_case($fila, CASE_UPPER);
            }, $st->fetchAll());
        };

        $tablas = [];
        foreach ($consulta("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME") as $r) {
            $tablas[$r['TABLE_NAME']] = [
                'engine' => $r['ENGINE'] ?: 'InnoDB',
                'collation' => $r['TABLE_COLLATION'] ?: 'utf8mb4_unicode_ci',
                'columnas' => [],
                'indices' => [],
                'fks' => [],
            ];
        }

        foreach ($consulta('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION') as $r) {
            if (!isset($tablas[$r['TABLE_NAME']])) {
                continue;
            }
            $tablas[$r['TABLE_NAME']]['columnas'][$r['COLUMN_NAME']] = [
                'orden' => (int)$r['ORDINAL_POSITION'],
                'tipo' => (string)$r['COLUMN_TYPE'],
                'nulo' => $r['IS_NULLABLE'] === 'YES',
                'defecto' => self::normalizarDefecto($r, $maria),
                'extra' => strtolower((string)$r['EXTRA']),
                'collation' => $r['COLLATION_NAME'],
                'comentario' => (string)($r['COLUMN_COMMENT'] ?? ''),
                'generada' => trim((string)($r['GENERATION_EXPRESSION'] ?? '')) !== '',
            ];
        }

        foreach ($consulta('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, COLLATION, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX') as $r) {
            if (!isset($tablas[$r['TABLE_NAME']])) {
                continue;
            }
            $nombre = (string)$r['INDEX_NAME'];
            $ix = $tablas[$r['TABLE_NAME']]['indices'][$nombre] ?? [
                'unico' => (string)$r['NON_UNIQUE'] === '0',
                'tipo' => strtoupper((string)$r['INDEX_TYPE']),
                'columnas' => [],
                'expresion' => false,
            ];
            if ($r['COLUMN_NAME'] === null) {
                $ix['expresion'] = true;
            } else {
                $ix['columnas'][] = [
                    'c' => $r['COLUMN_NAME'],
                    'sub' => $r['SUB_PART'] !== null ? (int)$r['SUB_PART'] : null,
                ];
            }
            $tablas[$r['TABLE_NAME']]['indices'][$nombre] = $ix;
        }

        $sqlFk = 'SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE '
            . 'FROM information_schema.KEY_COLUMN_USAGE k '
            . 'JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME '
            . 'WHERE k.TABLE_SCHEMA = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION';
        foreach ($consulta($sqlFk) as $r) {
            if (!isset($tablas[$r['TABLE_NAME']])) {
                continue;
            }
            $fk = $tablas[$r['TABLE_NAME']]['fks'][$r['CONSTRAINT_NAME']] ?? [
                'columnas' => [],
                'ref_tabla' => $r['REFERENCED_TABLE_NAME'],
                'ref_columnas' => [],
                'on_delete' => self::normalizarRegla((string)$r['DELETE_RULE']),
                'on_update' => self::normalizarRegla((string)$r['UPDATE_RULE']),
            ];
            $fk['columnas'][] = $r['COLUMN_NAME'];
            $fk['ref_columnas'][] = $r['REFERENCED_COLUMN_NAME'];
            $tablas[$r['TABLE_NAME']]['fks'][$r['CONSTRAINT_NAME']] = $fk;
        }

        return ['motor' => $maria ? 'MariaDB' : 'MySQL', 'version' => self::versionServidor($pdo), 'tablas' => $tablas];
    }

    private static function normalizarRegla(string $regla): string
    {
        $regla = strtoupper(trim($regla));

        return ($regla === '' || $regla === 'RESTRICT') ? 'NO ACTION' : $regla;
    }

    // MySQL y MariaDB informan COLUMN_DEFAULT distinto: se unifica en {t: ninguno|nulo|literal|expr, v}.
    private static function normalizarDefecto(array $r, bool $maria): array
    {
        $d = $r['COLUMN_DEFAULT'];
        $nulable = $r['IS_NULLABLE'] === 'YES';
        if ($d === null) {
            return ['t' => $nulable ? 'nulo' : 'ninguno', 'v' => null];
        }
        $d = (string)$d;
        if ($maria) {
            if (strcasecmp($d, 'NULL') === 0) {
                return ['t' => 'nulo', 'v' => null];
            }
            if (strlen($d) >= 2 && $d[0] === "'" && substr($d, -1) === "'") {
                return ['t' => 'literal', 'v' => str_replace("''", "'", substr($d, 1, -1))];
            }

            return is_numeric($d) ? ['t' => 'literal', 'v' => $d] : ['t' => 'expr', 'v' => $d];
        }
        if (stripos((string)$r['EXTRA'], 'default_generated') !== false) {
            return ['t' => 'expr', 'v' => $d];
        }

        return ['t' => 'literal', 'v' => $d];
    }

    // ------------------------------------------------------------------
    // Generación del esquema de referencia
    // ------------------------------------------------------------------

    public static function construirReferencia(PDO $pdo): array
    {
        $estructura = self::leerEstructura($pdo);
        $semillas = [];
        foreach (self::SEMILLAS as $tabla => $cfg) {
            if (!isset($estructura['tablas'][$tabla])) {
                continue;
            }
            $columnas = array_values(array_diff(array_keys($estructura['tablas'][$tabla]['columnas']), $cfg['excluir']));
            $orden = implode(', ', array_map([self::class, 'q'], $cfg['clave']));
            $lista = implode(', ', array_map([self::class, 'q'], $columnas));
            $filas = $pdo->query('SELECT ' . $lista . ' FROM ' . self::q($tabla) . ' ORDER BY ' . $orden)->fetchAll();
            $semillas[$tabla] = array_map(static function (array $fila): array {
                return array_map(static function ($v) {
                    return $v === null ? null : (string)$v;
                }, $fila);
            }, $filas);
        }

        return [
            'formato' => self::FORMATO,
            'generado_en' => date('Y-m-d H:i'),
            'origen_motor' => $estructura['motor'] . ' ' . $estructura['version'],
            'tablas' => self::sinTablasExcluidas($estructura['tablas']),
            'semillas' => $semillas,
        ];
    }

    // Quita las tablas de respaldo y las claves foráneas que apuntaban a ellas.
    private static function sinTablasExcluidas(array $tablas): array
    {
        foreach (array_keys($tablas) as $nombre) {
            if (preg_match(self::TABLAS_EXCLUIDAS_REGEX, (string)$nombre)) {
                unset($tablas[$nombre]);
            }
        }
        foreach ($tablas as $nombre => $tabla) {
            foreach ($tabla['fks'] as $fk => $def) {
                if (!isset($tablas[$def['ref_tabla']])) {
                    unset($tablas[$nombre]['fks'][$fk]);
                }
            }
        }

        return $tablas;
    }

    public static function cargarReferencia(string $ruta): array
    {
        if (!is_file($ruta)) {
            throw new RuntimeException("No existe el esquema de referencia: {$ruta}. Genéralo con generar_esquema_referencia.php");
        }
        $ref = json_decode((string)file_get_contents($ruta), true);
        if (!is_array($ref) || (int)($ref['formato'] ?? 0) !== self::FORMATO || !isset($ref['tablas'])) {
            throw new RuntimeException('Esquema de referencia inválido o de otro formato.');
        }
        $ref['tablas'] = self::sinTablasExcluidas($ref['tablas']);

        return $ref;
    }

    // ------------------------------------------------------------------
    // Planificación
    // ------------------------------------------------------------------

    /**
     * Compara referencia vs destino y devuelve la lista de acciones.
     * Cada acción: fase, tabla, objeto, sql (o null), estado (pendiente|omitida|info), nota.
     */
    public static function planificar(array $ref, array $destino, PDO $pdo, array $opc = []): array
    {
        $exactos = !empty($opc['nombres_exactos']);
        $ctx = [
            'maria' => $destino['motor'] === 'MariaDB',
            'collations' => self::collationsSoportadas($pdo),
        ];
        $d = $destino['tablas'];
        $acc = [];
        $nuevo = static function (string $fase, string $tabla, string $objeto, ?string $sql, string $estado, string $nota = '') use (&$acc): void {
            $acc[] = ['fase' => $fase, 'tabla' => $tabla, 'objeto' => $objeto, 'sql' => $sql, 'estado' => $estado, 'nota' => $nota];
        };

        // 1) Tablas
        foreach ($ref['tablas'] as $t => $rt) {
            if (isset($d[$t])) {
                continue;
            }
            $manuales = [];
            foreach ($rt['columnas'] as $c => $rc) {
                if (!empty($rc['generada'])) {
                    $manuales[] = $c;
                    unset($rt['columnas'][$c]);
                }
            }
            $nuevo('tablas', $t, $t, self::ddlCrearTabla($t, $rt, $ctx), 'pendiente', $manuales ? 'Columnas generadas omitidas (crear a mano): ' . implode(', ', $manuales) : '');
            $d[$t] = ['nueva' => true, 'engine' => $rt['engine'], 'collation' => $rt['collation'], 'columnas' => $rt['columnas'], 'indices' => $rt['indices'], 'fks' => []];
        }

        // 2) Columnas (en el orden de referencia; AFTER la anterior para conservar la posición)
        foreach ($ref['tablas'] as $t => $rt) {
            if (!empty($d[$t]['nueva'])) {
                continue;
            }
            $previa = null;
            $cols = $rt['columnas'];
            uasort($cols, static function (array $a, array $b): int {
                return $a['orden'] <=> $b['orden'];
            });
            foreach ($cols as $c => $rc) {
                if (!isset($d[$t]['columnas'][$c])) {
                    if (!empty($rc['generada']) || strpos($rc['extra'], 'auto_increment') !== false) {
                        $nuevo('columnas', $t, $c, null, 'omitida', 'Columna generada/auto_increment: crear a mano.');
                        continue;
                    }
                    $pos = $previa === null ? ' FIRST' : ' AFTER ' . self::q($previa);
                    $nuevo('columnas', $t, $c, 'ALTER TABLE ' . self::q($t) . ' ADD COLUMN ' . self::ddlColumna($c, $rc, $rt['collation'], $ctx, false) . $pos, 'pendiente');
                    $d[$t]['columnas'][$c] = $rc;
                } else {
                    $actual = $d[$t]['columnas'][$c];
                    if (self::esAmpliacionEnum($rc, $actual)) {
                        // Agregar valores al final de un ENUM no toca los datos existentes; se conservan nulabilidad y default actuales.
                        $actual = array_merge($actual, ['tipo' => $rc['tipo']]);
                        $nuevo('columnas', $t, $c, 'ALTER TABLE ' . self::q($t) . ' MODIFY COLUMN ' . self::ddlColumna($c, $actual, $d[$t]['collation'], $ctx, false), 'pendiente', 'Ampliación segura de ENUM (valores nuevos al final).');
                        $d[$t]['columnas'][$c] = $actual;
                    }
                    $dif = self::diferenciasColumna($rc, $actual);
                    if ($dif) {
                        $nuevo('columnas', $t, $c, null, 'info', 'Difiere de la referencia (' . implode('; ', $dif) . '). Revisar manualmente: ALTER TABLE ' . self::q($t) . ' MODIFY COLUMN ' . self::ddlColumna($c, $rc, $rt['collation'], $ctx, false));
                    }
                }
                $previa = $c;
            }
        }

        // 3) Índices (las tablas nuevas ya los traen en su CREATE TABLE)
        foreach ($ref['tablas'] as $t => $rt) {
            if (!empty($d[$t]['nueva'])) {
                continue;
            }
            foreach ($rt['indices'] as $n => $ri) {
                if (!empty($ri['expresion'])) {
                    $nuevo('indices', $t, $n, null, 'omitida', 'Índice funcional: crear a mano.');
                    continue;
                }
                $di = $d[$t]['indices'][$n] ?? null;
                if ($di !== null) {
                    if (self::firmaIndice($ri) !== self::firmaIndice($di)) {
                        $nuevo('indices', $t, $n, null, 'info', 'Existe con otra definición (ref: ' . self::firmaIndice($ri) . ' | actual: ' . self::firmaIndice($di) . ').');
                    }
                    continue;
                }
                if (!$exactos && $n !== 'PRIMARY') {
                    $equiv = self::buscarIndiceEquivalente($ri, $d[$t]['indices']);
                    if ($equiv !== null) {
                        $nuevo('indices', $t, $n, null, 'info', "Cubierto por el índice equivalente '{$equiv}' (usa --nombres-exactos para crearlo igual).");
                        continue;
                    }
                }
                $faltan = array_diff(array_column($ri['columnas'], 'c'), array_keys($d[$t]['columnas']));
                if ($faltan) {
                    $nuevo('indices', $t, $n, null, 'omitida', 'Faltan columnas: ' . implode(', ', $faltan));
                    continue;
                }
                if ($ri['unico'] && self::hayDuplicados($pdo, $t, array_column($ri['columnas'], 'c'))) {
                    $nuevo('indices', $t, $n, null, 'omitida', 'Hay valores duplicados; depurar y reejecutar.');
                    continue;
                }
                $nuevo('indices', $t, $n, 'ALTER TABLE ' . self::q($t) . ' ADD ' . self::ddlIndice($n, $ri), 'pendiente');
                $d[$t]['indices'][$n] = $ri;
            }
        }

        // 4) Claves foráneas (al final: ya existen todas las tablas/columnas/índices)
        foreach ($ref['tablas'] as $t => $rt) {
            foreach ($rt['fks'] as $n => $rf) {
                if (isset($d[$t]['fks'][$n])) {
                    continue;
                }
                if (!$exactos) {
                    $equiv = self::buscarFkEquivalente($rf, $d[$t]['fks']);
                    if ($equiv !== null) {
                        $nuevo('fks', $t, $n, null, 'info', "Cubierta por la clave foránea equivalente '{$equiv}'.");
                        continue;
                    }
                }
                $enUso = false;
                foreach ($d as $otra) {
                    if (isset($otra['fks'][$n])) {
                        $enUso = true;
                    }
                }
                if ($enUso) {
                    $nuevo('fks', $t, $n, null, 'omitida', 'El nombre de la restricción ya existe en otra tabla.');
                    continue;
                }
                $faltan = array_diff($rf['columnas'], array_keys($d[$t]['columnas']));
                $faltanRef = !isset($d[$rf['ref_tabla']]) ? $rf['ref_columnas'] : array_diff($rf['ref_columnas'], array_keys($d[$rf['ref_tabla']]['columnas']));
                if ($faltan || $faltanRef) {
                    $nuevo('fks', $t, $n, null, 'omitida', 'Faltan columnas o la tabla referenciada.');
                    continue;
                }
                if (empty($d[$t]['nueva'])) {
                    $huerfanos = self::contarHuerfanos($pdo, $t, $rf, !empty($d[$rf['ref_tabla']]['nueva']));
                    if ($huerfanos > 0) {
                        $nuevo('fks', $t, $n, null, 'omitida', "{$huerfanos} registro(s) huérfano(s). Ver: " . self::sqlHuerfanos($t, $rf));
                        continue;
                    }
                }
                $nuevo('fks', $t, $n, 'ALTER TABLE ' . self::q($t) . ' ADD ' . self::ddlFk($n, $rf), 'pendiente');
                $d[$t]['fks'][$n] = $rf;
            }
        }

        // 5) Semillas de catálogo
        foreach (self::SEMILLAS as $t => $cfg) {
            $filas = $ref['semillas'][$t] ?? [];
            if (!$filas || !isset($d[$t])) {
                continue;
            }
            $existentes = [];
            if (empty($d[$t]['nueva'])) {
                $faltanClave = array_diff($cfg['clave'], array_keys($d[$t]['columnas']));
                if ($faltanClave) {
                    $nuevo('semillas', $t, $t, null, 'omitida', 'Falta la columna clave: ' . implode(', ', $faltanClave));
                    continue;
                }
                $lista = implode(', ', array_map([self::class, 'q'], $cfg['clave']));
                foreach ($pdo->query('SELECT ' . $lista . ' FROM ' . self::q($t))->fetchAll(PDO::FETCH_NUM) as $fila) {
                    $existentes[implode('|', $fila)] = true;
                }
            }
            // Un catálogo con registros propios no se toca: mezclar códigos podría solapar rangos de edad del cliente.
            if ($existentes) {
                $claveDe = static function (array $fila) use ($cfg): string {
                    return implode('|', array_map(static function (string $c) use ($fila) {
                        return (string)($fila[$c] ?? '');
                    }, $cfg['clave']));
                };
                $faltantes = 0;
                foreach ($filas as $fila) {
                    if (!isset($existentes[$claveDe($fila)])) {
                        $faltantes++;
                    }
                }
                if ($faltantes > 0) {
                    $nuevo('semillas', $t, $t, null, 'info', "La tabla ya tiene registros propios: no se siembra (faltan {$faltantes} de " . count($filas) . ' de la referencia).');
                }
                continue;
            }
            // Una columna obligatoria (NOT NULL sin default) que la semilla no trae haría fallar el INSERT en BD antiguas.
            $obligatoriasSinValor = [];
            $columnasSemilla = array_keys(array_intersect_key($filas[0], $d[$t]['columnas']));
            foreach ($d[$t]['columnas'] as $nombreCol => $dc) {
                if (!$dc['nulo'] && $dc['defecto']['t'] === 'ninguno' && strpos($dc['extra'], 'auto_increment') === false
                    && empty($dc['generada']) && !in_array($nombreCol, $columnasSemilla, true)) {
                    $obligatoriasSinValor[] = $nombreCol;
                }
            }
            if ($obligatoriasSinValor) {
                $nuevo('semillas', $t, $t, null, 'omitida', 'La tabla tiene columnas obligatorias sin valor en la semilla: ' . implode(', ', $obligatoriasSinValor) . '. Insertar los registros a mano.');
                continue;
            }
            foreach ($filas as $fila) {
                $clave = implode('|', array_map(static function (string $c) use ($fila) {
                    return (string)($fila[$c] ?? '');
                }, $cfg['clave']));
                if (isset($existentes[$clave])) {
                    continue;
                }
                $fila = array_intersect_key($fila, $d[$t]['columnas']);
                $sql = 'INSERT INTO ' . self::q($t) . ' (' . implode(', ', array_map([self::class, 'q'], array_keys($fila))) . ') VALUES ('
                    . implode(', ', array_map([self::class, 'literal'], array_values($fila))) . ')';
                $nuevo('semillas', $t, $clave, $sql, 'pendiente');
            }
        }

        return $acc;
    }

    public static function aplicar(PDO $pdo, array $acciones): array
    {
        foreach ($acciones as $i => $a) {
            if ($a['estado'] !== 'pendiente') {
                continue;
            }
            try {
                $pdo->exec($a['sql']);
                $acciones[$i]['estado'] = 'ok';
            } catch (Throwable $e) {
                $acciones[$i]['estado'] = 'error';
                $acciones[$i]['nota'] = $e->getMessage();
            }
        }

        return $acciones;
    }

    public static function resumen(array $acciones): array
    {
        $r = [];
        foreach ($acciones as $a) {
            $r[$a['fase']][$a['estado']] = ($r[$a['fase']][$a['estado']] ?? 0) + 1;
        }

        return $r;
    }

    // ------------------------------------------------------------------
    // Comparaciones
    // ------------------------------------------------------------------

    private static function normalizarTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        $tipo = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $tipo);

        return $tipo === 'json' ? 'longtext' : $tipo;
    }

    private static function claveDefecto(array $d): string
    {
        if ($d['t'] === 'expr') {
            return 'expr|' . strtolower(str_replace(['()', "'", ' '], '', (string)$d['v']));
        }

        return $d['t'] . '|' . $d['v'];
    }

    private static function valoresEnum(string $tipo): ?array
    {
        if (!preg_match('/^enum\((.*)\)$/is', trim($tipo), $m)) {
            return null;
        }
        preg_match_all("/'((?:[^']|'')*)'/", $m[1], $valores);

        return array_map(static function (string $v): string {
            return str_replace("''", "'", $v);
        }, $valores[1]);
    }

    // true si el ENUM de referencia es el actual + valores nuevos al final (mismo orden de los existentes).
    private static function esAmpliacionEnum(array $ref, array $actual): bool
    {
        $r = self::valoresEnum($ref['tipo']);
        $a = self::valoresEnum($actual['tipo']);

        return $r !== null && $a !== null && count($r) > count($a) && array_slice($r, 0, count($a)) === $a;
    }

    private static function diferenciasColumna(array $ref, array $actual): array
    {
        $dif = [];
        if (self::normalizarTipo($ref['tipo']) !== self::normalizarTipo($actual['tipo'])) {
            $dif[] = "tipo {$actual['tipo']} → {$ref['tipo']}";
        }
        if ($ref['nulo'] !== $actual['nulo']) {
            $dif[] = 'nulabilidad ' . ($actual['nulo'] ? 'NULL' : 'NOT NULL') . ' → ' . ($ref['nulo'] ? 'NULL' : 'NOT NULL');
        }
        if (self::claveDefecto($ref['defecto']) !== self::claveDefecto($actual['defecto'])) {
            $dif[] = 'default ' . self::claveDefecto($actual['defecto']) . ' → ' . self::claveDefecto($ref['defecto']);
        }

        return $dif;
    }

    private static function firmaIndice(array $ix): string
    {
        $cols = array_map(static function (array $c): string {
            return $c['c'] . ($c['sub'] !== null ? '(' . $c['sub'] . ')' : '');
        }, $ix['columnas']);
        $tipo = in_array($ix['tipo'], ['FULLTEXT', 'SPATIAL'], true) ? $ix['tipo'] : 'BTREE';

        return ($ix['unico'] ? 'UNIQUE' : 'INDEX') . ' ' . $tipo . ' (' . implode(',', $cols) . ')';
    }

    private static function buscarIndiceEquivalente(array $ref, array $indices): ?string
    {
        $firma = self::firmaIndice($ref);
        foreach ($indices as $nombre => $ix) {
            if (empty($ix['expresion']) && self::firmaIndice($ix) === $firma) {
                return (string)$nombre;
            }
        }

        return null;
    }

    private static function buscarFkEquivalente(array $rf, array $fks): ?string
    {
        foreach ($fks as $nombre => $fk) {
            if ($fk['columnas'] === $rf['columnas'] && $fk['ref_tabla'] === $rf['ref_tabla'] && $fk['ref_columnas'] === $rf['ref_columnas']) {
                return (string)$nombre;
            }
        }

        return null;
    }

    // Un UNIQUE solo falla por tuplas completas no nulas repetidas.
    private static function hayDuplicados(PDO $pdo, string $tabla, array $columnas): bool
    {
        $lista = implode(', ', array_map([self::class, 'q'], $columnas));
        $noNulos = implode(' AND ', array_map(static function (string $c): string {
            return self::q($c) . ' IS NOT NULL';
        }, $columnas));
        $sql = 'SELECT 1 FROM ' . self::q($tabla) . ' WHERE ' . $noNulos . ' GROUP BY ' . $lista . ' HAVING COUNT(*) > 1 LIMIT 1';

        return $pdo->query($sql)->fetchColumn() !== false;
    }

    private static function condicionHuerfanos(string $tabla, array $rf, bool $padreNuevo): string
    {
        $hijoNoNulo = implode(' AND ', array_map(static function (string $c): string {
            return 'c.' . self::q($c) . ' IS NOT NULL';
        }, $rf['columnas']));
        if ($padreNuevo) {
            return 'SELECT COUNT(*) FROM ' . self::q($tabla) . ' c WHERE ' . $hijoNoNulo;
        }
        $on = [];
        foreach ($rf['columnas'] as $i => $c) {
            $on[] = 'p.' . self::q($rf['ref_columnas'][$i]) . ' = c.' . self::q($c);
        }

        return 'SELECT COUNT(*) FROM ' . self::q($tabla) . ' c LEFT JOIN ' . self::q($rf['ref_tabla']) . ' p ON ' . implode(' AND ', $on)
            . ' WHERE ' . $hijoNoNulo . ' AND p.' . self::q($rf['ref_columnas'][0]) . ' IS NULL';
    }

    private static function contarHuerfanos(PDO $pdo, string $tabla, array $rf, bool $padreNuevo): int
    {
        return (int)$pdo->query(self::condicionHuerfanos($tabla, $rf, $padreNuevo))->fetchColumn();
    }

    private static function sqlHuerfanos(string $tabla, array $rf): string
    {
        return str_replace('SELECT COUNT(*)', 'SELECT c.*', self::condicionHuerfanos($tabla, $rf, false)) . ';';
    }

    // ------------------------------------------------------------------
    // Generación de DDL
    // ------------------------------------------------------------------

    private static function collationsSoportadas(PDO $pdo): array
    {
        $r = [];
        foreach ($pdo->query('SELECT COLLATION_NAME FROM information_schema.COLLATIONS')->fetchAll(PDO::FETCH_COLUMN) as $c) {
            $r[$c] = true;
        }

        return $r;
    }

    // Collation de la referencia → una que el servidor destino conozca (MySQL 8 y MariaDB difieren en utf8mb4_0900/uca1400).
    private static function mapearCollation(?string $collation, array $ctx): ?string
    {
        if ($collation === null || $collation === '') {
            return null;
        }
        if (isset($ctx['collations'][$collation])) {
            return $collation;
        }
        if (strpos($collation, 'utf8mb4_') === 0) {
            foreach (['utf8mb4_unicode_ci', 'utf8mb4_general_ci'] as $alt) {
                if (isset($ctx['collations'][$alt])) {
                    return $alt;
                }
            }
        }

        return null;
    }

    public static function q(string $identificador): string
    {
        return '`' . str_replace('`', '``', $identificador) . '`';
    }

    public static function literal($valor): string
    {
        return $valor === null ? 'NULL' : "'" . str_replace(['\\', "'"], ['\\\\', "''"], (string)$valor) . "'";
    }

    // true si toda la expresión está envuelta por un único par de paréntesis (ignora los que están dentro de comillas).
    private static function estaEntreParentesis(string $v): bool
    {
        if ($v === '' || $v[0] !== '(' || substr($v, -1) !== ')') {
            return false;
        }
        $nivel = 0;
        $enComillas = false;
        $largo = strlen($v);
        for ($i = 0; $i < $largo; $i++) {
            $ch = $v[$i];
            if ($ch === "'" && ($i === 0 || $v[$i - 1] !== '\\')) {
                $enComillas = !$enComillas;
            } elseif (!$enComillas && $ch === '(') {
                $nivel++;
            } elseif (!$enComillas && $ch === ')') {
                $nivel--;
                if ($nivel === 0 && $i < $largo - 1) {
                    return false;
                }
            }
        }

        return $nivel === 0;
    }

    private static function ddlColumna(string $nombre, array $c, string $collationTabla, array $ctx, bool $permitirAutoIncrement): string
    {
        $sql = self::q($nombre) . ' ' . $c['tipo'];
        if (!empty($c['collation']) && $c['collation'] !== $collationTabla) {
            $col = self::mapearCollation($c['collation'], $ctx);
            if ($col !== null) {
                $sql .= ' CHARACTER SET ' . strstr($col, '_', true) . ' COLLATE ' . $col;
            }
        }
        $sql .= $c['nulo'] ? ' NULL' : ' NOT NULL';

        $d = $c['defecto'];
        if ($d['t'] === 'nulo' && $c['nulo']) {
            $sql .= ' DEFAULT NULL';
        } elseif ($d['t'] === 'literal') {
            $sql .= ' DEFAULT ' . self::literal($d['v']);
        } elseif ($d['t'] === 'expr') {
            $v = trim((string)$d['v']);
            if (preg_match('/^(current_timestamp|now|localtime|localtimestamp)(\(\d*\))?$/i', $v, $m)) {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP' . ((isset($m[2]) && $m[2] !== '()') ? $m[2] : '');
            } else {
                $sql .= ' DEFAULT ' . (self::estaEntreParentesis($v) ? $v : '(' . $v . ')');
            }
        }

        if ($permitirAutoIncrement && strpos($c['extra'], 'auto_increment') !== false) {
            $sql .= ' AUTO_INCREMENT';
        }
        if (preg_match('/on update current_timestamp(\((\d+)\))?/i', $c['extra'], $m)) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP' . (isset($m[2]) && $m[2] !== '' ? '(' . $m[2] . ')' : '');
        }
        if ($c['comentario'] !== '') {
            $sql .= ' COMMENT ' . self::literal($c['comentario']);
        }

        return $sql;
    }

    private static function listaColumnasIndice(array $ix): string
    {
        return '(' . implode(', ', array_map(static function (array $c): string {
            return self::q($c['c']) . ($c['sub'] !== null ? '(' . $c['sub'] . ')' : '');
        }, $ix['columnas'])) . ')';
    }

    private static function ddlIndice(string $nombre, array $ix): string
    {
        if ($nombre === 'PRIMARY') {
            return 'PRIMARY KEY ' . self::listaColumnasIndice($ix);
        }
        if ($ix['tipo'] === 'FULLTEXT') {
            return 'FULLTEXT KEY ' . self::q($nombre) . ' ' . self::listaColumnasIndice($ix);
        }
        if ($ix['tipo'] === 'SPATIAL') {
            return 'SPATIAL KEY ' . self::q($nombre) . ' ' . self::listaColumnasIndice($ix);
        }

        return ($ix['unico'] ? 'UNIQUE KEY ' : 'KEY ') . self::q($nombre) . ' ' . self::listaColumnasIndice($ix);
    }

    private static function ddlFk(string $nombre, array $fk): string
    {
        $sql = 'CONSTRAINT ' . self::q($nombre) . ' FOREIGN KEY (' . implode(', ', array_map([self::class, 'q'], $fk['columnas'])) . ') REFERENCES '
            . self::q($fk['ref_tabla']) . ' (' . implode(', ', array_map([self::class, 'q'], $fk['ref_columnas'])) . ')';
        if ($fk['on_delete'] !== 'NO ACTION') {
            $sql .= ' ON DELETE ' . $fk['on_delete'];
        }
        if ($fk['on_update'] !== 'NO ACTION') {
            $sql .= ' ON UPDATE ' . $fk['on_update'];
        }

        return $sql;
    }

    // CREATE TABLE con columnas e índices; las claves foráneas se agregan después (fase 4) para no depender del orden de creación.
    private static function ddlCrearTabla(string $tabla, array $rt, array $ctx): string
    {
        $lineas = [];
        $cols = $rt['columnas'];
        uasort($cols, static function (array $a, array $b): int {
            return $a['orden'] <=> $b['orden'];
        });
        foreach ($cols as $nombre => $c) {
            $lineas[] = '  ' . self::ddlColumna($nombre, $c, $rt['collation'], $ctx, true);
        }
        $indices = $rt['indices'];
        uksort($indices, static function (string $a, string $b): int {
            return ($a === 'PRIMARY' ? 0 : 1) <=> ($b === 'PRIMARY' ? 0 : 1);
        });
        foreach ($indices as $nombre => $ix) {
            if (empty($ix['expresion'])) {
                $lineas[] = '  ' . self::ddlIndice((string)$nombre, $ix);
            }
        }
        $collation = self::mapearCollation($rt['collation'], $ctx) ?? 'utf8mb4_unicode_ci';

        return 'CREATE TABLE ' . self::q($tabla) . " (\n" . implode(",\n", $lineas) . "\n) ENGINE=" . $rt['engine']
            . ' DEFAULT CHARSET=' . strstr($collation, '_', true) . ' COLLATE=' . $collation;
    }
}
