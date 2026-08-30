-- Profesionales solicitantes y asociacion a servicios
-- Incluye snapshot del profesional en cotizaciones para preservar historico de impresion.

CREATE TABLE IF NOT EXISTS profesionales_solicitantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(120) NOT NULL,
    apellidos VARCHAR(120) NULL,
    tipo_profesional VARCHAR(80) NOT NULL,
    numero_documento VARCHAR(30) NULL,
    registro_profesional VARCHAR(80) NULL,
    telefono VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ps_estado (estado),
    KEY idx_ps_nombre (nombres, apellidos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicio_profesional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    profesional_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_servicio_profesional (servicio_id, profesional_id),
    KEY idx_sp_servicio (servicio_id),
    KEY idx_sp_profesional (profesional_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cotizaciones'
              AND COLUMN_NAME = 'profesional_solicitante_id'
        ),
        'SELECT 1',
        'ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_id INT NULL'
    )
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cotizaciones'
              AND COLUMN_NAME = 'profesional_solicitante_nombre'
        ),
        'SELECT 1',
        'ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_nombre VARCHAR(190) NULL AFTER profesional_solicitante_id'
    )
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cotizaciones'
              AND COLUMN_NAME = 'profesional_solicitante_tipo'
        ),
        'SELECT 1',
        'ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_tipo VARCHAR(80) NULL AFTER profesional_solicitante_nombre'
    )
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cotizaciones'
              AND COLUMN_NAME = 'profesional_solicitante_registro'
        ),
        'SELECT 1',
        'ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_registro VARCHAR(80) NULL AFTER profesional_solicitante_tipo'
    )
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @sql_add_idx = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cotizaciones'
              AND INDEX_NAME = 'idx_cotizaciones_profesional_solicitante'
        ),
        'SELECT 1',
        'ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_profesional_solicitante (profesional_solicitante_id)'
    )
);
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;
