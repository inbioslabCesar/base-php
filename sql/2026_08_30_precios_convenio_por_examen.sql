-- Migracion: precios convenio y modo de tarifa por perfil
-- Fecha: 2026-08-30
-- Objetivo:
-- 1) Permitir precio exacto por examen para convenios/empresas.
-- 2) Permitir definir si empresa/convenio/cliente usa precio convenio por defecto.

SET @db := DATABASE();

-- examenes.precio_convenio
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'examenes'
      AND COLUMN_NAME = 'precio_convenio'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE examenes ADD COLUMN precio_convenio DECIMAL(10,2) NULL AFTER precio_publico",
    "SELECT 'examenes.precio_convenio ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- empresas.usar_precio_convenio
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'empresas'
      AND COLUMN_NAME = 'usar_precio_convenio'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE empresas ADD COLUMN usar_precio_convenio TINYINT(1) NOT NULL DEFAULT 0 AFTER descuento",
    "SELECT 'empresas.usar_precio_convenio ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- convenios.usar_precio_convenio
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'convenios'
      AND COLUMN_NAME = 'usar_precio_convenio'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE convenios ADD COLUMN usar_precio_convenio TINYINT(1) NOT NULL DEFAULT 0 AFTER descuento",
    "SELECT 'convenios.usar_precio_convenio ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- clientes.usar_precio_convenio
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'clientes'
      AND COLUMN_NAME = 'usar_precio_convenio'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE clientes ADD COLUMN usar_precio_convenio TINYINT(1) NOT NULL DEFAULT 0 AFTER descuento",
    "SELECT 'clientes.usar_precio_convenio ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificaciones rapidas
SHOW COLUMNS FROM examenes LIKE 'precio_convenio';
SHOW COLUMNS FROM empresas LIKE 'usar_precio_convenio';
SHOW COLUMNS FROM convenios LIKE 'usar_precio_convenio';
SHOW COLUMNS FROM clientes LIKE 'usar_precio_convenio';
