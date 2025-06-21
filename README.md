# INBIOSLAB - Sistema Modular para Laboratorio Clínico ## Descripción Sistema web modular y escalable para la gestión de un laboratorio clínico. Permite administrar usuarios, clientes, empresas, exámenes, resultados y cotizaciones, con autenticación y roles diferenciados. --- ## Estructura de Carpetas 

/src /auth login.php logout.php registro.php recuperar.php /clientes editar_cliente.php eliminar_cliente.php form_cliente.php tabla_cliente.php /empresas editar_empresa.php eliminar_empresa.php form_empresa.php tabla_empresa.php /usuarios editar_usuario.php eliminar_usuario.php form_usuario.php tabla_usuario.php /examenes editar_examen.php eliminar_examen.php form_examen.php tabla_examen.php /componentes navbar.php sidebar.php footer.php /conexion config.php dashboard.php index.php

 --- ## Tablas de Base de Datos (MySQL/MariaDB) ### Tabla usuarios ```sql CREATE TABLE usuarios ( id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, nombre VARCHAR(50) NOT NULL, apellido VARCHAR(50) NOT NULL, dni VARCHAR(20) NOT NULL UNIQUE, sexo ENUM('masculino','femenino','otro') NOT NULL, fecha_nacimiento DATE, email VARCHAR(100) NOT NULL UNIQUE, telefono VARCHAR(20), direccion VARCHAR(150), cargo VARCHAR(50), profesion VARCHAR(50), rol ENUM('admin','recepcionista','bioquimico') DEFAULT 'recepcionista', fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP, estado ENUM('activo','inactivo') DEFAULT 'activo' ); 

 CREATE TABLE clientes ( id INT AUTO_INCREMENT PRIMARY KEY, codigo_cliente VARCHAR(20) NOT NULL UNIQUE, nombre VARCHAR(50) NOT NULL, apellido VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, telefono VARCHAR(20), direccion VARCHAR(150), dni VARCHAR(20), sexo ENUM('masculino','femenino','otro'), fecha_nacimiento DATE, referencia VARCHAR(100), procedencia VARCHAR(100), promociones JSON, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP, estado ENUM('activo','inactivo') DEFAULT 'activo' ); 

 CREATE TABLE empresas ( id INT AUTO_INCREMENT PRIMARY KEY, ruc VARCHAR(20) NOT NULL UNIQUE, razon_social VARCHAR(100) NOT NULL, nombre_comercial VARCHAR(100), direccion VARCHAR(150), telefono VARCHAR(20), email VARCHAR(100) NOT NULL UNIQUE, representante VARCHAR(100), password VARCHAR(255) NOT NULL, convenio VARCHAR(100), estado ENUM('activo','inactivo') DEFAULT 'activo', fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP ); 

 CREATE TABLE examenes ( id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, descripcion TEXT, area VARCHAR(50), metodologia VARCHAR(100), fase_preanalitica TEXT, tiempo_proceso VARCHAR(50), precio_publico DECIMAL(10,2) NOT NULL, precio_convenio DECIMAL(10,2), precio_campania DECIMAL(10,2), precio_oferta DECIMAL(10,2), stock INT DEFAULT 0, contador_consumo INT DEFAULT 0, promociones JSON, estado ENUM('activo','inactivo') DEFAULT 'activo', fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP ); 

 CREATE TABLE formatos_resultados ( id INT AUTO_INCREMENT PRIMARY KEY, examen_id INT NOT NULL, nombre_formato VARCHAR(50), tipo_formato ENUM('media_hoja','a4','personalizado') DEFAULT 'a4', plantilla TEXT, FOREIGN KEY (examen_id) REFERENCES examenes(id) ); 

Buenas Prácticas Implementadas
Código modular, organizado por carpetas y componentes.
Uso de PDO y prepared statements para seguridad en la base de datos.
Contraseñas encriptadas con password_hash().
Validación y sanitización de datos del lado del servidor.
Generación automática de codigo_cliente.
Estructura lista para integración de DataTables, Bootstrap y otros frameworks modernos.
Próximos pasos sugeridos
Implementar formularios y lógica de registro/login para cada tipo de usuario.
Integrar DataTables y Bootstrap para tablas y diseño moderno.
Crear módulos para resultados, cotizaciones y reportes.
Agregar control de acceso por roles y sesiones.
Autor:
Cesar & Kodee (Hostinger AI)
2025



INBIOSLAB - Sistema de Gestión de Clientes
Descripción
Este proyecto es un sistema modular para la gestión de clientes de un laboratorio clínico, desarrollado en PHP y MySQL. Permite a los usuarios con permisos (admin) registrar, listar, editar y eliminar clientes, y cuenta con control de acceso por roles.
Características principales
CRUD de clientes: Alta, listado, edición y eliminación.
Campos únicos: Código de cliente, email y DNI no pueden repetirse.
Generación automática de código de cliente: Botón para generar códigos tipo HC-XXXXXX.
Campos adicionales: Edad, procedencia y referencia incluidos en el formulario y base de datos.
Paginación: El listado de clientes muestra 10 por página, con buscador dinámico.
Roles: Solo el admin puede gestionar clientes; los clientes solo ven una bienvenida.
Seguridad: Contraseñas hasheadas y validaciones en el backend.
Interfaz modular: Uso de componentes (header, navbar, sidebar, footer) para fácil personalización.
Estructura del proyecto
/src/ /clientes/ tabla_clientes.php form_clientes.php editar_cliente.php eliminar_cliente.php /componentes/ header.php navbar.php sidebar.php footer.php /conexion/ conexion.php /config/ config.php dashboard.php index.php 

Instalación
Clona el repositorio o sube los archivos a tu servidor local/hosting.
Importa la base de datos y ejecuta este SQL para agregar los nuevos campos:
ALTER TABLE clientes ADD COLUMN edad INT(3) AFTER apellido, ADD COLUMN procedencia VARCHAR(100) AFTER sexo, ADD COLUMN referencia VARCHAR(100) AFTER procedencia; 

Configura la conexión a la base de datos en /src/conexion/conexion.php. Accede al sistema desde:
http://localhost/base-php/src/dashboard.php
Uso
Agregar cliente: Ve a "Clientes" en el sidebar y haz clic en "Agregar Cliente". Llena el formulario y usa el botón "Generar" para crear un código automático si lo deseas.
Listar clientes: El listado muestra el código de cliente, nombre, apellido, email y acciones (editar/eliminar), con paginación y buscador.
Editar/eliminar: Usa los enlaces en la tabla para editar o eliminar clientes existentes.
Roles: El admin puede gestionar todo el CRUD; el cliente solo ve una bienvenida y puede cerrar sesión.
Notas
Para personalizar la apariencia, edita los archivos en /src/componentes/.
El sistema está preparado para extenderse a otros módulos (usuarios, empresas, etc.).
Si tienes dudas, revisa los archivos PHP comentados y la estructura modular.
¿Quieres que agregue instrucciones para otros módulos o más detalles técnicos?


++-------03-----
Proyecto: INBIOSLAB - Laboratorio Clínico Estructura de carpetas y archivos principales
Copy
src/
  ├── auth/
  │     ├── login.php, registro.php, recuperar.php, restablecer.php
  ├── clases/
  ├── clientes/
  │     ├── tabla_clientes.php, form_clientes.php, editar_cliente.php, eliminar_cliente.php
  ├── empresas/
  │     ├── tabla_empresas.php, form_empresa.php, editar_empresa.php, eliminar_empresa.php
  ├── usuarios/
  │     ├── tabla_usuarios.php, form_usuario.php, editar_usuario.php, eliminar_usuario.php
  ├── componentes/
  │     ├── header.php, navbar.php, sidebar.php, footer.php
  ├── config/
  │     ├── config.php
  ├── conexion/
  │     ├── conexion.php
  └── dashboard.php
Variables y lógica clave Variables de sesión
$_SESSION[&apos;usuario&apos;] → Email del usuario autenticado.
$_SESSION[&apos;nombre&apos;] → Nombre visible del usuario.
$_SESSION[&apos;rol&apos;] → Rol principal: admin, recepcionista, laboratorista, cliente, empresa.
(En empresas/clientes, el acceso es personalizado; en usuarios el acceso depende del rol ENUM.)
Roles y permisos
admin: Acceso total a CRUD de clientes, usuarios y empresas.
recepcionista: Verá solo su panel personalizado (por implementar).
laboratorista: Verá solo su panel personalizado (por implementar).
empresa: Panel propio, sin acceso a CRUD generales.
cliente: Solo saludo y futuras funciones como cotización y resultados.
Campos ENUM en la base de datos
usuarios.rol: &apos;admin&apos;, &apos;recepcionista&apos;, &apos;laboratorista&apos;
usuarios.sexo: &apos;masculino&apos;, &apos;femenino&apos;, &apos;otro&apos;
Validaciones en formularios
Los selects de rol y sexo usan exactamente los valores ENUM de la base de datos.
Validaciones PHP con in_array para evitar errores de truncado.
Email único para usuarios.
CRUD Modular
Cada entidad (clientes, usuarios, empresas) tiene sus propios archivos: tabla, form, editar, eliminar.
Todos los includes usan rutas absolutas con __DIR__.
Sidebar y dashboard
Sidebar muestra solo los enlaces permitidos según el rol.
dashboard.php valida permisos y carga vistas dinámicamente según el rol y el parámetro vista.
Pendientes y futuras tareas
Implementar vistas personalizadas para recepcionista, laboratorista, empresa y cliente.
Mejorar validaciones y feedback visual en formularios.
Agregar módulos de cotización, reportes, y paneles personalizados.
Documentar nuevos módulos y campos ENUM si se agregan.

----08-06-25------
BASE-PHP – Documentación de Módulos Estructura General
componentes/header.php: Contiene el encabezado del sitio y el botón hamburguesa para abrir el sidebar en dispositivos móviles.
componentes/sidebar.php: Sidebar responsivo. Es fijo en escritorio/tablet y se muestra como offcanvas en móvil. Los enlaces se muestran según el rol del usuario.
componentes/footer.php: Pie de página fijo para todo el sistema.
dashboard.php: Orquesta la carga dinámica de vistas y módulos según el rol y las acciones del usuario.
config/config.php: Define constantes como BASE_URL para rutas absolutas.
Roles Soportados
admin: Acceso completo a usuarios, empresas y clientes.
empresa: Acceso a su propio panel.
recepcionista: Acceso a su propio panel.
laboratorista: Acceso a su propio panel.
cliente: Acceso a su propio panel.
Sidebar
Fijo en escritorio/tablet (d-none d-md-block).
Offcanvas en móvil (d-md-none), activado por el botón hamburguesa del header.
Enlaces dinámicos según el rol del usuario.
Estilos con Bootstrap 5 y Bootstrap Icons.
Main Content
El contenido principal (&lt;main&gt;) se alinea al costado del sidebar usando Bootstrap Flexbox.
En móvil, el main ocupa el 100% del ancho cuando el sidebar está oculto.
Responsive
Totalmente responsivo con Bootstrap 5.
El botón hamburguesa solo aparece en móvil y controla el sidebar offcanvas.
Espaciado adicional en el sidebar móvil para mejorar la experiencia de usuario.
Rutas y Includes
Todos los includes usan rutas absolutas con __DIR__ para evitar errores de ubicación.
Los archivos de vistas para cada rol están organizados en carpetas específicas.
Ejemplo de Estructura
Copy
BASE-PHP/src/
│
├── componentes/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
│
├── config/
│   └── config.php
│
├── usuarios/
│   └── editar_usuario.php
│   └── eliminar_usuario.php
│   └── form_usuarios.php
│   └── tabla_usuarios.php vistas/
│                          └── panel_admin.php
│                          │__ panel_recepcionista.php
│                          │__ panel_laboratorista.php
│
├── empresas/
│   └── editar_empresa.php
│   └── eliminar_empresa.php
│   └── form_empresa.php
│   └── tabla_uempresas.php vistas/
│                           └── panel_empresa.php
|
├── convenios/
│   └── editar_convenio.php
│   └── eliminar_convenio.php
│   └── form_convenios.php
│   └── tabla_convenios.php vistas/
│                           └── panel_cconvenio.php
│
├── clientes/
│   └── editar_cliente.php
│   └── eliminar_cliente.php
│   └── form_clientes.php
│   └── tabla_clientes.php vistas/
│                           └── panel_cliente.php
├── examenes/
│   └── editar_examen.php
│   └── eliminar_examen.php
│   └── form_examenes.php
│   └── tabla_examenes.php funciones/
|                          |__examenes_crud.php
│
├── dashboard.php
└── ...
Notas
Asegúrate de tener Bootstrap 5 y Bootstrap Icons correctamente cargados en tu header.
Para agregar nuevos roles o vistas, sigue la estructura y lógica de los módulos existentes.
Todos los cambios de diseño deben hacerse en los archivos de componentes para mantener la modularidad.

----------11-06-2025-------------
# Módulo de Cotizaciones – INBIOSLAB

Este módulo permite la generación, gestión y consulta de cotizaciones de exámenes para clientes, empresas y convenios, tanto desde el panel de usuario como desde los perfiles de recepcionista y laboratorista.

---

## Estructura de Base de Datos

### Tabla: cotizaciones
- `id`: INT, clave primaria, auto-incremental.
- `codigo`: VARCHAR(30), código único de cotización (ej: COT-2024-0001).
- `id_cliente`: INT, referencia a clientes (nullable).
- `id_empresa`: INT, referencia a empresas (nullable).
- `id_convenio`: INT, referencia a convenios (nullable).
- `tipo_usuario`: ENUM('cliente','empresa','convenio'), identifica el tipo de usuario.
- `fecha`: DATETIME, fecha de generación.
- `total`: DECIMAL(10,2), monto total de la cotización.
- `estado_pago`: ENUM('pendiente','pagado'), estado del pago.
- `observaciones`: TEXT, comentarios adicionales.
- `pdf_url`: VARCHAR(255), ruta del PDF generado (opcional).
- `creado_por`: INT, ID del usuario que generó la cotización.
- `rol_creador`: ENUM('cliente','recepcionista','laboratorista','admin'), rol del creador.

### Tabla: cotizaciones_detalle
- `id`: INT, clave primaria.
- `id_cotizacion`: INT, referencia a cotizaciones.
- `id_examen`: INT, referencia a examenes.
- `nombre_examen`: VARCHAR(100), nombre del examen al momento de cotizar.
- `precio_unitario`: DECIMAL(10,2), precio por examen.
- `cantidad`: INT, cantidad solicitada.
- `subtotal`: DECIMAL(10,2), total por examen.

---

## Funcionalidades

### 1. Generación de Cotizaciones
- El usuario (cliente, empresa, convenio, recepcionista o laboratorista) selecciona exámenes y genera una cotización.
- El sistema calcula precios según el tipo de usuario y muestra el total.
- Se genera un PDF formal con logo, datos del laboratorio, datos del cliente/empresa/convenio, lista de exámenes, totales y condiciones.
- El PDF incluye el código de cotización y los datos para rotulación de muestras.

### 2. Consulta e Historial
- Cada usuario puede ver su historial de cotizaciones filtrando por fecha, estado de pago, etc.
- Recepcionistas y laboratoristas pueden buscar clientes por DNI, código, nombre o apellido para generar cotizaciones en el laboratorio.
- El sistema registra quién y cuándo realizó cada cotización.
- El historial muestra estado del pago y permite descargar/imprimir el PDF.

### 3. Restricciones y Seguridad
- Si la cotización tiene exámenes pendientes de pago, el usuario no puede descargar los resultados.
- Solo el usuario correspondiente o personal autorizado puede ver y descargar sus cotizaciones y resultados.

### 4. Reportes y Auditoría
- Para empresas/convenios: resumen de producción y deuda según el contrato (mensual, quincenal, semanal, diario).
- Auditoría de cotizaciones por usuario creador (recepcionista/laboratorista).

---

## Ejemplo de Flujo

1. Recepcionista busca cliente y genera cotización en el sistema.
2. El cliente recibe su cotización formal en PDF, con todos los datos y código para rotulación.
3. El cliente paga y, una vez confirmado el pago, puede descargar sus resultados.
4. El sistema guarda todo el historial y permite reportes por usuario, periodo, estado de pago, etc.

---

## Tecnologías Recomendadas

- PHP + MySQL (PDO)
- Bootstrap 5 + DataTables para la interfaz
- Librería para PDF: mPDF, DomPDF o TCPDF

---

## Seguridad y Buenas Prácticas

- Validación de datos en servidor y cliente.
- Control de permisos según rol.
- Uso de IDs y códigos únicos para trazabilidad.
- Protección de archivos PDF y datos sensibles.

---

## Futuras Mejoras

- Notificaciones por email/SMS al generar cotizaciones o cuando el resultado esté disponible.
- Integración con pasarela de pagos.
- Reportes gráficos de producción y deuda.

---

**INBIOSLAB – Gestión inteligente de laboratorio clínico**

src/
└── cotizaciones/
    ├── cotizaciones.php             # Listado e historial de cotizaciones del cliente
    ├── form_cotizacion.php          # Formulario para crear nueva cotización
    ├── crear_cotizacion.php         # Lógica para crear cotización (procesa el formulario)
    ├── ver_cotizacion.php           # Vista/Detalle de una cotización específica
    ├── descargar_pdf.php            # Descarga la cotización en PDF (si está pagada)
    ├── imprimir_cotizacion.php      # Vista amigable para imprimir
    ├── data/
    │   └── cotizaciones_detalle.php # Gestión AJAX/detalle de exámenes en la cotización (opcional)
    └── assets/
        ├── cotizacion.css           # Estilos específicos para cotizaciones (opcional)
        └── cotizacion.js            # JS específico para formularios/cotizaciones (opcional)


¿Qué hace cada archivo?
cotizaciones.php:
Muestra el historial de cotizaciones del cliente, con filtros por fecha, estado y acciones de ver, descargar o imprimir.

form_cotizacion.php:
Formulario para seleccionar exámenes y generar una nueva cotización.

crear_cotizacion.php:
Recibe los datos del formulario, guarda la cabecera y el detalle en la base de datos, y redirige al detalle o historial.

ver_cotizacion.php:
Muestra todos los datos de una cotización específica, con opción de descargar PDF o imprimir si está pagada.

descargar_pdf.php:
Genera y descarga el PDF de la cotización (solo si el estado es “pagado”).

imprimir_cotizacion.php:
Muestra la cotización en un formato optimizado para impresión (A4/A5).

data/cotizaciones_detalle.php (opcional):
Para cargar detalles dinámicamente vía AJAX si quieres una experiencia más interactiva.

assets/:
Para tus estilos y scripts propios del módulo de cotizaciones.    

-----------12-06-25-------------
Módulo de Cotizaciones – INBIOSLAB Descripción General
Este módulo permite la gestión integral de cotizaciones para el laboratorio clínico INBIOSLAB, soportando múltiples roles de usuario (admin, cliente, empresa, convenio, recepcionista, laboratorista). Incluye historial, generación, edición, eliminación, exportación, impresión y manejo seguro de sesiones.

Estructura de Archivos
cotizaciones/
    crear_cotizacion.php
    descargar_pdf.php
    editar_cotizacion.php
    eliminar_cotizacion.php
    form_cotizacion.php
    imprimir_cotizacion.php
    ver_cotizacion.php
    cotizaciones.php
 assets/
    cotizacion.css
    cotizacion.js
 data/
    cotizaciones_detalle.php


funcionalidades recomendadas para continuar y fortalecer tu sistema:

1. Edición de Cotizaciones
Permitir que los usuarios editen cotizaciones pendientes.
Archivo sugerido: editar_cotizacion.php.
Validar que solo el creador o roles autorizados puedan editar.
2. Eliminación de Cotizaciones
Permitir borrar cotizaciones (solo pendientes o según permisos).
Archivo sugerido: eliminar_cotizacion.php.
Confirmación antes de eliminar.
3. Ver Detalle de Cotización
Mostrar todos los datos y exámenes de una cotización en una vista detallada.
Archivo sugerido: ver_cotizacion.php.
4. Descargar e Imprimir PDF
Mejorar la presentación del PDF.
Archivo sugerido: descargar_pdf.php, imprimir_cotizacion.php.
5. Notificaciones y Seguimiento
Enviar email al crear o actualizar una cotización (opcional).
Mostrar historial de cambios o auditoría.
6. Filtrado y Búsqueda Avanzada
Permitir filtrar cotizaciones por fecha, estado, cliente, etc.
7. Gestión de Pagos
Marcar cotizaciones como pagadas, agregar comprobante o fecha de pago.
Cambiar el estado_pago desde la interfaz.
8. Gráficas y Reportes
Mostrar estadísticas: cotizaciones por mes, por estado, por usuario, etc.

Flujo y Convenciones 1. Acceso y Sesiones
Control de acceso: Todas las vistas y acciones verifican el rol y el ID del usuario mediante variables de sesión ($_SESSION[&apos;rol&apos;], $_SESSION[&apos;usuario_id&apos;], $_SESSION[&apos;cliente_id&apos;], etc.).
Redirecciones: Toda lógica que use header() se ejecuta antes de incluir header.php/sidebar.php para evitar errores de headers.
Roles soportados: admin, cliente, empresa, convenio, recepcionista, laboratorista.
2. CRUD de Cotizaciones
Crear: El formulario se accede vía dashboard.php?vista=form_cotizacion y se procesa con dashboard.php?action=crear_cotizacion.
Ver: Detalles completos en ver_cotizacion.php, mostrando exámenes, cantidades y totales.
Editar y Eliminar: Solo para cotizaciones pendientes y según permisos.
Listar: Vista responsiva con DataTables, muestra código, fecha, exámenes, total, estado y acciones.
3. Base de Datos
Campos clave:
codigo (string, generado automáticamente)
creado_por (ID del usuario que crea)
rol_creador (rol del creador)
nombre_examen (en cotizaciones_detalle, obligatorio)
Relaciones: Cada cotización puede estar asociada a cliente, empresa o convenio, y guarda historial de detalles.
4. Interfaz y Usabilidad
Responsividad: Tablas y botones adaptados para PC, tablet y móvil, usando Bootstrap y Bootstrap Icons.
Acciones: En PC/tablet, botones directos; en móvil, menú desplegable.
Mensajes: Uso de $_SESSION[&apos;mensaje&apos;] para retroalimentación al usuario.
5. Seguridad y Buenas Prácticas
Validación: Todos los formularios validan datos requeridos y roles antes de procesar.
Preparación de consultas: Uso de PDO y consultas preparadas para evitar inyección SQL.
No acceso directo: Todos los archivos lógicos se acceden mediante el dashboard y no directamente.
6. Recomendaciones para Continuar
Implementa edición y eliminación segura de cotizaciones.
Mejora la gestión de pagos y permite cambiar el estado desde la interfaz.
Agrega notificaciones por email/SMS (opcional).
Añade filtros avanzados y reportes gráficos.
Documenta cualquier nuevo endpoint, vista o lógica siguiendo este estándar.
Cómo contribuir
Sigue la estructura de carpetas y la lógica modular.
Usa siempre validaciones de sesión y rol.
Ejecuta lógica de redirección antes de cualquier renderizado HTML.
Mantén los mensajes y la interfaz consistentes con Bootstrap y DataTables.
Documenta cualquier cambio importante en este archivo.


Documentación: Visualización de Cotizaciones Pendientes en Panel Cliente Descripción
El panel del cliente muestra un resumen de cotizaciones pendientes, incluyendo el número de cotizaciones en proceso y el monto total a pagar. Esta funcionalidad es clave para que el cliente tenga visibilidad clara y rápida de sus deudas y procesos activos.

Consulta SQL utilizada
Se obtiene el total y la suma de los importes de cotizaciones agrupadas por estado de pago:

Copy
$stmt = $pdo->prepare("
    SELECT estado_pago, COUNT(*) as total, COALESCE(SUM(total),0) as suma
    FROM cotizaciones
    WHERE id_cliente = ?
    GROUP BY estado_pago
");
$stmt->execute([$id_cliente]);
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
Procesamiento en PHP
Se recorre el array resultante para identificar las cotizaciones pendientes y sumar el total:

Copy
$pendientes = 0;
$total_deuda = 0;
foreach ($estados as $e) {
    if (strtolower($e['estado_pago']) === 'pendiente') {
        $pendientes = $e['total'];
        $total_deuda = $e['suma'];
    }
}
Se utiliza strtolower para evitar problemas con mayúsculas/minúsculas.
No se usa floatval/intval si la consulta y la base de datos ya devuelven datos correctos, pero puede agregarse para mayor robustez.
Visualización en la interfaz
El resultado se muestra en una card Bootstrap:

Copy
<p class="mb-0"><strong>Total a pagar:</strong> S/ <?= number_format($total_deuda, 2) ?></p>
Esto asegura que el cliente vea el monto exacto de sus cotizaciones pendientes, con formato monetario adecuado.

Recomendaciones y buenas prácticas
Asegurarse de que el campo total en la tabla cotizaciones sea numérico y nunca NULL.
Usar COALESCE o IFNULL en la consulta SQL para evitar valores NULL en la suma.
Siempre validar y sanitizar los datos antes de mostrarlos en la interfaz.
Si se agregan nuevos estados de pago, actualizar la lógica del foreach en consecuencia.
Resumen
Este flujo garantiza que el cliente siempre vea el número correcto de cotizaciones pendientes y el total a pagar, mejorando la experiencia y la transparencia en el sistema.
<form action="dashboard.php?action=<?= $esEdicion ? 'editar_promocion&id=' . $promo['id'] : 'crear_promocion' ?>" method="post" enctype="multipart/form-data">

<button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Actualizar' : 'Crear' ?> Cotización</button>
        <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary">Cancelar</a>


        ----19/06/2025---
        Proyecto modular en PHP para la gestión de usuarios y vistas con control de acceso basado en roles.

Estructura del Proyecto
BASE-PHP/
│
├── src/
│   ├── autenticacion/
│   ├── configuracion/
│   ├── clientes/
│   ├── componentes/
│   ├── conexión/
│   │   └── conexion.php
│   ├── dashboard.php
│   ├── index.php
│   └── ...
├── tmp/
├── vendor/
└── ...
Principales características
Gestión de sesiones:
Las sesiones se inician solo si no están activas, previniendo errores de headers.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Control de acceso basado en roles:
Los permisos y accesos a vistas están definidos en arrays asociativos, asignando acciones y vistas a cada rol de usuario.

Carga dinámica de vistas y componentes:
El archivo dashboard.php valida el rol y la vista solicitada antes de incluir los archivos correspondientes.
Los componentes comunes (header.php, sidebar.php, footer.php) se incluyen dinámicamente para mantener consistencia y evitar errores de headers.

Manejo centralizado de rutas:
Se utiliza la constante BASE_URL para gestionar rutas, facilitando cambios y mantenimiento.

Mensajes de sesión y manejo de errores:
Los mensajes y alertas se muestran según la lógica de acceso y las acciones realizadas.

Conexión a Base de Datos
La conexión se encuentra en:
src/conexión/conexion.php

Ejecución local
Clona el repositorio.
Configura tu entorno local (por ejemplo, XAMPP, Laragon, etc.).
Asegúrate de que tu base de datos esté configurada y la conexión en conexion.php sea correcta.
Accede a index.php desde tu navegador.
Recomendaciones
Mantén actualizada la lógica de roles y vistas en los arrays para nuevos módulos.
Centraliza rutas y configuraciones en archivos únicos para facilitar el mantenimiento.
Si agregas nuevos componentes o vistas, sigue la misma estructura modular para asegurar compatibilidad.

Aquí tienes una visión general de las tablas principales y cómo pueden relacionarse con tus módulos futuros:

clientes: Almacena la información de los clientes, incluyendo datos personales, contacto y credenciales (contraseñas hash).
config_empresa: Guarda la configuración de la empresa, útil para mostrar información corporativa o parametrizar módulos.
cotizaciones: Registra las cotizaciones realizadas, incluyendo referencias a clientes, empresa, convenios y detalles de pago.
promociones: Permite gestionar promociones activas, con campos para títulos, descripciones, imágenes, descuentos y vigencia.
examenes: Contiene los datos de los exámenes disponibles, con información técnica y comercial relevante.
Cada tabla puede servir como base para módulos específicos (clientes, cotizaciones, promociones, exámenes, etc.).