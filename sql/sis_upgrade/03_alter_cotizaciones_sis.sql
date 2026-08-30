-- Fase 1 SIS / Convenios
-- Marcador de atención SIS y vínculo opcional con cobertura.

ALTER TABLE cotizaciones
    ADD COLUMN es_sis TINYINT(1) NOT NULL DEFAULT 0 AFTER descuento_aplicado,
    ADD COLUMN sis_cobertura_id INT NULL AFTER es_sis,
    ADD COLUMN sis_monto_atencion DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER sis_cobertura_id,
    ADD COLUMN sis_registrado_en DATETIME NULL AFTER sis_monto_atencion,
    ADD COLUMN sis_registrado_por INT NULL AFTER sis_registrado_en;

CREATE INDEX idx_cotizaciones_es_sis ON cotizaciones (es_sis);
