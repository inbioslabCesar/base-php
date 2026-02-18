-- Migración de compatibilidad: created_at para inventario_items
-- Seguro para ejecutar múltiples veces.

SET @inventario_items_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_items'
);

SET @sql_add_created_at := (
    SELECT IF(
        @inventario_items_exists = 0,
        "SELECT 'inventario_items no existe; primero ejecuta sql/agregar_tablas_inventario.sql' AS info",
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_items'
                  AND COLUMN_NAME = 'created_at'
            ),
            "SELECT 'created_at ya existe en inventario_items' AS info",
            'ALTER TABLE inventario_items ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER activo'
        )
    )
);
PREPARE stmt_add_created_at FROM @sql_add_created_at;
EXECUTE stmt_add_created_at;
DEALLOCATE PREPARE stmt_add_created_at;

SET @sql_add_idx_created_at := (
    SELECT IF(
        @inventario_items_exists = 0,
        "SELECT 'inventario_items no existe; índice no aplica' AS info",
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_items'
                  AND INDEX_NAME = 'idx_inventario_items_created_at'
            ),
            "SELECT 'idx_inventario_items_created_at ya existe' AS info",
            'ALTER TABLE inventario_items ADD INDEX idx_inventario_items_created_at (created_at)'
        )
    )
);
PREPARE stmt_add_idx_created_at FROM @sql_add_idx_created_at;
EXECUTE stmt_add_idx_created_at;
DEALLOCATE PREPARE stmt_add_idx_created_at;

SET @sql_add_updated_at := (
    SELECT IF(
        @inventario_items_exists = 0,
        "SELECT 'inventario_items no existe; updated_at no aplica' AS info",
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_items'
                  AND COLUMN_NAME = 'updated_at'
            ),
            "SELECT 'updated_at ya existe en inventario_items' AS info",
            'ALTER TABLE inventario_items ADD COLUMN updated_at DATETIME NULL AFTER created_at'
        )
    )
);
PREPARE stmt_add_updated_at FROM @sql_add_updated_at;
EXECUTE stmt_add_updated_at;
DEALLOCATE PREPARE stmt_add_updated_at;

SELECT
    CASE
        WHEN @inventario_items_exists = 0 THEN 'No se aplicó migración: inventario_items no existe'
        ELSE 'Migración aplicada/verificada: created_at + índice + updated_at'
    END AS resultado;
