-- Sincroniza PRODUCCION con estructura de DESARROLLO (15-02-2026)
-- Alcance: agregar faltantes detectados (sin eliminar tablas/columnas existentes)
-- Fuente comparada:
--   - Desarrollo: sql/laboratorio (16).sql
--   - Producción: sql/u330560936_laboratorio (9).sql

START TRANSACTION;

-- =========================================================
-- 1) Tabla cotizaciones: anulación segura + estado_pago
-- =========================================================
ALTER TABLE `cotizaciones`
  MODIFY COLUMN `estado_pago` enum('pendiente','abonado','pagado','anulada') DEFAULT 'pendiente';

SET @sql_add_cot_anulada_at := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'cotizaciones'
        AND COLUMN_NAME = 'anulada_at'
    ),
    'SELECT 1',
    'ALTER TABLE cotizaciones ADD COLUMN anulada_at datetime DEFAULT NULL'
  )
);
PREPARE stmt_add_cot_anulada_at FROM @sql_add_cot_anulada_at;
EXECUTE stmt_add_cot_anulada_at;
DEALLOCATE PREPARE stmt_add_cot_anulada_at;

SET @sql_add_cot_anulada_por := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'cotizaciones'
        AND COLUMN_NAME = 'anulada_por'
    ),
    'SELECT 1',
    'ALTER TABLE cotizaciones ADD COLUMN anulada_por int DEFAULT NULL'
  )
);
PREPARE stmt_add_cot_anulada_por FROM @sql_add_cot_anulada_por;
EXECUTE stmt_add_cot_anulada_por;
DEALLOCATE PREPARE stmt_add_cot_anulada_por;

SET @sql_add_cot_anulado_motivo := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'cotizaciones'
        AND COLUMN_NAME = 'anulado_motivo'
    ),
    'SELECT 1',
    'ALTER TABLE cotizaciones ADD COLUMN anulado_motivo varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL'
  )
);
PREPARE stmt_add_cot_anulado_motivo FROM @sql_add_cot_anulado_motivo;
EXECUTE stmt_add_cot_anulado_motivo;
DEALLOCATE PREPARE stmt_add_cot_anulado_motivo;

SET @sql_add_idx_cot_anulada_at := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'cotizaciones'
        AND INDEX_NAME = 'idx_cotizaciones_anulada_at'
    ),
    'SELECT 1',
    'ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_anulada_at (anulada_at)'
  )
);
PREPARE stmt_add_idx_cot_anulada_at FROM @sql_add_idx_cot_anulada_at;
EXECUTE stmt_add_idx_cot_anulada_at;
DEALLOCATE PREPARE stmt_add_idx_cot_anulada_at;

SET @sql_add_idx_cot_anulada_por := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'cotizaciones'
        AND INDEX_NAME = 'idx_cotizaciones_anulada_por'
    ),
    'SELECT 1',
    'ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_anulada_por (anulada_por)'
  )
);
PREPARE stmt_add_idx_cot_anulada_por FROM @sql_add_idx_cot_anulada_por;
EXECUTE stmt_add_idx_cot_anulada_por;
DEALLOCATE PREPARE stmt_add_idx_cot_anulada_por;

-- =========================================================
-- 1.05) Inventario base (requerido para todos los proyectos)
-- =========================================================
CREATE TABLE IF NOT EXISTS `inventario_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(30) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `presentacion` varchar(120) DEFAULT NULL,
  `factor_presentacion` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unidad_medida` varchar(30) NOT NULL,
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_critico` decimal(12,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventario_items_codigo` (`codigo`),
  KEY `idx_inventario_items_nombre` (`nombre`),
  KEY `idx_inventario_items_categoria` (`categoria`),
  KEY `idx_inventario_items_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql_add_inv_marca := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'inventario_items'
        AND COLUMN_NAME = 'marca'
    ),
    'SELECT 1',
    'ALTER TABLE inventario_items ADD COLUMN marca varchar(80) DEFAULT NULL AFTER categoria'
  )
);
PREPARE stmt_add_inv_marca FROM @sql_add_inv_marca;
EXECUTE stmt_add_inv_marca;
DEALLOCATE PREPARE stmt_add_inv_marca;

SET @sql_add_inv_presentacion := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'inventario_items'
        AND COLUMN_NAME = 'presentacion'
    ),
    'SELECT 1',
    'ALTER TABLE inventario_items ADD COLUMN presentacion varchar(120) DEFAULT NULL AFTER marca'
  )
);
PREPARE stmt_add_inv_presentacion FROM @sql_add_inv_presentacion;
EXECUTE stmt_add_inv_presentacion;
DEALLOCATE PREPARE stmt_add_inv_presentacion;

SET @sql_add_inv_factor := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'inventario_items'
        AND COLUMN_NAME = 'factor_presentacion'
    ),
    'SELECT 1',
    'ALTER TABLE inventario_items ADD COLUMN factor_presentacion decimal(12,4) NOT NULL DEFAULT 1 AFTER presentacion'
  )
);
PREPARE stmt_add_inv_factor FROM @sql_add_inv_factor;
EXECUTE stmt_add_inv_factor;
DEALLOCATE PREPARE stmt_add_inv_factor;

SET @sql_add_inv_stock_critico := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'inventario_items'
        AND COLUMN_NAME = 'stock_critico'
    ),
    'SELECT 1',
    'ALTER TABLE inventario_items ADD COLUMN stock_critico decimal(12,2) NOT NULL DEFAULT 0 AFTER stock_minimo'
  )
);
PREPARE stmt_add_inv_stock_critico FROM @sql_add_inv_stock_critico;
EXECUTE stmt_add_inv_stock_critico;
DEALLOCATE PREPARE stmt_add_inv_stock_critico;

CREATE TABLE IF NOT EXISTS `inventario_lotes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `lote_codigo` varchar(80) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cantidad_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inventario_lotes_item` (`item_id`),
  KEY `idx_inventario_lotes_venc` (`fecha_vencimiento`),
  KEY `idx_inventario_lotes_stock` (`cantidad_actual`),
  CONSTRAINT `fk_inventario_lotes_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `lote_id` int DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventario_mov_item` (`item_id`),
  KEY `idx_inventario_mov_lote` (`lote_id`),
  KEY `idx_inventario_mov_tipo` (`tipo`),
  KEY `idx_inventario_mov_fecha` (`fecha_hora`),
  CONSTRAINT `fk_inventario_mov_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventario_mov_lote` FOREIGN KEY (`lote_id`) REFERENCES `inventario_lotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 1.1) inventario_items: compatibilidad created_at/updated_at
-- =========================================================
SET @inventario_items_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventario_items'
);

SET @sql_add_inv_created_at := (
  SELECT IF(
    @inventario_items_exists = 0,
    "SELECT 'inventario_items no existe: omitir created_at/updated_at' AS info",
    IF(
      EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_items'
          AND COLUMN_NAME = 'created_at'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_items ADD COLUMN created_at datetime NOT NULL DEFAULT current_timestamp() AFTER activo'
    )
  )
);
PREPARE stmt_add_inv_created_at FROM @sql_add_inv_created_at;
EXECUTE stmt_add_inv_created_at;
DEALLOCATE PREPARE stmt_add_inv_created_at;

SET @sql_add_inv_updated_at := (
  SELECT IF(
    @inventario_items_exists = 0,
    "SELECT 'inventario_items no existe: omitir created_at/updated_at' AS info",
    IF(
      EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_items'
          AND COLUMN_NAME = 'updated_at'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_items ADD COLUMN updated_at datetime DEFAULT NULL AFTER created_at'
    )
  )
);
PREPARE stmt_add_inv_updated_at FROM @sql_add_inv_updated_at;
EXECUTE stmt_add_inv_updated_at;
DEALLOCATE PREPARE stmt_add_inv_updated_at;

SET @sql_add_idx_inv_created_at := (
  SELECT IF(
    @inventario_items_exists = 0,
    "SELECT 'inventario_items no existe: omitir índice created_at' AS info",
    IF(
      EXISTS(
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_items'
          AND INDEX_NAME = 'idx_inventario_items_created_at'
      ),
      'SELECT 1',
      'ALTER TABLE inventario_items ADD INDEX idx_inventario_items_created_at (created_at)'
    )
  )
);
PREPARE stmt_add_idx_inv_created_at FROM @sql_add_idx_inv_created_at;
EXECUTE stmt_add_idx_inv_created_at;
DEALLOCATE PREPARE stmt_add_idx_inv_created_at;

-- =========================================================
-- 2) Tablas faltantes de inventario interno
-- =========================================================
CREATE TABLE IF NOT EXISTS `inventario_consumos_examen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int NOT NULL,
  `id_examen` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad_consumida` decimal(12,4) NOT NULL,
  `origen_evento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'resultado',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aplicado',
  `usuario_id` int DEFAULT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_consumo_evento` (`id_cotizacion`,`id_examen`,`item_id`,`origen_evento`),
  KEY `idx_inventario_consumo_cotizacion` (`id_cotizacion`),
  KEY `idx_inventario_consumo_examen` (`id_examen`),
  KEY `idx_inventario_consumo_item` (`item_id`),
  KEY `idx_inventario_consumo_fecha` (`fecha_hora`),
  CONSTRAINT `fk_inventario_consumo_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventario_examen_recetas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_examen` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad_por_prueba` decimal(12,4) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventario_receta_examen_item` (`id_examen`,`item_id`),
  KEY `idx_inventario_receta_examen` (`id_examen`),
  KEY `idx_inventario_receta_item` (`item_id`),
  KEY `idx_inventario_receta_activo` (`activo`),
  CONSTRAINT `fk_inventario_receta_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventario_transferencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origen` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'almacen_principal',
  `destino` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laboratorio',
  `usuario_id` int DEFAULT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventario_transferencias_fecha` (`fecha_hora`),
  KEY `idx_inventario_transferencias_destino` (`destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventario_transferencias_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transferencia_id` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventario_transfer_det_transf` (`transferencia_id`),
  KEY `idx_inventario_transfer_det_item` (`item_id`),
  CONSTRAINT `fk_inventario_transfer_det_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventario_transfer_det_transferencia` FOREIGN KEY (`transferencia_id`) REFERENCES `inventario_transferencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
