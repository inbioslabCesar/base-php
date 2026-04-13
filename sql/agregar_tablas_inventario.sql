-- Tablas base para Inventario de Reactivos e Insumos
-- Ejecutar una vez en la base de datos

CREATE TABLE IF NOT EXISTS inventario_items (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    categoria VARCHAR(30) NOT NULL,
    marca VARCHAR(80) NULL,
    presentacion VARCHAR(120) NULL,
    factor_presentacion DECIMAL(12,4) NOT NULL DEFAULT 1,
    unidad_medida VARCHAR(30) NOT NULL,
    controla_stock TINYINT(1) NOT NULL DEFAULT 1,
    stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_critico DECIMAL(12,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_inventario_items_codigo (codigo),
    KEY idx_inventario_items_nombre (nombre),
    KEY idx_inventario_items_categoria (categoria),
    KEY idx_inventario_items_controla_stock (controla_stock),
    KEY idx_inventario_items_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existía, agregar columnas faltantes (compatibilidad)
SET @sql_add_marca := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_items'
              AND COLUMN_NAME = 'marca'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_items ADD COLUMN marca VARCHAR(80) NULL AFTER categoria'
    )
);
PREPARE stmt_add_marca FROM @sql_add_marca;
EXECUTE stmt_add_marca;
DEALLOCATE PREPARE stmt_add_marca;

SET @sql_add_presentacion := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_items'
              AND COLUMN_NAME = 'presentacion'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_items ADD COLUMN presentacion VARCHAR(120) NULL AFTER marca'
    )
);
PREPARE stmt_add_presentacion FROM @sql_add_presentacion;
EXECUTE stmt_add_presentacion;
DEALLOCATE PREPARE stmt_add_presentacion;

SET @sql_add_factor_presentacion := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_items'
              AND COLUMN_NAME = 'factor_presentacion'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_items ADD COLUMN factor_presentacion DECIMAL(12,4) NOT NULL DEFAULT 1 AFTER presentacion'
    )
);
PREPARE stmt_add_factor_presentacion FROM @sql_add_factor_presentacion;
EXECUTE stmt_add_factor_presentacion;
DEALLOCATE PREPARE stmt_add_factor_presentacion;

SET @sql_add_controla_stock := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_items'
              AND COLUMN_NAME = 'controla_stock'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_items ADD COLUMN controla_stock TINYINT(1) NOT NULL DEFAULT 1 AFTER unidad_medida'
    )
);
PREPARE stmt_add_controla_stock FROM @sql_add_controla_stock;
EXECUTE stmt_add_controla_stock;
DEALLOCATE PREPARE stmt_add_controla_stock;

SET @sql_add_idx_controla_stock := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_items'
              AND INDEX_NAME = 'idx_inventario_items_controla_stock'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_items ADD INDEX idx_inventario_items_controla_stock (controla_stock)'
    )
);
PREPARE stmt_add_idx_controla_stock FROM @sql_add_idx_controla_stock;
EXECUTE stmt_add_idx_controla_stock;
DEALLOCATE PREPARE stmt_add_idx_controla_stock;

CREATE TABLE IF NOT EXISTS inventario_lotes (
    id INT NOT NULL AUTO_INCREMENT,
    item_id INT NOT NULL,
    lote_codigo VARCHAR(80) NOT NULL,
    fecha_vencimiento DATE NULL,
    cantidad_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
    cantidad_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_inventario_lotes_item (item_id),
    KEY idx_inventario_lotes_venc (fecha_vencimiento),
    KEY idx_inventario_lotes_stock (cantidad_actual),
    CONSTRAINT fk_inventario_lotes_item FOREIGN KEY (item_id) REFERENCES inventario_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventario_movimientos (
    id INT NOT NULL AUTO_INCREMENT,
    item_id INT NOT NULL,
    lote_id INT NULL,
    tipo VARCHAR(20) NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL,
    observacion VARCHAR(255) NULL,
    origen VARCHAR(30) NOT NULL DEFAULT 'inventario',
    usuario_id INT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventario_mov_item (item_id),
    KEY idx_inventario_mov_lote (lote_id),
    KEY idx_inventario_mov_tipo (tipo),
    KEY idx_inventario_mov_origen (origen),
    KEY idx_inventario_mov_fecha (fecha_hora),
    CONSTRAINT fk_inventario_mov_item FOREIGN KEY (item_id) REFERENCES inventario_items (id) ON DELETE CASCADE,
    CONSTRAINT fk_inventario_mov_lote FOREIGN KEY (lote_id) REFERENCES inventario_lotes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
