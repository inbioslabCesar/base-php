-- Habilitar "Particular → Factura" en cotizaciones
--
-- Este script agrega columnas para:
-- - Guardar el tipo de comprobante (boleta/factura) por cotización
-- - Guardar los datos del receptor cuando un PARTICULAR solicita FACTURA (RUC)
--
-- Notas:
-- - Ejecutar una sola vez en tu BD LOCAL y en PRODUCCIÓN.
-- - Si ya existen columnas con estos nombres, el ALTER fallará (en ese caso, omite la línea correspondiente).

ALTER TABLE cotizaciones
  ADD COLUMN comprobante_tipo VARCHAR(10) NULL
  COMMENT 'boleta|factura (si es NULL se infiere por id_empresa)';

ALTER TABLE cotizaciones
  ADD COLUMN receptor_tipo_documento VARCHAR(2) NULL
  COMMENT 'Tipo doc receptor SUNAT (6=RUC, 1=DNI, etc)';

ALTER TABLE cotizaciones
  ADD COLUMN receptor_numero_documento VARCHAR(20) NULL
  COMMENT 'Número de documento del receptor (RUC/DNI)';

ALTER TABLE cotizaciones
  ADD COLUMN receptor_razon_social VARCHAR(255) NULL
  COMMENT 'Razón social del receptor (para factura)';

ALTER TABLE cotizaciones
  ADD COLUMN receptor_direccion VARCHAR(255) NULL
  COMMENT 'Dirección del receptor (opcional)';

-- Opcional (recomendado): índices simples para búsquedas/reportes
-- (Si te da error por duplicado, omite la línea)
CREATE INDEX idx_cotizaciones_comprobante_tipo ON cotizaciones (comprobante_tipo);
CREATE INDEX idx_cotizaciones_receptor_numero_documento ON cotizaciones (receptor_numero_documento);

-- Opcional: backfill para registros antiguos (no es obligatorio)
-- Si no lo ejecutas, el sistema igual funciona porque infiere por id_empresa.
--
-- UPDATE cotizaciones
-- SET comprobante_tipo = CASE
--   WHEN id_empresa IS NOT NULL AND id_empresa > 0 THEN 'factura'
--   ELSE 'boleta'
-- END
-- WHERE comprobante_tipo IS NULL;
