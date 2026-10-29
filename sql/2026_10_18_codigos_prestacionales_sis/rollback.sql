-- Rollback: Codigos prestacionales SIS y gestantes (2026-10-18)
-- ATENCION: elimina los datos de codigo prestacional / gestante guardados en las cotizaciones.
-- Hacer respaldo antes de ejecutar.

SET @db := DATABASE();

SET @exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND INDEX_NAME = 'idx_cotizaciones_codprest_fecha');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP INDEX idx_cotizaciones_codprest_fecha", "SELECT 'indice no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'codigo_prestacional_id');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN codigo_prestacional_id", "SELECT 'codigo_prestacional_id no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'codigo_prestacional');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN codigo_prestacional", "SELECT 'codigo_prestacional no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_paciente_meses');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN edad_paciente_meses", "SELECT 'edad_paciente_meses no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'es_gestante');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN es_gestante", "SELECT 'es_gestante no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_gestacional_semanas');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN edad_gestacional_semanas", "SELECT 'edad_gestacional_semanas no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_gestacional_dias');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN edad_gestacional_dias", "SELECT 'edad_gestacional_dias no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'fpp');
SET @sql := IF(@exists > 0, "ALTER TABLE cotizaciones DROP COLUMN fpp", "SELECT 'fpp no existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS sis_codigos_prestacionales;
