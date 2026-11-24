-- Control de pagos del sistema (mensualidad / alojamiento anual) - 2026-11-21
-- Idempotente: solo crea tablas si no existen. No modifica ni borra datos.
-- Compatible con MySQL 5.7+/8 y MariaDB 10.x/11.x.

CREATE TABLE IF NOT EXISTS `sistema_suscripcion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_cfg_id` INT NOT NULL COMMENT 'config_empresa.id',
  `modalidad` ENUM('MENSUAL','ANUAL','EXENTO') NOT NULL DEFAULT 'EXENTO',
  `plan` VARCHAR(60) NULL,
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dia_cobro` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Solo MENSUAL (1-31). Si el mes es mas corto se usa su ultimo dia',
  `primer_vencimiento` DATE NULL COMMENT 'Primer ciclo a controlar. En ANUAL fija el dia/mes del aniversario',
  `dias_aviso` SMALLINT UNSIGNED NULL COMMENT 'NULL = 5 (mensual) / 30 (anual)',
  `dias_gracia` SMALLINT UNSIGNED NULL COMMENT 'NULL = nunca se bloquea. N = bloqueo N dias despues del vencimiento',
  `medios_pago` TEXT NULL,
  `mensaje_extra` VARCHAR(255) NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sistema_suscripcion_empresa` (`empresa_cfg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sistema_suscripcion_pagos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT UNSIGNED NOT NULL,
  `concepto` ENUM('MENSUALIDAD','ALOJAMIENTO','IMPLEMENTACION','OTRO') NOT NULL DEFAULT 'MENSUALIDAD',
  `periodo` VARCHAR(7) NULL COMMENT 'YYYY-MM (mensual) o YYYY (anual). NULL en implementacion/otro',
  `periodo_activo` VARCHAR(7) NULL COMMENT 'Igual a periodo mientras el pago no este anulado. Evita pagar dos veces el mismo ciclo',
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_pago` DATE NOT NULL,
  `metodo` VARCHAR(40) NULL,
  `referencia` VARCHAR(120) NULL,
  `nota` VARCHAR(255) NULL,
  `registrado_por` VARCHAR(120) NULL,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  `anulado_en` DATETIME NULL,
  `anulado_motivo` VARCHAR(255) NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sistema_pago_periodo_activo` (`suscripcion_id`, `periodo_activo`),
  KEY `idx_sistema_pago_suscripcion_fecha` (`suscripcion_id`, `fecha_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verificacion
SHOW TABLES LIKE 'sistema_suscripcion%';
SHOW COLUMNS FROM `sistema_suscripcion`;
SHOW COLUMNS FROM `sistema_suscripcion_pagos`;
