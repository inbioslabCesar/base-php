-- Tablas para apertura/cierre de caja (MVP)
-- Ejecutar manualmente en producción antes de usar la funcionalidad de caja

CREATE TABLE IF NOT EXISTS cajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_operacion DATE NOT NULL,
    numero_turno TINYINT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',

    usuario_apertura_id INT NOT NULL,
    fecha_hora_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacion_apertura TEXT NULL,

    usuario_cierre_id INT NULL,
    fecha_hora_cierre DATETIME NULL,
    monto_contado_efectivo DECIMAL(10,2) NULL,

    ingresos_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    egresos_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    caja_teorica_efectivo DECIMAL(10,2) NULL,
    diferencia_efectivo DECIMAL(10,2) NULL,
    observacion_cierre TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cajas_estado (estado),
    INDEX idx_cajas_fecha_operacion (fecha_operacion),
    UNIQUE KEY uk_cajas_fecha_turno (fecha_operacion, numero_turno),
    INDEX idx_cajas_apertura (fecha_hora_apertura),
    INDEX idx_cajas_usuario_apertura (usuario_apertura_id),
    INDEX idx_cajas_usuario_cierre (usuario_cierre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS caja_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caja_id INT NOT NULL,
    tipo ENUM('ingreso', 'egreso', 'ajuste') NOT NULL,
    origen ENUM('pago', 'egreso_manual', 'apertura', 'cierre', 'ajuste_manual', 'otro') NOT NULL DEFAULT 'otro',
    metodo_pago VARCHAR(50) NOT NULL DEFAULT 'efectivo',
    monto DECIMAL(10,2) NOT NULL,
    afecta_efectivo TINYINT(1) NOT NULL DEFAULT 1,

    referencia_tipo VARCHAR(50) NULL,
    referencia_id INT NULL,
    descripcion VARCHAR(255) NULL,

    usuario_id INT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_cmov_caja (caja_id),
    INDEX idx_cmov_fecha (fecha_hora),
    INDEX idx_cmov_metodo (metodo_pago),
    INDEX idx_cmov_ref (referencia_tipo, referencia_id),

    CONSTRAINT fk_caja_movimientos_caja
        FOREIGN KEY (caja_id) REFERENCES cajas(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
