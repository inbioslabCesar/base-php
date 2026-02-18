-- Eliminar tablas no usadas (confirmadas vacías)
-- Ejecutar en la BD objetivo (desarrollo o producción) con la conexión correcta.
-- Versión compatible con usuarios sin permisos sobre information_schema.

SELECT DATABASE() AS base_actual;

DROP TABLE IF EXISTS `examenes_cliente`;
DROP TABLE IF EXISTS `examenes_convenio`;
DROP TABLE IF EXISTS `examenes_empresa`;
DROP TABLE IF EXISTS `examenes_promocion`;
DROP TABLE IF EXISTS `promociones_empresa`;
DROP TABLE IF EXISTS `promociones_examen`;
DROP TABLE IF EXISTS `resultados`;

-- Verificación posterior
SHOW TABLES LIKE 'examenes_cliente';
SHOW TABLES LIKE 'examenes_convenio';
SHOW TABLES LIKE 'examenes_empresa';
SHOW TABLES LIKE 'examenes_promocion';
SHOW TABLES LIKE 'promociones_empresa';
SHOW TABLES LIKE 'promociones_examen';
SHOW TABLES LIKE 'resultados';
