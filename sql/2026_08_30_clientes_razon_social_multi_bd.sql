-- Migracion multi-BD: agregar soporte de razon_social en clientes.
-- Fecha: 2026-08-30
-- Objetivo: habilitar modelo formal para pacientes/entidades con RUC.
-- Uso: ejecutar manualmente en CADA base de datos de cada laboratorio.

-- 1) Validar que existe tabla clientes.
SHOW TABLES LIKE 'clientes';

-- 2) Agregar columna razon_social si no existe.
-- Nota: esta sintaxis funciona en MariaDB modernas y MySQL recientes.
ALTER TABLE clientes
ADD COLUMN IF NOT EXISTS razon_social VARCHAR(255) NULL AFTER apellido;

-- 3) Backfill opcional para registros ya guardados como tipo_documento = ruc.
--    Si razon_social esta vacia, se llena con nombre + apellido.
UPDATE clientes
SET razon_social = TRIM(CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellido, '')))
WHERE LOWER(COALESCE(tipo_documento, '')) = 'ruc'
  AND (razon_social IS NULL OR TRIM(razon_social) = '');

-- 4) Verificacion post-migracion.
SHOW COLUMNS FROM clientes LIKE 'razon_social';

-- 5) Conteo de clientes RUC sin razon social (debe tender a 0 tras normalizacion operativa).
SELECT COUNT(*) AS clientes_ruc_sin_razon_social
FROM clientes
WHERE LOWER(COALESCE(tipo_documento, '')) = 'ruc'
  AND (razon_social IS NULL OR TRIM(razon_social) = '');
