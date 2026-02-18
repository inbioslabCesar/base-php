-- Inventario Interno (Fase 1)
-- Configuración de receta de consumo por examen + base para transferencias/consumos

CREATE TABLE IF NOT EXISTS inventario_examen_recetas (
    id INT NOT NULL AUTO_INCREMENT,
    id_examen INT NOT NULL,
    item_id INT NOT NULL,
    cantidad_por_prueba DECIMAL(12,4) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    observacion VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_inventario_receta_examen_item (id_examen, item_id),
    KEY idx_inventario_receta_examen (id_examen),
    KEY idx_inventario_receta_item (item_id),
    KEY idx_inventario_receta_activo (activo),
    CONSTRAINT fk_inventario_receta_item FOREIGN KEY (item_id) REFERENCES inventario_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventario_transferencias (
    id INT NOT NULL AUTO_INCREMENT,
    origen VARCHAR(50) NOT NULL DEFAULT 'almacen_principal',
    destino VARCHAR(50) NOT NULL DEFAULT 'laboratorio',
    usuario_id INT NULL,
    observacion VARCHAR(255) NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventario_transferencias_fecha (fecha_hora),
    KEY idx_inventario_transferencias_destino (destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventario_transferencias_detalle (
    id INT NOT NULL AUTO_INCREMENT,
    transferencia_id INT NOT NULL,
    item_id INT NOT NULL,
    cantidad DECIMAL(12,4) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventario_transfer_det_transf (transferencia_id),
    KEY idx_inventario_transfer_det_item (item_id),
    CONSTRAINT fk_inventario_transfer_det_transferencia FOREIGN KEY (transferencia_id) REFERENCES inventario_transferencias (id) ON DELETE CASCADE,
    CONSTRAINT fk_inventario_transfer_det_item FOREIGN KEY (item_id) REFERENCES inventario_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventario_consumos_examen (
    id INT NOT NULL AUTO_INCREMENT,
    id_cotizacion INT NOT NULL,
    id_examen INT NOT NULL,
    item_id INT NOT NULL,
    cantidad_consumida DECIMAL(12,4) NOT NULL,
    origen_evento VARCHAR(30) NOT NULL DEFAULT 'resultado',
    estado VARCHAR(20) NOT NULL DEFAULT 'aplicado',
    usuario_id INT NULL,
    observacion VARCHAR(255) NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_consumo_evento (id_cotizacion, id_examen, item_id, origen_evento),
    KEY idx_inventario_consumo_cotizacion (id_cotizacion),
    KEY idx_inventario_consumo_examen (id_examen),
    KEY idx_inventario_consumo_item (item_id),
    KEY idx_inventario_consumo_fecha (fecha_hora),
    CONSTRAINT fk_inventario_consumo_item FOREIGN KEY (item_id) REFERENCES inventario_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
