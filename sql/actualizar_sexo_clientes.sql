-- Script para actualizar el campo sexo en la tabla clientes
-- Agregar las opciones 'macho' y 'hembra' para veterinarias

USE inbioslab; -- Cambia este nombre por el de tu base de datos

-- Modificar el ENUM para incluir las nuevas opciones
ALTER TABLE clientes 
MODIFY COLUMN sexo ENUM('masculino', 'femenino', 'macho', 'hembra', 'otro') 
COLLATE utf8mb4_unicode_ci;

-- Verificar el cambio
DESCRIBE clientes;

-- Consulta para ver la estructura actualizada del campo sexo
SHOW COLUMNS FROM clientes LIKE 'sexo';

