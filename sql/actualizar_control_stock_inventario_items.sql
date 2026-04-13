-- Agrega control de stock para diferenciar consumibles vs activos fijos
-- y habilita categoría activo_fijo para bienes (sillas, equipos, etc.)

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
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
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @sql_add_idx = (
    SELECT IF(
        EXISTS (
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
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

-- Activos fijos no deben entrar a alertas por stock
UPDATE inventario_items
SET controla_stock = 0,
    stock_minimo = 0,
    stock_critico = 0
WHERE categoria = 'activo_fijo';
