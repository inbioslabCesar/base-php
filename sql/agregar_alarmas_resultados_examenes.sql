-- Agrega soporte de alarmas por examen en resultados_examenes
-- Ejecutar en el mismo esquema donde corre la app.

ALTER TABLE resultados_examenes
    ADD COLUMN alarma_activa TINYINT(1) NOT NULL DEFAULT 0 AFTER observaciones,
    ADD COLUMN alarma_dias INT NULL AFTER alarma_activa,
    ADD COLUMN alarma_fecha_objetivo DATETIME NULL AFTER alarma_dias,
    ADD COLUMN alarma_estado VARCHAR(20) NULL AFTER alarma_fecha_objetivo,
    ADD COLUMN alarma_ultimo_aviso DATETIME NULL AFTER alarma_estado,
    ADD COLUMN alarma_whatsapp_destino VARCHAR(32) NULL AFTER alarma_ultimo_aviso;

CREATE INDEX idx_resultados_alarmas_estado
    ON resultados_examenes (estado, alarma_activa, alarma_fecha_objetivo);

CREATE INDEX idx_resultados_alarmas_cot
    ON resultados_examenes (id_cotizacion, alarma_activa, estado);
