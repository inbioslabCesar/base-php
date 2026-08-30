# Guia Rapida: Prueba de Conflicto Offline en Pacientes

Fecha: ____/____/______
Responsable: ____________________

## Objetivo
Verificar que una edicion offline desfasada de pacientes no sobreescriba cambios online mas recientes.

## Precondiciones
- Existe al menos 1 paciente editable.
- Sesion A y Sesion B abiertas con el mismo usuario o usuarios con permisos.
- Ambas sesiones apuntan al mismo paciente.

## Escenario Canonico (5 pasos)
1. Sesion A: abrir formulario del paciente y dejar la pagina abierta.
2. Sesion A: cortar internet, cambiar telefono o direccion y guardar (queda en cola offline).
3. Sesion B: con internet activo, editar el mismo paciente y guardar online.
4. Sesion A: restaurar internet y pulsar Sincronizar.
5. Validar resultado.

## Resultado Esperado
- La sincronizacion de Sesion A debe devolver conflicto y NO aplicar sobreescritura.
- Mensaje de error esperado: Conflicto de edicion, requiere revision manual.
- Codigo esperado: offline_conflict_edit_stale.
- En el panel de cola de pacientes: estado conflict.
- El dato final del paciente debe conservar el cambio de Sesion B.

## Evidencia Minima a Guardar
- ID o documento del paciente probado.
- Captura del mensaje de conflicto.
- Captura de la cola con estado conflict.
- Valor final de telefono/direccion en ficha del paciente.

## Criterio de Aprobacion
- Aprobado si 3 de 3 intentos consecutivos cumplen el resultado esperado sin sobreescritura.

## Si Falla
- Marcar incidencia local desde el boton Marcar incidencia.
- Mantener modulo en modo online para ediciones del paciente afectado.
- Escalar con evidencia al cierre del dia.
