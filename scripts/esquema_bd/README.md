# Actualizador de esquema de BD (`scripts/esquema_bd/`)

Lleva la BD de **cualquier cliente**, esté tan desactualizada como esté, a la estructura de **desarrollo**. Crea automáticamente lo que falte: **tablas, columnas, índices, claves foráneas y datos semilla** (catálogo SIS). Sirve en MySQL y MariaDB.

**Nunca elimina ni modifica lo existente.** Si una columna/índice ya existe pero difiere (tipo, default…), lo informa como `INFO` con el `ALTER` sugerido para decidir a mano.

## Cómo funciona
1. `generar_esquema_referencia.php` lee la BD de **desarrollo** (solo lectura) y guarda su estructura en `sql/esquema_referencia/esquema_referencia.json` (se versiona en el repo).
2. `actualizar_esquema_bd.php` lee la BD del cliente, la compara con ese JSON y calcula lo que falta. Decide mirando la BD real, por eso no importa cuántas versiones esté atrasada ni hace falta llevar un número de versión.

Orden de aplicación: tablas → columnas (en su posición, `AFTER` la anterior) → índices → claves foráneas → semillas.

## Flujo de trabajo
```
# 1) Tras cambiar la BD de desarrollo: regenerar y subir el JSON
php scripts/esquema_bd/generar_esquema_referencia.php

# 2) En cada cliente (en el servidor): SIMULACRO, no toca nada
php scripts/esquema_bd/actualizar_esquema_bd.php --empresa=medditech

# 3) Aplicar, después de hacer respaldo de la BD
php scripts/esquema_bd/actualizar_esquema_bd.php --empresa=medditech --aplicar --confirmo-respaldo
```
La conexión sale de `src/config/empresas/{empresa}.php` (no se imprimen credenciales). `--todas` recorre todas las empresas, útil si desde esa máquina se alcanzan todas las BD.

## Opciones
| Opción | Efecto |
|---|---|
| `--empresa=slug` / `--todas` | Qué BD(s) |
| `--aplicar --confirmo-respaldo` | Ejecuta los cambios (por defecto es simulacro) |
| `--solo=tablas,columnas,indices,fks,semillas` | Limita las fases |
| `--nombres-exactos` | Crea índices/FK por nombre aunque ya exista uno equivalente con otro nombre (deja la BD idéntica a dev en nombres) |
| `--sql=archivo.sql` | Guarda el SQL pendiente por empresa |
| `--host= --puerto= --bd= --usuario= --clave=` | Sobrescriben la conexión (acceso remoto / túnel) |
| `--ref=archivo.json` | Esquema de referencia alternativo |

Código de salida: `0` al día, `1` error o acciones sin aplicar, `2` hay cambios pendientes u omitidas que requieren revisión manual (simulacro o tras aplicar). `--empresa` y `--todas` no pueden usarse juntos.

## Seguridad y comportamiento
- **Simulacro por defecto**; `--aplicar` exige `--confirmo-respaldo`. Haz respaldo antes en cada BD.
- Al aplicar se guarda un registro en `tmp/esquema_bd_AAAAMMDD_HHMMSS.log` (carpeta ignorada por git) y se **verifica** al final que no quede nada pendiente.
- Cada acción se ejecuta por separado: si una falla, se informa y el resto continúa.
- **Claves foráneas**: se crean solo si no hay registros huérfanos. Si los hay: `OMITIDA` + consulta para verlos. Se depuran a mano y se reejecuta.
- **UNIQUE / PRIMARY**: solo si no hay duplicados.
- **Índices/FK equivalentes** (mismas columnas) con otro nombre se consideran cubiertos (`INFO`) para no duplicar; usa `--nombres-exactos` para igualar nombres.
- **Semillas**: solo inserta los códigos cuando la tabla está **vacía o es nueva**. Si el cliente ya tiene registros propios no los mezcla (avisa con `INFO`), porque podría solapar rangos de edad. Nunca copia `icono_ruta`. Tablas semilla definidas en `EsquemaBd::SEMILLAS`.
- **Tablas de respaldo** de desarrollo (`backup_*`, `*_backup`, `*_backup_*`) se excluyen de la referencia y no se crean en los clientes (`EsquemaBd::TABLAS_EXCLUIDAS_REGEX`).
- **ENUM**: si la referencia solo **agrega valores al final** de un ENUM existente (ej. `cotizaciones.rol_creador`), se amplía automáticamente conservando nulabilidad y default actuales. Si cambia el orden o se quitan valores, queda como `INFO` para revisar a mano.
- Collations de MySQL 8 (`utf8mb4_0900_*`) se traducen a `utf8mb4_unicode_ci` si el destino (MariaDB) no las conoce.

## Qué NO hace (a propósito)
- Eliminar tablas/columnas/índices sobrantes (se ven con la huella de `sql/2026_11_21_igualar_produccion_con_desarrollo/verificar_estructura.sql`).
- Cambiar tipo/nulabilidad/default de columnas existentes (p. ej. `cotizaciones.rol_creador` sin default): sale como `INFO` con el `ALTER` sugerido. Única excepción: ampliar un ENUM agregando valores al final.
- Renombrar columnas, corregir datos o *backfills* (van como migraciones numeradas en `sql/AAAA_MM_DD_.../`).
- Columnas generadas, `AUTO_INCREMENT` añadido a una tabla existente, índices funcionales y `CHECK`: se avisan para crear a mano.

## Dónde ejecutarlo
Las BD de hosting suelen aceptar solo conexiones locales (`DB_HOST = localhost`). Opciones: ejecutarlo en el propio servidor (SSH o *Cron Job* del hosting) o habilitar MySQL remoto y usar `--host`/`--puerto`.

## Verificar que quedó igual a dev
Ejecutar `sql/2026_11_21_igualar_produccion_con_desarrollo/verificar_estructura.sql` en dev y en el cliente: la `huella_global` debe coincidir (usando `--nombres-exactos`, y sin objetos sobrantes en el cliente).
