-- Migración: exámenes referenciados/tercerizados y liquidación contable
-- Fecha: 2026-02-24

SET @db := DATABASE();

-- ===============================
-- 1) Tabla maestra de laboratorios referenciados
-- ===============================
CREATE TABLE IF NOT EXISTS laboratorios_referenciados (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_laboratorios_referenciados_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================
-- 2) Extensiones en cotizaciones_detalle
-- ===============================
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN es_referenciado TINYINT(1) NOT NULL DEFAULT 0 AFTER subtotal',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'es_referenciado'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN laboratorio_referenciado_nombre VARCHAR(150) NULL AFTER es_referenciado',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'laboratorio_referenciado_nombre'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN costo_laboratorio_referenciado DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER laboratorio_referenciado_nombre',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'costo_laboratorio_referenciado'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN costo_logistica_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER costo_laboratorio_referenciado',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'costo_logistica_extra'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        "ALTER TABLE cotizaciones_detalle ADD COLUMN estado_liquidacion ENUM('pendiente','liquidado') NOT NULL DEFAULT 'pendiente' AFTER costo_logistica_extra",
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'estado_liquidacion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN fecha_liquidacion DATETIME NULL AFTER estado_liquidacion',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'fecha_liquidacion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN liquidado_por INT NULL AFTER fecha_liquidacion',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'liquidado_por'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN egreso_laboratorio_id INT NULL AFTER liquidado_por',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'egreso_laboratorio_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD COLUMN egreso_logistica_id INT NULL AFTER egreso_laboratorio_id',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND COLUMN_NAME = 'egreso_logistica_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cotizaciones_detalle ADD INDEX idx_cd_referenciado_liquidacion (es_referenciado, estado_liquidacion)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones_detalle' AND INDEX_NAME = 'idx_cd_referenciado_liquidacion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ===============================
-- 3) Extensiones en egresos para trazabilidad
-- ===============================
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD COLUMN categoria VARCHAR(60) NULL AFTER descripcion',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND COLUMN_NAME = 'categoria'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD COLUMN subcategoria VARCHAR(120) NULL AFTER categoria',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND COLUMN_NAME = 'subcategoria'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD COLUMN id_cotizacion INT NULL AFTER subcategoria',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND COLUMN_NAME = 'id_cotizacion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD COLUMN id_cotizacion_detalle INT NULL AFTER id_cotizacion',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND COLUMN_NAME = 'id_cotizacion_detalle'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD COLUMN origen_auto TINYINT(1) NOT NULL DEFAULT 0 AFTER id_cotizacion_detalle',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND COLUMN_NAME = 'origen_auto'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD INDEX idx_egresos_categoria_fecha (categoria, fecha)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND INDEX_NAME = 'idx_egresos_categoria_fecha'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE egresos ADD INDEX idx_egresos_cotizacion_detalle (id_cotizacion, id_cotizacion_detalle)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'egresos' AND INDEX_NAME = 'idx_egresos_cotizacion_detalle'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
