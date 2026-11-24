# Igualar producción (medditech) con desarrollo (laboratorio) — 2026-11-21

Referencia: volcados de estructura en `sql/comparar desarrollo-produccion 21-11-2026/`. Desarrollo es la referencia estable.
Script **independiente** e **idempotente** (MySQL 5.7+/8 y MariaDB 10.x/11.x). **No modifica ni borra datos.**

## Cambios en BD (solo estructura)

| Tabla | Cambio |
|---|---|
| `caja_movimientos` | Índices `idx_mov_ref_tipo_origen`, `idx_mov_fecha_hora`, `idx_mov_caja_tipo_efectivo` |
| `cotizaciones` | Índices `idx_cotizaciones_comprobante_tipo`, `idx_cotizaciones_receptor_numero_documento`, `idx_cotizaciones_codprest_fecha`; `rol_creador` pasa a `NOT NULL` **sin** `DEFAULT 'cliente'` |
| `convenio_cliente`, `empresa_cliente` | `UNIQUE uq_convenio_cliente` / `uq_empresa_cliente` (prod ya tenía un UNIQUE equivalente sin ese nombre) |
| `examenes_cliente/_convenio/_empresa/_promocion` | Índices `idx_examenes_*` |
| 7 tablas pivote + `resultados` | **14 claves foráneas** `fk_*` (ver abajo). `resultados.id_cotizacion` es `ON DELETE CASCADE` |
| Mismas tablas | Se eliminan los índices simples redundantes (`examen_id`, `cliente_id`, `examenes_empresa_ibfk_1`, …) que dev ya no tiene |

## Puntos de atención
- **Claves foráneas**: cada FK se crea solo si **no hay huérfanos**. Si hay, se muestra `FK omitida ...: hay huerfanos` y el resto sigue. Las consultas de verificación del final listan los huérfanos por relación. Corregir/depurar esos registros manualmente y reejecutar el script.
- Con las FK activas, borrar un examen/empresa/cliente/convenio/promoción con datos asociados fallará con error 1451 (como ya ocurre en dev). `resultados` se eliminan en cascada al borrar una cotización.
- `rol_creador` sin default: seguro porque `crear_cotizacion.php` es el único `INSERT` en `cotizaciones` y siempre envía el rol.
- No cambia nada del modo operativo SIS ni del cálculo de precios.

## Ejecución en producción
1. **Respaldo** completo de la BD.
2. Ejecutar `migracion.sql` (phpMyAdmin → SQL/Importar, utf8mb4), de preferencia fuera de horario (los `ALTER` sobre `cotizaciones`, `resultados` y `caja_movimientos` pueden tardar según el volumen).
3. Revisar los mensajes `info` y las consultas finales.

## Verificación post-despliegue
- Primera consulta final: lista de `fk_*` (esperado 14).
- Segunda consulta: huérfanos (esperado 0 en todas las filas).
- `SHOW COLUMNS ... rol_creador`: `Default` = NULL.
- Opcional: volver a exportar la estructura de prod y comparar con dev.

## Rollback
`rollback.sql` quita las FK, restaura los índices simples originales, elimina los índices nuevos y devuelve `DEFAULT 'cliente'` a `rol_creador`.
