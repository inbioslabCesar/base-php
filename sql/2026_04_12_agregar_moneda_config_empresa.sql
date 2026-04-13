-- Configuracion de moneda por empresa

SET @db := DATABASE();

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_codigo'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_codigo VARCHAR(10) NOT NULL DEFAULT 'PEN' AFTER celular",
    "SELECT 'moneda_codigo ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Crear una fila base si la tabla de configuracion esta vacia
SET @rows_config_empresa := (SELECT COUNT(*) FROM config_empresa);
SET @sql := IF(@rows_config_empresa = 0,
    "INSERT INTO config_empresa (nombre, ruc, direccion, celular, moneda_codigo, moneda_simbolo, moneda_posicion, moneda_decimales, moneda_separador_decimal, moneda_separador_miles) VALUES ('Empresa', '', '', '', 'PEN', 'S/', 'prefix', 2, '.', ',')",
    "SELECT 'config_empresa ya tiene registros' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_simbolo'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_simbolo VARCHAR(10) NOT NULL DEFAULT 'S/' AFTER moneda_codigo",
    "SELECT 'moneda_simbolo ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_posicion'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_posicion ENUM('prefix','suffix') NOT NULL DEFAULT 'prefix' AFTER moneda_simbolo",
    "SELECT 'moneda_posicion ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_decimales'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_decimales TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER moneda_posicion",
    "SELECT 'moneda_decimales ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_separador_decimal'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_separador_decimal VARCHAR(1) NOT NULL DEFAULT '.' AFTER moneda_decimales",
    "SELECT 'moneda_separador_decimal ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'config_empresa'
      AND COLUMN_NAME = 'moneda_separador_miles'
);
SET @sql := IF(@exists = 0,
    "ALTER TABLE config_empresa ADD COLUMN moneda_separador_miles VARCHAR(1) NOT NULL DEFAULT ',' AFTER moneda_separador_decimal",
    "SELECT 'moneda_separador_miles ya existe' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
