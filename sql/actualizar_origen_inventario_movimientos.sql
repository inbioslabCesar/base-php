-- Agrega columna de origen para separar movimientos del inventario principal
-- y transferencias internas de laboratorio.

SET @sql_add_col = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inventario_movimientos'
              AND COLUMN_NAME = 'origen'
        ),
        'SELECT 1',
        "ALTER TABLE inventario_movimientos ADD COLUMN origen VARCHAR(30) NOT NULL DEFAULT 'inventario' AFTER observacion"
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
              AND TABLE_NAME = 'inventario_movimientos'
              AND INDEX_NAME = 'idx_inventario_mov_origen'
        ),
        'SELECT 1',
        'ALTER TABLE inventario_movimientos ADD INDEX idx_inventario_mov_origen (origen)'
    )
);
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

UPDATE inventario_movimientos
SET origen = CASE
    WHEN COALESCE(observacion, '') LIKE 'Transferencia interna #% a laboratorio%' THEN 'transferencia_interna'
    ELSE 'inventario'
END
WHERE origen IS NULL OR origen = '' OR origen = 'inventario';
