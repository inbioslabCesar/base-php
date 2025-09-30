-- Actualizar tabla pagos para el sistema de modificación de montos
-- Agregar campos necesarios para el registro de cambios de total

-- Agregar campo metodo_pago
ALTER TABLE pagos 
ADD COLUMN metodo_pago VARCHAR(50) DEFAULT 'efectivo' 
COMMENT 'Método de pago utilizado: efectivo, tarjeta, transferencia, yape, descarga_anticipada, cambio_total';

-- Agregar campo observaciones para registrar cambios de monto total
ALTER TABLE pagos 
ADD COLUMN observaciones TEXT NULL 
COMMENT 'Observaciones adicionales, especialmente para cambios de monto total';

-- Comentario explicativo
-- El campo metodo_pago permite registrar el método usado para cada pago
-- El campo observaciones se usa para registrar cuando se modifica el monto total de una cotización
-- Los registros con metodo_pago = 'cambio_total' y monto = 0 indican modificaciones al total acordado