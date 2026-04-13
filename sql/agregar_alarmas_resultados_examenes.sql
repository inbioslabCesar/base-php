-- Agrega soporte de alarmas por examen en resultados_examenes
-- Versión compatible con hosting compartido (sin INFORMATION_SCHEMA).

DROP PROCEDURE IF EXISTS sp_migracion_alarmas_resultados_examenes;
DELIMITER $$

CREATE PROCEDURE sp_migracion_alarmas_resultados_examenes()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END; -- Columna duplicada
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END; -- Índice duplicado

    ALTER TABLE resultados_examenes
        ADD COLUMN IF NOT EXISTS alarma_activa TINYINT(1) NOT NULL DEFAULT 0 AFTER observaciones,
        ADD COLUMN IF NOT EXISTS alarma_dias INT NULL AFTER alarma_activa,
        ADD COLUMN IF NOT EXISTS alarma_fecha_objetivo DATETIME NULL AFTER alarma_dias,
        ADD COLUMN IF NOT EXISTS alarma_estado VARCHAR(20) NULL AFTER alarma_fecha_objetivo,
        ADD COLUMN IF NOT EXISTS alarma_ultimo_aviso DATETIME NULL AFTER alarma_estado,
        ADD COLUMN IF NOT EXISTS alarma_whatsapp_destino VARCHAR(32) NULL AFTER alarma_ultimo_aviso;

    CREATE INDEX idx_resultados_alarmas_estado
        ON resultados_examenes (estado, alarma_activa, alarma_fecha_objetivo);

    CREATE INDEX idx_resultados_alarmas_cot
        ON resultados_examenes (id_cotizacion, alarma_activa, estado);
END$$

DELIMITER ;

CALL sp_migracion_alarmas_resultados_examenes();
DROP PROCEDURE IF EXISTS sp_migracion_alarmas_resultados_examenes;

-- Verificación rápida (no requiere INFORMATION_SCHEMA)
SHOW COLUMNS FROM resultados_examenes LIKE 'alarma_%';
SHOW INDEX FROM resultados_examenes WHERE Key_name IN ('idx_resultados_alarmas_estado', 'idx_resultados_alarmas_cot');
SELECT 'migracion_alarmas_resultados_examenes_ok' AS estado;
