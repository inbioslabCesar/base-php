-- Verifica tablas candidatas no usadas y genera DROP seguro (solo vacías y sin FK entrantes)
-- Ejecutar en la BD de desarrollo correcta (usa DATABASE() actual).

SET @schema := DATABASE();

DROP TEMPORARY TABLE IF EXISTS tmp_candidates;
CREATE TEMPORARY TABLE tmp_candidates (
  table_name VARCHAR(128) PRIMARY KEY
);

INSERT INTO tmp_candidates (table_name) VALUES
  ('examenes_cliente'),
  ('examenes_convenio'),
  ('examenes_empresa'),
  ('examenes_promocion'),
  ('promociones_empresa'),
  ('promociones_examen'),
  ('resultados');

DROP TEMPORARY TABLE IF EXISTS tmp_counts;
CREATE TEMPORARY TABLE tmp_counts (
  table_name VARCHAR(128) PRIMARY KEY,
  row_count BIGINT NULL
);

-- Obtiene conteo exacto por tabla existente
SET @tbl := NULL;
SET @sql := NULL;

DROP PROCEDURE IF EXISTS sp_fill_tmp_counts;
DELIMITER $$
CREATE PROCEDURE sp_fill_tmp_counts()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE v_table VARCHAR(128);

  DECLARE cur CURSOR FOR
    SELECT c.table_name
    FROM tmp_candidates c
    INNER JOIN information_schema.tables t
      ON t.table_schema = @schema
     AND t.table_name = c.table_name
     AND t.table_type = 'BASE TABLE';

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_table;
    IF done = 1 THEN
      LEAVE read_loop;
    END IF;

    SET @sql = CONCAT(
      'INSERT INTO tmp_counts(table_name, row_count) ',
      'SELECT ''', v_table, ''', COUNT(*) FROM `', @schema, '`.`', v_table, '`'
    );

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END LOOP;

  CLOSE cur;
END$$
DELIMITER ;

CALL sp_fill_tmp_counts();
DROP PROCEDURE IF EXISTS sp_fill_tmp_counts;

-- Resumen: existencia, filas exactas, FK entrantes/salientes
SELECT
  c.table_name,
  CASE WHEN t.table_name IS NULL THEN 'NO' ELSE 'SI' END AS existe,
  tc.row_count,
  (
    SELECT COUNT(*)
    FROM information_schema.key_column_usage k
    WHERE k.table_schema = @schema
      AND k.referenced_table_schema = @schema
      AND k.referenced_table_name = c.table_name
  ) AS fk_entrantes,
  (
    SELECT COUNT(*)
    FROM information_schema.key_column_usage k
    WHERE k.table_schema = @schema
      AND k.table_name = c.table_name
      AND k.referenced_table_name IS NOT NULL
  ) AS fk_salientes,
  CASE
    WHEN t.table_name IS NULL THEN 'NO_EXISTE'
    WHEN COALESCE(tc.row_count, 0) > 0 THEN 'NO_ELIMINAR_CON_DATOS'
    WHEN (
      SELECT COUNT(*)
      FROM information_schema.key_column_usage k
      WHERE k.table_schema = @schema
        AND k.referenced_table_schema = @schema
        AND k.referenced_table_name = c.table_name
    ) > 0 THEN 'NO_ELIMINAR_REFERENCIADA'
    ELSE 'CANDIDATA_DROP'
  END AS estado
FROM tmp_candidates c
LEFT JOIN information_schema.tables t
  ON t.table_schema = @schema
 AND t.table_name = c.table_name
 AND t.table_type = 'BASE TABLE'
LEFT JOIN tmp_counts tc
  ON tc.table_name = c.table_name
ORDER BY c.table_name;

-- SQL sugerido para ejecutar manualmente (solo candidatas seguras)
SELECT CONCAT('DROP TABLE `', c.table_name, '`;') AS drop_sql
FROM tmp_candidates c
INNER JOIN information_schema.tables t
  ON t.table_schema = @schema
 AND t.table_name = c.table_name
 AND t.table_type = 'BASE TABLE'
LEFT JOIN tmp_counts tc
  ON tc.table_name = c.table_name
WHERE COALESCE(tc.row_count, 0) = 0
  AND (
    SELECT COUNT(*)
    FROM information_schema.key_column_usage k
    WHERE k.table_schema = @schema
      AND k.referenced_table_schema = @schema
      AND k.referenced_table_name = c.table_name
  ) = 0
ORDER BY c.table_name;

-- Limpieza opcional
-- DROP TEMPORARY TABLE IF EXISTS tmp_counts;
-- DROP TEMPORARY TABLE IF EXISTS tmp_candidates;
