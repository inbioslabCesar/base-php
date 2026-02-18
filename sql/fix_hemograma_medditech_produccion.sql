-- Corrección BD-only para Medditech: hemograma id_examen=151
-- Problema: campos porcentuales guardados con valores absolutos (miles)
-- Motor objetivo: MySQL 8+

-- =====================================================
-- 1) PARÁMETROS
-- =====================================================
SET @EXAMEN_HEMOGRAMA := 151;

-- Claves JSON (según plantilla actual de Medditech)
SET @K_WBC            := '$.id_parametro_param_1766514051901_291093'; -- R. GLOBULOS BLANCOS

SET @K_ABA_PCT        := '$.id_parametro_param_1766514051902_172279';
SET @K_SEG_PCT        := '$.id_parametro_param_1766514051902_619431';
SET @K_LIN_PCT        := '$.id_parametro_param_1766514051902_206536';
SET @K_MON_PCT        := '$.id_parametro_param_1766514051902_330850';
SET @K_EOS_PCT        := '$.id_parametro_param_1766514051902_42';
SET @K_BAS_PCT        := '$.id_parametro_param_1766514051902_506710';

SET @K_ABA_ABS        := '$.id_parametro_param_1766514051902_643234';
SET @K_SEG_ABS        := '$.id_parametro_param_1766514051902_793326';
SET @K_LIN_ABS        := '$.id_parametro_param_1766514051902_866166';
SET @K_MON_ABS        := '$.id_parametro_param_1766514051902_77809';
SET @K_EOS_ABS        := '$.id_parametro_param_1766514051902_602896';
SET @K_BAS_ABS        := '$.id_parametro_param_1766514051902_535976';

-- =====================================================
-- 2) AUDITORÍA PREVIA (solo lectura)
--    Muestra filas sospechosas y el porcentaje esperado
-- =====================================================
SELECT
    re.id,
    re.id_cliente,
    re.id_cotizacion,
    re.fecha_ingreso,
    CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) AS wbc,

    CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) AS seg_pct_guardado,
    CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_ABS)), ''), ',', '') AS DECIMAL(12,3)) AS seg_abs,
    ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_ABS)), ''), ',', '') AS DECIMAL(12,3))
         /
         NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)), 0)
        ) * 100,
    0) AS seg_pct_esperado,

    CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) AS lin_pct_guardado,
    CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_ABS)), ''), ',', '') AS DECIMAL(12,3)) AS lin_abs,
    ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_ABS)), ''), ',', '') AS DECIMAL(12,3))
         /
         NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)), 0)
        ) * 100,
    0) AS lin_pct_esperado
FROM resultados_examenes re
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND (
        CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
  )
ORDER BY re.id;

-- =====================================================
-- 6) RECUPERACIÓN SI QUEDARON NULL (ejecutar si pasó)
--    Este bloque usa el BACKUP ORIGINAL y reescribe resultados
--    calculando % correcto desde los valores mal guardados.
-- =====================================================

-- 6.1 Verificar filas afectadas en backup
SELECT id, id_cliente, id_cotizacion, fecha_ingreso
FROM resultados_examenes_backup_fix_hemograma_20260217
ORDER BY id;

-- 6.2 Recuperar y corregir desde backup en una sola pasada
UPDATE resultados_examenes re
JOIN resultados_examenes_backup_fix_hemograma_20260217 b ON b.id = re.id
SET re.resultados = JSON_SET(
  b.resultados,
  @K_ABA_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR),
  @K_SEG_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR),
  @K_LIN_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR),
  @K_MON_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR),
  @K_EOS_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR),
  @K_BAS_PCT, CAST(
    CASE
      WHEN CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0
       AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
      THEN ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3))
         /
         CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3))) * 100,
      0)
      ELSE CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(b.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3))
    END
  AS CHAR)
)
WHERE re.id_examen = @EXAMEN_HEMOGRAMA;

SELECT ROW_COUNT() AS filas_recuperadas_y_corregidas;

-- 6.3 Validación rápida de recuperación
SELECT
  re.id,
  re.id_cliente,
  re.id_cotizacion,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)) AS seg_pct,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)) AS lin_pct,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)) AS mon_pct,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)) AS eos_pct,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)) AS bas_pct,
  JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)) AS wbc
FROM resultados_examenes re
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND re.id IN (
    SELECT b.id
    FROM resultados_examenes_backup_fix_hemograma_20260217 b
  )
ORDER BY re.id;

-- Conteo de afectados
SELECT COUNT(*) AS total_afectados
FROM resultados_examenes re
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND (
        CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
  );

-- =====================================================
-- 3) RESPALDO DE FILAS AFECTADAS
-- =====================================================
CREATE TABLE IF NOT EXISTS resultados_examenes_backup_fix_hemograma_20260217 AS
SELECT *
FROM resultados_examenes re
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND (
        CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
  );

-- =====================================================
-- 4) UPDATE CORRECTIVO
--    Recalcula % = (ABS / WBC) * 100 para cada subgrupo
-- =====================================================
UPDATE resultados_examenes re
SET re.resultados = JSON_SET(
    re.resultados,
    @K_ABA_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR),
    @K_SEG_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR),
    @K_LIN_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR),
    @K_MON_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR),
    @K_EOS_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR),
    @K_BAS_PCT, CAST(ROUND(
        (CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_ABS)), ''), ',', '') AS DECIMAL(12,3))
         / NULLIF(CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)),0)) * 100, 0
    ) AS CHAR)
)
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND (
        CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
     OR CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)), ''), ',', '') AS DECIMAL(12,3)) > 100
  )
  AND CAST(REPLACE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)), ''), ',', '') AS DECIMAL(12,3)) > 0;

SELECT ROW_COUNT() AS filas_actualizadas;

-- =====================================================
-- 5) VALIDACIÓN POST-UPDATE
-- =====================================================
SELECT
    re.id,
    re.id_cliente,
    re.id_cotizacion,
    re.fecha_ingreso,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_ABA_PCT)) AS aba_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_PCT)) AS seg_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_PCT)) AS lin_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_MON_PCT)) AS mon_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_EOS_PCT)) AS eos_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_BAS_PCT)) AS bas_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_SEG_ABS)) AS seg_abs,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_LIN_ABS)) AS lin_abs,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, @K_WBC)) AS wbc
FROM resultados_examenes re
WHERE re.id_examen = @EXAMEN_HEMOGRAMA
  AND re.id IN (
      SELECT b.id
      FROM resultados_examenes_backup_fix_hemograma_20260217 b
  )
ORDER BY re.id;
