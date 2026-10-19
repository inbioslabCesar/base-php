# Instrucciones del proyecto (base-php / laboratorio InbiosLab)

## Cambios en la base de datos (OBLIGATORIO)
Sigue siempre [docs/regla_cambios_bd.md](../docs/regla_cambios_bd.md):
- Antes de implementar, avisa si la mejora crea o modifica tablas/columnas. Si no, indica "Sin cambios en BD".
- Guarda el SQL en `sql/AAAA_MM_DD_nombre_de_la_mejora/` (`migracion.sql` idempotente, `rollback.sql`, `README.md`).
- Al cerrar la tarea, lista los "Scripts a ejecutar en producción" (o "Ninguno").
- El código debe tolerar que la migración aún no esté aplicada (verificar columnas/tablas).

## Modos operativos
- `config_empresa.modo_operativo` puede ser `PARTICULAR`, `SIS` o `MIXTO` (`app_operacion_context()`).
- En modo SIS **nunca** se deben exponer precios (ni en HTML, JSON, PDF ni mensajes); el bloqueo debe ser en el servidor.

## Regla de precios por perfil
`usar_precio_convenio = 1` y `examenes.precio_convenio > 0` → precio convenio; en otro caso `precio_publico * (1 - descuento/100)`.
