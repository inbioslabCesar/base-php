# Plan tecnico: Formato de examenes dinamico por columnas (sin romper legado)

## 1) Objetivo
Permitir que cada formato de examen de laboratorio defina sus propias columnas:
- Renombrar encabezados.
- Agregar o quitar columnas.
- Mantener la logica actual de captura, formulas, referencia y PDF.
- No romper historicos ni examenes existentes.

## 2) Alcance funcional esperado
El sistema debe soportar:
- Formatos clasicos (estructura actual de 5 columnas) sin cambios.
- Formatos dinamicos (plantilla por columnas) para examenes nuevos o seleccionados.
- Ejemplos como tabla de motilidad:
  - MOTILIDAD
  - EVAL 1 HORA
  - EVAL 2 HORAS
  - UNIDADES
  - VALORES REFERENCIALES
- Vista de captura y PDF alineadas al mismo esquema de columnas.

Fuera de alcance inicial:
- Diseñador visual avanzado tipo drag and drop libre de celdas combinadas.
- Versionado historico completo por cada cambio de plantilla (se mantiene snapshot actual por cotizacion).

## 3) Diagnostico actual (resumen)
Situacion observada en el codigo actual:
- Builder de examenes con columnas fijas y serializacion por indices de celda.
- Render de captura de resultados orientado a tipos de fila y campos esperados.
- PDF de resultados con 5 columnas fijas y colspan fijo.
- Guardado de formato adicional en JSON flexible con id_parametro estable.
- Snapshot del formato en resultados por cotizacion, lo que protege historicos.

Conclusion: la mejora es viable y puede hacerse de forma incremental con compatibilidad hacia atras.

## 4) Principio de compatibilidad
Regla principal:
- Si un examen NO tiene definicion dinamica, usar flujo actual sin cambios.
- Si un examen SI tiene definicion dinamica, activar renderer dinamico.

Esto permite rollout gradual sin impacto global.

## 5) Propuesta de modelo de datos (v2)
Se propone extender el JSON de formato con un esquema v2.

### 5.1 Estructura de alto nivel
```json
{
  "schema_version": 2,
  "layout": {
    "columns": [
      {
        "id": "col_parametro",
        "label": "Motilidad",
        "kind": "text",
        "role": "descriptor",
        "editable": false,
        "visible_capture": true,
        "visible_pdf": true,
        "width": "28%",
        "order": 1
      },
      {
        "id": "col_eval_1h",
        "label": "Eval 1 hora",
        "kind": "result",
        "role": "result",
        "editable": true,
        "visible_capture": true,
        "visible_pdf": true,
        "width": "18%",
        "order": 2
      },
      {
        "id": "col_eval_2h",
        "label": "Eval 2 horas",
        "kind": "result",
        "role": "result",
        "editable": true,
        "visible_capture": true,
        "visible_pdf": true,
        "width": "18%",
        "order": 3
      },
      {
        "id": "col_unidades",
        "label": "Unidades",
        "kind": "text",
        "role": "unit",
        "editable": false,
        "visible_capture": true,
        "visible_pdf": true,
        "width": "12%",
        "order": 4
      },
      {
        "id": "col_referencia",
        "label": "Valores referenciales",
        "kind": "reference",
        "role": "reference",
        "editable": false,
        "visible_capture": true,
        "visible_pdf": true,
        "width": "24%",
        "order": 5
      }
    ],
    "rows": [
      {
        "id_parametro": "param_abc123",
        "type": "data",
        "cells": {
          "col_parametro": "3. Lineear Rapido",
          "col_eval_1h": "65",
          "col_eval_2h": "50",
          "col_unidades": "%",
          "col_referencia": "(Grado 3: > 25)"
        },
        "style": {
          "bold": false,
          "italic": false,
          "align": "left"
        }
      }
    ]
  },
  "legacy": {
    "adicional": []
  }
}
```

### 5.2 Campos clave
- columns.id: llave estable interna (no depende del texto visible).
- columns.label: encabezado editable por usuario.
- columns.kind: tipo tecnico (text, result, reference, formula, select, long_text).
- columns.role: comportamiento de negocio (result, reference, descriptor, unit).
- rows.cells: valor por id de columna.

### 5.3 Compatibilidad con formulas
Las formulas deben migrar de referencias por nombre a referencias por id estable:
- Antes: [Hemoglobina]/[Hematocrito]
- Propuesto: [param_hemoglobina]/[param_hematocrito]

Se puede mostrar nombre amigable en UI y guardar id estable internamente.

## 6) Arquitectura de implementacion por fases

## Fase 0: Alineacion funcional
Objetivo:
- Definir reglas de negocio para columnas dinamicas.

Entregables:
- Catalogo de tipos de columna y roles.
- Matriz de comportamiento captura vs PDF.
- Criterios de aceptacion.

Criterio de salida:
- Documento aprobado por negocio y equipo tecnico.

## Fase 1: Adaptador de compatibilidad
Objetivo:
- Introducir parser comun que soporte formato clasico y v2.

Entregables:
- Funcion adaptadora:
  - Si schema_version=2, usa layout dinamico.
  - Si no existe, transforma legacy a estructura canonica temporal.
- Sin cambios visuales al usuario final aun.

Criterio de salida:
- Examenes actuales siguen funcionando igual.

## Fase 2: Builder dinamico de columnas
Objetivo:
- Permitir crear/editar columnas desde UI.

Entregables:
- Pantalla para agregar, renombrar, ordenar, ocultar columnas.
- Restricciones de seguridad:
  - No eliminar columna en uso sin confirmacion.
  - Si se renombra, no perder llaves internas.
- Preview en tiempo real basado en schema, no en indices fijos.

Criterio de salida:
- Se puede reproducir tabla tipo motilidad en el builder.

## Fase 3: Captura de resultados dinamica
Objetivo:
- Render de examen en base al esquema v2.

Entregables:
- Render dinamico por columns + rows.
- Inputs editables segun kind/editable.
- Guardado de resultados por llave estable.
- Fallback para leer datos legacy por nombre cuando aplique.

Criterio de salida:
- Captura web funcional para formatos v2 sin romper legacy.

## Fase 4: PDF dinamico
Objetivo:
- PDF alineado al esquema de columnas definido.

Entregables:
- Cabecera de tabla dinamica (labels variables).
- Cuerpo dinamico por rows/cells.
- Manejo correcto de titulos/subtitulos/texto largo con colspan segun numero de columnas.
- Fallback a layout clasico para examenes legacy.

Criterio de salida:
- PDF del caso motilidad coincide con el formato esperado.

## Fase 5: Migracion gradual y rollout
Objetivo:
- Activacion progresiva sin riesgo operativo.

Entregables:
- Feature flag de activacion por examen o por area.
- Piloto con 1 o 2 examenes complejos.
- Checklist de validacion y monitoreo.

Criterio de salida:
- Produccion estable con adopcion progresiva.

## 7) Riesgos y mitigacion
1. Riesgo: formulas dependen de nombres visibles.
   Mitigacion: llaves estables internas + traductor legacy.

2. Riesgo: desalineacion por serializacion con indices de tabla.
   Mitigacion: serializar por id de columna.

3. Riesgo: PDF rompe estilos por columnas variables.
   Mitigacion: layout responsive con anchos configurables y fallback clasico.

4. Riesgo: experiencia movil con muchas columnas.
   Mitigacion: vista compacta + scroll horizontal + columnas prioritarias.

5. Riesgo: regresion en examenes actuales.
   Mitigacion: feature flag + adaptador + suite de pruebas de regresion.

## 8) Estrategia de datos y migracion
No hacer migracion masiva inicial.
Estrategia recomendada:
- Registros legacy permanecen igual.
- Examen nuevo dinamico nace en v2.
- Examen legacy solo migra cuando el usuario lo edite y confirme migracion.
- Mantener snapshot por cotizacion para historico inmutable.

## 9) Pruebas minimas por fase
Casos obligatorios:
- Crear formato clasico (debe seguir igual).
- Crear formato dinamico con columnas personalizadas.
- Renombrar columna sin perder datos previos.
- Agregar columna nueva y guardar resultados.
- Eliminar columna no critica con confirmacion.
- Formula entre celdas con ids estables.
- PDF clasico intacto y PDF dinamico correcto.
- Prueba en desktop y movil.

## 10) Estimacion de esfuerzo
Estimacion orientativa:
- Fase 0: 2 a 4 dias.
- Fase 1: 2 a 3 dias.
- Fase 2: 5 a 8 dias.
- Fase 3: 4 a 7 dias.
- Fase 4: 4 a 6 dias.
- Fase 5: 3 a 5 dias.

Total aproximado: 3 a 5 semanas (dependiendo de ajustes de negocio y QA).

## 11) Recomendacion de ramas (git)
Si, se recomienda trabajar en rama nueva.

Propuesta:
- Rama principal de la iniciativa:
  - feature/lab-format-dynamic-columns
- Subramas por fase (opcional si el equipo es grande):
  - feature/lab-format-v2-adapter
  - feature/lab-format-v2-builder
  - feature/lab-format-v2-capture
  - feature/lab-format-v2-pdf

Reglas sugeridas:
- PR pequenos por fase, no un PR gigante.
- Merge con feature flag apagado por defecto.
- Activacion controlada por examen para piloto.

## 12) Criterio de exito final
Se considera exitoso cuando:
- El usuario puede definir nombres de columnas personalizados.
- Puede agregar columnas adicionales sin cambios de codigo.
- No se rompen examenes legacy ni historicos impresos.
- Captura y PDF reflejan exactamente la estructura definida en el formato.
