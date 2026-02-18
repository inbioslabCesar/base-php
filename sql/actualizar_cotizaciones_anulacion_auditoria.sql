-- Agrega trazabilidad de anulación en cotizaciones
-- Ejecutar una sola vez en la base activa

-- IMPORTANTE:
-- 1) Ejecuta cada sentencia una por una.
-- 2) Si aparece "Duplicate column name" o "Duplicate key name", ignora ese paso.

ALTER TABLE cotizaciones ADD COLUMN anulada_at DATETIME NULL;
ALTER TABLE cotizaciones ADD COLUMN anulada_por INT NULL;
ALTER TABLE cotizaciones ADD COLUMN anulado_motivo VARCHAR(255) NULL;

-- Índices opcionales para auditoría
CREATE INDEX idx_cotizaciones_anulada_at ON cotizaciones (anulada_at);
CREATE INDEX idx_cotizaciones_anulada_por ON cotizaciones (anulada_por);
