-- Optimización de rendimiento para Inventario (Producción)
-- Fecha: 2026-02-16
-- Objetivo: acelerar consultas de stock, lotes, transferencias y consumos internos.
-- Seguro para ejecutar múltiples veces (idempotente).

START TRANSACTION;

-- =========================================================
-- inventario_items
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_items'
);

SET @sql_idx_items_created_at := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_items no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_items' AND INDEX_NAME = 'idx_inventario_items_created_at'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_items ADD INDEX idx_inventario_items_created_at (created_at)'
    )
  )
);
PREPARE stmt_idx_items_created_at FROM @sql_idx_items_created_at;
EXECUTE stmt_idx_items_created_at;
DEALLOCATE PREPARE stmt_idx_items_created_at;

-- =========================================================
-- inventario_lotes
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_lotes'
);

SET @sql_idx_lotes_item_stock_venc := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_lotes no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_lotes' AND INDEX_NAME = 'idx_inventario_lotes_item_stock_venc'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_lotes ADD INDEX idx_inventario_lotes_item_stock_venc (item_id, cantidad_actual, fecha_vencimiento, id)'
    )
  )
);
PREPARE stmt_idx_lotes_item_stock_venc FROM @sql_idx_lotes_item_stock_venc;
EXECUTE stmt_idx_lotes_item_stock_venc;
DEALLOCATE PREPARE stmt_idx_lotes_item_stock_venc;

SET @sql_idx_lotes_venc_stock_item := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_lotes no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_lotes' AND INDEX_NAME = 'idx_inventario_lotes_venc_stock_item'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_lotes ADD INDEX idx_inventario_lotes_venc_stock_item (fecha_vencimiento, cantidad_actual, item_id)'
    )
  )
);
PREPARE stmt_idx_lotes_venc_stock_item FROM @sql_idx_lotes_venc_stock_item;
EXECUTE stmt_idx_lotes_venc_stock_item;
DEALLOCATE PREPARE stmt_idx_lotes_venc_stock_item;

-- =========================================================
-- inventario_movimientos
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_movimientos'
);

SET @sql_idx_mov_fecha_id := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_movimientos no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_movimientos' AND INDEX_NAME = 'idx_inventario_mov_fecha_id'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_movimientos ADD INDEX idx_inventario_mov_fecha_id (fecha_hora, id)'
    )
  )
);
PREPARE stmt_idx_mov_fecha_id FROM @sql_idx_mov_fecha_id;
EXECUTE stmt_idx_mov_fecha_id;
DEALLOCATE PREPARE stmt_idx_mov_fecha_id;

-- =========================================================
-- inventario_consumos_examen
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_consumos_examen'
);

SET @sql_idx_consumo_estado_item := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_consumos_examen no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_consumos_examen' AND INDEX_NAME = 'idx_consumo_estado_item'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_consumos_examen ADD INDEX idx_consumo_estado_item (estado, item_id)'
    )
  )
);
PREPARE stmt_idx_consumo_estado_item FROM @sql_idx_consumo_estado_item;
EXECUTE stmt_idx_consumo_estado_item;
DEALLOCATE PREPARE stmt_idx_consumo_estado_item;

SET @sql_idx_consumo_repeticion := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_consumos_examen no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_consumos_examen' AND INDEX_NAME = 'idx_consumo_repeticion'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_consumos_examen ADD INDEX idx_consumo_repeticion (estado, origen_evento, fecha_hora, id)'
    )
  )
);
PREPARE stmt_idx_consumo_repeticion FROM @sql_idx_consumo_repeticion;
EXECUTE stmt_idx_consumo_repeticion;
DEALLOCATE PREPARE stmt_idx_consumo_repeticion;

-- =========================================================
-- inventario_transferencias
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_transferencias'
);

SET @sql_idx_transfer_destino_id := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_transferencias no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_transferencias' AND INDEX_NAME = 'idx_transfer_destino_id'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_transferencias ADD INDEX idx_transfer_destino_id (destino, id)'
    )
  )
);
PREPARE stmt_idx_transfer_destino_id FROM @sql_idx_transfer_destino_id;
EXECUTE stmt_idx_transfer_destino_id;
DEALLOCATE PREPARE stmt_idx_transfer_destino_id;

SET @sql_idx_transfer_fecha_id := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_transferencias no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_transferencias' AND INDEX_NAME = 'idx_transfer_fecha_id'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_transferencias ADD INDEX idx_transfer_fecha_id (fecha_hora, id)'
    )
  )
);
PREPARE stmt_idx_transfer_fecha_id FROM @sql_idx_transfer_fecha_id;
EXECUTE stmt_idx_transfer_fecha_id;
DEALLOCATE PREPARE stmt_idx_transfer_fecha_id;

-- =========================================================
-- inventario_transferencias_detalle
-- =========================================================
SET @tbl_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_transferencias_detalle'
);

SET @sql_idx_transfer_det_item_transfer := (
  SELECT IF(
    @tbl_exists = 0,
    "SELECT 'inventario_transferencias_detalle no existe' AS info",
    IF(
      EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_transferencias_detalle' AND INDEX_NAME = 'idx_transfer_det_item_transfer'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_transferencias_detalle ADD INDEX idx_transfer_det_item_transfer (item_id, transferencia_id)'
    )
  )
);
PREPARE stmt_idx_transfer_det_item_transfer FROM @sql_idx_transfer_det_item_transfer;
EXECUTE stmt_idx_transfer_det_item_transfer;
DEALLOCATE PREPARE stmt_idx_transfer_det_item_transfer;

COMMIT;

SELECT 'Optimización de índices de inventario aplicada/verificada.' AS resultado;
