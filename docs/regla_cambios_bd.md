# Regla obligatoria: cambios en la base de datos

Cualquier mejora que **cree o modifique tablas, columnas, índices, vistas o datos semilla** debe seguir esta regla.

## 1. Avisar antes de hacerlo
- Antes de escribir código que dependa de un cambio en la BD, informar en el chat: qué tablas/columnas se crean o modifican y por qué.
- Si una mejora **no** toca la BD, decirlo explícitamente ("Sin cambios en BD").

## 2. Dónde se guarda el SQL
Carpeta propia por mejora, con fecha y nombre:

```
sql/AAAA_MM_DD_nombre_de_la_mejora/
    migracion.sql   -> script idempotente (se puede ejecutar más de una vez)
    rollback.sql    -> revierte la migración
    README.md       -> qué hace, orden de ejecución, verificación post-despliegue
```

## 3. Cómo se escribe el SQL
- Idempotente: `CREATE TABLE IF NOT EXISTS`, y para columnas/índices consultar `information_schema` antes de `ALTER` (ver `sql/2026_08_30_precios_convenio_por_examen.sql`).
- Nunca `DROP`/`DELETE` de datos existentes en la migración.
- Incluir consultas de verificación al final (`SHOW COLUMNS`, `SHOW TABLES`).
- El código PHP debe tolerar que la migración aún no se haya ejecutado (verificar columnas/tablas antes de usarlas), igual que el resto del sistema.

## 4. Cierre de cada tarea
Terminar siempre con una lista **"Scripts a ejecutar en producción"** con ruta y orden, o con "Ninguno".

## 5. Esquema de referencia (clientes desactualizados)
Cada vez que cambie la estructura de la BD de **desarrollo** (además del SQL de la sección 2), regenerar el esquema de referencia y subirlo:

```
php scripts/esquema_bd/generar_esquema_referencia.php
```

Con él, `scripts/esquema_bd/actualizar_esquema_bd.php` lleva cualquier BD de cliente al esquema actual creando lo que falte (ver `scripts/esquema_bd/README.md`). Es simulacro por defecto y nunca elimina nada.

## Historial de mejoras
| Fecha | Mejora | Cambios en BD |
|---|---|---|
| 2026-10-14 | Cotizador Express – Fase 1 | Ninguno |
| 2026-10-14 | Cotizador Express – Fases 2 y 3 (historial, conversión, enlace público, plantilla) | 3 tablas nuevas: `sql/2026_10_14_cotizador_express/` |
| 2026-10-14 | Cotizador Express – Fase 4 (deuda sin atender en Contabilidad) | Ninguno (solo lectura) |
| 2026-11-01 | Sincronización de estructura medditech → producción | 3 columnas de edad, `controla_stock`, 10 índices, `UNIQUE usuarios.email`, tabla nueva `sis_codigos_prestacionales`: `sql/2026_11_01_sincronizacion_estructura_medditech/` |
| 2026-11-21 | Igualar producción con desarrollo (índices, UNIQUE, 14 FK, `rol_creador` sin default) | `sql/2026_11_21_igualar_produccion_con_desarrollo/` |
| 2026-11-21 | Actualizador de esquema para clientes desactualizados (`scripts/esquema_bd/`) | Ninguno en dev (herramienta; crea lo que falte en otras BD al ejecutarse) |
| 2026-11-21 | Control de pagos del sistema (aviso de mensualidad/alojamiento anual, panel engineer, bloqueo opcional) | 2 tablas nuevas `sistema_suscripcion` y `sistema_suscripcion_pagos`: `sql/2026_11_21_control_pagos_sistema/` |
