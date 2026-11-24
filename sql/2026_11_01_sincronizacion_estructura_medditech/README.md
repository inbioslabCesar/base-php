# Sincronización de estructura medditech → producción (2026-11-01)

Script **independiente** (no depende de otras migraciones) y **idempotente**. Compatible con MySQL 5.7+/8 y MariaDB 10.x. No borra datos.

## Cambios en BD

| Tabla | Cambio |
|---|---|
| `inventario_items` | Columna `controla_stock TINYINT(1) NOT NULL DEFAULT 1` + índice `idx_inventario_items_controla_stock` |
| `pagos` | Índices `idx_pagos_fecha (fecha)` y `idx_pagos_fecha_metodo (fecha, metodo_pago)` |
| `promociones_empresa` | Índices `idx_promociones_empresa_empresa`, `idx_promociones_empresa_promocion` (sin FKs) |
| `promociones_examen` | Índices `idx_promociones_examen_examen`, `idx_promociones_examen_promocion` (sin FKs) |
| `resultados` | Índices `idx_resultados_cotizacion`, `idx_resultados_examen`, `idx_resultados_cotizacion_examen` |
| `resultados_examenes` | Columnas `edad_paciente_valor DECIMAL(10,6)`, `edad_paciente_texto VARCHAR(120)`, `fecha_ref_edad DATETIME` (todas NULL) |
| `sis_codigos_prestacionales` | **Tabla nueva** + semilla de códigos 301–306 (solo inserta los que falten) |
| `usuarios` | `UNIQUE uq_usuarios_email (email)` — solo si no hay emails duplicados |

### Diferencias respecto al borrador original
- `resultados_examenes`: el borrador usaba `INT / VARCHAR(50) / DATE`; el sistema (`EdadPacienteService`, `2026_08_30_resultados_edad_snapshot.sql`) usa `DECIMAL(10,6) / VARCHAR(120) / DATETIME`. Con `INT` se truncaría la edad (p. ej. 0.5 años → 0).
- `sis_codigos_prestacionales`: el borrador tenía `descripcion/activo` únicamente; el código PHP exige `nombre`, `tipo_regla`, `edad_min_meses`, `edad_max_meses`, `icono_clase`, `icono_ruta`, `color`, `orden`.
- Se eliminó `AFTER <columna>` (fallaba si esa columna no existía) y `ADD COLUMN IF NOT EXISTS` (solo MariaDB).
- Los índices de `promociones_*` no crean claves foráneas: en producción podrían existir huérfanos y la FK fallaría.
- El `UNIQUE` de `usuarios.email` se omite con aviso si hay duplicados (en vez de abortar).

## Ejecución en producción
1. **Respaldo** de la BD (exportación completa).
2. Ejecutar `migracion.sql` en la BD de producción (phpMyAdmin → Importar o pestaña SQL; codificación utf8mb4).
3. Cada paso muestra un mensaje `info` si se omitió (objeto ya existente o tabla inexistente). Es seguro reejecutarlo.

## Verificación post-despliegue
Al final de `migracion.sql` hay consultas de solo lectura:
- `SHOW COLUMNS` de `controla_stock` y de las 3 columnas de edad.
- Tabla `sis_codigos_prestacionales` con 6 filas (301–306).
- Listado de los 10 índices + `uq_usuarios_email`.
- Emails duplicados: debe devolver **0 filas**; si hay filas, depurar y reejecutar.

## Rollback
`rollback.sql` elimina solo índices. El borrado de columnas/tabla está comentado (pérdida de datos).
