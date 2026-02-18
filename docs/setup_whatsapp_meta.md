# Configurar WhatsApp (Meta Cloud API) para alarmas

## 1) Crear app y obtener credenciales en Meta

1. Entra a `https://developers.facebook.com/`.
2. Crea una app tipo **Business**.
3. Agrega el producto **WhatsApp**.
4. En **WhatsApp > API Setup** copia:
   - `Phone number ID`
   - `Temporary access token` (para prueba inicial)

## 2) Configurar el proyecto

Editar `scripts/.whatsapp.env`:

```env
WHATSAPP_REMINDER_WEBHOOK_URL=http://127.0.0.1:8090/whatsapp_webhook_receiver.php
WHATSAPP_REMINDER_THROTTLE_HOURS=0
META_WHATSAPP_PHONE_NUMBER_ID=PEGAR_AQUI
META_WHATSAPP_TOKEN=PEGAR_AQUI
META_WHATSAPP_COUNTRY_CODE=51
```

## 3) Validar conexión con Meta y envío de prueba

```bash
php scripts/probar_meta_whatsapp.php 945241682
```

- Si todo está bien, debe salir `Mensaje de prueba enviado correctamente`.
- Si da error `(#131030)` o similar, debes agregar ese número como **recipient de prueba** en Meta.

## 4) Ejecutar alarmas reales

En una terminal (dejar activo):

```bash
php -S 127.0.0.1:8090 -t scripts
```

En otra terminal:

```bash
php scripts/enviar_recordatorios_whatsapp.php
```

## 5) Ver logs locales

- `tmp/whatsapp_webhook.log`

## Notas

- El token temporal de Meta expira rápido; luego conviene usar token permanente.
- Si cambias de entorno, verifica que `scripts/.whatsapp.env` exista y tenga valores válidos.

## 6) Plantilla sugerida en español (producción)

Crear en Meta una plantilla de tipo **Utility** con:

- Nombre: `alarma_laboratorio_es`
- Idioma: `es`
- Body:

```text
Recordatorio de laboratorio
Paciente: {{1}}
Examen: {{2}}
Cotización: {{3}}
Estado: {{4}}
Fecha objetivo: {{5}}
```

Cuando esté aprobada, en `scripts/.whatsapp.env` usar:

```env
META_WHATSAPP_SEND_MODE=template
META_WHATSAPP_TEMPLATE_NAME=alarma_laboratorio_es
META_WHATSAPP_TEMPLATE_LANG=es
META_WHATSAPP_TEMPLATE_USE_PARAMS=1
```
