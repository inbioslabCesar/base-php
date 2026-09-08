-- Fase 1: separar fechas clinicas de la fecha comercial de cotizacion.
-- Ejecutar una vez por base de datos.
-- Compatibilidad: evita "ADD COLUMN IF NOT EXISTS" para motores/versiones que no lo soportan.

SET @db := DATABASE();

SET @has_re_fecha_proceso := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'fecha_proceso_en'
);
SET @sql_re_fecha_proceso := IF(
    @has_re_fecha_proceso = 0,
    'ALTER TABLE resultados_examenes ADD COLUMN fecha_proceso_en DATETIME NULL AFTER fecha_ingreso',
    'SELECT 1'
);
PREPARE stmt_re_fecha_proceso FROM @sql_re_fecha_proceso;
EXECUTE stmt_re_fecha_proceso;
DEALLOCATE PREPARE stmt_re_fecha_proceso;

SET @has_re_fecha_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'fecha_validacion_en'
);
SET @sql_re_fecha_validacion := IF(
    @has_re_fecha_validacion = 0,
    'ALTER TABLE resultados_examenes ADD COLUMN fecha_validacion_en DATETIME NULL AFTER fecha_proceso_en',
    'SELECT 1'
);
PREPARE stmt_re_fecha_validacion FROM @sql_re_fecha_validacion;
EXECUTE stmt_re_fecha_validacion;
DEALLOCATE PREPARE stmt_re_fecha_validacion;

SET @has_re_validado_por := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'validado_por'
);
SET @sql_re_validado_por := IF(
    @has_re_validado_por = 0,
    'ALTER TABLE resultados_examenes ADD COLUMN validado_por INT NULL AFTER fecha_validacion_en',
    'SELECT 1'
);
PREPARE stmt_re_validado_por FROM @sql_re_validado_por;
EXECUTE stmt_re_validado_por;
DEALLOCATE PREPARE stmt_re_validado_por;

SET @has_re_estado_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'estado_validacion'
);
SET @sql_re_estado_validacion := IF(
    @has_re_estado_validacion = 0,
    "ALTER TABLE resultados_examenes ADD COLUMN estado_validacion VARCHAR(20) NULL DEFAULT 'pendiente' AFTER validado_por",
    'SELECT 1'
);
PREPARE stmt_re_estado_validacion FROM @sql_re_estado_validacion;
EXECUTE stmt_re_estado_validacion;
DEALLOCATE PREPARE stmt_re_estado_validacion;

SET @has_cot_fecha_proceso := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'cotizaciones'
      AND COLUMN_NAME = 'fecha_proceso_inicio'
);
SET @sql_cot_fecha_proceso := IF(
    @has_cot_fecha_proceso = 0,
    'ALTER TABLE cotizaciones ADD COLUMN fecha_proceso_inicio DATETIME NULL AFTER fecha',
    'SELECT 1'
);
PREPARE stmt_cot_fecha_proceso FROM @sql_cot_fecha_proceso;
EXECUTE stmt_cot_fecha_proceso;
DEALLOCATE PREPARE stmt_cot_fecha_proceso;

SET @has_cot_fecha_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'cotizaciones'
      AND COLUMN_NAME = 'fecha_validacion_final'
);
SET @sql_cot_fecha_validacion := IF(
    @has_cot_fecha_validacion = 0,
    'ALTER TABLE cotizaciones ADD COLUMN fecha_validacion_final DATETIME NULL AFTER fecha_proceso_inicio',
    'SELECT 1'
);
PREPARE stmt_cot_fecha_validacion FROM @sql_cot_fecha_validacion;
EXECUTE stmt_cot_fecha_validacion;
DEALLOCATE PREPARE stmt_cot_fecha_validacion;

-- Inicializa fecha de proceso para historicos donde hubo captura previa.
UPDATE resultados_examenes
SET fecha_proceso_en = fecha_ingreso
WHERE fecha_proceso_en IS NULL
  AND resultados IS NOT NULL
  AND JSON_VALID(resultados)
  AND JSON_LENGTH(resultados) > 0;

-- Inicializa estado de validacion en registros existentes.
UPDATE resultados_examenes
SET estado_validacion = 'pendiente'
WHERE estado_validacion IS NULL OR TRIM(estado_validacion) = '';

-- Consolida fecha de proceso por cotizacion para historicos.
UPDATE cotizaciones c
JOIN (
    SELECT id_cotizacion, MIN(fecha_proceso_en) AS fecha_min_proceso
    FROM resultados_examenes
    WHERE fecha_proceso_en IS NOT NULL
    GROUP BY id_cotizacion
) x ON x.id_cotizacion = c.id
SET c.fecha_proceso_inicio = COALESCE(c.fecha_proceso_inicio, x.fecha_min_proceso);
