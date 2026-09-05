-- Evita duplicados en tablas pivote y garantiza unicidad por par entidad-cliente.

DELETE cc1
FROM convenio_cliente cc1
INNER JOIN convenio_cliente cc2
    ON cc1.convenio_id = cc2.convenio_id
   AND cc1.cliente_id = cc2.cliente_id
   AND cc1.id > cc2.id;

DELETE ec1
FROM empresa_cliente ec1
INNER JOIN empresa_cliente ec2
    ON ec1.empresa_id = ec2.empresa_id
   AND ec1.cliente_id = ec2.cliente_id
   AND ec1.id > ec2.id;

SET @idx_conv := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'convenio_cliente'
      AND index_name = 'uq_convenio_cliente'
);
SET @sql_conv := IF(
    @idx_conv = 0,
    'ALTER TABLE convenio_cliente ADD UNIQUE KEY uq_convenio_cliente (convenio_id, cliente_id)',
    'SELECT 1'
);
PREPARE stmt_conv FROM @sql_conv;
EXECUTE stmt_conv;
DEALLOCATE PREPARE stmt_conv;

SET @idx_emp := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'empresa_cliente'
      AND index_name = 'uq_empresa_cliente'
);
SET @sql_emp := IF(
    @idx_emp = 0,
    'ALTER TABLE empresa_cliente ADD UNIQUE KEY uq_empresa_cliente (empresa_id, cliente_id)',
    'SELECT 1'
);
PREPARE stmt_emp FROM @sql_emp;
EXECUTE stmt_emp;
DEALLOCATE PREPARE stmt_emp;
