-- Migración SIMPLE para PRODUCCIÓN (Hostinger) - Facturación/CPE
-- Úsala si tu servidor no permite SQL dinámico (PREPARE) o si quieres algo directo.
--
-- Ejecutar en phpMyAdmin (SQL) o consola MariaDB/MySQL.
--
-- Asegura:
-- 1) Flag Solo Ticket vs CPE
-- 2) Estado de pago con 'abonado'

-- 1) Agregar columna faltante (en tu dump de producción NO existe)
ALTER TABLE cotizaciones
  ADD COLUMN emitir_comprobante TINYINT(1) NOT NULL DEFAULT 1;

-- 2) Ampliar enum de estado_pago para soportar pagos parciales
ALTER TABLE cotizaciones
  MODIFY COLUMN estado_pago ENUM('pendiente','abonado','pagado') DEFAULT 'pendiente';
