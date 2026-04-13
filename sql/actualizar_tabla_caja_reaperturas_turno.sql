-- Agrega enlace de reapertura al turno/caja origen para consolidar ingresos por turno
-- Versión ultra-compatible: sin information_schema (apta para hostings restringidos)

DROP PROCEDURE IF EXISTS sp_migrar_caja_reaperturas_turno;
DELIMITER $$
CREATE PROCEDURE sp_migrar_caja_reaperturas_turno()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;

    ALTER TABLE caja_reaperturas ADD COLUMN caja_origen_id INT NULL AFTER estado;
    ALTER TABLE caja_reaperturas ADD COLUMN turno_responsable INT NULL AFTER caja_origen_id;

    ALTER TABLE caja_reaperturas ADD INDEX idx_caja_reaperturas_caja_origen (caja_origen_id);
    ALTER TABLE caja_reaperturas ADD INDEX idx_caja_reaperturas_turno_responsable (turno_responsable);

    -- Backfill idempotente y seguro (sin depender de numero_turno)
    UPDATE caja_reaperturas r
    LEFT JOIN (
        SELECT c1.fecha_operacion, c1.id, 1 AS numero_turno
        FROM cajas c1
        INNER JOIN (
            SELECT fecha_operacion, MAX(fecha_hora_cierre) AS max_cierre
            FROM cajas
            WHERE estado = 'cerrada'
            GROUP BY fecha_operacion
        ) m ON m.fecha_operacion = c1.fecha_operacion AND m.max_cierre = c1.fecha_hora_cierre
    ) u ON u.fecha_operacion = r.fecha_operacion
    SET r.caja_origen_id = COALESCE(r.caja_origen_id, u.id),
        r.turno_responsable = COALESCE(r.turno_responsable, u.numero_turno)
    WHERE r.caja_origen_id IS NULL OR r.turno_responsable IS NULL;
END$$
DELIMITER ;

CALL sp_migrar_caja_reaperturas_turno();
DROP PROCEDURE IF EXISTS sp_migrar_caja_reaperturas_turno;
