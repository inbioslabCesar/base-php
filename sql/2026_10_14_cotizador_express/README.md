# Cotizador Express – Fases 2 y 3 (2026-10-14)

## Cambios en la base de datos
Solo **3 tablas nuevas** (`CREATE TABLE IF NOT EXISTS`). No se modifica ninguna tabla existente.

| Tabla | Uso |
|---|---|
| `cotizaciones_express` | Historial de cotizaciones rápidas: folio `EXP-AAAA-00001`, token del enlace público, tarifa, totales, vigencia, estado (`emitida`, `convertida`, `anulada`; "vencida" se calcula por fecha), canales usados y conversión. |
| `cotizaciones_express_items` | Exámenes de cada cotización con el precio congelado (snapshot). |
| `cotizador_express_plantillas` | Plantilla editable del mensaje (`precios` e `info`). |

Las cotizaciones express **no** se guardan en `cotizaciones`, por lo que no suman a "Deuda de Clientes".

## Orden de ejecución en producción
1. Respaldar la base de datos.
2. Ejecutar `migracion.sql` (es idempotente: puede correrse más de una vez).
3. Verificar que las 3 consultas `SHOW TABLES` del final devuelvan una fila.
4. Probar: crear una cotización en *Cotizador Express*, copiar el mensaje y abrir *Historial*.

Sin la migración el cotizador sigue funcionando (sin historial, enlace público ni plantillas editables).

## Rollback
`rollback.sql` elimina las 3 tablas (se pierde el historial express). No afecta a ningún otro dato.

## Fase 4 (sin cambios de BD)
La separación de deuda en Contabilidad solo agrega consultas de lectura sobre `cotizaciones`, `pagos` y `resultados_examenes`.
