-- PRE-CHEQUEO (solo lectura)
-- Verifica si producción ya tiene los cambios de sincronización con desarrollo.
-- No modifica datos ni estructura.

SELECT DATABASE() AS base_actual;

-- =========================================================
-- 1) Verificar tablas faltantes de inventario interno
-- =========================================================
SHOW TABLES LIKE 'inventario_consumos_examen';
SHOW TABLES LIKE 'inventario_examen_recetas';
SHOW TABLES LIKE 'inventario_transferencias';
SHOW TABLES LIKE 'inventario_transferencias_detalle';

-- =========================================================
-- 2) Verificar columnas de anulación en cotizaciones
-- =========================================================
SHOW COLUMNS FROM `cotizaciones` LIKE 'anulada_at';
SHOW COLUMNS FROM `cotizaciones` LIKE 'anulada_por';
SHOW COLUMNS FROM `cotizaciones` LIKE 'anulado_motivo';

-- =========================================================
-- 3) Verificar índices en cotizaciones
-- =========================================================
SHOW INDEX FROM `cotizaciones` WHERE Key_name = 'idx_cotizaciones_anulada_at';
SHOW INDEX FROM `cotizaciones` WHERE Key_name = 'idx_cotizaciones_anulada_por';

-- =========================================================
-- 4) Verificar enum estado_pago
--    (en la columna Type debe aparecer: 'anulada')
-- =========================================================
SHOW COLUMNS FROM `cotizaciones` LIKE 'estado_pago';

-- =========================================================
-- 5) Resumen rápido sugerido de interpretación
-- =========================================================
-- - Si un SHOW TABLES LIKE devuelve 0 filas => la tabla falta.
-- - Si un SHOW COLUMNS LIKE devuelve 0 filas => la columna falta.
-- - Si un SHOW INDEX devuelve 0 filas => el índice falta.
-- - Si en estado_pago no aparece 'anulada' dentro del enum => falta el MODIFY COLUMN.
