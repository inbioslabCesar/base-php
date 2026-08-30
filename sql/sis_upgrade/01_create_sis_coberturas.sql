-- Fase 1 SIS / Convenios
-- Nueva tabla aislada para datos de cobertura SIS.

CREATE TABLE IF NOT EXISTS sis_coberturas (
    id INT NOT NULL AUTO_INCREMENT,
    cotizacion_id INT NOT NULL,
    cliente_id INT NOT NULL,
    numero_afiliacion VARCHAR(80) NULL,
    numero_autorizacion VARCHAR(80) NULL,
    numero_fua VARCHAR(80) NULL,
    aseguradora VARCHAR(150) NULL,
    plan VARCHAR(150) NULL,
    estado_validacion ENUM('pendiente', 'autorizada', 'observada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    monto_atencion DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observaciones TEXT NULL,
    creado_por INT NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sis_coberturas_cotizacion_id (cotizacion_id),
    KEY idx_sis_coberturas_cliente_id (cliente_id),
    KEY idx_sis_coberturas_numero_autorizacion (numero_autorizacion),
    CONSTRAINT fk_sis_coberturas_cotizacion
        FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sis_coberturas_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
