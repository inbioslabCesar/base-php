-- Migracion: Cotizador Express - historial, conversion y plantillas
-- Fecha: 2026-10-14
-- Objetivo:
--   1) cotizaciones_express / cotizaciones_express_items: historial de cotizaciones rapidas
--      (snapshot de precios, folio, vigencia, enlace publico por token, conversion a atencion).
--   2) cotizador_express_plantillas: plantilla editable del mensaje (WhatsApp/copiar).
-- Seguridad: solo CREATE TABLE IF NOT EXISTS. No modifica ni elimina tablas/datos existentes.
-- Las cotizaciones express NO forman parte de `cotizaciones`, por lo tanto NO suman a la deuda de Contabilidad.

CREATE TABLE IF NOT EXISTS cotizaciones_express (
    id INT(11) NOT NULL AUTO_INCREMENT,
    folio VARCHAR(20) DEFAULT NULL,
    token CHAR(40) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'emitida',
    modo_operativo VARCHAR(20) NOT NULL DEFAULT 'PARTICULAR',
    tarifa_tipo VARCHAR(20) NOT NULL DEFAULT 'particular',
    tarifa_id INT(11) DEFAULT NULL,
    tarifa_nombre VARCHAR(150) DEFAULT NULL,
    destinatario_nombre VARCHAR(150) DEFAULT NULL,
    destinatario_telefono VARCHAR(20) DEFAULT NULL,
    cliente_id INT(11) DEFAULT NULL,
    descuento_extra DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vigencia_dias SMALLINT(6) NOT NULL DEFAULT 7,
    vence_en DATE NOT NULL,
    incluir_indicaciones TINYINT(1) NOT NULL DEFAULT 1,
    canales VARCHAR(100) DEFAULT NULL,
    ultimo_envio_en DATETIME DEFAULT NULL,
    creado_por INT(11) DEFAULT NULL,
    rol_creador VARCHAR(30) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    convertida_cotizacion_id INT(11) DEFAULT NULL,
    convertida_en DATETIME DEFAULT NULL,
    anulada_en DATETIME DEFAULT NULL,
    anulada_por INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cotexp_token (token),
    UNIQUE KEY uq_cotexp_folio (folio),
    KEY idx_cotexp_estado_fecha (estado, creado_en),
    KEY idx_cotexp_vence (vence_en),
    KEY idx_cotexp_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cotizaciones_express_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    express_id INT(11) NOT NULL,
    examen_id INT(11) NOT NULL,
    nombre_examen VARCHAR(255) NOT NULL,
    cantidad SMALLINT(6) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_cotexp_items_express (express_id),
    CONSTRAINT fk_cotexp_items_express FOREIGN KEY (express_id) REFERENCES cotizaciones_express (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cotizador_express_plantillas (
    tipo VARCHAR(20) NOT NULL,
    plantilla TEXT NOT NULL,
    actualizado_por INT(11) DEFAULT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificaciones rapidas
SHOW TABLES LIKE 'cotizaciones_express';
SHOW TABLES LIKE 'cotizaciones_express_items';
SHOW TABLES LIKE 'cotizador_express_plantillas';
