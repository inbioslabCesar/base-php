-- Agrega orden de impresion por examen en resultados_examenes
-- Compatible con MySQL 5.7+ usando INFORMATION_SCHEMA.

DROP PROCEDURE IF EXISTS sp_agregar_orden_impresion_resultados_examenes;
DELIMITER $$
CREATE PROCEDURE sp_agregar_orden_impresion_resultados_examenes()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'resultados_examenes'
          AND COLUMN_NAME = 'orden_impresion'
    ) THEN
        ALTER TABLE resultados_examenes
            ADD COLUMN orden_impresion INT NULL AFTER id_cotizacion;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'resultados_examenes'
          AND INDEX_NAME = 'idx_resultados_cot_orden'
    ) THEN
        CREATE INDEX idx_resultados_cot_orden
            ON resultados_examenes (id_cotizacion, orden_impresion, id);
    END IF;
END$$
DELIMITER ;

CALL sp_agregar_orden_impresion_resultados_examenes();
DROP PROCEDURE IF EXISTS sp_agregar_orden_impresion_resultados_examenes;

-- Backfill basico para historicos
UPDATE resultados_examenes
SET orden_impresion = id
WHERE orden_impresion IS NULL;

SELECT 'migracion_orden_impresion_resultados_examenes_ok' AS estado;
