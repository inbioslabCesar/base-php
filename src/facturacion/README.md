# Flujo de Facturación y SUNAT

Este módulo integra la emisión de boletas/facturas contra el API de facturación (ERP externo) y el envío a SUNAT.

## Resumen del Flujo
- Cobro registrado → si saldo llega a 0, se dispara `emitirComprobante` (automático).
- Emisión manual disponible desde el detalle de cotización.
- El servicio arma el payload CPE desde BD y, cuando se definan `base_url` y credenciales, enviará al API.

## Síncrono vs Asíncrono
- **Síncrono (recomendado al inicio):**
  - Paso 1: Login (token) bajo demanda.
  - Paso 2: Crear comprobante (`POST /v1/invoices`).
  - Paso 3: Enviar a SUNAT (`POST /v1/invoices/{id}/send`).
  - Paso 4: Responder estado inmediato (aceptado/rechazado/observado).
  - Ventaja: feedback inmediato al usuario.
- **Asíncrono (escala/robustez):**
  - API acepta la solicitud y encola el envío; devuelve `job_id`.
  - El ERP consulta estado (`GET /jobs/{id}`) o recibe webhook.
  - Ventaja: resiliencia cuando SUNAT o el API están lentos.
  - Recomendación: iniciar síncrono y migrar a asíncrono si hay volumen.

## Estados
- `pendiente`: armado del comprobante; listo para enviar.
- `enviado`: enviado a API, esperando SUNAT.
- `aceptado`: SUNAT aceptó (CDR disponible).
- `rechazado` o `observado`: SUNAT devolvió error/observación.

## Descargas
- `XML` generado del comprobante.
- `PDF` del CPE (plantilla del ERP o propia). Formatos soportados: `A4` y `80mm`.
- `CDR` (constancia) de SUNAT.

## Manejo de Errores
- No bloquear el ERP: si el API falla, el cobro queda registrado y se agenda reintento.
- Log no bloqueante en `tmp/facturacion/logs/`.
- Idempotencia: evitar emitir dos veces la misma cotización (clave por `cotizacion_id`).

## Archivos Clave
- Servicio: `src/facturacion/FacturacionService.php`, `src/facturacion/FacturacionAuthService.php`.
- Hooks: `src/pagos/pago_cotizacion_guardar.php`, `src/cotizaciones/api/pago_masivo.php`.
- Acciones: `src/cotizaciones/api/emitir_comprobante.php` y botones en `src/cotizaciones/views/detalle_cotizacion.php`.
- Endpoints de estado/descargas (ERP): ver sección Endpoints ERP.

## Endpoints ERP (propuestos)
- Estado: `GET dashboard.php?action=estado_comprobante&id={cotizacion_id}`
- Descarga: `GET dashboard.php?action=descargar_comprobante&id={cotizacion_id}&tipo=xml|pdf|cdr`

## Configuración Pendiente
- `base_url_dev` y `base_url_prod` del API de facturación.
- Credenciales de la empresa emisora.

## Modo Exonerado (sin IGV)
Si tu sede está exonerada de IGV, hemos dejado el sistema configurado para no aplicar el 18%:

- En `src/config/facturacion_config.php`:
  - `defaults.modo_impuestos = "exonerado"`
  - `defaults.porcentaje_igv = 0`
  - `defaults.tip_afe_igv = "20"` (Exonerado - Operación Onerosa)

Esto hace que cada ítem se emita con `porcentaje_igv=0` y `tip_afe_igv=20`. Si necesitas volver a régimen gravado, cambia `modo_impuestos` a `gravado` y ajusta `porcentaje_igv=18`, `tip_afe_igv="10"`.

## Formato PDF 80mm
Para imprimir en ticket de 80mm, definimos el formato por defecto:

- En `src/config/facturacion_config.php`:
  - `defaults.pdf_format = "80mm"`
- También puedes pedir un formato distinto en tiempo de ejecución:
  - `src/cotizaciones/api/descargar_comprobante.php?id={id}&tipo=pdf&formato=A4` (si deseas A4 temporalmente)

## Ejemplos cURL (local)
Usa `BASE_URL=http://127.0.0.1:8000` y el `access_token` que devuelve el API.

```bash
# Login (API local)
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sistema-sunat.com","password":"Admin123!@#"}'

# Crear comprobante
# Guía de Implementación de Facturación (Modelo SUNAT)

Este documento describe el modelo aplicado en este proyecto para integrar emisión de CPE (boletas/facturas) con un API externo y su envío a SUNAT. Está pensado para reutilizarlo en otros proyectos.

## Arquitectura y Archivos
- Autenticación y token: [src/facturacion/FacturacionAuthService.php](src/facturacion/FacturacionAuthService.php)
- Servicio de facturación: [src/facturacion/FacturacionService.php](src/facturacion/FacturacionService.php)
- Configuración del API: [src/config/facturacion_config.php](src/config/facturacion_config.php)
- Hooks de negocio:
  - Post‑pago individual: [src/pagos/pago_cotizacion_guardar.php](src/pagos/pago_cotizacion_guardar.php)
  - Pago masivo: [src/cotizaciones/api/pago_masivo.php](src/cotizaciones/api/pago_masivo.php)
- Endpoints ERP:
  - Emitir manual: [src/cotizaciones/api/emitir_comprobante.php](src/cotizaciones/api/emitir_comprobante.php)
  - Estado: [src/cotizaciones/api/estado_comprobante.php](src/cotizaciones/api/estado_comprobante.php)
  - Descargas: [src/cotizaciones/api/descargar_comprobante.php](src/cotizaciones/api/descargar_comprobante.php)
- Persistencia y logs (archivos):
  - Token cache: tmp/api_tokens.json
  - Estado por cotización: tmp/facturacion/status.json
  - Payloads generados: tmp/facturacion/payloads/
  - Logs: tmp/facturacion/logs/facturacion.log

## Configuración del API
Define `base_url`, credenciales y defaults del emisor en [src/config/facturacion_config.php](src/config/facturacion_config.php):

- `base_url`: URL del API externo (ej. `http://127.0.0.1:8000`).
- `auth.username/password`: usuario del API (Sanctum/JWT).
- `defaults.company_id/branch_id`: IDs de empresa y sucursal emisora.
- `defaults.metodo_envio`: `individual` o `resumen_diario`.
- `defaults.modo_impuestos`: `gravado` (IGV 18%) o `exonerado` (IGV 0%).
- `defaults.pdf_format`: `A4` o `80mm` para la descarga del PDF.
- Rutas soportadas:
  - Login: `/api/auth/login`
  - Crear boleta: `/api/v1/boletas`
  - Crear factura: `/api/v1/invoices`
  - Enviar SUNAT: `/api/v1/{boletas|invoices}/{id}/send-sunat`
  - Descargas: `download-pdf`, `download-xml`, `download-cdr`
  - Estado: `/api/v1/{boletas|invoices}/{id}/status` (si no existe, usar GET del recurso `/api/v1/{boletas|invoices}/{id}`)

## Flujo de Emisión
1. Disparador: al registrar pago y quedar saldo=0 se llama `emitirComprobante(cotizacion_id)`.
2. `FacturacionService` arma el payload desde la BD y lo guarda en `tmp/facturacion/payloads/`.
3. Autenticación bajo demanda: `FacturacionAuthService->getToken()` con cache en `tmp/api_tokens.json`.
4. Crear comprobante remoto: `POST create_{boleta|factura}`.
5. Enviar a SUNAT: `POST send-sunat` y mapear `estado_sunat` → `aceptado/observado/rechazado/enviado`.
6. Guardar estado local en `status.json` y exponerlo con `estado_comprobante.php`.

## Payload CPE (resumen)
- Cliente: `tipo_documento` (DNI=1, RUC=6), `numero_documento`, `razon_social`.
- Ítems: `codigo`, `descripcion`, `unidad`, `cantidad`, `mto_valor_unitario` (sin IGV), `porcentaje_igv`, `tip_afe_igv`.
- Serie: `B001` (boleta) para particulares, `F001` (factura) para empresa.
- Afectación/IGV:
  - Gravado: `porcentaje_igv=18`, `tip_afe_igv="10"`.
  - Exonerado: `porcentaje_igv=0`, `tip_afe_igv="20"`.

### Ejemplo de Payload (Boleta exonerada)
```json
{
  "company_id": 1,
  "branch_id": 1,
  "metodo_envio": "individual",
  "serie": "B001",
  "fecha_emision": "2025-12-23",
  "client": {
    "tipo_documento": "1",
    "numero_documento": "12345678",
    "razon_social": "Juan Perez"
  },
  "detalles": [
    {
      "codigo": "EX-37",
      "descripcion": "Ácido Úrico",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_valor_unitario": 15.00,
      "porcentaje_igv": 0,
      "tip_afe_igv": "20"
    }
  ],
  "origen": { "cotizacion_id": 2046 }
}
```

### Ejemplo de Payload (Factura gravada)
```json
{
  "company_id": 1,
  "branch_id": 1,
  "metodo_envio": "individual",
  "serie": "F001",
  "fecha_emision": "2025-12-23",
  "client": {
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "Empresa SAC"
  },
  "detalles": [
    {
      "codigo": "EX-99",
      "descripcion": "Perfil Lipídico",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_valor_unitario": 25.42,
      "porcentaje_igv": 18,
      "tip_afe_igv": "10"
    }
  ],
  "origen": { "cotizacion_id": 3001 }
}
```

## Estados
- `pendiente`: armado del comprobante; listo para enviar.
- `enviado`: enviado al API; esperando SUNAT.
- `aceptado`: SUNAT aceptó (CDR disponible).
- `rechazado`/`observado`: SUNAT devolvió error u observación.

## Descargas (PDF/XML/CDR)
- ERP consulta estado y, con `remote_id`, llama a los endpoints de descarga.
- PDF por defecto usa `defaults.pdf_format` (ej. `80mm`). Puedes forzar formato con `&formato=A4`.
- Si el documento no está aceptado, XML/CDR pueden no estar disponibles.

## Hooks y UI
- Automático post‑pago: [src/pagos/pago_cotizacion_guardar.php](src/pagos/pago_cotizacion_guardar.php) emite cuando saldo=0.
- Pago masivo: [src/cotizaciones/api/pago_masivo.php](src/cotizaciones/api/pago_masivo.php) emite para varios IDs.
- Acciones manuales y descargas en la vista: [src/cotizaciones/views/detalle_cotizacion.php](src/cotizaciones/views/detalle_cotizacion.php).

## Manejo de errores y logs
- No bloquear cobros: si la emisión falla, se registra el pago y se guarda `last_error` en `status.json`.
- Logs detallados en `tmp/facturacion/logs/facturacion.log` con cada llamada HTTP y códigos.
- Recomendado: reintento programado (cron/job) hasta `aceptado` y mostrar `sunat_message` en UI.

## Ejecución rápida (scripts)
- Emitir una cotización: [scripts/emit_cotizacion.php](scripts/emit_cotizacion.php)
- Refrescar estado: [scripts/refresh_status.php](scripts/refresh_status.php)

### Comandos (Laragon / PowerShell)
```powershell
# Login (cURL, opcional para probar API)
curl -X POST http://127.0.0.1:8000/api/auth/login -H "Content-Type: application/json" -d '{"email":"admin@sistema-sunat.com","password":"Admin123!@#"}'

# Emitir desde script
& "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" "c:\laragon\www\base-php\scripts\emit_cotizacion.php" 2046

# Refrescar estado
& "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" "c:\laragon\www\base-php\scripts\refresh_status.php" 2046

# Descarga PDF 80mm desde ERP
Invoke-WebRequest -Uri "http://localhost/base-php/src/cotizaciones/api/descargar_comprobante.php?id=2046&tipo=pdf" -OutFile cpe.pdf

# Descarga XML/CDR (requiere aceptado)
Invoke-WebRequest -Uri "http://localhost/base-php/src/cotizaciones/api/descargar_comprobante.php?id=2046&tipo=xml" -OutFile cpe.xml
Invoke-WebRequest -Uri "http://localhost/base-php/src/cotizaciones/api/descargar_comprobante.php?id=2046&tipo=cdr" -OutFile cdr.zip
```

## Checklist para portar a otro proyecto
1. Copiar `FacturacionAuthService.php`, `FacturacionService.php` y `facturacion_config.php`.
2. Ajustar `base_url`, credenciales y `company_id/branch_id` en `facturacion_config.php`.
3. Integrar hooks de negocio donde se confirme el pago (saldo=0).
4. Exponer endpoints ERP de `estado` y `descargar` o integrarlos a tus controladores existentes.
5. Añadir vista/acciones de UI y mensajes de error (`last_error`, `sunat_message`).
6. Habilitar logs y monitoreo; opcional: cron de reintentos y webhook del API.

Con este modelo, tu ERP emite, envía y gestiona descargas/estado de CPEs de forma consistente contra el API de SUNAT.

## Diagrama del Flujo (alto nivel)

```
[Registrar Pago] -> [Saldo = 0?] --sí--> [emitirComprobante]
          |               |
          |               v
          no           [Build Payload]
                 |
                 v
            [Login Token]
                 |
                 v
          [POST Crear CPE]
                 |
                 v
               [POST send-sunat]
                 |
                 v
           [status.json] ← [Mapear estado]
                 |
                 v
         [Descargas PDF/XML/CDR]
```
