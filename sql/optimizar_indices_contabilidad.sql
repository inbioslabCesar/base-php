-- Optimización opcional de índices para Contabilidad
-- Objetivo: reducir tiempos de consulta en historial diario y caja
-- Compatible con MySQL/MariaDB antiguos (sin CREATE INDEX IF NOT EXISTS)

-- =========================
-- Índices en tabla pagos
-- =========================

SET @sql_idx_pagos_fecha := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pagos'
        ),
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'pagos'
                  AND INDEX_NAME = 'idx_pagos_fecha'
            ),
            'SELECT 1',
            'CREATE INDEX idx_pagos_fecha ON pagos (fecha)'
        ),
        'SELECT 1'
    )
);
PREPARE stmt_idx_pagos_fecha FROM @sql_idx_pagos_fecha;
EXECUTE stmt_idx_pagos_fecha;
DEALLOCATE PREPARE stmt_idx_pagos_fecha;

SET @sql_idx_pagos_fecha_metodo := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pagos'
        ),
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'pagos'
                  AND INDEX_NAME = 'idx_pagos_fecha_metodo'
            ),
            'SELECT 1',
            'CREATE INDEX idx_pagos_fecha_metodo ON pagos (fecha, metodo_pago)'
        ),
        'SELECT 1'
    )
);
PREPARE stmt_idx_pagos_fecha_metodo FROM @sql_idx_pagos_fecha_metodo;
EXECUTE stmt_idx_pagos_fecha_metodo;
DEALLOCATE PREPARE stmt_idx_pagos_fecha_metodo;


-- ================================
-- Índices en tabla caja_movimientos
-- ================================

SET @sql_idx_mov_ref_tipo_origen := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'caja_movimientos'
        ),
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_movimientos'
                  AND INDEX_NAME = 'idx_mov_ref_tipo_origen'
            ),
            'SELECT 1',
            'CREATE INDEX idx_mov_ref_tipo_origen ON caja_movimientos (referencia_id, tipo, origen, referencia_tipo)'
        ),
        'SELECT 1'
    )
);
PREPARE stmt_idx_mov_ref_tipo_origen FROM @sql_idx_mov_ref_tipo_origen;
EXECUTE stmt_idx_mov_ref_tipo_origen;
DEALLOCATE PREPARE stmt_idx_mov_ref_tipo_origen;

SET @sql_idx_mov_fecha_hora := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'caja_movimientos'
        ),
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_movimientos'
                  AND INDEX_NAME = 'idx_mov_fecha_hora'
            ),
            'SELECT 1',
            'CREATE INDEX idx_mov_fecha_hora ON caja_movimientos (fecha_hora)'
        ),
        'SELECT 1'
    )
);
PREPARE stmt_idx_mov_fecha_hora FROM @sql_idx_mov_fecha_hora;
EXECUTE stmt_idx_mov_fecha_hora;
DEALLOCATE PREPARE stmt_idx_mov_fecha_hora;

SET @sql_idx_mov_caja_tipo_efectivo := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'caja_movimientos'
        ),
        IF(
            EXISTS(
                SELECT 1
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_movimientos'
                  AND INDEX_NAME = 'idx_mov_caja_tipo_efectivo'
            ),
            'SELECT 1',
            'CREATE INDEX idx_mov_caja_tipo_efectivo ON caja_movimientos (caja_id, tipo, afecta_efectivo)'
        ),
        'SELECT 1'
    )
);
PREPARE stmt_idx_mov_caja_tipo_efectivo FROM @sql_idx_mov_caja_tipo_efectivo;
EXECUTE stmt_idx_mov_caja_tipo_efectivo;
DEALLOCATE PREPARE stmt_idx_mov_caja_tipo_efectivo;

-- Fin
