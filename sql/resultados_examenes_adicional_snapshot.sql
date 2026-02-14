-- Agrega snapshot del formato (adicional) en resultados_examenes
-- Objetivo: que el render, PDF y % completado de cotizaciones históricas
-- no cambien aunque se edite el examen (examenes.adicional) en el futuro.

-- 1) Agregar columna (si no existe)
ALTER TABLE resultados_examenes
  ADD COLUMN adicional_snapshot LONGTEXT NULL AFTER resultados;

-- 2) Backfill inicial: copiar el adicional actual del examen
-- Solo para filas que aún no tienen snapshot.
UPDATE resultados_examenes re
JOIN examenes e ON e.id = re.id_examen
SET re.adicional_snapshot = e.adicional
WHERE re.adicional_snapshot IS NULL;

-- Opcional: si quieres forzar snapshot incluso cuando está vacío
-- (descomenta si tu tabla usa '' en vez de NULL)
-- UPDATE resultados_examenes re
-- JOIN examenes e ON e.id = re.id_examen
-- SET re.adicional_snapshot = e.adicional
-- WHERE re.adicional_snapshot IS NULL OR re.adicional_snapshot = '';



ALTER TABLE config_empresa
  ADD COLUMN maps_embed TEXT NULL;

  -- Si tu tabla usa utf8mb4, puedes forzarlo así (opcional):
ALTER TABLE config_empresa
  ADD COLUMN maps_embed TEXT
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  NULL;