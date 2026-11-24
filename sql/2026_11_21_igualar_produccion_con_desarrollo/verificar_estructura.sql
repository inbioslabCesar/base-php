-- =============================================================================
-- Verificar que dos bases (dev y produccion) tienen la misma estructura.
-- Ejecutar EL MISMO script en cada base (con la BD seleccionada) y comparar la salida.
-- Compara por tabla: columnas (nombre, tipo, nulabilidad, default), indices
-- (nombre, unico, columnas) y claves foraneas (columnas, destino, ON DELETE/UPDATE).
-- Normaliza diferencias MySQL vs MariaDB (ancho de int(11), comillas/() en defaults,
-- RESTRICT = NO ACTION). Ignora el orden de las columnas, collations y engine.
-- Solo lectura. Requiere MySQL 5.7+/MariaDB 10+.
-- =============================================================================

SET SESSION group_concat_max_len = 4000000;

-- ---------------------------------------------------------------------------
-- 1) HUELLA GLOBAL: si es igual en las dos bases, la estructura es identica.
-- ---------------------------------------------------------------------------
SELECT MD5(GROUP_CONCAT(x.linea ORDER BY BINARY x.linea SEPARATOR '\n')) AS huella_global, COUNT(*) AS elementos
FROM (
    SELECT CONCAT('COL|', c.TABLE_NAME, '|', c.COLUMN_NAME, '|',
        IF(c.DATA_TYPE IN ('tinyint','smallint','mediumint','int','bigint'),
           CONCAT(c.DATA_TYPE, IF(c.COLUMN_TYPE LIKE '%unsigned%', ' unsigned', '')),
           c.COLUMN_TYPE), '|', c.IS_NULLABLE, '|',
        LOWER(REPLACE(REPLACE(IFNULL(c.COLUMN_DEFAULT, 'null'), '()', ''), '''', '')), '|',
        IF(c.EXTRA LIKE '%auto_increment%', 'AI', '')) AS linea
    FROM information_schema.COLUMNS c
    JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
    WHERE c.TABLE_SCHEMA = DATABASE()
    UNION ALL
    SELECT CONCAT('IDX|', s.TABLE_NAME, '|', s.INDEX_NAME, '|', MIN(s.NON_UNIQUE), '|',
        GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX SEPARATOR ','))
    FROM information_schema.STATISTICS s
    WHERE s.TABLE_SCHEMA = DATABASE()
    GROUP BY s.TABLE_NAME, s.INDEX_NAME
    UNION ALL
    SELECT CONCAT('FK|', k.TABLE_NAME, '|', k.CONSTRAINT_NAME, '|',
        GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ','), '->', k.REFERENCED_TABLE_NAME, '(',
        GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ','), ')|',
        REPLACE(r.DELETE_RULE, 'RESTRICT', 'NO ACTION'), '|', REPLACE(r.UPDATE_RULE, 'RESTRICT', 'NO ACTION'))
    FROM information_schema.KEY_COLUMN_USAGE k
    JOIN information_schema.REFERENTIAL_CONSTRAINTS r
      ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
    WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
    GROUP BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE, r.UPDATE_RULE
) x;

-- ---------------------------------------------------------------------------
-- 2) DETALLE: una fila por columna / indice / FK. Exportar el resultado de cada
--    base (CSV o copiar) y compararlo en un diff (VS Code: "Comparar archivos").
--    Si la huella global difiere, aqui se ve exactamente que linea cambia.
-- ---------------------------------------------------------------------------
SELECT x.linea AS detalle
FROM (
    SELECT CONCAT('COL|', c.TABLE_NAME, '|', c.COLUMN_NAME, '|',
        IF(c.DATA_TYPE IN ('tinyint','smallint','mediumint','int','bigint'),
           CONCAT(c.DATA_TYPE, IF(c.COLUMN_TYPE LIKE '%unsigned%', ' unsigned', '')),
           c.COLUMN_TYPE), '|', c.IS_NULLABLE, '|',
        LOWER(REPLACE(REPLACE(IFNULL(c.COLUMN_DEFAULT, 'null'), '()', ''), '''', '')), '|',
        IF(c.EXTRA LIKE '%auto_increment%', 'AI', '')) AS linea
    FROM information_schema.COLUMNS c
    JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
    WHERE c.TABLE_SCHEMA = DATABASE()
    UNION ALL
    SELECT CONCAT('IDX|', s.TABLE_NAME, '|', s.INDEX_NAME, '|', MIN(s.NON_UNIQUE), '|',
        GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX SEPARATOR ','))
    FROM information_schema.STATISTICS s
    WHERE s.TABLE_SCHEMA = DATABASE()
    GROUP BY s.TABLE_NAME, s.INDEX_NAME
    UNION ALL
    SELECT CONCAT('FK|', k.TABLE_NAME, '|', k.CONSTRAINT_NAME, '|',
        GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ','), '->', k.REFERENCED_TABLE_NAME, '(',
        GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ','), ')|',
        REPLACE(r.DELETE_RULE, 'RESTRICT', 'NO ACTION'), '|', REPLACE(r.UPDATE_RULE, 'RESTRICT', 'NO ACTION'))
    FROM information_schema.KEY_COLUMN_USAGE k
    JOIN information_schema.REFERENTIAL_CONSTRAINTS r
      ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
    WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
    GROUP BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE, r.UPDATE_RULE
) x
ORDER BY BINARY x.linea;
-- Nota phpMyAdmin: puede mostrar "#1146 tabla x no existe" al contar filas; es cosmetico.
-- Usa "Exportar" (CSV) para obtener todas las filas, no solo las 25 de la pagina.
