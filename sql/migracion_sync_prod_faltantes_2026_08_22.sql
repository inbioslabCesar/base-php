-- Migracion generada automaticamente: 2026-08-22 23:05:57
-- Origen: laboratorio | Destino: inbioslabstore_prod
-- Alcance: SOLO faltantes (tablas, columnas e indices)
SET NAMES utf8mb4;
USE u330560936_laboratorio;

-- ===== TABLAS FALTANTES =====
CREATE TABLE IF NOT EXISTS `laboratorio_turnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `estado` enum('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cerrado_en` datetime DEFAULT NULL,
  `observacion_apertura` varchar(255) DEFAULT NULL,
  `observacion_cierre` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_turnos_usuario_estado` (`usuario_id`,`estado`),
  KEY `idx_turnos_abierto_en` (`abierto_en`),
  CONSTRAINT `fk_turnos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE IF NOT EXISTS `profesionales_solicitantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_profesional` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_documento` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_profesional` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ps_estado` (`estado`),
  KEY `idx_ps_nombre` (`nombres`,`apellidos`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `resultados_sync_operaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operation_id` varchar(80) NOT NULL,
  `cotizacion_id` int DEFAULT NULL,
  `estado` enum('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
  `payload_json` mediumtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resultados_sync_operation_id` (`operation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE IF NOT EXISTS `servicio_cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servicio_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_servicio_cliente` (`servicio_id`,`cliente_id`),
  KEY `idx_sc_servicio` (`servicio_id`),
  KEY `idx_sc_cliente` (`cliente_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `servicio_descargas_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servicio_id` int NOT NULL,
  `cotizacion_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `usuario_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sda_servicio` (`servicio_id`),
  KEY `idx_sda_cotizacion` (`cotizacion_id`),
  KEY `idx_sda_cliente` (`cliente_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `servicio_profesional` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servicio_id` int NOT NULL,
  `profesional_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_servicio_profesional` (`servicio_id`,`profesional_id`),
  KEY `idx_sp_servicio` (`servicio_id`),
  KEY `idx_sp_profesional` (`profesional_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `servicios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsable` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_servicios_codigo` (`codigo`),
  UNIQUE KEY `uq_servicios_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `sis_coberturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `numero_afiliacion` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_autorizacion` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_fua` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aseguradora` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_validacion` enum('pendiente','autorizada','observada','rechazada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `monto_atencion` decimal(10,2) NOT NULL DEFAULT '0.00',
  `observaciones` text COLLATE utf8mb4_general_ci,
  `creado_por` int DEFAULT NULL,
  `creada_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizada_en` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sis_coberturas_cotizacion_id` (`cotizacion_id`),
  KEY `idx_sis_coberturas_cliente_id` (`cliente_id`),
  KEY `idx_sis_coberturas_numero_autorizacion` (`numero_autorizacion`),
  CONSTRAINT `fk_sis_coberturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sis_coberturas_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== COLUMNAS FALTANTES =====
ALTER TABLE config_empresa ADD COLUMN modo_operativo enum('PARTICULAR','SIS','MIXTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PARTICULAR' AFTER celular;
ALTER TABLE config_empresa ADD COLUMN portal_publico_enable tinyint(1) NOT NULL DEFAULT '1' AFTER modo_operativo;
ALTER TABLE config_empresa ADD COLUMN portal_publico_estilo varchar(20) COLLATE utf8mb4_unicode_ci NULL DEFAULT 'clasico' AFTER portal_publico_enable;
ALTER TABLE config_empresa ADD COLUMN logo_fondo_navbar varchar(20) COLLATE utf8mb4_unicode_ci NULL DEFAULT '#ffffff' AFTER color_texto;
ALTER TABLE config_empresa ADD COLUMN ubicaciones_json text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER maps_embed;
ALTER TABLE cotizaciones ADD COLUMN servicio_id int NULL DEFAULT NULL AFTER id_convenio;
ALTER TABLE cotizaciones ADD COLUMN es_sis tinyint(1) NOT NULL DEFAULT '0' AFTER descuento_aplicado;
ALTER TABLE cotizaciones ADD COLUMN sis_cobertura_id int NULL DEFAULT NULL AFTER es_sis;
ALTER TABLE cotizaciones ADD COLUMN sis_monto_atencion decimal(10,2) NOT NULL DEFAULT '0.00' AFTER sis_cobertura_id;
ALTER TABLE cotizaciones ADD COLUMN sis_registrado_en datetime NULL DEFAULT NULL AFTER sis_monto_atencion;
ALTER TABLE cotizaciones ADD COLUMN sis_registrado_por int NULL DEFAULT NULL AFTER sis_registrado_en;
ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_id int NULL DEFAULT NULL AFTER anulado_motivo;
ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_nombre varchar(190) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER profesional_solicitante_id;
ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_tipo varchar(80) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER profesional_solicitante_nombre;
ALTER TABLE cotizaciones ADD COLUMN profesional_solicitante_registro varchar(80) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER profesional_solicitante_tipo;
ALTER TABLE resultados_examenes ADD COLUMN id_turno int NULL DEFAULT NULL AFTER id_laboratorista;
ALTER TABLE usuarios ADD COLUMN colegiatura_numero varchar(60) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER profesion;
ALTER TABLE usuarios ADD COLUMN ctmp_numero varchar(60) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER colegiatura_numero;
ALTER TABLE usuarios ADD COLUMN firma varchar(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER ctmp_numero;
ALTER TABLE usuarios ADD COLUMN privilegios_json text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER rol;

-- ===== INDICES FALTANTES =====
ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_es_sis (es_sis);
ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_profesional_solicitante (profesional_solicitante_id);

-- Fin de migracion de faltantes
