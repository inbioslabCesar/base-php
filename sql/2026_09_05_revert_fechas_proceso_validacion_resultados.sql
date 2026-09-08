-- Rollback Fase 1: fechas clinicas y validacion de resultados
-- Uso: ejecutar SOLO si decides revertir la migracion 2026_09_05_fechas_proceso_validacion_resultados.sql
-- Recomendacion: correr primero en desarrollo y con backup de base de datos.
-- Nota tecnica: ALTER TABLE en MySQL/MariaDB realiza commit implicito.
-- Por ello, este script prioriza respaldar antes de eliminar columnas.

-- 1) Respaldo liviano de datos de las columnas nuevas (si existen)
-- Nota: se crea una tabla de respaldo por ejecucion para no perder trazabilidad.

SET @ts := DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s');
SET @db := DATABASE();

-- Backup de resultados_examenes (columnas agregadas en migracion)
SET @has_re_fecha_proceso := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'fecha_proceso_en'
);
SET @has_re_fecha_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'fecha_validacion_en'
);
SET @has_re_validado_por := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'validado_por'
);
SET @has_re_estado_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'resultados_examenes'
      AND COLUMN_NAME = 'estado_validacion'
);

SET @do_backup_re := IF(
    @has_re_fecha_proceso + @has_re_fecha_validacion + @has_re_validado_por + @has_re_estado_validacion > 0,
    1,
    0
);

SET @sql_backup_re := IF(
    @do_backup_re = 1,
    CONCAT(
        'CREATE TABLE IF NOT EXISTS backup_re_validacion_', @ts, ' AS ',
        'SELECT id, id_cotizacion, id_examen',
        IF(@has_re_fecha_proceso = 1, ', fecha_proceso_en', ', NULL AS fecha_proceso_en'),
        IF(@has_re_fecha_validacion = 1, ', fecha_validacion_en', ', NULL AS fecha_validacion_en'),
        IF(@has_re_validado_por = 1, ', validado_por', ', NULL AS validado_por'),
        IF(@has_re_estado_validacion = 1, ', estado_validacion', ', NULL AS estado_validacion'),
        ' FROM resultados_examenes'
    ),
    'SELECT 1'
);
PREPARE stmt_backup_re FROM @sql_backup_re;
EXECUTE stmt_backup_re;
DEALLOCATE PREPARE stmt_backup_re;

-- Backup de cotizaciones (columnas agregadas en migracion)
SET @has_cot_fecha_proceso := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'cotizaciones'
      AND COLUMN_NAME = 'fecha_proceso_inicio'
);
SET @has_cot_fecha_validacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'cotizaciones'
      AND COLUMN_NAME = 'fecha_validacion_final'
);

SET @do_backup_cot := IF(@has_cot_fecha_proceso + @has_cot_fecha_validacion > 0, 1, 0);

SET @sql_backup_cot := IF(
    @do_backup_cot = 1,
    CONCAT(
        'CREATE TABLE IF NOT EXISTS backup_cot_validacion_', @ts, ' AS ',
        'SELECT id',
        IF(@has_cot_fecha_proceso = 1, ', fecha_proceso_inicio', ', NULL AS fecha_proceso_inicio'),
        IF(@has_cot_fecha_validacion = 1, ', fecha_validacion_final', ', NULL AS fecha_validacion_final'),
        ' FROM cotizaciones'
    ),
    'SELECT 1'
);
PREPARE stmt_backup_cot FROM @sql_backup_cot;
EXECUTE stmt_backup_cot;
DEALLOCATE PREPARE stmt_backup_cot;

-- 2) Eliminar columnas agregadas por la migracion (si existen)

SET @drop_re := CONCAT(
    'ALTER TABLE resultados_examenes',
    IF(@has_re_fecha_proceso = 1, ' DROP COLUMN fecha_proceso_en', ''),
    IF(@has_re_fecha_validacion = 1, IF(@has_re_fecha_proceso = 1, ', DROP COLUMN fecha_validacion_en', ' DROP COLUMN fecha_validacion_en'), ''),
    IF(@has_re_validado_por = 1, IF(@has_re_fecha_proceso + @has_re_fecha_validacion > 0, ', DROP COLUMN validado_por', ' DROP COLUMN validado_por'), ''),
    IF(@has_re_estado_validacion = 1, IF(@has_re_fecha_proceso + @has_re_fecha_validacion + @has_re_validado_por > 0, ', DROP COLUMN estado_validacion', ' DROP COLUMN estado_validacion'), '')
);

SET @drop_re_sql := IF(
    @has_re_fecha_proceso + @has_re_fecha_validacion + @has_re_validado_por + @has_re_estado_validacion > 0,
    @drop_re,
    'SELECT 1'
);
PREPARE stmt_drop_re FROM @drop_re_sql;
EXECUTE stmt_drop_re;
DEALLOCATE PREPARE stmt_drop_re;

SET @drop_cot := CONCAT(
    'ALTER TABLE cotizaciones',
    IF(@has_cot_fecha_proceso = 1, ' DROP COLUMN fecha_proceso_inicio', ''),
    IF(@has_cot_fecha_validacion = 1, IF(@has_cot_fecha_proceso = 1, ', DROP COLUMN fecha_validacion_final', ' DROP COLUMN fecha_validacion_final'), '')
);

SET @drop_cot_sql := IF(
    @has_cot_fecha_proceso + @has_cot_fecha_validacion > 0,
    @drop_cot,
    'SELECT 1'
);
PREPARE stmt_drop_cot FROM @drop_cot_sql;
EXECUTE stmt_drop_cot;
DEALLOCATE PREPARE stmt_drop_cot;

-- Verificacion sugerida post-rollback:
-- SHOW COLUMNS FROM resultados_examenes LIKE 'fecha_proceso_en';
-- SHOW COLUMNS FROM resultados_examenes LIKE 'fecha_validacion_en';
-- SHOW COLUMNS FROM resultados_examenes LIKE 'validado_por';
-- SHOW COLUMNS FROM resultados_examenes LIKE 'estado_validacion';
-- SHOW COLUMNS FROM cotizaciones LIKE 'fecha_proceso_inicio';
-- SHOW COLUMNS FROM cotizaciones LIKE 'fecha_validacion_final';
