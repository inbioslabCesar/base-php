-- Migracion: corregir duplicidad de id_parametro en examenes y snapshots
-- Fecha: 2026-05-20
-- Requiere MySQL 8.0+ (ROW_NUMBER)
-- Objetivo: evitar cruce de resultados cuando dos parametros comparten el mismo id_parametro.

-- 0) Verificacion inicial (informativa)
SELECT 'duplicados_en_examenes_antes' AS bloque, COUNT(*) AS total
FROM (
    SELECT e.id AS id_examen, jt.id_parametro
    FROM examenes e
    JOIN JSON_TABLE(
        e.adicional,
        '$[*]' COLUMNS (
            ord FOR ORDINALITY,
            id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
        )
    ) jt
    WHERE e.adicional IS NOT NULL
      AND e.adicional <> ''
      AND jt.id_parametro <> ''
    GROUP BY e.id, jt.id_parametro
    HAVING COUNT(*) > 1
) t;

SELECT 'duplicados_en_snapshot_antes' AS bloque, COUNT(*) AS total
FROM (
    SELECT re.id AS id_resultado, jt.id_parametro
    FROM resultados_examenes re
    JOIN JSON_TABLE(
        re.adicional_snapshot,
        '$[*]' COLUMNS (
            ord FOR ORDINALITY,
            id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
        )
    ) jt
    WHERE re.adicional_snapshot IS NOT NULL
      AND re.adicional_snapshot <> ''
      AND jt.id_parametro <> ''
    GROUP BY re.id, jt.id_parametro
    HAVING COUNT(*) > 1
) t;

-- 1) Corregir duplicados en examenes.adicional
DROP TEMPORARY TABLE IF EXISTS tmp_fix_examenes_ids;
CREATE TEMPORARY TABLE tmp_fix_examenes_ids (
    id_examen INT NOT NULL,
    idx INT NOT NULL,
    nuevo_id VARCHAR(191) NOT NULL,
    PRIMARY KEY (id_examen, idx)
);

INSERT INTO tmp_fix_examenes_ids (id_examen, idx, nuevo_id)
SELECT
    q.id_examen,
    q.idx,
    CONCAT('param_', UNIX_TIMESTAMP(), '_', q.id_examen, '_', q.idx, '_', LPAD(q.rn, 3, '0')) AS nuevo_id
FROM (
    SELECT
        x.id_examen,
        x.idx,
        x.id_parametro,
        ROW_NUMBER() OVER (
            PARTITION BY x.id_examen, x.id_parametro
            ORDER BY x.idx
        ) AS rn
    FROM (
        SELECT
            e.id AS id_examen,
            jt.ord - 1 AS idx,
            jt.id_parametro
        FROM examenes e
        JOIN JSON_TABLE(
            e.adicional,
            '$[*]' COLUMNS (
                ord FOR ORDINALITY,
                id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
            )
        ) jt
        WHERE e.adicional IS NOT NULL
          AND e.adicional <> ''
          AND jt.id_parametro <> ''
    ) x
) q
WHERE q.rn > 1;

DROP PROCEDURE IF EXISTS sp_fix_dup_ids_examenes;
DELIMITER $$
CREATE PROCEDURE sp_fix_dup_ids_examenes()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_examen INT;
    DECLARE v_idx INT;
    DECLARE v_nuevo_id VARCHAR(191);

    DECLARE cur CURSOR FOR
        SELECT id_examen, idx, nuevo_id
        FROM tmp_fix_examenes_ids
        ORDER BY id_examen, idx;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    loop_rows: LOOP
        FETCH cur INTO v_examen, v_idx, v_nuevo_id;
        IF done = 1 THEN
            LEAVE loop_rows;
        END IF;

        SET @json_path = CONCAT('$[', v_idx, '].id_parametro');
        UPDATE examenes
        SET adicional = JSON_SET(adicional, @json_path, v_nuevo_id)
        WHERE id = v_examen;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL sp_fix_dup_ids_examenes();
DROP PROCEDURE IF EXISTS sp_fix_dup_ids_examenes;

-- 2) Corregir duplicados en resultados_examenes.adicional_snapshot
DROP TEMPORARY TABLE IF EXISTS tmp_fix_snapshot_ids;
CREATE TEMPORARY TABLE tmp_fix_snapshot_ids (
    id_resultado INT NOT NULL,
    idx INT NOT NULL,
    nuevo_id VARCHAR(191) NOT NULL,
    PRIMARY KEY (id_resultado, idx)
);

INSERT INTO tmp_fix_snapshot_ids (id_resultado, idx, nuevo_id)
SELECT
    q.id_resultado,
    q.idx,
    CONCAT('param_', UNIX_TIMESTAMP(), '_', q.id_resultado, '_', q.idx, '_', LPAD(q.rn, 3, '0')) AS nuevo_id
FROM (
    SELECT
        x.id_resultado,
        x.idx,
        x.id_parametro,
        ROW_NUMBER() OVER (
            PARTITION BY x.id_resultado, x.id_parametro
            ORDER BY x.idx
        ) AS rn
    FROM (
        SELECT
            re.id AS id_resultado,
            jt.ord - 1 AS idx,
            jt.id_parametro
        FROM resultados_examenes re
        JOIN JSON_TABLE(
            re.adicional_snapshot,
            '$[*]' COLUMNS (
                ord FOR ORDINALITY,
                id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
            )
        ) jt
        WHERE re.adicional_snapshot IS NOT NULL
          AND re.adicional_snapshot <> ''
          AND jt.id_parametro <> ''
    ) x
) q
WHERE q.rn > 1;

DROP PROCEDURE IF EXISTS sp_fix_dup_ids_snapshot;
DELIMITER $$
CREATE PROCEDURE sp_fix_dup_ids_snapshot()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_resultado INT;
    DECLARE v_idx INT;
    DECLARE v_nuevo_id VARCHAR(191);

    DECLARE cur CURSOR FOR
        SELECT id_resultado, idx, nuevo_id
        FROM tmp_fix_snapshot_ids
        ORDER BY id_resultado, idx;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    loop_rows: LOOP
        FETCH cur INTO v_resultado, v_idx, v_nuevo_id;
        IF done = 1 THEN
            LEAVE loop_rows;
        END IF;

        SET @json_path = CONCAT('$[', v_idx, '].id_parametro');
        UPDATE resultados_examenes
        SET adicional_snapshot = JSON_SET(adicional_snapshot, @json_path, v_nuevo_id)
        WHERE id = v_resultado;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL sp_fix_dup_ids_snapshot();
DROP PROCEDURE IF EXISTS sp_fix_dup_ids_snapshot;

-- 3) Verificacion final (informativa)
SELECT 'duplicados_en_examenes_despues' AS bloque, COUNT(*) AS total
FROM (
    SELECT e.id AS id_examen, jt.id_parametro
    FROM examenes e
    JOIN JSON_TABLE(
        e.adicional,
        '$[*]' COLUMNS (
            ord FOR ORDINALITY,
            id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
        )
    ) jt
    WHERE e.adicional IS NOT NULL
      AND e.adicional <> ''
      AND jt.id_parametro <> ''
    GROUP BY e.id, jt.id_parametro
    HAVING COUNT(*) > 1
) t;

SELECT 'duplicados_en_snapshot_despues' AS bloque, COUNT(*) AS total
FROM (
    SELECT re.id AS id_resultado, jt.id_parametro
    FROM resultados_examenes re
    JOIN JSON_TABLE(
        re.adicional_snapshot,
        '$[*]' COLUMNS (
            ord FOR ORDINALITY,
            id_parametro VARCHAR(191) PATH '$.id_parametro' DEFAULT '' ON EMPTY
        )
    ) jt
    WHERE re.adicional_snapshot IS NOT NULL
      AND re.adicional_snapshot <> ''
      AND jt.id_parametro <> ''
    GROUP BY re.id, jt.id_parametro
    HAVING COUNT(*) > 1
) t;

SELECT 'fix_duplicidad_id_parametro_ok' AS estado;
