# Hoja de Pruebas Piloto Offline-First (Rutina Diaria)

Fecha de inicio: ____/____/______
Responsable: Proyecto individual
Duracion sugerida: 7 dias continuos

## Objetivo diario
Validar que los flujos offline sincronicen sin duplicados y sin romper operacion online.

## Reglas del piloto
- Ejecutar primero escenario offline y luego escenario online de control.
- Registrar evidencia minima por cada caso (captura o nota de resultado).
- Si un caso falla 2 veces seguidas, marcar incidencia y pausar ese modulo en offline.
- El reporte de resultados generado sin internet es solo provisional interno; no usarlo como entrega formal al paciente.

## Preparacion (antes de iniciar cada dia)
- Confirmar que no hay pendientes de cola de ayer en agenda, caja, inventario, pacientes y resultados.
- Limpiar solo errores antiguos no recuperables usando boton "Limpiar errores".
- Verificar fecha/hora del servidor y del equipo.

---

## Bloque A: Agenda (10-15 min)

### Caso A1 - Registro offline y sync
1. Desconectar internet.
2. Registrar una cita en agendar.
3. Confirmar mensaje de encolado offline.
4. Reconectar internet.
5. Usar boton "Sincronizar".
6. Validar que la cita se aplico 1 sola vez.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Operation ID (si visible): ____________________
- Observacion: __________________________________

### Caso A2 - Control online (no regresion)
1. Con internet activo, registrar una cita normal.
2. Validar guardado inmediato sin pasar por cola.

Resultado:
- [ ] OK
- [ ] FALLA

---

## Bloque B: Caja (10-15 min)

### Caso B1 - Apertura offline y sync
1. Desconectar internet.
2. Encolar apertura de caja (monto y observacion).
3. Reconectar internet.
4. Sincronizar.
5. Validar que solo exista 1 apertura efectiva.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Turno generado: ____________________
- Duplicado detectado: [ ] SI  [ ] NO

### Caso B2 - Control online (no regresion)
1. Realizar apertura normal online (cuando aplique en ambiente de prueba).
2. Validar comportamiento habitual.

Resultado:
- [ ] OK
- [ ] FALLA

---

## Bloque C: Inventario bajo riesgo (10-15 min)

### Caso C1 - Entrada offline y sync
1. Desconectar internet.
2. Registrar movimiento tipo "entrada".
3. Reconectar internet.
4. Sincronizar.
5. Validar stock final esperado.

Resultado:
- [ ] OK
- [ ] FALLA

### Caso C2 - Restriccion de alto riesgo offline
1. Desconectar internet.
2. Intentar movimiento de alto riesgo (salida/ajuste_neg/merma/vencido).
3. Validar que el sistema lo bloquee offline.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Tipo probado: ____________________
- Mensaje mostrado: ____________________

---

## Bloque D: Panel de soporte (5-10 min)

### Caso D1 - Ver cola y detalle
1. Abrir "Ver cola" en cada modulo.
2. Confirmar visualizacion de pendientes/errores.

Resultado:
- [ ] OK
- [ ] FALLA

### Caso D2 - Marcar incidencia
1. Usar "Marcar incidencia" cuando exista error.
2. Confirmar que se registro localmente para seguimiento.

Resultado:
- [ ] OK
- [ ] FALLA

---

## Bloque E: Pacientes (10-15 min)

### Caso E1 - Crear paciente offline y sync
1. Desconectar internet.
2. Ir a formulario de pacientes y registrar nuevo paciente.
3. Confirmar mensaje de encolado offline.
4. Reconectar internet.
5. Usar boton "Sincronizar" del formulario.
6. Validar que el paciente se creo 1 sola vez.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Documento: ____________________
- Duplicado detectado: [ ] SI  [ ] NO

### Caso E2 - Editar paciente offline y sync
1. Desconectar internet.
2. Abrir un paciente existente y editar telefono/direccion.
3. Guardar en offline.
4. Reconectar internet.
5. Sincronizar.
6. Validar que los cambios se aplicaron correctamente.

Resultado:
- [ ] OK
- [ ] FALLA

### Caso E3 - Control online (no regresion)
1. Con internet activo, crear o editar paciente normalmente.
2. Validar que el guardado online inmediato sigue funcionando.

Resultado:
- [ ] OK
- [ ] FALLA

### Caso E4 - Conflicto de edicion offline (desfasado)
1. Abrir el mismo paciente en dos sesiones distintas (Sesion A y Sesion B).
2. En Sesion A, desconectar internet y modificar telefono/direccion. Guardar para encolar.
3. En Sesion B (online), modificar cualquier campo del mismo paciente y guardar online.
4. Volver a Sesion A, reconectar internet y pulsar "Sincronizar".
5. Validar que la operacion de Sesion A NO sobreescriba a Sesion B y quede marcada como conflicto.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Mensaje esperado: Conflicto de edicion, requiere revision manual.
- Codigo esperado: offline_conflict_edit_stale
- Estado en cola: conflict

---

## Bloque F: Resultados (15-20 min)

### Caso F1 - Guardado offline y sync
1. Desconectar internet.
2. Abrir formulario de resultados de una cotizacion.
3. Llenar al menos 2 parametros y guardar.
4. Confirmar mensaje de encolado offline.
5. Reconectar internet y usar "Sincronizar".
6. Validar que el resultado se guardo una sola vez.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Cotizacion: ____________________
- Duplicado detectado: [ ] SI  [ ] NO

### Caso F2 - Descarga sin internet (provisional)
1. Con internet desconectado, pulsar "Descargar PDF" en resultados.
2. Validar que se descargue reporte local provisional (HTML).
3. Reconectar internet y descargar PDF oficial.
4. Validar que la entrega formal al paciente se haga solo con PDF oficial.

Resultado:
- [ ] OK
- [ ] FALLA

Evidencia:
- Archivo provisional descargado: [ ] SI  [ ] NO
- PDF oficial descargado luego de reconexion: [ ] SI  [ ] NO
- Entrega al paciente realizada con PDF oficial: [ ] SI  [ ] NO

### Caso F3 - Control online (no regresion)
1. Con internet activo, guardar resultados normalmente.
2. Confirmar mensaje de guardado exitoso.

Resultado:
- [ ] OK
- [ ] FALLA

---

## Criterios de aceptacion diaria
- 0 duplicados confirmados en agenda/caja/inventario/pacientes/resultados.
- Al menos 95% de pendientes sincronizados al primer reintento.
- 0 regresiones en flujo online.

Si NO cumple:
- Marcar estado del modulo como "Offline pausado".
- Operar modulo en modo online hasta corregir.

## Resumen diario
Dia: ____

- Agenda: [ ] Verde  [ ] Amarillo  [ ] Rojo
- Caja: [ ] Verde  [ ] Amarillo  [ ] Rojo
- Inventario: [ ] Verde  [ ] Amarillo  [ ] Rojo
- Pacientes: [ ] Verde  [ ] Amarillo  [ ] Rojo
- Resultados: [ ] Verde  [ ] Amarillo  [ ] Rojo

Incidencias del dia:
1. __________________________________________
2. __________________________________________
3. __________________________________________

Decision de cierre del dia:
- [ ] Continuar piloto igual
- [ ] Continuar con ajustes menores
- [ ] Pausar offline en modulo especifico

Modulo pausado (si aplica): ____________________
Motivo: ______________________________________

---

## Criterio de cierre de piloto (al final de 7 dias)
Aprobar despliegue gradual solo si:
- 0 duplicados durante 3 dias consecutivos.
- Sincronizacion mayor o igual a 95% en primer reintento.
- Sin incidentes criticos abiertos.

Si no cumple, extender piloto 3 dias mas con foco en el modulo fallido.
