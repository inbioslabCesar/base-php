-- Soporte para edad dinamica sin fecha de nacimiento
-- Se guarda la edad manual como referencia temporal para proyectarla en el tiempo.

ALTER TABLE clientes
    ADD COLUMN IF NOT EXISTS edad_referida_valor DECIMAL(10,6) NULL AFTER edad,
    ADD COLUMN IF NOT EXISTS edad_referida_fecha DATETIME NULL AFTER edad_referida_valor;

-- Backfill inicial para pacientes sin fecha de nacimiento y con edad manual.
UPDATE clientes
SET
    edad_referida_valor = CASE
        WHEN edad_referida_valor IS NOT NULL THEN edad_referida_valor
        WHEN TRIM(COALESCE(edad, '')) = '' THEN NULL
        WHEN LOWER(TRIM(COALESCE(edad, ''))) LIKE '%mes%'
            THEN ROUND(CAST(REPLACE(TRIM(COALESCE(edad, '')), ',', '.') AS DECIMAL(10,6)) / 12, 6)
        WHEN LOWER(TRIM(COALESCE(edad, ''))) LIKE '%dia%'
            THEN ROUND(CAST(REPLACE(TRIM(COALESCE(edad, '')), ',', '.') AS DECIMAL(10,6)) / 365.2425, 6)
        WHEN TRIM(COALESCE(edad, '')) REGEXP '[0-9]'
            THEN CAST(REPLACE(TRIM(COALESCE(edad, '')), ',', '.') AS DECIMAL(10,6))
        ELSE NULL
    END,
    edad_referida_fecha = COALESCE(edad_referida_fecha, fecha_registro, NOW())
WHERE (fecha_nacimiento IS NULL OR fecha_nacimiento = '0000-00-00' OR TRIM(fecha_nacimiento) = '')
  AND TRIM(COALESCE(edad, '')) <> '';
