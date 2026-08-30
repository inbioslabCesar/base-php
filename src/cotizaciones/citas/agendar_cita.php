
<?php
// Asegurar zona horaria Perú
date_default_timezone_set('America/Lima');
$id_cotizacion = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;

// Obtener información de la cotización para contexto
require_once __DIR__ . '/../../conexion/conexion.php';
$cotizacion = null;
if ($id_cotizacion > 0) {
    $stmt = $pdo->prepare("SELECT c.*, cl.nombre, cl.apellido FROM cotizaciones c LEFT JOIN clientes cl ON c.id_cliente = cl.id WHERE c.id = ?");
    $stmt->execute([$id_cotizacion]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
$urlVolverCotizaciones = 'dashboard.php?vista=cotizaciones';
if ($rolActual === 'cliente') {
    $urlVolverCotizaciones = 'dashboard.php?vista=cotizaciones_clientes';
} elseif ($rolActual === 'empresa') {
    $urlVolverCotizaciones = 'dashboard.php?vista=cotizaciones_empresas';
} elseif ($rolActual === 'convenio') {
    $urlVolverCotizaciones = 'dashboard.php?vista=cotizaciones_convenios';
}
$urlEditarCotizacion = 'dashboard.php?vista=form_cotizacion&id=' . $id_cotizacion . '&edit=1';

// Configurar fecha y hora por defecto (fecha y hora actual exacta)
$fecha_actual = date('Y-m-d');
$hora_actual = date('H:i'); // Hora exacta actual
?>

<style>
.cita-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.cita-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
    max-width: 600px;
    margin: 0 auto;
}

.cita-header {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.cita-header h4 {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
}

.cita-header .subtitle {
    margin-top: 8px;
    opacity: 0.9;
    font-size: 0.9rem;
}

.cita-body {
    padding: 40px 30px;
}

.form-floating {
    margin-bottom: 20px;
}

.form-floating > .form-control,
.form-floating > .form-select {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-size: 1rem;
    padding: 12px 16px;
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    transform: translateY(-2px);
}

.form-floating > label {
    color: #6c757d;
    font-weight: 500;
}

.tipo-toma-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 25px;
}

.tipo-option {
    position: relative;
}

.tipo-option input[type="radio"] {
    display: none;
}

.tipo-option label {
    display: block;
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    background: white;
    position: relative;
    overflow: hidden;
}

.tipo-option input[type="radio"]:checked + label {
    border-color: #4CAF50;
    background: rgba(76, 175, 80, 0.05);
    color: #4CAF50;
    font-weight: 600;
}

.tipo-option label:hover {
    border-color: #4CAF50;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
}

.tipo-icon {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

.direccion-field {
    max-height: 0;
    overflow: hidden;
    transition: all 0.4s ease;
    opacity: 0;
}

.direccion-field.show {
    max-height: 200px;
    opacity: 1;
    margin-bottom: 20px;
}

.datetime-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.btn-group-custom {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.btn-custom {
    padding: 12px 30px;
    border-radius: 25px;
    border: none;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-success-custom {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
}

.btn-success-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
    color: white;
}

.btn-secondary-custom {
    background: #6c757d;
    color: white;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    transform: translateY(-2px);
    color: white;
}

.info-alert {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: none;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border-left: 4px solid #2196F3;
}

.quick-times {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.quick-time {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.quick-time:hover {
    border-color: #4CAF50;
    background: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}

.offline-sync-panel {
    margin-top: 14px;
    padding: 12px;
    border: 1px solid #dbe7ff;
    border-radius: 12px;
    background: #f8fbff;
}

.offline-sync-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
}

.offline-sync-text {
    font-size: 0.92rem;
    color: #334155;
}

@media (max-width: 768px) {
    .cita-container {
        padding: 10px;
    }
    
    .cita-body {
        padding: 25px 20px;
    }
    
    .tipo-toma-options {
        grid-template-columns: 1fr;
    }
    
    .datetime-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-group-custom {
        flex-direction: column;
    }
    
    .quick-times {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="cita-container">
  <div class="container">
    <div class="card cita-card">
      <div class="cita-header">
        <h4>📅 Agendar Cita de Toma de Muestra</h4>
        <?php if ($cotizacion && isset($cotizacion['nombre'])): ?>
        <div class="subtitle">
          Paciente: <strong><?php echo htmlspecialchars(trim($cotizacion['nombre'] . ' ' . $cotizacion['apellido'])); ?></strong>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="cita-body">
        <div class="info-alert">
          <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2" style="color: #2196F3;"></i>
            <small>
              <strong>Fecha y hora actual:</strong> Para laboratorio se toma inmediatamente. 
              Para domicilio puedes programar fecha y hora según tu conveniencia.
                            <br>
                            <strong>Importante:</strong> La cotización ya está registrada. Si olvidaste agregar o quitar exámenes, usa <em>Volver a editar exámenes</em>.
            </small>
          </div>
        </div>

        <form action="dashboard.php?action=procesar_agenda" method="POST" id="agendarForm">
          <input type="hidden" name="id_cotizacion" value="<?php echo $id_cotizacion; ?>">

          <div class="tipo-toma-options">
            <div class="tipo-option">
              <input type="radio" name="tipo_toma" id="laboratorio" value="laboratorio" checked>
              <label for="laboratorio">
                <span class="tipo-icon">🏥</span>
                <div><strong>En Laboratorio</strong></div>
                <small>Más rápido y económico</small>
              </label>
            </div>
            <div class="tipo-option">
              <input type="radio" name="tipo_toma" id="domicilio" value="domicilio">
              <label for="domicilio">
                <span class="tipo-icon">🏠</span>
                <div><strong>A Domicilio</strong></div>
                <small>Mayor comodidad</small>
              </label>
            </div>
          </div>

          <div class="datetime-grid">
                        <div class="form-floating">
                            <input type="date" 
                                         name="fecha_toma" 
                                         id="fecha_toma" 
                                         class="form-control" 
                                         value="<?php echo $fecha_actual; ?>"
                                         min="<?php echo date('Y-m-d'); ?>"
                                         required>
                            <label for="fecha_toma">📅 Fecha de toma</label>
                        </div>

                        <div class="form-floating">
                            <input type="time" 
                                         name="hora_toma" 
                                         id="hora_toma" 
                                         class="form-control" 
                                         value="<?php echo $hora_actual; ?>"
                                         required>
                            <label for="hora_toma">🕐 Hora de toma</label>
                        </div>
          </div>

          <div class="quick-times">
            <div class="quick-time" onclick="setTime('08:00')">8:00 AM</div>
            <div class="quick-time" onclick="setTime('09:00')">9:00 AM</div>
            <div class="quick-time" onclick="setTime('10:00')">10:00 AM</div>
            <div class="quick-time" onclick="setTime('11:00')">11:00 AM</div>
            <div class="quick-time" onclick="setTime('14:00')">2:00 PM</div>
            <div class="quick-time" onclick="setTime('15:00')">3:00 PM</div>
            <div class="quick-time" onclick="setTime('16:00')">4:00 PM</div>
            <div class="quick-time" onclick="setTime('17:00')">5:00 PM</div>
          </div>

          <div class="direccion-field" id="direccion_field">
            <div class="form-floating">
              <input type="text" 
                     name="direccion_toma" 
                     id="direccion_toma" 
                     class="form-control"
                     placeholder="Ingrese la dirección completa...">
              <label for="direccion_toma">📍 Dirección para toma a domicilio</label>
            </div>
            <small class="text-muted ms-2">
              <i class="fas fa-truck me-1"></i>
              Se aplicará costo adicional por servicio a domicilio
            </small>
          </div>

          <div class="btn-group-custom">
            <button type="submit" class="btn btn-custom btn-success-custom">
              <i class="fas fa-calendar-check"></i>
              Confirmar Cita
            </button>
                        <a href="<?= htmlspecialchars($urlEditarCotizacion, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-custom btn-secondary-custom">
                            <i class="fas fa-file-medical"></i>
                            Volver a editar exámenes
                        </a>
                        <a href="<?= htmlspecialchars($urlVolverCotizaciones, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-custom btn-secondary-custom">
              <i class="fas fa-arrow-left"></i>
                            Omitir agenda por ahora
            </a>
          </div>

                    <div class="offline-sync-panel">
                        <div class="offline-sync-row">
                                <div class="offline-sync-text" id="agendaOfflineEstado">Sin pendientes offline.</div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agendaSyncNowBtn">Sincronizar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="agendaVerColaBtn">Ver cola</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="agendaLimpiarErroresBtn">Limpiar errores</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="agendaIncidenciaBtn">Marcar incidencia</button>
                                </div>
                        </div>
                        <div class="mt-2 small text-muted" id="agendaColaDetalle" style="display:none;"></div>
                    </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Funcionalidad mejorada para agendar citas
document.addEventListener('DOMContentLoaded', function() {
    const tipoRadios = document.querySelectorAll('input[name="tipo_toma"]');
    const direccionField = document.getElementById('direccion_field');
    const direccionInput = document.getElementById('direccion_toma');
    const fechaInput = document.getElementById('fecha_toma');
    const horaInput = document.getElementById('hora_toma');
    const form = document.getElementById('agendarForm');
    const offlineStatusEl = document.getElementById('agendaOfflineEstado');
    const syncNowBtn = document.getElementById('agendaSyncNowBtn');
    const verColaBtn = document.getElementById('agendaVerColaBtn');
    const limpiarErroresBtn = document.getElementById('agendaLimpiarErroresBtn');
    const incidenciaBtn = document.getElementById('agendaIncidenciaBtn');
    const colaDetalleEl = document.getElementById('agendaColaDetalle');

    const AGENDA_DB_NAME = 'agenda_offline_db_v1';
    const AGENDA_STORE_NAME = 'agenda_queue';
    const ACTION_URL = 'dashboard.php?action=procesar_agenda';
    const INCIDENT_KEY = 'offline_sync_incidents_v1';

    function createOperationId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'op_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
    }

    function openAgendaDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(AGENDA_DB_NAME, 1);
            req.onupgradeneeded = function(event) {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(AGENDA_STORE_NAME)) {
                    const store = db.createObjectStore(AGENDA_STORE_NAME, { keyPath: 'operation_id' });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('created_at', 'created_at', { unique: false });
                }
            };
            req.onsuccess = function(event) { resolve(event.target.result); };
            req.onerror = function(event) { reject(event.target.error || new Error('No se pudo abrir IndexedDB')); };
        });
    }

    async function withStore(mode, fn) {
        const db = await openAgendaDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(AGENDA_STORE_NAME, mode);
            const store = tx.objectStore(AGENDA_STORE_NAME);
            let result;
            try {
                result = fn(store, tx);
            } catch (err) {
                reject(err);
                return;
            }
            tx.oncomplete = function() {
                resolve(result);
                db.close();
            };
            tx.onerror = function(event) {
                reject(event.target.error || new Error('Error en transaccion IndexedDB'));
                db.close();
            };
        });
    }

    async function queueAgendaPayload(payload) {
        const record = {
            operation_id: payload.offline_operation_id,
            payload: payload,
            status: 'pending',
            created_at: Date.now(),
            retries: 0,
            last_error: ''
        };
        await withStore('readwrite', function(store) {
            store.put(record);
        });
    }

    async function getPendingQueue() {
        return withStore('readonly', function(store) {
            return new Promise((resolve, reject) => {
                const req = store.getAll();
                req.onsuccess = function() {
                    const rows = Array.isArray(req.result) ? req.result : [];
                    resolve(rows.filter(function(r) { return r.status !== 'synced'; }));
                };
                req.onerror = function(event) {
                    reject(event.target.error || new Error('No se pudo leer cola offline'));
                };
            });
        });
    }

    function pushIncident(moduleName, detail) {
        try {
            const rows = JSON.parse(localStorage.getItem(INCIDENT_KEY) || '[]');
            const list = Array.isArray(rows) ? rows : [];
            list.push({
                module: moduleName,
                detail: detail,
                at: new Date().toISOString(),
                path: location.pathname + location.search,
            });
            localStorage.setItem(INCIDENT_KEY, JSON.stringify(list.slice(-200)));
        } catch (err) {
        }
    }

    function summarizeQueue(items) {
        const list = Array.isArray(items) ? items : [];
        const total = list.length;
        const errores = list.filter((r) => String(r.status || '') === 'error').length;
        return { total, errores };
    }

    function renderQueueDetail(items) {
        if (!colaDetalleEl) {
            return;
        }
        const list = Array.isArray(items) ? items : [];
        if (!list.length) {
            colaDetalleEl.textContent = 'Cola vacia.';
            return;
        }
        const lines = list.slice(0, 8).map(function (r, idx) {
            const st = String(r.status || 'pending');
            const retries = Number(r.retries || 0);
            const at = r.created_at ? new Date(r.created_at).toLocaleString() : '-';
            return (idx + 1) + '. [' + st + '] ' + (r.operation_id || '-') + ' | reintentos: ' + retries + ' | ' + at;
        });
        colaDetalleEl.textContent = lines.join(' | ');
    }

    async function markQueueRecord(record) {
        await withStore('readwrite', function(store) {
            store.put(record);
        });
    }

    async function deleteQueueRecord(operationId) {
        await withStore('readwrite', function(store) {
            store.delete(operationId);
        });
    }

    async function clearErrorRecords() {
        const pending = await getPendingQueue();
        const errors = pending.filter((r) => String(r.status || '') === 'error');
        for (const rec of errors) {
            await deleteQueueRecord(rec.operation_id);
        }
        return errors.length;
    }

    async function refreshOfflineStatus() {
        if (!offlineStatusEl) {
            return;
        }
        try {
            const pending = await getPendingQueue();
            renderQueueDetail(pending);
            const resumen = summarizeQueue(pending);
            if (!resumen.total) {
                offlineStatusEl.textContent = navigator.onLine
                    ? 'Sin pendientes offline.'
                    : 'Sin internet. No hay pendientes en cola.';
                return;
            }
            offlineStatusEl.textContent = 'Pendientes: ' + resumen.total + ' | errores: ' + resumen.errores + '. Se sincronizan al reconectar.';
        } catch (err) {
            offlineStatusEl.textContent = 'No se pudo leer cola offline.';
        }
    }

    async function sendAgendaPayload(payload) {
        const body = new URLSearchParams();
        body.set('id_cotizacion', String(payload.id_cotizacion || ''));
        body.set('tipo_toma', String(payload.tipo_toma || 'laboratorio'));
        body.set('fecha_toma', String(payload.fecha_toma || ''));
        body.set('hora_toma', String(payload.hora_toma || ''));
        body.set('direccion_toma', String(payload.direccion_toma || ''));
        body.set('offline_sync', '1');
        body.set('offline_operation_id', String(payload.offline_operation_id || ''));

        const resp = await fetch(ACTION_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: body.toString()
        });

        const data = await resp.json().catch(function() { return {}; });
        if (!resp.ok || !data || data.ok !== true) {
            throw new Error((data && data.message) ? data.message : 'Fallo de sincronizacion');
        }
        return data;
    }

    async function syncAgendaQueue() {
        if (!navigator.onLine) {
            await refreshOfflineStatus();
            return;
        }

        const pending = await getPendingQueue();
        if (!pending.length) {
            await refreshOfflineStatus();
            return;
        }

        for (const rec of pending) {
            try {
                await sendAgendaPayload(rec.payload || {});
                await deleteQueueRecord(rec.operation_id);
            } catch (err) {
                rec.status = 'error';
                rec.retries = Number(rec.retries || 0) + 1;
                rec.last_error = String(err && err.message ? err.message : 'Error de sincronizacion');
                await markQueueRecord(rec);
            }
        }

        await refreshOfflineStatus();
    }


    // Manejar cambio de tipo de toma
    tipoRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const esDomicilio = this.value === 'domicilio';
            const esLaboratorio = this.value === 'laboratorio';
            if (esDomicilio) {
                direccionField.classList.add('show');
                direccionInput.setAttribute('required', 'required');
                // Permitir editar fecha y hora
                fechaInput.removeAttribute('readonly');
                horaInput.removeAttribute('readonly');
                fechaInput.style.backgroundColor = '';
                horaInput.style.backgroundColor = '';
                setTimeout(() => {
                    direccionInput.focus();
                }, 300);
                mostrarNotificacion('Para domicilio puedes programar fecha y hora', 'info');
            } else if (esLaboratorio) {
                direccionField.classList.remove('show');
                direccionInput.removeAttribute('required');
                direccionInput.value = '';
                // Para laboratorio: mantener fecha y hora actual (toma inmediata)
                const fechaActual = new Date();
                fechaInput.value = fechaActual.toISOString().split('T')[0];
                horaInput.value = fechaActual.toTimeString().slice(0, 5);
                // Permitir editar fecha y hora (no readonly)
                fechaInput.removeAttribute('readonly');
                horaInput.removeAttribute('readonly');
                fechaInput.style.backgroundColor = '';
                horaInput.style.backgroundColor = '';
                mostrarNotificacion('En laboratorio: toma inmediata con fecha y hora actual', 'success');
            }
        });
    });

    // No inicializar fecha/hora en JS, solo usar la generada por PHP

    // Validación inteligente de fecha (solo para domicilio)
    fechaInput.addEventListener('change', function() {
        const tipoSeleccionado = document.querySelector('input[name="tipo_toma"]:checked').value;
        
        if (tipoSeleccionado === 'domicilio') {
            // Para domicilio, validar que no sea fecha pasada
            const fechaSeleccionada = new Date(this.value);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0); // Reset hora para comparar solo fecha
            fechaSeleccionada.setHours(0, 0, 0, 0);
            
            if (fechaSeleccionada < hoy) {
                this.value = new Date().toISOString().split('T')[0];
                mostrarNotificacion('No puedes seleccionar una fecha pasada', 'warning');
            }
        }
    });

    // Validación de hora (solo para domicilio)
    horaInput.addEventListener('change', function() {
        const tipoSeleccionado = document.querySelector('input[name="tipo_toma"]:checked').value;
        
        if (tipoSeleccionado === 'domicilio') {
            const fechaSeleccionada = new Date(fechaInput.value);
            const hoy = new Date();
            const esHoy = fechaSeleccionada.toDateString() === hoy.toDateString();
            
            if (esHoy) {
                const horaSeleccionada = parseInt(this.value.split(':')[0]);
                const minutoSeleccionado = parseInt(this.value.split(':')[1]);
                const horaActual = new Date().getHours();
                const minutoActual = new Date().getMinutes();
                
                const tiempoSeleccionado = horaSeleccionada * 60 + minutoSeleccionado;
                const tiempoActual = horaActual * 60 + minutoActual;
                
                if (tiempoSeleccionado <= tiempoActual) {
                    const nuevaHora = new Date();
                    nuevaHora.setHours(nuevaHora.getHours() + 1, 0, 0, 0);
                    this.value = nuevaHora.toTimeString().slice(0, 5);
                    mostrarNotificacion('Hora ajustada al siguiente horario disponible', 'warning');
                }
            }
        }
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const tipo = document.querySelector('input[name="tipo_toma"]:checked').value;
        
        if (tipo === 'domicilio' && !direccionInput.value.trim()) {
            e.preventDefault();
            direccionInput.focus();
            mostrarNotificacion('Por favor ingrese la dirección para el servicio a domicilio', 'error');
            return;
        }

        // Mostrar confirmación mejorada
        const fechaValue = fechaInput.value;
        const horaValue = horaInput.value;
        
        // Crear fecha correctamente
        const [year, month, day] = fechaValue.split('-');
        const fecha = new Date(year, month - 1, day); // month - 1 porque los meses van de 0-11
        
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        // Formatear hora a formato 12 horas
        const [hora, minutos] = horaValue.split(':');
        const horaNum = parseInt(hora);
        const horaFormateada = horaNum > 12 ? `${horaNum - 12}:${minutos} PM` : `${horaNum}:${minutos} AM`;
        if (horaNum === 12) horaFormateada = `12:${minutos} PM`;
        if (horaNum === 0) horaFormateada = `12:${minutos} AM`;
        
        let mensajeConfirmacion = '';
        
        if (tipo === 'laboratorio') {
            mensajeConfirmacion = `¿Confirmar toma de muestra INMEDIATA en laboratorio?\n\n` +
                                `Fecha y hora: HOY ${horaFormateada}\n` +
                                `Tipo: En laboratorio (toma inmediata)`;
        } else {
            mensajeConfirmacion = `¿Confirmar cita programada para el ${fechaFormateada}?\n\n` +
                                `Hora: ${horaFormateada}\n` +
                                `Tipo: A domicilio\n` +
                                `Dirección: ${direccionInput.value}`;
        }
        
        const confirmacion = confirm(mensajeConfirmacion);
        
        if (!confirmacion) {
            e.preventDefault();
            return;
        }

        if (!navigator.onLine) {
            e.preventDefault();

            const payload = {
                id_cotizacion: String(<?= (int)$id_cotizacion ?>),
                tipo_toma: tipo,
                fecha_toma: fechaValue,
                hora_toma: horaValue,
                direccion_toma: tipo === 'domicilio' ? direccionInput.value.trim() : '',
                offline_operation_id: createOperationId(),
            };

            queueAgendaPayload(payload)
                .then(function() {
                    mostrarNotificacion('Sin internet: cita guardada en cola para sincronizar.', 'warning');
                    refreshOfflineStatus();
                })
                .catch(function() {
                    mostrarNotificacion('No se pudo guardar en cola offline.', 'error');
                });
        }
    });

    // Inicializar estado
    direccionField.classList.remove('show');

    if (syncNowBtn) {
        syncNowBtn.addEventListener('click', function() {
            syncAgendaQueue()
                .then(function() {
                    mostrarNotificacion('Sincronizacion de agenda completada.', 'success');
                })
                .catch(function(err) {
                    mostrarNotificacion('Error al sincronizar: ' + (err && err.message ? err.message : 'desconocido'), 'error');
                });
        });
    }

    if (verColaBtn && colaDetalleEl) {
        verColaBtn.addEventListener('click', function () {
            const hidden = colaDetalleEl.style.display === 'none';
            colaDetalleEl.style.display = hidden ? 'block' : 'none';
            if (hidden) {
                refreshOfflineStatus();
            }
        });
    }

    if (limpiarErroresBtn) {
        limpiarErroresBtn.addEventListener('click', function () {
            clearErrorRecords()
                .then(function (n) {
                    mostrarNotificacion('Registros con error eliminados: ' + n, 'warning');
                    refreshOfflineStatus();
                })
                .catch(function () {
                    mostrarNotificacion('No se pudieron limpiar errores.', 'error');
                });
        });
    }

    if (incidenciaBtn) {
        incidenciaBtn.addEventListener('click', function () {
            getPendingQueue().then(function (pending) {
                const r = summarizeQueue(pending);
                const detail = 'Agenda pendientes=' + r.total + ', errores=' + r.errores;
                pushIncident('agenda', detail);
                mostrarNotificacion('Incidencia registrada para soporte.', 'info');
            });
        });
    }

    window.addEventListener('online', function() {
        syncAgendaQueue().catch(function() {});
    });

    refreshOfflineStatus();
    if (navigator.onLine) {
        syncAgendaQueue().catch(function() {});
    }
});

// Función para establecer hora rápida (solo para domicilio)
function setTime(hora) {
    const tipoSeleccionado = document.querySelector('input[name="tipo_toma"]:checked').value;
    
    if (tipoSeleccionado !== 'domicilio') {
        mostrarNotificacion('Los horarios rápidos solo están disponibles para toma a domicilio', 'info');
        return;
    }
    
    const horaInput = document.getElementById('hora_toma');
    horaInput.value = hora;
    
    // Validar si la hora es válida para hoy
    const fechaSeleccionada = new Date(document.getElementById('fecha_toma').value);
    const hoy = new Date();
    const esHoy = fechaSeleccionada.toDateString() === hoy.toDateString();
    
    if (esHoy) {
        const horaSeleccionada = parseInt(hora.split(':')[0]);
        const horaActual = new Date().getHours();
        
        if (horaSeleccionada <= horaActual) {
            mostrarNotificacion('Esta hora ya ha pasado para hoy. Seleccione una fecha futura.', 'warning');
        }
    }
    
    // Efecto visual
    const quickTimes = document.querySelectorAll('.quick-time');
    quickTimes.forEach(qt => qt.classList.remove('active'));
    event.target.classList.add('active');
    
    setTimeout(() => {
        event.target.classList.remove('active');
    }, 1000);
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notif = document.createElement('div');
    notif.className = `alert alert-${tipo === 'error' ? 'danger' : tipo === 'warning' ? 'warning' : 'info'} alert-dismissible fade show position-fixed`;
    notif.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    notif.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notif);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notif.parentNode) {
            notif.remove();
        }
    }, 5000);
}

// Añadir estilo para el estado activo de quick-times
const style = document.createElement('style');
style.textContent = `
    .quick-time.active {
        background: #4CAF50 !important;
        color: white !important;
        border-color: #4CAF50 !important;
        transform: scale(1.05);
    }
`;
document.head.appendChild(style);

// Compatibilidad con la función original
function toggleDireccion() {
    const tipo = document.getElementById('tipo_toma');
    const tipoValue = tipo ? tipo.value : 'laboratorio';
    const direccionField = document.getElementById('direccion_field');
    const direccionInput = document.getElementById('direccion_toma');
    
    if (tipoValue === 'domicilio') {
        direccionField.classList.add('show');
        direccionInput.setAttribute('required', 'required');
    } else {
        direccionField.classList.remove('show');
        direccionInput.removeAttribute('required');
        direccionInput.value = '';
    }
}
</script>
