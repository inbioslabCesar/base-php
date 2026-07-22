# Plan de PRs: Formato de examenes dinamico por columnas

## 1) Objetivo del plan
Definir un flujo de trabajo por PRs pequenos para implementar formatos de examen dinamicos sin romper:
- Flujo actual de examenes legacy.
- Captura de resultados en operacion.
- Impresion PDF historica.

## 2) Estrategia de ramas
Rama base de iniciativa:
- feature/lab-format-dynamic-columns

Subramas recomendadas por PR:
- feature/lab-format-v2-pr1-foundation
- feature/lab-format-v2-pr2-adapter
- feature/lab-format-v2-pr3-builder
- feature/lab-format-v2-pr4-capture
- feature/lab-format-v2-pr5-pdf
- feature/lab-format-v2-pr6-rollout

Regla:
- Cada subrama sale de feature/lab-format-dynamic-columns.
- Cada PR vuelve a feature/lab-format-dynamic-columns.
- Al final, un PR final a main cuando QA del piloto este aprobado.

## 3) Orden de PRs y alcance exacto

## PR 1: Foundation y Feature Flag
Titulo sugerido:
- PR1 - Base tecnica para formato dinamico y feature flag

Incluye:
- Config de feature flag para activar formato v2 por examen.
- Contrato inicial de schema v2 (documentado en codigo).
- Estructuras auxiliares para parser canonico.
- Sin cambios visuales para usuarios.

No incluye:
- Builder v2.
- Render dinamico en captura.
- PDF dinamico.

Checklist tecnico:
- Feature flag apagado por defecto.
- Legacy sigue funcionando igual.
- Logs basicos para saber si un examen entra a flujo v2 o legacy.

Checklist QA:
- Crear/editar examen legacy sigue igual.
- Captura legacy sin cambios.
- PDF legacy sin cambios.

Criterio de merge:
- Cero regresiones visibles en flujo actual.

## PR 2: Adaptador legacy-v2
Titulo sugerido:
- PR2 - Adaptador de formato legacy a modelo canonico

Incluye:
- Funcion adaptadora:
  - Si schema_version=2: usa layout v2.
  - Si no: transforma adicional legacy a estructura canonica en runtime.
- Tests unitarios del adaptador.

No incluye:
- UI nueva.
- Cambios de PDF.

Checklist tecnico:
- Cobertura de casos legacy: Parametro, Titulo, Subtitulo, Texto Largo.
- Respeta id_parametro estable existente.

Checklist QA:
- Examenes actuales renderizan igual en captura.
- No se alteran datos guardados.

Criterio de merge:
- Adaptador validado con muestras reales de examenes existentes.

## PR 3: Builder v2 (columnas dinamicas)
Titulo sugerido:
- PR3 - Constructor de formatos con columnas dinamicas

Incluye:
- UI para crear, renombrar, ordenar, agregar y ocultar columnas.
- Serializacion por id de columna estable, no por indices de celda.
- Preview en tiempo real segun esquema.
- Compatibilidad visual movil y desktop.

No incluye:
- Render de captura de resultados v2.
- PDF v2.

Checklist tecnico:
- Validaciones de esquema (ids unicos, tipos validos, orden valido).
- Bloqueo de acciones destructivas sin confirmacion.

Checklist QA:
- Crear plantilla tipo motilidad desde UI.
- Renombrar columnas sin perder datos de plantilla.
- Agregar columna nueva sin errores JS.

Criterio de merge:
- Builder guarda y reabre plantilla v2 correctamente.

## PR 4: Captura de resultados v2
Titulo sugerido:
- PR4 - Render y guardado de resultados para formato dinamico

Incluye:
- Render de captura en base a schema v2.
- Inputs editables por tipo de columna.
- Guardado de resultados por llaves estables.
- Fallback de lectura legacy por nombre cuando aplique.
- Compatibilidad con formulas en ids estables.

No incluye:
- PDF v2.

Checklist tecnico:
- No rompe flujo legacy.
- Mantiene data-referencias y validacion en tiempo real en campos aplicables.
- Mantiene id_parametro como ancla de compatibilidad.

Checklist QA:
- Guardar y reabrir resultados de examen v2.
- Renombrar columna y confirmar persistencia correcta.
- Verificar examen legacy en paralelo (sin degradacion).

Criterio de merge:
- Captura v2 estable con prueba funcional de 1 examen piloto.

## PR 5: PDF dinamico v2
Titulo sugerido:
- PR5 - Generacion PDF con columnas dinamicas y fallback legacy

Incluye:
- Header PDF dinamico segun labels de columnas.
- Render de filas dinamicas por cells.
- Manejo de titulos/subtitulos/texto largo con colspan segun numero de columnas.
- Fallback a renderer de 5 columnas para legacy.

No incluye:
- Activacion global.

Checklist tecnico:
- Mantiene estilos legibles para 5+ columnas.
- Control de anchos y saltos de pagina.

Checklist QA:
- PDF v2 del caso motilidad coincide con formato esperado.
- PDF legacy sin cambios.

Criterio de merge:
- Aprobacion funcional de laboratorio en el formato objetivo.

## PR 6: Rollout controlado y observabilidad
Titulo sugerido:
- PR6 - Activacion gradual, metricas y guia operativa

Incluye:
- Activacion por examen o por area mediante flag.
- Checklist de habilitacion para soporte.
- Telemetria/logs de errores por render v2.
- Plan de rollback rapido por examen.

Checklist tecnico:
- Switch inmediato v2 -> legacy por examen.
- Logs de fallos con contexto minimo.

Checklist QA:
- Piloto en 1-2 examenes reales.
- Validacion desktop y movil.
- Validacion en impresiones reales.

Criterio de merge:
- Piloto estable por al menos un ciclo operativo definido por negocio.

## 4) Definicion de Done por PR
Para cada PR:
- Compila y pasa pruebas existentes.
- Incluye pruebas nuevas del alcance.
- Incluye notas de cambio en docs.
- Incluye evidencia de QA (capturas o checklist marcado).
- No introduce regresion en flujo legacy.

## 5) Matriz minima de pruebas por fase
Casos transversales:
- Crear examen legacy.
- Editar examen legacy.
- Crear formato v2 simple.
- Crear formato v2 tipo motilidad (multiple columnas de resultado).
- Guardar resultados y reabrir.
- Generar PDF legacy y v2.
- Pruebas en desktop y movil.

Casos de borde:
- Columna nueva agregada despues de tener resultados previos.
- Columna renombrada con resultados existentes.
- Formula con dependencias encadenadas.
- Referencias por edad/sexo en campos aplicables.

## 6) Plan de rollback
Nivel 1 (inmediato):
- Desactivar feature flag del examen afectado.

Nivel 2 (operativo):
- Volver a flujo legacy para toda el area.

Nivel 3 (codigo):
- Revertir ultimo PR de rollout, manteniendo PRs base ya validados.

## 7) Convencion de commits sugerida
- feat(lab-format): PR1 foundation and feature flag
- feat(lab-format): PR2 legacy to canonical adapter
- feat(lab-format): PR3 dynamic builder
- feat(lab-format): PR4 dynamic capture and save
- feat(lab-format): PR5 dynamic pdf renderer
- chore(lab-format): PR6 rollout and observability

## 8) Calendario sugerido (orientativo)
- Semana 1: PR1 + PR2
- Semana 2: PR3
- Semana 3: PR4
- Semana 4: PR5
- Semana 5: PR6 + piloto + ajustes

## 9) Responsables recomendados
- Backend: adaptador, guardado, PDF.
- Frontend: builder v2 y captura v2.
- QA funcional: laboratorio y validacion cruzada legacy/v2.
- Soporte: checklist de activacion y rollback.

## 10) Resultado esperado
Al cerrar PR6:
- Se puede crear formato con columnas personalizadas.
- Se pueden agregar columnas sin tocar codigo.
- Captura y PDF reflejan exactamente el formato definido.
- Examenes legacy continan operando sin cambios.
