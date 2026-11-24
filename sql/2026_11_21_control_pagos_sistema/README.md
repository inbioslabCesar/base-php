# Control de pagos del sistema — 2026-11-21

Permite al ingeniero (rol `engineer`) controlar el cobro de cada instalación: **mensualidad** (día fijo cada mes) o **alojamiento anual**. Los usuarios internos (admin, recepcionista, laboratorista) ven un aviso pequeño arriba a la derecha que no se puede cerrar hasta que el ingeniero registre el pago; opcionalmente el sistema se bloquea tras N días de gracia.

## Cambios en BD (2 tablas nuevas, sin tocar las existentes)

| Tabla | Para qué |
|---|---|
| `sistema_suscripcion` | Una fila por empresa (`config_empresa.id`): modalidad, monto, día de cobro, primer vencimiento, días de aviso, días de gracia, medios de pago |
| `sistema_suscripcion_pagos` | Historial de pagos (mensualidad, alojamiento, implementación, otro). `periodo_activo` + `UNIQUE` impide pagar dos veces el mismo ciclo |

El código tolera que la migración no esté aplicada: sin las tablas no se muestra aviso ni se bloquea nada, y la vista del ingeniero indica qué script ejecutar.

## Ejecución
1. Respaldo de la BD.
2. Ejecutar `migracion.sql` (idempotente) en la BD de cada cliente. Alternativa: `scripts/esquema_bd/actualizar_esquema_bd.php` tras regenerar el esquema de referencia.
3. Entrar como `engineer` → **Control de pagos** → configurar la empresa.

## Verificación
- `SHOW TABLES LIKE 'sistema_suscripcion%';` → 2 tablas.
- Configurar una empresa MENSUAL con vencimiento a pocos días: el aviso aparece arriba a la derecha para admin/recepcionista/laboratorista.
- Registrar el pago: el aviso desaparece hasta la siguiente ventana.

## Bloqueo (opcional)
Si `dias_gracia` tiene valor, pasados N días del vencimiento sin pago, admin/recepcionista/laboratorista ven una pantalla de suspensión al entrar a `dashboard.php`. El engineer, los pacientes (rol cliente) y la verificación por QR no se bloquean. Se levanta al registrar el pago. No se borra ningún dato.

## Rollback
`rollback.sql` (elimina las 2 tablas y el historial).
