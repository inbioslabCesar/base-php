-- Tabla para solicitudes y aprobaciones de reapertura extraordinaria de caja
CREATE TABLE IF NOT EXISTS caja_reaperturas (
    id INT NOT NULL AUTO_INCREMENT,
    fecha_operacion DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    caja_origen_id INT NULL,
    turno_responsable INT NULL,
    motivo_solicitud VARCHAR(255) NOT NULL,
    solicitado_por_id INT NOT NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aprobado_por_id INT NULL,
    fecha_aprobacion DATETIME NULL,
    caja_reabierta_id INT NULL,
    observacion_aprobacion VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_caja_reaperturas_fecha_estado (fecha_operacion, estado),
    KEY idx_caja_reaperturas_caja_origen (caja_origen_id),
    KEY idx_caja_reaperturas_turno_responsable (turno_responsable),
    KEY idx_caja_reaperturas_solicitante (solicitado_por_id),
    KEY idx_caja_reaperturas_aprobador (aprobado_por_id),
    KEY idx_caja_reaperturas_caja (caja_reabierta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
