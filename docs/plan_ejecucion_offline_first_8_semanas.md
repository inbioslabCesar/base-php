# Plan de Ejecucion Offline-First (8 Semanas)

## Objetivo
Implementar un enfoque hibrido:
- Landing de pacientes online-first, sin friccion.
- PWA opcional para pacientes recurrentes.
- Offline-first real para operacion interna (agenda, caja, inventario) con sincronizacion segura.

## Alcance
- Incluye: frontend web, backend PHP/API, base de datos, QA, despliegue gradual y capacitacion.
- Excluye: app nativa en stores (Play Store/App Store).

## Equipo y Roles
- Frontend: UI/UX, service worker, almacenamiento local, estados de sincronizacion.
- Backend: endpoints idempotentes, reconciliacion, auditoria, reglas de conflicto.
- QA: casos offline/online, duplicados, rendimiento de reconexion.
- Operaciones: piloto por sede, soporte, capacitacion, protocolo de contingencia.

## Criterios de Exito (KPI)
- Menos de 1% de operaciones fallidas en reconexion.
- Menos de 0.2% de duplicados transaccionales.
- 95% de pendientes sincronizados en menos de 5 minutos tras reconexion.
- Cero interrupciones operativas criticas en cortes menores a 2 horas.
- Sin caida de conversion en landing por cambios PWA.

---

## Semana 1: Diseno tecnico y linea base

### Frontend
- Definir matriz de pantallas criticas por modulo (agenda, caja, inventario).
- Instrumentar metricas base de performance (tiempo de carga, interacciones clave).
- Definir UX de estados de red: online, offline, sincronizando, error.

### Backend
- Definir contrato de idempotencia por operacion (operation_id, source, created_at).
- Definir estructura de tabla de auditoria y estado de sincronizacion.
- Documentar codigos de error para reintentos controlados.

### QA
- Crear plan de pruebas offline/online y escenarios de reconexion.
- Definir dataset de pruebas por sede y por tipo de operacion.

### Operaciones
- Seleccionar sede piloto y usuarios clave.
- Definir protocolo de reporte de incidentes durante piloto.

### Checklist de salida
- [ ] Documento de arquitectura offline aprobado.
- [ ] Contrato API de sincronizacion aprobado.
- [ ] Matriz de pruebas inicial validada.
- [ ] Sede piloto confirmada.

---

## Semana 2: Base PWA y cache de estaticos (sin transacciones offline)

### Frontend
- Implementar manifest web app.
- Registrar service worker para cache de assets estaticos (CSS/JS/iconos).
- Agregar prompt de instalacion opcional para pacientes recurrentes.
- Mantener flujo de landing sin bloqueo por instalacion.

### Backend
- Exponer version de build/config para invalidar cache de manera segura.
- Asegurar headers de cache coherentes para estaticos/versionado.

### QA
- Probar instalacion opcional en Android/Chrome y navegadores de escritorio compatibles.
- Validar que sin instalar se mantenga experiencia normal.
- Validar estrategia de invalidacion de cache tras deploy.

### Operaciones
- Revisar mensajes de soporte para usuarios que vean opcion "Instalar".

### Checklist de salida
- [ ] Manifest y service worker operativos en entorno de pruebas.
- [ ] Instalacion opcional visible sin afectar conversion.
- [ ] Cache invalidado correctamente entre versiones.

---

## Semana 3: Cola offline para Agenda (MVP)

### Frontend
- Implementar cola local (IndexedDB) para operaciones de agenda.
- Guardar operaciones pendientes con metadata (operation_id, modulo, payload, timestamp).
- Mostrar badge y listado basico de pendientes.

### Backend
- Crear endpoint de sincronizacion por lote para agenda.
- Implementar idempotencia server-side por operation_id.
- Registrar auditoria de operaciones sincronizadas.

### QA
- Validar creacion de citas offline y sincronizacion al reconectar.
- Validar no-duplicidad ante reintentos multiples.

### Operaciones
- Ensayar protocolo de caida de red con usuarios piloto.

### Checklist de salida
- [ ] Agenda crea pendientes offline correctamente.
- [ ] Sincronizacion por lote funcional.
- [ ] Idempotencia validada en pruebas.

---

## Semana 4: Cola offline para Caja (MVP)

### Frontend
- Extender cola local a operaciones de caja (abonos/pagos permitidos en piloto).
- UI clara por estado: pendiente, sincronizado, error, requiere revision.

### Backend
- Endpoint de sincronizacion para caja con validaciones de negocio.
- Reglas anti-duplicado y conciliacion basica de montos.

### QA
- Pruebas de reconexion en pagos con casos de timeout e intermitencia.
- Validar consistencia entre frontend y backend en estados finales.

### Operaciones
- Definir reglas operativas de que pagos SI/NO se permiten offline.

### Checklist de salida
- [ ] Caja sincroniza operaciones sin duplicados.
- [ ] Casos de timeout/reintento cubiertos.
- [ ] Regla operativa de pagos offline aprobada.

---

## Semana 5: Inventario offline controlado

### Frontend
- Habilitar cola offline para movimientos internos de bajo riesgo.
- Bloquear en UI operaciones de alto riesgo fuera de linea.

### Backend
- Sincronizacion de inventario con validacion de stock y trazabilidad.
- Preparar reglas de conflicto (por timestamp, prioridad rol, sede).

### QA
- Pruebas de conflictos de stock entre sedes en reconexion.
- Pruebas de bloqueo en operaciones no permitidas offline.

### Operaciones
- Definir politicas de inventario durante contingencia de red.

### Checklist de salida
- [ ] Movimientos de bajo riesgo operativos offline.
- [ ] Operaciones restringidas correctamente bloqueadas.
- [ ] Conflictos detectados y auditados.

---

## Semana 6: Resolucion de conflictos y panel de soporte

### Frontend
- Crear panel interno de cola: ver pendientes, reintentar, filtrar por modulo/sede.
- Exponer errores accionables para soporte operativo.

### Backend
- Implementar motor de reconciliacion (auto + manual asistida).
- Endpoint de reintento individual y por lote.

### QA
- Validar escenarios de conflicto severo y recuperacion.
- Validar que no haya perdida silenciosa de operaciones.

### Operaciones
- Entrenar personal de soporte en uso de panel y protocolo de escalamiento.

### Checklist de salida
- [ ] Panel de soporte funcional.
- [ ] Reconciliacion automatica aplicada en casos comunes.
- [ ] Flujo de escalamiento documentado.

---

## Semana 7: Piloto controlado por sede

### Frontend
- Activar feature flags por sede/usuario.
- Ajustar UX segun feedback real del piloto.

### Backend
- Monitoreo de tasa de errores de sync por modulo.
- Endurecimiento de logs y trazabilidad por operation_id.

### QA
- Smoke tests diarios en horario real de operacion.
- Reporte diario de defectos y severidad.

### Operaciones
- Ejecucion de piloto con mesa de ayuda activa.
- Registro de incidentes y tiempos de resolucion.

### Checklist de salida
- [ ] Piloto activo con feature flags.
- [ ] Incidentes clasificados y atendidos.
- [ ] KPI preliminares dentro de umbrales.

---

## Semana 8: Go-live progresivo y cierre

### Frontend
- Ajustes finales de UX y mensajes de estados.
- Hardening de instalacion opcional en landing.

### Backend
- Ajustes finales de performance en sincronizacion.
- Cierre de observabilidad y alertas.

### QA
- Regresion final end-to-end de modulos impactados.
- Firma de salida por criterios de aceptacion.

### Operaciones
- Despliegue progresivo por sedes.
- Capacitacion final y manual corto de contingencia.

### Checklist de salida
- [ ] Despliegue progresivo completado.
- [ ] KPI de estabilizacion dentro de objetivo.
- [ ] Manual operativo entregado.
- [ ] Plan de mejora continua definido.

---

## Riesgos Clave y Mitigacion
- Duplicados por reconexion: idempotencia obligatoria + operation_id unico.
- Desfase de stock entre sedes: reconciliacion por reglas + auditoria completa.
- Fatiga operativa en piloto: feature flags + soporte activo + entrenamiento.
- Cache desactualizada: versionado de assets + invalidacion controlada.

## Politica Recomendada para Pacientes
- Landing publica se mantiene web normal.
- Instalacion PWA solo como opcion (no obligatoria).
- No habilitar flujos transaccionales criticos offline para pacientes.

## Entregables Finales
- Arquitectura offline documentada.
- PWA opcional para pacientes recurrentes.
- Cola/sync operativa en agenda, caja e inventario (alcance definido).
- Panel de soporte de sincronizacion.
- Playbook operativo para contingencias de red.
- Hoja diaria de piloto: `docs/hoja_pruebas_piloto_offline_first_diario.md`.

---

## Estado de Avance Real (Implementado)

- Semana 1: Completada.
	- Diagnostico tecnico creado en `docs/diagnostico_offline_first_semana1.md`.
	- Baseline de red en dashboard: `src/assets/js/offline-readiness-baseline.js`.

- Semana 2: Completada (alcance seguro).
	- Manifest dinamico: `src/pwa/manifest.php`.
	- Service worker de cache/fallback publico: `src/pwa/sw.js`.
	- Offline fallback: `src/pwa/offline.html`.
	- Registro/instalacion opcional: `src/pwa/pwa-register.js`, `src/pwa/pwa-install.js`.
	- Integrado en landing clasica y premium.

- Semana 3: MVP de agenda implementado.
	- Cola offline local (IndexedDB) en vista de agenda.
	- Sincronizacion manual y automatica al reconectar.
	- Idempotencia backend via `offline_operation_id`.
	- Persistencia de operaciones de sync en tabla `agenda_sync_operaciones` (auto-creacion en primer uso).
	- Archivos: `src/cotizaciones/citas/agendar_cita.php`, `src/cotizaciones/api/procesar_agenda.php`.

- Semana 4: MVP parcial de caja implementado (apertura offline controlada).
	- Cola offline local (IndexedDB) para apertura de caja cuando no hay internet.
	- Sincronizacion manual y automatica al reconectar.
	- Idempotencia backend via `offline_operation_id` para evitar reaperturas duplicadas.
	- Persistencia de operaciones de sync en tabla `caja_sync_operaciones` (auto-creacion en primer uso).
	- Archivos: `src/contabilidad/contabilidad.php`, `src/contabilidad/caja_abrir.php`.

- Semana 5: MVP parcial de inventario implementado (movimientos de bajo riesgo).
	- Cola offline local (IndexedDB) para `entrada` y `ajuste_pos` cuando no hay internet.
	- Sincronizacion manual y automatica al reconectar.
	- Idempotencia backend via `offline_operation_id` para evitar duplicados.
	- Persistencia de operaciones de sync en tabla `inventario_sync_operaciones` (auto-creacion en primer uso).
	- Modo sync JSON en endpoint de movimientos para consumo por cola offline.
	- Archivos: `src/inventario/inventario.php`, `src/inventario/movimiento_guardar.php`.

- Semana 6: Mini panel de soporte de sincronizacion implementado.
	- Agenda, Caja e Inventario ahora muestran controles de soporte:
		- Ver cola (detalle de pendientes/errores)
		- Reintentar sincronizacion manual
		- Limpiar registros en error
		- Marcar incidencia local para soporte (bitacora local `offline_sync_incidents_v1`)
	- Estado de cola ahora informa total de pendientes y cantidad de errores por modulo.
	- Archivos: `src/cotizaciones/citas/agendar_cita.php`, `src/contabilidad/contabilidad.php`, `src/inventario/inventario.php`.

Nota:
- Este MVP no extiende aun a cierre de caja, ajustes manuales de caja, ni movimientos de inventario de mayor riesgo (`salida`, `ajuste_neg`, `merma`, `vencido`) en modo offline.

---

## Extension Recomendada: Semanas 9 y 10 (Pacientes Offline MVP)

### Semana 9: Formulario de pacientes con cola offline

#### Frontend
- Interceptar envio en `form_cliente` cuando no hay internet.
- Encolar operaciones de crear/editar en IndexedDB con `offline_operation_id`.
- Exponer estado de cola y controles de soporte (sincronizar, ver cola, limpiar errores, marcar incidencia).

#### Backend
- Mantener flujo tradicional online sin cambios funcionales.
- Aceptar modo `offline_sync=1` y responder JSON para sincronizacion.
- Preparar validaciones equivalentes para modo online y offline.

#### QA
- Probar alta de paciente offline y sincronizacion posterior.
- Probar edicion de paciente offline y sincronizacion posterior.
- Verificar ausencia de duplicados por reintento.

#### Operaciones
- Incluir pacientes en rutina diaria de piloto.
- Definir politica de uso cuando se detecten conflictos de edicion.

#### Checklist de salida
- [ ] Crear paciente offline encola correctamente.
- [ ] Editar paciente offline encola correctamente.
- [ ] Sync manual y auto aplican operaciones sin duplicados.

### Semana 10: Endurecimiento de conflictos y salida operativa

#### Frontend
- Mejorar mensajes de error para casos de conflicto (documento duplicado, validacion, datos incompletos).
- Agregar vista de ultimos errores de sync de pacientes para soporte.

#### Backend
- Idempotencia persistente por `operation_id` en tabla de operaciones sync de pacientes.
- Trazabilidad de estado por operacion (`pendiente`, `aplicado`, `error`) para auditoria.
- Reglas de negocio para conflictos de datos (documento duplicado, cliente inexistente en edicion).

#### QA
- Pruebas de reconexion intermitente con reintentos multiples.
- Pruebas de conflicto de documento duplicado entre sedes/usuarios.
- Pruebas de no regresion en flujo online de pacientes.

#### Operaciones
- Definir protocolo de resolucion manual de conflictos de pacientes.
- Incluir metricas de pacientes en reporte diario de piloto.

#### Checklist de salida
- [ ] Idempotencia validada con reintentos repetidos.
- [ ] Conflictos de documento detectados y reportados.
- [ ] Flujo online de pacientes sin regresiones.

---

## Estado de Avance Real (Extension 9-10)

- Semana 9: Implementada.
	- Formulario de pacientes con cola offline en IndexedDB y controles de soporte.
	- Sync manual y auto al reconectar.
	- Archivo: `src/clientes/form_cliente.php`.

- Semana 10: Implementada en alcance MVP.
	- Endpoints de crear/editar pacientes aceptan `offline_sync=1` y `offline_operation_id`.
	- Respuesta JSON para motor de sincronizacion y redirecciones intactas para flujo online.
	- Tabla `clientes_sync_operaciones` con deduplicacion por `operation_id` y estado de operacion.
	- Archivos: `src/clientes/crear.php`, `src/clientes/editar.php`.

Politica aplicada en MVP actual:
- Estrategia de conflicto en edicion offline de pacientes: bloqueo por version desfasada con revision manual obligatoria.
- Implementacion: token de estado base (`offline_base_hash`) en formulario y validacion en backend al sincronizar; si cambia el registro en servidor, responde conflicto `409` y no sobreescribe datos.

Monitoreo operativo agregado:
- Script consolidado de KPI offline por modulo (agenda, caja, inventario, pacientes, resultados): `scripts/reporte_kpi_offline_sync.php`.
- Permite seguimiento diario de:
	- Total de operaciones sincronizadas.
	- Porcentaje aplicado, error y duplicado.
	- Semaforo automatico contra metas del plan (<1% fallas, <0.2% duplicados, >=95% aplicado).

Cobertura critica actual (sin internet):
- Registro/edicion de pacientes: encola y sincroniza al reconectar.
- Llenado de resultados: encola y sincroniza al reconectar.
- Descarga de resultados: reporte local provisional offline + PDF oficial online al reconectar.

Uso sugerido en piloto y go-live:
- CLI (hoy): `php scripts/reporte_kpi_offline_sync.php`
- CLI (fecha): `php scripts/reporte_kpi_offline_sync.php --date=YYYY-MM-DD`
- JSON (integraciones): `php scripts/reporte_kpi_offline_sync.php --date=YYYY-MM-DD --json`
- Historico diario: `php scripts/reporte_kpi_offline_sync.php --save --date=YYYY-MM-DD`
- Atajo Windows: `scripts/run_kpi_offline_daily.cmd`

Panel interno agregado:
- Vista `dashboard.php?vista=offline_monitor` con KPI por modulo y semaforos.
- Incluye visor de incidencias locales (`offline_sync_incidents_v1`) para soporte operativo.
- Incluye alerta visual destacada cuando cualquier KPI entra en estado `ALERTA`.

Resumen semanal automatico:
- Cada ejecucion con `--save` ahora genera tambien consolidado de ultimos 7 dias.
- Ruta: `docs/offline_kpi_reports/weekly/`.
- Formatos: JSON y Markdown para seguimiento gerencial.

---

## Cierre de Fase 1 (estado formal)

Estado: CERRADA Y OPERATIVA en alcance de continuidad offline de registro/sincronizacion.

Incluye como terminado:
- Cola offline + sync + idempotencia en agenda, caja (apertura), inventario bajo riesgo, pacientes y resultados.
- Manejo de conflictos en pacientes por `offline_base_hash` con bloqueo de sobreescritura desfasada.
- Monitoreo operativo (vista de monitoreo, KPI diario y consolidado semanal).

Limitacion conocida aceptada para esta fase:
- El archivo generado sin internet en resultados es PROVISIONAL (HTML local) y no reemplaza el PDF oficial institucional.
- El PDF oficial para entrega al paciente se emite desde servidor al reconectar.

Pendiente para Fase 2 (postergado por decision de alcance):
- Generacion de PDF oficial con formato institucional completo en modo offline.

Regla operativa hasta abrir Fase 2:
- Sin internet: registrar y guardar resultados en cola, y usar solo reporte provisional para referencia interna.
- Con internet: sincronizar y emitir PDF oficial para entrega al paciente.
