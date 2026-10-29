-- Migracion: Codigos prestacionales por curso de vida MINSA (SIS) y atencion a gestantes
-- Fecha: 2026-10-18
-- Idempotente: se puede ejecutar mas de una vez. No elimina ni modifica datos existentes.
-- Cambios:
--   1) Tabla nueva `sis_codigos_prestacionales` (catalogo parametrizable 301-306).
--   2) Columnas nuevas en `cotizaciones` (codigo asignado + datos de gestante + edad snapshot).
--   3) Indice para el reporte de estadistica SIS.

SET @db := DATABASE();

-- 1) Catalogo de codigos prestacionales
-- Rango de edad en MESES COMPLETOS, ambos limites inclusivos; NULL en max = sin limite.
-- Ej.: 301 = 0..143 (hasta 11a 11m 29d), 305 = 720.. (60 anios o mas).
CREATE TABLE IF NOT EXISTS sis_codigos_prestacionales (
    id INT NOT NULL AUTO_INCREMENT,
    codigo CHAR(3) NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion_minsa VARCHAR(255) NULL,
    tipo_regla ENUM('EDAD', 'GESTANTE') NOT NULL DEFAULT 'EDAD',
    edad_min_meses INT NULL,
    edad_max_meses INT NULL,
    icono_clase VARCHAR(80) NULL,
    icono_ruta VARCHAR(255) NULL,
    color VARCHAR(20) NULL,
    orden SMALLINT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sis_codigos_prestacionales_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos semilla (INSERT IGNORE: no pisa cambios hechos luego desde Configuracion Operativa).
-- Verificar las descripciones contra el catalogo oficial vigente del MINSA antes de produccion.
INSERT IGNORE INTO sis_codigos_prestacionales
    (codigo, nombre, descripcion_minsa, tipo_regla, edad_min_meses, edad_max_meses, icono_clase, color, orden)
VALUES
    ('301', 'Niño',         'Etapa de vida niño: 0 a 11 años, 11 meses, 29 días',         'EDAD',     0,    143,  'bi-emoji-smile',      '#0d6efd', 1),
    ('302', 'Adolescente',  'Etapa de vida adolescente: 12 a 17 años, 11 meses, 29 días', 'EDAD',     144,  215,  'bi-person-arms-up',  '#6f42c1', 2),
    ('303', 'Joven',        'Etapa de vida joven: 18 a 29 años, 11 meses, 29 días',       'EDAD',     216,  359,  'bi-person',           '#198754', 3),
    ('304', 'Adulto',       'Etapa de vida adulto: 30 a 59 años, 11 meses, 29 días',      'EDAD',     360,  719,  'bi-person-workspace', '#fd7e14', 4),
    ('305', 'Adulto mayor', 'Etapa de vida adulto mayor: 60 años a más',                  'EDAD',     720,  NULL, 'bi-person-wheelchair', '#6c757d', 5),
    ('306', 'Gestante',     'Atención prenatal / gestante',                               'GESTANTE', NULL, NULL, 'bi-gender-female',    '#d63384', 6);

-- 2) cotizaciones: columnas nuevas
-- codigo_prestacional_id
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'codigo_prestacional_id');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN codigo_prestacional_id INT NULL",
    "SELECT 'cotizaciones.codigo_prestacional_id ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- codigo_prestacional (snapshot del codigo: el historico no cambia si luego se edita el catalogo)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'codigo_prestacional');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN codigo_prestacional CHAR(3) NULL",
    "SELECT 'cotizaciones.codigo_prestacional ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- edad_paciente_meses (snapshot de la edad en meses completos a la fecha de la cotizacion)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_paciente_meses');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN edad_paciente_meses INT NULL",
    "SELECT 'cotizaciones.edad_paciente_meses ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- es_gestante
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'es_gestante');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN es_gestante TINYINT(1) NOT NULL DEFAULT 0",
    "SELECT 'cotizaciones.es_gestante ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- edad_gestacional_semanas
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_gestacional_semanas');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN edad_gestacional_semanas TINYINT UNSIGNED NULL",
    "SELECT 'cotizaciones.edad_gestacional_semanas ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- edad_gestacional_dias (0-6, dias adicionales a las semanas)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'edad_gestacional_dias');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN edad_gestacional_dias TINYINT UNSIGNED NULL",
    "SELECT 'cotizaciones.edad_gestacional_dias ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fpp (fecha probable de parto)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'fpp');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD COLUMN fpp DATE NULL",
    "SELECT 'cotizaciones.fpp ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Indice para estadistica SIS por codigo y rango de fechas
SET @exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cotizaciones' AND INDEX_NAME = 'idx_cotizaciones_codprest_fecha');
SET @sql := IF(@exists = 0,
    "ALTER TABLE cotizaciones ADD INDEX idx_cotizaciones_codprest_fecha (codigo_prestacional, fecha)",
    "SELECT 'idx_cotizaciones_codprest_fecha ya existe' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificacion
SHOW TABLES LIKE 'sis_codigos_prestacionales';
SELECT codigo, nombre, tipo_regla, edad_min_meses, edad_max_meses, activo FROM sis_codigos_prestacionales ORDER BY orden;
SHOW COLUMNS FROM cotizaciones WHERE Field IN ('codigo_prestacional_id', 'codigo_prestacional', 'edad_paciente_meses', 'es_gestante', 'edad_gestacional_semanas', 'edad_gestacional_dias', 'fpp');
SHOW INDEX FROM cotizaciones WHERE Key_name = 'idx_cotizaciones_codprest_fecha';
