-- Diagnóstico SOLO LECTURA (no modifica datos)
-- Objetivo: detectar posibles cruces entre porcentuales y absolutos
-- por colisión de nombres (ej. SEGMENTADOS vs SEGMENTADOS.)
--
-- Ejecutar en producción (Medditech) sobre la BD activa.
-- Recomendación: correr bloque por bloque.

/* ============================================================
   1) Resumen: cuántos hemogramas tienen campos clave presentes
   ============================================================ */
WITH hemo AS (
    SELECT
        re.id,
        re.id_cliente,
        re.id_cotizacion,
        re.fecha_ingreso,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')) AS wbc_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')) AS seg_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')) AS seg_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')) AS lin_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')) AS lin_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')) AS mon_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')) AS mon_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."ABASTONADOS"')) AS aba_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."ABASTONADOS."')) AS aba_abs_raw
    FROM resultados_examenes re
    WHERE re.id_examen = 151
)
SELECT
    COUNT(*) AS total_hemogramas,
    SUM(seg_pct_raw IS NOT NULL AND seg_pct_raw <> '') AS con_segmentados_pct,
    SUM(seg_abs_raw IS NOT NULL AND seg_abs_raw <> '') AS con_segmentados_abs,
    SUM(lin_pct_raw IS NOT NULL AND lin_pct_raw <> '') AS con_linfocitos_pct,
    SUM(lin_abs_raw IS NOT NULL AND lin_abs_raw <> '') AS con_linfocitos_abs,
    SUM(mon_pct_raw IS NOT NULL AND mon_pct_raw <> '') AS con_monocitos_pct,
    SUM(mon_abs_raw IS NOT NULL AND mon_abs_raw <> '') AS con_monocitos_abs,
    SUM(aba_pct_raw IS NOT NULL AND aba_pct_raw <> '') AS con_abastonados_pct,
    SUM(aba_abs_raw IS NOT NULL AND aba_abs_raw <> '') AS con_abastonados_abs
FROM hemo;

/* ============================================================
   2) Muestra rápida de valores crudos (últimos 30 hemogramas)
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
   3) Sospecha #1: absoluto igual al porcentaje (muy probable cruce)
   ============================================================ */
WITH x AS (
    SELECT
        re.id,
        re.fecha_ingreso,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')) AS wbc_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')) AS seg_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')) AS seg_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')) AS lin_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')) AS lin_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')) AS mon_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')) AS mon_abs_raw
    FROM resultados_examenes re
    WHERE re.id_examen = 151
), n AS (
    SELECT
        id,
        fecha_ingreso,
        CASE WHEN REPLACE(IFNULL(wbc_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(wbc_raw, ',', '') AS DECIMAL(12,4)) END AS wbc,
        CASE WHEN REPLACE(IFNULL(seg_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_pct_raw, ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(seg_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_abs_raw, ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(lin_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_pct_raw, ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(lin_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_abs_raw, ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(mon_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_pct_raw, ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(mon_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_abs_raw, ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM x
)
SELECT
    id,
    fecha_ingreso,
    wbc,
    seg_pct, seg_abs,
    lin_pct, lin_abs,
    mon_pct, mon_abs
FROM n
WHERE
    (seg_pct IS NOT NULL AND seg_abs IS NOT NULL AND ABS(seg_pct - seg_abs) < 0.0001)
 OR (lin_pct IS NOT NULL AND lin_abs IS NOT NULL AND ABS(lin_pct - lin_abs) < 0.0001)
 OR (mon_pct IS NOT NULL AND mon_abs IS NOT NULL AND ABS(mon_pct - mon_abs) < 0.0001)
ORDER BY id DESC;

/* ============================================================
   4) Sospecha #2: absoluto no cuadra con su fórmula
      esperado = WBC * % / 100
   ============================================================ */
WITH x AS (
    SELECT
        re.id,
        re.id_cliente,
        re.id_cotizacion,
        re.fecha_ingreso,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')) AS wbc_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')) AS seg_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')) AS seg_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')) AS lin_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')) AS lin_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')) AS mon_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')) AS mon_abs_raw
    FROM resultados_examenes re
    WHERE re.id_examen = 151
), n AS (
    SELECT
        id,
        id_cliente,
        id_cotizacion,
        fecha_ingreso,
        CASE WHEN REPLACE(IFNULL(wbc_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(wbc_raw, ',', '') AS DECIMAL(12,4)) END AS wbc,
        CASE WHEN REPLACE(IFNULL(seg_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_pct_raw, ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(seg_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_abs_raw, ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(lin_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_pct_raw, ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(lin_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_abs_raw, ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(mon_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_pct_raw, ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(mon_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_abs_raw, ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM x
)
SELECT
    id,
    id_cliente,
    id_cotizacion,
    fecha_ingreso,
    wbc,
    seg_pct,
    seg_abs,
    ROUND((wbc * seg_pct) / 100, 0) AS seg_abs_esperado,
    ABS(seg_abs - ROUND((wbc * seg_pct) / 100, 0)) AS seg_diff,
    lin_pct,
    lin_abs,
    ROUND((wbc * lin_pct) / 100, 0) AS lin_abs_esperado,
    ABS(lin_abs - ROUND((wbc * lin_pct) / 100, 0)) AS lin_diff,
    mon_pct,
    mon_abs,
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
   5) Conteo final de sospechosos (resumen ejecutivo)
   ============================================================ */
WITH x AS (
    SELECT
        re.id,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."R. GLOBULOS BLANCOS"')) AS wbc_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS"')) AS seg_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."SEGMENTADOS."')) AS seg_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS"')) AS lin_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."LINFOCITOS."')) AS lin_abs_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS"')) AS mon_pct_raw,
        JSON_UNQUOTE(JSON_EXTRACT(re.resultados, '$."MONOCITOS."')) AS mon_abs_raw
    FROM resultados_examenes re
    WHERE re.id_examen = 151
), n AS (
    SELECT
        id,
        CASE WHEN REPLACE(IFNULL(wbc_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(wbc_raw, ',', '') AS DECIMAL(12,4)) END AS wbc,
        CASE WHEN REPLACE(IFNULL(seg_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_pct_raw, ',', '') AS DECIMAL(12,4)) END AS seg_pct,
        CASE WHEN REPLACE(IFNULL(seg_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(seg_abs_raw, ',', '') AS DECIMAL(12,4)) END AS seg_abs,
        CASE WHEN REPLACE(IFNULL(lin_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_pct_raw, ',', '') AS DECIMAL(12,4)) END AS lin_pct,
        CASE WHEN REPLACE(IFNULL(lin_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(lin_abs_raw, ',', '') AS DECIMAL(12,4)) END AS lin_abs,
        CASE WHEN REPLACE(IFNULL(mon_pct_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_pct_raw, ',', '') AS DECIMAL(12,4)) END AS mon_pct,
        CASE WHEN REPLACE(IFNULL(mon_abs_raw,''), ',', '') REGEXP '^[0-9]+(\\.[0-9]+)?$'
             THEN CAST(REPLACE(mon_abs_raw, ',', '') AS DECIMAL(12,4)) END AS mon_abs
    FROM x
)
SELECT
    COUNT(*) AS total_hemogramas,
    SUM(
        (seg_pct IS NOT NULL AND seg_abs IS NOT NULL AND ABS(seg_pct - seg_abs) < 0.0001)
        OR (lin_pct IS NOT NULL AND lin_abs IS NOT NULL AND ABS(lin_pct - lin_abs) < 0.0001)
        OR (mon_pct IS NOT NULL AND mon_abs IS NOT NULL AND ABS(mon_pct - mon_abs) < 0.0001)
    ) AS sospecha_igualdad_pct_abs,
    SUM(
        wbc IS NOT NULL AND (
            (seg_pct IS NOT NULL AND seg_abs IS NOT NULL AND ABS(seg_abs - ROUND((wbc * seg_pct) / 100, 0)) > 25)
            OR
            (lin_pct IS NOT NULL AND lin_abs IS NOT NULL AND ABS(lin_abs - ROUND((wbc * lin_pct) / 100, 0)) > 25)
            OR
            (mon_pct IS NOT NULL AND mon_abs IS NOT NULL AND ABS(mon_abs - ROUND((wbc * mon_pct) / 100, 0)) > 25)
        )
    ) AS sospecha_formula_no_cuadra
FROM n;
