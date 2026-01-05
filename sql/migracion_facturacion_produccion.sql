-- Migración para PRODUCCIÓN (Hostinger) - Flujo de Facturación/CPE
-- Fecha: 2026-01-04
--
-- Objetivo:
-- 1) Soportar el flag "Solo Ticket" vs CPE (emitir_comprobante)
-- 2) Soportar "Particular -> Factura" (RUC) guardando receptor_* y comprobante_tipo
-- 3) Permitir estado_pago = 'abonado' (evita errores por ENUM)
--
-- Nota:
-- - Este proyecto guarda el estado de facturación (remote_id, status, rutas xml/cdr/pdf)
--   en archivos bajo tmp/facturacion/ (NO en BD). En hosting asegúrate que /tmp sea escribible.
--
-- Este script es idempotente: solo aplica cambios si no existen.

SET @db := DATABASE();

-- -----------------------------
-- 1) cotizaciones.emitir_comprobante
-- -----------------------------
SELECT COUNT(*) INTO @col_emitir
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'emitir_comprobante';

SET @sql := IF(
  @col_emitir = 0,
  'ALTER TABLE cotizaciones ADD COLUMN emitir_comprobante TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT "OK: cotizaciones.emitir_comprobante ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 2) Particular -> Factura: comprobante_tipo + receptor_*
-- -----------------------------
SELECT COUNT(*) INTO @col_comp_tipo
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'comprobante_tipo';

SET @sql := IF(
  @col_comp_tipo = 0,
  'ALTER TABLE cotizaciones ADD COLUMN comprobante_tipo VARCHAR(10) NULL COMMENT "boleta|factura (si es NULL se infiere por id_empresa)"',
  'SELECT "OK: cotizaciones.comprobante_tipo ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_receptor_td
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'receptor_tipo_documento';

SET @sql := IF(
  @col_receptor_td = 0,
  'ALTER TABLE cotizaciones ADD COLUMN receptor_tipo_documento VARCHAR(2) NULL COMMENT "Tipo doc receptor SUNAT (6=RUC, 1=DNI, etc)"',
  'SELECT "OK: cotizaciones.receptor_tipo_documento ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_receptor_nd
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'receptor_numero_documento';

SET @sql := IF(
  @col_receptor_nd = 0,
  'ALTER TABLE cotizaciones ADD COLUMN receptor_numero_documento VARCHAR(20) NULL COMMENT "Número de documento del receptor (RUC/DNI)"',
  'SELECT "OK: cotizaciones.receptor_numero_documento ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_receptor_rs
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'receptor_razon_social';

SET @sql := IF(
  @col_receptor_rs = 0,
  'ALTER TABLE cotizaciones ADD COLUMN receptor_razon_social VARCHAR(255) NULL COMMENT "Razón social del receptor (para factura)"',
  'SELECT "OK: cotizaciones.receptor_razon_social ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_receptor_dir
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'receptor_direccion';

SET @sql := IF(
  @col_receptor_dir = 0,
  'ALTER TABLE cotizaciones ADD COLUMN receptor_direccion VARCHAR(255) NULL COMMENT "Dirección del receptor (opcional)"',
  'SELECT "OK: cotizaciones.receptor_direccion ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 3) estado_pago: permitir 'abonado'
-- En producción (dump u330560936_laboratorio) está como ENUM('pendiente','pagado')
-- El sistema ahora usa: pendiente | abonado | pagado
-- -----------------------------
SELECT COLUMN_TYPE INTO @estado_pago_type
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'estado_pago'
LIMIT 1;

SET @needs_estado_patch := IF(@estado_pago_type IS NULL, 0, IF(@estado_pago_type LIKE '%abonado%', 0, 1));

SET @sql := IF(
  @needs_estado_patch = 1,
  'ALTER TABLE cotizaciones MODIFY COLUMN estado_pago ENUM(\'pendiente\',\'abonado\',\'pagado\') DEFAULT \'pendiente\'',
  'SELECT "OK: cotizaciones.estado_pago ya soporta abonado"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 4) Índices (opcionales/recomendados)
-- -----------------------------
SELECT COUNT(*) INTO @idx_comp
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND INDEX_NAME = 'idx_cotizaciones_comprobante_tipo';

SET @sql := IF(
  @idx_comp = 0,
  'CREATE INDEX idx_cotizaciones_comprobante_tipo ON cotizaciones (comprobante_tipo)',
  'SELECT "OK: idx_cotizaciones_comprobante_tipo ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @idx_receptor
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND INDEX_NAME = 'idx_cotizaciones_receptor_numero_documento';

SET @sql := IF(
  @idx_receptor = 0,
  'CREATE INDEX idx_cotizaciones_receptor_numero_documento ON cotizaciones (receptor_numero_documento)',
  'SELECT "OK: idx_cotizaciones_receptor_numero_documento ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Fin
SELECT 'Migración de facturación aplicada.' AS result;
