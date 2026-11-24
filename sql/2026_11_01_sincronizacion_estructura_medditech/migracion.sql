-- =============================================================================
-- Migracion independiente: sincronizacion de estructura (medditech -> produccion)
-- Fecha: 2026-11-01
-- Compatible con MySQL 5.7+/8 y MariaDB 10.x (no usa ADD COLUMN IF NOT EXISTS,
-- ni procedimientos almacenados). Idempotente: se puede ejecutar varias veces.
-- No hace DROP/DELETE de datos. Cada paso verifica en information_schema
-- y se omite si la tabla/columna no existe o el objeto ya esta creado.
--
-- Contenido:
--  1. inventario_items.controla_stock (+ indice)
--  2. pagos: indices por fecha y fecha+metodo_pago
--  3. promociones_empresa / promociones_examen: indices (sin FKs)
--  4. resultados: indices por cotizacion/examen
--  5. resultados_examenes: snapshot de edad (3 columnas)
--  6. sis_codigos_prestacionales: tabla nueva + semilla 301-306
--  7. usuarios.email UNIQUE (solo si no hay emails duplicados)
-- =============================================================================

SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1. inventario_items.controla_stock
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventario_items') = 0,
    "SELECT 'inventario_items no existe: omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventario_items' AND COLUMN_NAME = 'controla_stock') > 0,
        "SELECT 'inventario_items.controla_stock ya existe' AS info",
        "ALTER TABLE `inventario_items` ADD COLUMN `controla_stock` TINYINT(1) NOT NULL DEFAULT 1"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventario_items' AND COLUMN_NAME IN ('controla_stock')) <> 1,
    "SELECT 'inventario_items.controla_stock no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventario_items' AND INDEX_NAME = 'idx_inventario_items_controla_stock') > 0,
        "SELECT 'idx_inventario_items_controla_stock ya existe' AS info",
        "ALTER TABLE `inventario_items` ADD INDEX `idx_inventario_items_controla_stock` (`controla_stock`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2. pagos: indices
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND COLUMN_NAME IN ('fecha')) <> 1,
    "SELECT 'pagos.fecha no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_pagos_fecha') > 0,
        "SELECT 'idx_pagos_fecha ya existe' AS info",
        "ALTER TABLE `pagos` ADD INDEX `idx_pagos_fecha` (`fecha`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND COLUMN_NAME IN ('fecha', 'metodo_pago')) <> 2,
    "SELECT 'pagos.fecha/metodo_pago no existen: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_pagos_fecha_metodo') > 0,
        "SELECT 'idx_pagos_fecha_metodo ya existe' AS info",
        "ALTER TABLE `pagos` ADD INDEX `idx_pagos_fecha_metodo` (`fecha`, `metodo_pago`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 3. promociones_empresa / promociones_examen: indices (sin claves foraneas,
--    para no fallar si hay registros huerfanos en produccion)
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND COLUMN_NAME IN ('empresa_id')) <> 1,
    "SELECT 'promociones_empresa.empresa_id no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND INDEX_NAME = 'idx_promociones_empresa_empresa') > 0,
        "SELECT 'idx_promociones_empresa_empresa ya existe' AS info",
        "ALTER TABLE `promociones_empresa` ADD INDEX `idx_promociones_empresa_empresa` (`empresa_id`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND COLUMN_NAME IN ('promocion_id')) <> 1,
    "SELECT 'promociones_empresa.promocion_id no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND INDEX_NAME = 'idx_promociones_empresa_promocion') > 0,
        "SELECT 'idx_promociones_empresa_promocion ya existe' AS info",
        "ALTER TABLE `promociones_empresa` ADD INDEX `idx_promociones_empresa_promocion` (`promocion_id`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND COLUMN_NAME IN ('examen_id')) <> 1,
    "SELECT 'promociones_examen.examen_id no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND INDEX_NAME = 'idx_promociones_examen_examen') > 0,
        "SELECT 'idx_promociones_examen_examen ya existe' AS info",
        "ALTER TABLE `promociones_examen` ADD INDEX `idx_promociones_examen_examen` (`examen_id`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND COLUMN_NAME IN ('promocion_id')) <> 1,
    "SELECT 'promociones_examen.promocion_id no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND INDEX_NAME = 'idx_promociones_examen_promocion') > 0,
        "SELECT 'idx_promociones_examen_promocion ya existe' AS info",
        "ALTER TABLE `promociones_examen` ADD INDEX `idx_promociones_examen_promocion` (`promocion_id`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4. resultados: indices
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND COLUMN_NAME IN ('id_cotizacion')) <> 1,
    "SELECT 'resultados.id_cotizacion no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_cotizacion') > 0,
        "SELECT 'idx_resultados_cotizacion ya existe' AS info",
        "ALTER TABLE `resultados` ADD INDEX `idx_resultados_cotizacion` (`id_cotizacion`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND COLUMN_NAME IN ('id_examen')) <> 1,
    "SELECT 'resultados.id_examen no existe: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_examen') > 0,
        "SELECT 'idx_resultados_examen ya existe' AS info",
        "ALTER TABLE `resultados` ADD INDEX `idx_resultados_examen` (`id_examen`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND COLUMN_NAME IN ('id_cotizacion', 'id_examen')) <> 2,
    "SELECT 'resultados.id_cotizacion/id_examen no existen: indice omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_cotizacion_examen') > 0,
        "SELECT 'idx_resultados_cotizacion_examen ya existe' AS info",
        "ALTER TABLE `resultados` ADD INDEX `idx_resultados_cotizacion_examen` (`id_cotizacion`, `id_examen`)"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 5. resultados_examenes: snapshot de edad
--    Tipos iguales a sql/2026_08_30_resultados_edad_snapshot.sql (los que usa
--    EdadPacienteService): DECIMAL(10,6) / VARCHAR(120) / DATETIME.
--    Sin AFTER: no depende de que exista otra columna.
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes') = 0,
    "SELECT 'resultados_examenes no existe: omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes' AND COLUMN_NAME = 'edad_paciente_valor') > 0,
        "SELECT 'resultados_examenes.edad_paciente_valor ya existe' AS info",
        "ALTER TABLE `resultados_examenes` ADD COLUMN `edad_paciente_valor` DECIMAL(10,6) NULL"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes') = 0,
    "SELECT 'resultados_examenes no existe: omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes' AND COLUMN_NAME = 'edad_paciente_texto') > 0,
        "SELECT 'resultados_examenes.edad_paciente_texto ya existe' AS info",
        "ALTER TABLE `resultados_examenes` ADD COLUMN `edad_paciente_texto` VARCHAR(120) NULL"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes') = 0,
    "SELECT 'resultados_examenes no existe: omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados_examenes' AND COLUMN_NAME = 'fecha_ref_edad') > 0,
        "SELECT 'resultados_examenes.fecha_ref_edad ya existe' AS info",
        "ALTER TABLE `resultados_examenes` ADD COLUMN `fecha_ref_edad` DATETIME NULL"
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 6. sis_codigos_prestacionales (tabla nueva)
--    Esquema segun lo que leen src/config/operacion_context.php y
--    config_sis_codigos_guardar.php (nombre, tipo_regla, rangos en meses,
--    icono, color, orden).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sis_codigos_prestacionales` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(10) NOT NULL,
    `nombre` VARCHAR(80) NOT NULL,
    `descripcion_minsa` VARCHAR(255) NULL,
    `tipo_regla` ENUM('EDAD','GESTANTE') NOT NULL DEFAULT 'EDAD',
    `edad_min_meses` INT NULL,
    `edad_max_meses` INT NULL,
    `icono_clase` VARCHAR(60) NULL,
    `icono_ruta` VARCHAR(255) NULL,
    `color` VARCHAR(7) NULL,
    `orden` INT NOT NULL DEFAULT 0,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sis_codigos_codigo` (`codigo`),
    KEY `idx_sis_codigos_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Semilla (solo inserta los codigos que no existan; nunca pisa valores editados).
-- Solo se ejecuta si la tabla tiene el esquema esperado.
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sis_codigos_prestacionales'
        AND COLUMN_NAME IN ('codigo','nombre','descripcion_minsa','tipo_regla','edad_min_meses','edad_max_meses','icono_clase','color','orden','activo')) = 10,
    "INSERT INTO `sis_codigos_prestacionales`
        (`codigo`,`nombre`,`descripcion_minsa`,`tipo_regla`,`edad_min_meses`,`edad_max_meses`,`icono_clase`,`color`,`orden`,`activo`)
     SELECT s.* FROM (
        SELECT '301' AS c, 'Niño' AS n, 'Etapa de vida niño: 0 a 11 años, 11 meses, 29 días' AS d, 'EDAD' AS t, 0 AS emin, 143 AS emax, 'bi-emoji-smile' AS i, '#0d6efd' AS col, 1 AS o, 1 AS a
        UNION ALL SELECT '302','Adolescente','Etapa de vida adolescente: 12 a 17 años, 11 meses, 29 días','EDAD',144,215,'bi-person-arms-up','#6f42c1',2,1
        UNION ALL SELECT '303','Joven','Etapa de vida joven: 18 a 29 años, 11 meses, 29 días','EDAD',216,359,'bi-person','#198754',3,1
        UNION ALL SELECT '304','Adulto','Etapa de vida adulto: 30 a 59 años, 11 meses, 29 días','EDAD',360,719,'bi-person-workspace','#fd7e14',4,1
        UNION ALL SELECT '305','Adulto mayor','Etapa de vida adulto mayor: 60 años a más','EDAD',720,NULL,'bi-person-wheelchair','#6c757d',5,1
        UNION ALL SELECT '306','Gestante','Atención prenatal / gestante','GESTANTE',NULL,NULL,'bi-gender-female','#d63384',6,1
     ) s
     WHERE NOT EXISTS (SELECT 1 FROM `sis_codigos_prestacionales` x WHERE x.`codigo` = s.c)",
    "SELECT 'sis_codigos_prestacionales existe con otro esquema: semilla omitida, revisar manualmente' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 7. usuarios.email UNIQUE
--    Se omite (con aviso) si ya existe un indice unico sobre email o si hay
--    emails duplicados; en ese caso resolver los duplicados (ver consulta de
--    verificacion al final) y volver a ejecutar este script.
-- -----------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email') = 0,
    "SELECT 'usuarios.email no existe: omitido' AS info",
    IF(
        (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email'
            AND SEQ_IN_INDEX = 1 AND NON_UNIQUE = 0
            AND INDEX_NAME IN (SELECT INDEX_NAME FROM (
                SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios'
                 GROUP BY INDEX_NAME HAVING COUNT(*) = 1) solo_una)) > 0,
        "SELECT 'usuarios.email ya tiene indice unico' AS info",
        IF(
            (SELECT COUNT(*) FROM (SELECT `email` FROM `usuarios` GROUP BY `email` HAVING COUNT(*) > 1) dup) > 0,
            "SELECT 'usuarios.email tiene duplicados: UNIQUE omitido, depurar y reejecutar' AS info",
            "ALTER TABLE `usuarios` ADD CONSTRAINT `uq_usuarios_email` UNIQUE (`email`)"
        )
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- VERIFICACION (solo lectura)
-- =============================================================================
SHOW COLUMNS FROM inventario_items LIKE 'controla_stock';
SHOW COLUMNS FROM resultados_examenes LIKE 'edad_paciente%';
SHOW COLUMNS FROM resultados_examenes LIKE 'fecha_ref_edad';
SHOW TABLES LIKE 'sis_codigos_prestacionales';
SELECT codigo, nombre, tipo_regla, edad_min_meses, edad_max_meses, activo FROM sis_codigos_prestacionales ORDER BY orden;

SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columnas, MIN(NON_UNIQUE) AS no_unico
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
    'idx_inventario_items_controla_stock', 'idx_pagos_fecha', 'idx_pagos_fecha_metodo',
    'idx_promociones_empresa_empresa', 'idx_promociones_empresa_promocion',
    'idx_promociones_examen_examen', 'idx_promociones_examen_promocion',
    'idx_resultados_cotizacion', 'idx_resultados_examen', 'idx_resultados_cotizacion_examen',
    'uq_usuarios_email')
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- Emails duplicados (debe devolver 0 filas para poder aplicar el UNIQUE)
SELECT email, COUNT(*) AS veces FROM usuarios GROUP BY email HAVING COUNT(*) > 1;
