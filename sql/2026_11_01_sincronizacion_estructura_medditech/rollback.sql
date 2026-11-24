-- =============================================================================
-- Rollback: sincronizacion de estructura (medditech -> produccion) 2026-11-01
-- Elimina SOLO indices (seguro, no pierde datos). Las columnas y la tabla estan
-- comentadas porque pueden contener datos reales (snapshot de edad, catalogo
-- SIS personalizado); descomentar unicamente si se confirma que se crearon con
-- esta migracion y se tiene respaldo.
-- =============================================================================

SET @db := DATABASE();

-- Helper por indice: DROP INDEX solo si existe.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventario_items' AND INDEX_NAME = 'idx_inventario_items_controla_stock') > 0, 'ALTER TABLE `inventario_items` DROP INDEX `idx_inventario_items_controla_stock`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_pagos_fecha') > 0, 'ALTER TABLE `pagos` DROP INDEX `idx_pagos_fecha`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_pagos_fecha_metodo') > 0, 'ALTER TABLE `pagos` DROP INDEX `idx_pagos_fecha_metodo`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND INDEX_NAME = 'idx_promociones_empresa_empresa') > 0, 'ALTER TABLE `promociones_empresa` DROP INDEX `idx_promociones_empresa_empresa`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_empresa' AND INDEX_NAME = 'idx_promociones_empresa_promocion') > 0, 'ALTER TABLE `promociones_empresa` DROP INDEX `idx_promociones_empresa_promocion`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND INDEX_NAME = 'idx_promociones_examen_examen') > 0, 'ALTER TABLE `promociones_examen` DROP INDEX `idx_promociones_examen_examen`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'promociones_examen' AND INDEX_NAME = 'idx_promociones_examen_promocion') > 0, 'ALTER TABLE `promociones_examen` DROP INDEX `idx_promociones_examen_promocion`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_cotizacion') > 0, 'ALTER TABLE `resultados` DROP INDEX `idx_resultados_cotizacion`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_examen') > 0, 'ALTER TABLE `resultados` DROP INDEX `idx_resultados_examen`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'resultados' AND INDEX_NAME = 'idx_resultados_cotizacion_examen') > 0, 'ALTER TABLE `resultados` DROP INDEX `idx_resultados_cotizacion_examen`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'uq_usuarios_email') > 0, 'ALTER TABLE `usuarios` DROP INDEX `uq_usuarios_email`', "SELECT 'sin cambios' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- DESTRUCTIVO (con perdida de datos) - descomentar solo con respaldo previo:
-- -----------------------------------------------------------------------------
-- ALTER TABLE `inventario_items` DROP COLUMN `controla_stock`;
-- ALTER TABLE `resultados_examenes` DROP COLUMN `edad_paciente_valor`, DROP COLUMN `edad_paciente_texto`, DROP COLUMN `fecha_ref_edad`;
-- DROP TABLE IF EXISTS `sis_codigos_prestacionales`;
