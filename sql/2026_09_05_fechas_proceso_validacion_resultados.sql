-- Fase 1: separar fechas clinicas de la fecha comercial de cotizacion.
-- Ejecutar una vez por base de datos.

ALTER TABLE resultados_examenes
    ADD COLUMN IF NOT EXISTS fecha_proceso_en DATETIME NULL AFTER fecha_ingreso,
    ADD COLUMN IF NOT EXISTS fecha_validacion_en DATETIME NULL AFTER fecha_proceso_en,
    ADD COLUMN IF NOT EXISTS validado_por INT NULL AFTER fecha_validacion_en,
    ADD COLUMN IF NOT EXISTS estado_validacion VARCHAR(20) NULL DEFAULT 'pendiente' AFTER validado_por;

ALTER TABLE cotizaciones
    ADD COLUMN IF NOT EXISTS fecha_proceso_inicio DATETIME NULL AFTER fecha,
    ADD COLUMN IF NOT EXISTS fecha_validacion_final DATETIME NULL AFTER fecha_proceso_inicio;

-- Inicializa fecha de proceso para historicos donde hubo captura previa.
UPDATE resultados_examenes
SET fecha_proceso_en = fecha_ingreso
WHERE fecha_proceso_en IS NULL
  AND resultados IS NOT NULL
  AND JSON_VALID(resultados)
  AND JSON_LENGTH(resultados) > 0;

-- Inicializa estado de validacion en registros existentes.
UPDATE resultados_examenes
SET estado_validacion = 'pendiente'
WHERE estado_validacion IS NULL OR TRIM(estado_validacion) = '';

-- Consolida fecha de proceso por cotizacion para historicos.
UPDATE cotizaciones c
JOIN (
    SELECT id_cotizacion, MIN(fecha_proceso_en) AS fecha_min_proceso
    FROM resultados_examenes
    WHERE fecha_proceso_en IS NOT NULL
    GROUP BY id_cotizacion
) x ON x.id_cotizacion = c.id
SET c.fecha_proceso_inicio = COALESCE(c.fecha_proceso_inicio, x.fecha_min_proceso);
