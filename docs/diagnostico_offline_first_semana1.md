# Diagnostico Tecnico Offline-First (Semana 1)

Fecha: 2026-08-22

## Resumen Ejecutivo
Estado actual del sistema: online-first.

Hallazgos:
- No existe manifest PWA en el proyecto.
- No existe service worker registrado.
- No existe uso de IndexedDB para cola transaccional offline.
- No existe sincronizacion por lotes al reconectar (background sync o equivalente propio).
- Si existe uso de localStorage/sessionStorage, enfocado a estado UI y preferencias.

## Evidencia Relevante por Modulo

### 1) Landing Publica
- Entradas publicas en [index.php](../index.php) y plantillas premium en [src/public/portal_premium.php](../src/public/portal_premium.php) y [src/public/portal_premium_b.php](../src/public/portal_premium_b.php).
- Uso de localStorage para carrito temporal en [src/public/portal_premium.php](../src/public/portal_premium.php) y [src/public/portal_premium_b.php](../src/public/portal_premium_b.php).
- No hay instalacion PWA ni capa offline real.

### 2) Agenda
- Flujo principal de vista en [src/cotizaciones/citas/agendar_cita.php](../src/cotizaciones/citas/agendar_cita.php).
- Procesamiento backend de agenda en [src/cotizaciones/api/procesar_agenda.php](../src/cotizaciones/api/procesar_agenda.php).
- No se detecta cola offline ni idempotencia por operation_id para reconexion.

### 3) Caja / Contabilidad
- Acciones de caja como [src/contabilidad/caja_abrir.php](../src/contabilidad/caja_abrir.php), [src/contabilidad/caja_cerrar.php](../src/contabilidad/caja_cerrar.php), [src/contabilidad/caja_ajuste.php](../src/contabilidad/caja_ajuste.php).
- Operaciones criticas son POST directos a BD sin capa offline/sync.

### 4) Inventario
- Vista principal en [src/inventario/inventario.php](../src/inventario/inventario.php) y modulo interno en [src/inventario/inventario_interno.php](../src/inventario/inventario_interno.php).
- Sin cola offline ni reconciliacion de conflictos por reconexion.

## Brechas Frente a Offline-First
- Persistencia transaccional local (faltante).
- Identificador idempotente por operacion (faltante).
- Sincronizacion por lotes y reintentos (faltante).
- Resolucion de conflictos (faltante).
- Observabilidad de sincronizacion por modulo/sede (faltante).

## Implementacion Minima Aplicada en Semana 1
Se implemento una base no invasiva para preparacion offline:
- Monitor global de estado de red para el dashboard interno.
- Registro de eventos online/offline en localStorage (buffer acotado).
- Exposicion de helper global para diagnostico rapido en navegador.

Archivo agregado:
- [src/assets/js/offline-readiness-baseline.js](../src/assets/js/offline-readiness-baseline.js)

Integracion:
- Script cargado globalmente desde [src/componentes/footer.php](../src/componentes/footer.php).

## Resultado de Semana 1
- Diagnostico tecnico completado.
- Instrumentacion base de red activa en entorno interno.
- Sin cambios de riesgo en flujos de negocio.

## Siguiente Paso Recomendado (Semana 2)
- Agregar manifest + service worker solo para cache de estaticos.
- Mantener instalacion opcional para pacientes.
- No habilitar aun operaciones transaccionales offline hasta definir idempotencia backend.
