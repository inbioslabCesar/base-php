# Códigos prestacionales SIS (301-306) y gestantes

Fecha: 2026-10-18

## Qué hace
- Crea `sis_codigos_prestacionales`: catálogo parametrizable (rango de edad en meses completos, descripción MINSA, ícono, color) con semilla 301-306.
- Agrega a `cotizaciones`: `codigo_prestacional_id`, `codigo_prestacional` (snapshot), `edad_paciente_meses`, `es_gestante`, `edad_gestacional_semanas`, `edad_gestacional_dias`, `fpp`.
- Crea el índice `idx_cotizaciones_codprest_fecha (codigo_prestacional, fecha)` para el reporte.

## Orden de ejecución
1. `migracion.sql` (idempotente; usa `DATABASE()`, ejecutar con la BD seleccionada).
2. `rollback.sql` solo si hay que revertir (borra los datos de código/gestante).

## Verificación
Las consultas `SHOW`/`SELECT` al final de `migracion.sql` deben mostrar 1 tabla, 6 códigos, 7 columnas y 1 índice.

## Notas
- Ejecutar los `ALTER` sobre `cotizaciones` en horario de baja carga.
- El PHP debe verificar existencia de tabla/columnas (`app_database_has_table` / `app_database_has_column`) antes de usarlas.
- Contrastar las descripciones sembradas con el catálogo oficial vigente del MINSA.
