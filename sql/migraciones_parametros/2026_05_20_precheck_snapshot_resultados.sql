-- Precheck/migracion minima de snapshot para resultados_examenes
-- Fecha: 2026-05-20
-- Uso: ejecutar solo si tu BD aun no tiene la columna adicional_snapshot.

DROP PROCEDURE IF EXISTS sp_precheck_snapshot_resultados;
DELIMITER $$
CREATE PROCEDURE sp_precheck_snapshot_resultados()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'resultados_examenes'
          AND COLUMN_NAME = 'adicional_snapshot'
    ) THEN
        ALTER TABLE resultados_examenes
            ADD COLUMN adicional_snapshot LONGTEXT NULL AFTER resultados;

        UPDATE resultados_examenes re
        JOIN examenes e ON e.id = re.id_examen
        SET re.adicional_snapshot = e.adicional
        WHERE re.adicional_snapshot IS NULL;
    END IF;
END$$
DELIMITER ;

CALL sp_precheck_snapshot_resultados();
DROP PROCEDURE IF EXISTS sp_precheck_snapshot_resultados;

SELECT 'precheck_snapshot_resultados_ok' AS estado;
