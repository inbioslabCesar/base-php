# PDF package (mini)

Este paquete contiene archivos mínimos para reproducir el flujo de generación de PDF usado en el proyecto.

Contenido:
- `descarga-pdf.php` - Endpoint que genera un PDF con mPDF (entrada: ?cotizacion_id=)
- `reporte_tcpdf.php` - Ejemplo con TCPDF
- `src/conexion.php` - Ejemplo de conexión PDO
- `src/config.php` - Configuración mínima
- `composer.json` - Dependencias (mpdf, tcpdf)

Instrucciones rápidas

1. Copiar la carpeta `pdf-package` a la raíz del proyecto o clonar en un servidor.
2. Desde la carpeta donde está `composer.json`, ejecutar:

```powershell
composer install
```

3. Ajustar `src/conexion.php` con los datos de tu base de datos.
4. Asegurarse de que las imágenes (logo/firma) referenciadas existan y las rutas en la DB o en `src/config.php` sean correctas.
5. Probar en el navegador:

```
http://tu-servidor/pdf-package/descarga-pdf.php?cotizacion_id=1
```

Notas
- El paquete no incluye la carpeta `vendor/` para mantenerlo ligero. Ejecuta `composer install` para descargar dependencias.
- Validar permisos y seguridad: el endpoint no realiza autenticación por defecto.
- Para integración completa, adapta las consultas SQL a tus tablas.
