-- Revierte el control de pagos del sistema. ATENCION: elimina el historial de pagos registrado.
-- Haga respaldo antes de ejecutar.
DROP TABLE IF EXISTS `sistema_suscripcion_pagos`;
DROP TABLE IF EXISTS `sistema_suscripcion`;
