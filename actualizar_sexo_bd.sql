-- ========================================
-- Script para actualizar campo sexo
-- Laboratorio InbiosLab
-- ========================================

-- IMPORTANTE: Ejecuta este script en tu base de datos MySQL
-- Puedes usar phpMyAdmin, MySQL Workbench, o cualquier cliente MySQL

-- 1. Seleccionar la base de datos (cambia 'inbioslab' por el nombre de tu BD)
USE inbioslab;

-- 2. Mostrar estructura actual del campo sexo (antes del cambio)
SELECT 'ESTRUCTURA ANTES DEL CAMBIO:' as info;
SHOW COLUMNS FROM clientes LIKE 'sexo';

-- 3. Actualizar el campo sexo para incluir 'macho' y 'hembra'
ALTER TABLE clientes 
MODIFY COLUMN sexo ENUM('masculino', 'femenino', 'macho', 'hembra', 'otro') 
COLLATE utf8mb4_unicode_ci;

-- 4. Mostrar estructura actualizada del campo sexo (después del cambio)
SELECT 'ESTRUCTURA DESPUÉS DEL CAMBIO:' as info;
SHOW COLUMNS FROM clientes LIKE 'sexo';

-- 5. Verificar que no hay errores
SELECT 'ACTUALIZACIÓN COMPLETADA EXITOSAMENTE' as resultado;

-- ========================================
-- INSTRUCCIONES:
-- 1. Copia todo este contenido
-- 2. Pégalo en phpMyAdmin (pestaña SQL)
-- 3. Haz clic en "Continuar" o "Ejecutar"
-- 4. Verifica que muestre el mensaje de éxito
-- ========================================