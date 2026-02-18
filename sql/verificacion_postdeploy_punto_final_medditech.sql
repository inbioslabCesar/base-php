-- Verificación POST-DEPLOY (solo lectura)
-- Objetivo: confirmar que el sistema distingue correctamente
-- campos porcentuales vs absolutos con punto final.
-- BD: Medditech

/* ============================================================
   A) Vista rápida de hemogramas recientes
   ============================================================ */
SELECT
    re.id,
    re.fecha_ingreso,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')) AS wbc,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')) AS seg_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')) AS seg_abs,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')) AS lin_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')) AS lin_abs,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')) AS mon_pct,
    JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')) AS mon_abs
FROM resultados_examenes re
WHERE re.id_examen = 151
ORDER BY re.id DESC
LIMIT 30;

/* ============================================================
   B) Posible cruce PCT->ABS (absoluto igual al % en valores no triviales)
      Nota: se excluyen ceros para evitar falsos positivos normales.
   ============================================================ */
WITH x AS (
    SELECT
        re.id,
        re.fecha_ingreso,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM resultados_examenes re
    WHERE re.id_examen = 151
      AND re.fecha_ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
)
SELECT
    id,
    fecha_ingreso,
    seg_pct, seg_abs,
    lin_pct, lin_abs,
    mon_pct, mon_abs
FROM x
WHERE
    (seg_pct > 0 AND seg_abs > 0 AND ABS(seg_pct - seg_abs) < 0.0001)
 OR (lin_pct > 0 AND lin_abs > 0 AND ABS(lin_pct - lin_abs) < 0.0001)
 OR (mon_pct > 0 AND mon_abs > 0 AND ABS(mon_pct - mon_abs) < 0.0001)
ORDER BY id DESC;

/* ============================================================
   C) Validación de fórmula ABS = WBC * % / 100 (tolerancia > 25)
   ============================================================ */
WITH n AS (
    SELECT
        re.id,
        re.fecha_ingreso,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')), ',', '') AS DECIMAL(12,4)) END AS wbc,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM resultados_examenes re
    WHERE re.id_examen = 151
      AND re.fecha_ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
)
SELECT
    id,
    fecha_ingreso,
    wbc,
    seg_pct, seg_abs,
    ROUND((wbc * seg_pct) / 100, 0) AS seg_abs_esperado,
    ABS(seg_abs - ROUND((wbc * seg_pct) / 100, 0)) AS seg_diff,
    lin_pct, lin_abs,
    ROUND((wbc * lin_pct) / 100, 0) AS lin_abs_esperado,
    ABS(lin_abs - ROUND((wbc * lin_pct) / 100, 0)) AS lin_diff,
    mon_pct, mon_abs,
    ROUND((wbc * mon_pct) / 100, 0) AS mon_abs_esperado,
    ABS(mon_abs - ROUND((wbc * mon_pct) / 100, 0)) AS mon_diff
FROM n
WHERE
    wbc IS NOT NULL
    AND (
        (seg_pct IS NOT NULL AND seg_abs IS NOT NULL AND ABS(seg_abs - ROUND((wbc * seg_pct) / 100, 0)) > 25)
        OR
        (lin_pct IS NOT NULL AND lin_abs IS NOT NULL AND ABS(lin_abs - ROUND((wbc * lin_pct) / 100, 0)) > 25)
        OR
        (mon_pct IS NOT NULL AND mon_abs IS NOT NULL AND ABS(mon_abs - ROUND((wbc * mon_pct) / 100, 0)) > 25)
    )
ORDER BY id DESC;

/* ============================================================
   D) Resumen ejecutivo rápido
   ============================================================ */
WITH n AS (
    SELECT
        re.id,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')), ',', '') AS DECIMAL(12,4)) END AS wbc,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')), ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')), ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')), ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')), ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM resultados_examenes re
    WHERE re.id_examen = 151
      AND re.fecha_ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
)
SELECT
    COUNT(*) AS total_ultimos_30d,
    SUM(
        (seg_pct > 0 AND seg_abs > 0 AND ABS(seg_pct - seg_abs) < 0.0001)
        OR
        (lin_pct > 0 AND lin_abs > 0 AND ABS(lin_pct - lin_abs) < 0.0001)
        OR
        (mon_pct > 0 AND mon_abs > 0 AND ABS(mon_pct - mon_abs) < 0.0001)
    ) AS posibles_cruces_pct_abs,
    SUM(
        wbc IS NOT NULL AND (
            (seg_pct IS NOT NULL AND seg_abs IS NOT NULL AND ABS(seg_abs - ROUND((wbc * seg_pct) / 100, 0)) > 25)
            OR
            (lin_pct IS NOT NULL AND lin_abs IS NOT NULL AND ABS(lin_abs - ROUND((wbc * lin_pct) / 100, 0)) > 25)
            OR
            (mon_pct IS NOT NULL AND mon_abs IS NOT NULL AND ABS(mon_abs - ROUND((wbc * mon_pct) / 100, 0)) > 25)
        )
    ) AS no_cuadra_formula
FROM n;
