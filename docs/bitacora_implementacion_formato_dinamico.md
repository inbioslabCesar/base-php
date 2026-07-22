# Bitacora de implementacion: formato dinamico de examenes

Fecha: 2026-07-19

## Objetivo de esta ejecucion
Dejar una primera mejora funcional en el sistema para soportar formatos dinamicos por columnas, con fallback completo al formato legacy.

## Cambios aplicados

1. Feature flag de activacion
- Se agrego la bandera LAB_FORMAT_V2_ENABLED en configuracion global.
- Valor por defecto actual: habilitado (1) si no existe variable de entorno.
- Archivo: src/config/config.php

2. Helper comun para formato v2
- Nuevo helper con funciones de:
  - deteccion de esquema v2,
  - lectura de columnas y filas,
  - llaves estables por celda (v2__row__col),
  - lectura de resultados,
  - visibilidad por destino (captura/PDF),
  - editabilidad por columna.
- Archivo: src/examenes/formato_dinamico_helper.php

3. Captura de resultados (pantalla operativa)
- Se integro renderer v2 en la vista de examen.
- Si el examen viene con schema_version=2 y layout.columns/layout.rows:
  - se muestra tabla dinamica,
  - celdas editables se guardan con key estable v2__{rowId}__{colId},
  - celdas no editables se muestran como plantilla.
- Si no hay v2, se mantiene flujo legacy intacto.
- Archivo: src/resultados/vistas/ExamCardView.php

4. Guardado de resultados
- Se ajusto normalizacion para preservar keys v2__* sin remapeo.
- Archivo: src/resultados/guardar.php

5. PDF de resultados
- Se agrego soporte de item TablaV2 en armado de datos del PDF.
- Se agrego renderer en HTML de PDF para tabla dinamica anidada.
- Se mantiene renderer legacy de 5 columnas.
- Archivos:
  - src/resultados/resultados_pdf_datos.php
  - src/resultados/resultados_pdf_html.php

6. Endpoint de reporte JSON
- Se agrego salida TablaV2 cuando el formato es v2.
- Se mantiene salida legacy en el resto.
- Archivo: src/resultados/datos-reporte.php

7. Vista de lectura de resultados guardados
- Se agrego render v2 en ver.php.
- Se mantiene render legacy.
- Archivo: src/resultados/ver.php

8. Builder visual v2 en formulario de examen
- Se agrego switch de modo en formulario de examen:
  - legacy (actual)
  - dinamico v2
- Se agregaron secciones nuevas en UI para:
  - columnas dinamicas (label, id, tipo, editable, visible captura/pdf, ancho, orden)
  - filas dinamicas
  - preview en tiempo real de tabla v2
- La serializacion de guardado ahora es condicional:
  - si v2 activo: guarda schema_version=2 con layout.columns/layout.rows
  - si v2 inactivo: mantiene JSON legacy actual
- Archivos:
  - src/examenes/form_examen.php
  - src/examenes/format-builder.js

9. Migrador asistido legacy -> v2 (nuevo)
- Se agrego boton de migracion en el modo v2: "Migrar formato legacy a v2".
- La migracion convierte automaticamente:
  - Tipo Titulo/Subtitulo legacy -> filas v2 tipo title/subtitle.
  - Tipo Parametro/Campo/Texto Largo -> filas v2 tipo data.
- Se crea una base de columnas v2 equivalente al formato estandar:
  - Parametro
  - Metodologia
  - Resultado
  - Unidades
  - Valores referenciales
- El id_parametro legacy se reutiliza como id de fila v2 cuando existe.
- Archivos:
  - src/examenes/form_examen.php
  - src/examenes/format-builder.js

10. Validaciones avanzadas del builder v2 (nuevo)
- Se agrego validacion previa al guardado cuando el modo v2 esta activo.
- Si falla validacion, se bloquea submit y se muestran errores visibles en pantalla.
- Reglas implementadas:
  - Al menos una columna.
  - IDs de columna no vacios y unicos.
  - Etiquetas de columna no vacias.
  - Al menos una columna visible en captura.
  - Al menos una columna editable.
  - Al menos una fila.
  - Al menos una fila tipo data.
  - Fila title/subtitle requiere etiqueta.
  - Celdas no pueden apuntar a columnas inexistentes.
- Archivos:
  - src/examenes/form_examen.php
  - src/examenes/format-builder.js

11. Soporte de formulas en formato dinamico v2 (nuevo)
- Se implemento soporte de formulas para celdas editables en v2.
- Sintaxis soportada:
  - [colId]: referencia columna de la misma fila.
  - [rowId:colId]: referencia columna de otra fila.
- En captura de resultados:
  - campos con formula se marcan como calculados y readonly,
  - recalculan en tiempo real al cambiar campos fuente,
  - soporta dependencias encadenadas.
- En backend (PDF y reportes):
  - se recalculan formulas server-side para asegurar salida consistente aunque el valor no se haya guardado manualmente.
- En builder v2:
  - se agrego input de formula por celda editable,
  - se serializa en row.formulas,
  - se valida sintaxis basica antes de guardar.
- Migrador legacy -> v2:
  - convierte formulas legacy [Nombre] a tokens v2 [rowId:resultado] cuando encuentra correspondencia por nombre.
- Archivos:
  - src/examenes/formato_dinamico_helper.php
  - src/resultados/vistas/ExamCardView.php
  - src/resultados/recursos/formulario.js
  - src/resultados/resultados_pdf_datos.php
  - src/resultados/datos-reporte.php
  - src/resultados/ver.php
  - src/examenes/format-builder.js

## Validaciones realizadas
- Revision de errores de compilacion/sintaxis en todos los archivos tocados.
- Estado actual: sin errores reportados por el analizador.

## Como usar el builder v2 desde la UI (actual)
1. Ir a crear/editar examen.
2. Activar switch: "Usar formato dinamico de columnas (v2)".
3. En "Columnas dinamicas":
  - agregar columnas,
  - definir etiqueta e id,
  - marcar si es editable,
  - definir visibilidad en captura y PDF.
4. En "Filas del formato":
  - agregar filas,
  - elegir tipo (data, titulo, subtitulo),
  - llenar celdas para filas data.
5. Revisar "Vista previa dinamica".
6. Guardar examen.

Flujo recomendado para examenes existentes en legacy:
1. Abrir examen en editar.
2. Activar modo v2.
3. Clic en "Migrar formato legacy a v2".
4. Revisar/ajustar columnas y filas resultantes.
5. Guardar examen.

Nota:
- Si el switch v2 esta apagado, el examen sigue usando el constructor legacy y no cambia su comportamiento previo.
- Si el switch v2 esta encendido y hay errores de esquema, el sistema no deja guardar hasta corregirlos.

## Como preparar un examen en modo v2 (actual)
Por ahora, el builder visual legacy no genera esquema v2 automaticamente.
Para usar v2 hoy, el campo examenes.adicional debe tener JSON v2 valido.

Ejemplo minimo:
```json
{
  "schema_version": 2,
  "layout": {
    "columns": [
      {"id":"motilidad","label":"Motilidad","kind":"text","editable":false,"visible_capture":true,"visible_pdf":true,"order":1},
      {"id":"eval_1h","label":"Eval 1 hora","kind":"result","editable":true,"visible_capture":true,"visible_pdf":true,"order":2},
      {"id":"eval_2h","label":"Eval 2 horas","kind":"result","editable":true,"visible_capture":true,"visible_pdf":true,"order":3},
      {"id":"unidad","label":"Unidades","kind":"text","editable":false,"visible_capture":true,"visible_pdf":true,"order":4},
      {"id":"ref","label":"Valores referenciales","kind":"reference","editable":false,"visible_capture":true,"visible_pdf":true,"order":5}
    ],
    "rows": [
      {
        "id":"fila_1",
        "type":"data",
        "cells": {
          "motilidad":"3. Lineear rapido",
          "eval_1h":"",
          "eval_2h":"",
          "unidad":"%",
          "ref":"(Grado 3: > 25)"
        }
      }
    ]
  }
}
```

## Procedimiento recomendado de despliegue
1. Crear rama: feature/lab-format-dynamic-columns
2. Publicar PR tecnico inicial con estos cambios.
3. Probar en entorno de pruebas con 1 examen piloto en v2.
4. Validar:
- captura desktop y movil,
- guardado y reapertura,
- impresion PDF,
- flujo legacy en paralelo.
5. Si hay incidencia, rollback rapido:
- setear LAB_FORMAT_V2_ENABLED=0 en entorno,
- redeploy.

## Pendientes para siguiente iteracion
1. Migrador asistido legacy -> v2 desde formulario de examen.
2. Reglas de formula v2 por ids estables.
3. Tipos de columna avanzados (select/rango validado).
4. Pruebas funcionales automatizadas de v2 y regresion legacy.
