# Runbook: Migracion clientes.razon_social en multiples laboratorios

## Objetivo
Estandarizar el modelo de identidad para pacientes/entidades con RUC agregando la columna razon_social en la tabla clientes, sin interrumpir operacion.

## Alcance
- Aplica a cada base de datos tenant (un laboratorio por BD).
- Ejecucion manual por laboratorio.
- Idempotente: se puede re-ejecutar sin romper.

## Archivos
- SQL de migracion: sql/2026_08_30_clientes_razon_social_multi_bd.sql

## Requisitos previos
1. Acceso MySQL con permisos ALTER sobre cada BD.
2. Ventana de cambio aprobada.
3. Lista de laboratorios con:
- nombre_laboratorio
- host
- puerto
- nombre_bd
- usuario
4. Backup validado por cada BD antes de migrar.

## Matriz de control sugerida
Registrar una fila por laboratorio con estos estados:
- pendiente
- backup_ok
- migrado_ok
- verificado_ok
- rollback
- observacion

## Procedimiento por laboratorio
### Paso 1: Conectarse a la BD
Ejemplo en Windows PowerShell:

```powershell
mysql -h HOST -P 3306 -u USER -p NOMBRE_BD
```

### Paso 2: Pre-check
Dentro de MySQL:

```sql
SELECT DATABASE() AS bd_actual;
SELECT COUNT(*) AS tabla_clientes_existe
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'clientes';
```

Criterio:
- Si tabla_clientes_existe = 0, detener y registrar incidencia.

### Paso 3: Backup previo
Opcion A (PowerShell):

```powershell
mysqldump -h HOST -P 3306 -u USER -p --single-transaction --routines --triggers NOMBRE_BD > backup_NOMBRE_BD_yyyyMMdd_HHmm.sql
```

Criterio:
- Archivo generado y tamano mayor a 0.

### Paso 4: Ejecutar migracion
Opcion A (desde MySQL ya conectado):

```sql
SOURCE c:/laragon/www/base-php/sql/2026_08_30_clientes_razon_social_multi_bd.sql;
```

Opcion B (desde PowerShell):

```powershell
mysql -h HOST -P 3306 -u USER -p NOMBRE_BD < c:/laragon/www/base-php/sql/2026_08_30_clientes_razon_social_multi_bd.sql
```

### Paso 5: Verificacion tecnica

```sql
SELECT
    c.column_name,
    c.column_type,
    c.is_nullable,
    c.ordinal_position
FROM information_schema.columns c
WHERE c.table_schema = DATABASE()
  AND c.table_name = 'clientes'
  AND c.column_name = 'razon_social';

SELECT COUNT(*) AS clientes_ruc_sin_razon_social
FROM clientes
WHERE LOWER(COALESCE(tipo_documento, '')) = 'ruc'
  AND (razon_social IS NULL OR TRIM(razon_social) = '');
```

Criterio:
- Debe existir la columna razon_social.
- El conteo de pendientes no bloquea salida, pero debe registrarse para limpieza operativa.

### Paso 6: Verificacion funcional minima
En la aplicacion:
1. Abrir registro de paciente.
2. Ingresar RUC valido y salir del campo.
3. Confirmar autocompletado y guardado.
4. Editar paciente RUC y confirmar persistencia.

## Rollback
Solo si hay incidente critico.

### Opcion rapida
Restaurar backup completo del tenant afectado.

### Opcion estructural (si no hubo datos nuevos dependientes)

```sql
ALTER TABLE clientes DROP COLUMN razon_social;
```

Nota:
- Preferir restore completo si hubo cambios de datos durante la ventana.

## Orden de despliegue recomendado
1. Piloto: 1 laboratorio de bajo riesgo.
2. Lote 1: 20% de laboratorios.
3. Lote 2: resto.

## Criterio de exito global
- 100% de BDs con columna clientes.razon_social creada.
- 0 incidencias bloqueantes abiertas.
- Flujo DNI/RUC operativo en alta/edicion y busqueda.

## Checklist rapido por laboratorio
- [ ] Conexion validada a BD correcta
- [ ] Backup generado
- [ ] SQL ejecutado sin error
- [ ] Columna razon_social verificada
- [ ] Prueba funcional completada
- [ ] Estado actualizado en matriz de control
