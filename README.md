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
│ ├── login.php, registro.php, recuperar.php, restablecer.php
├── clases/
├── clientes/
│ ├── tabla_clientes.php, form_clientes.php, editar_cliente.php, eliminar_cliente.php
├── empresas/
│ ├── tabla_empresas.php, form_empresa.php, editar_empresa.php, eliminar_empresa.php
├── usuarios/
│ ├── tabla_usuarios.php, form_usuario.php, editar_usuario.php, eliminar_usuario.php
├── componentes/
│ ├── header.php, navbar.php, sidebar.php, footer.php
├── config/
│ ├── config.php
├── conexion/
│ ├── conexion.php
└── dashboard.php
Variables y lógica clave Variables de sesión
$_SESSION[&apos;usuario&apos;] → Email del usuario autenticado.
$\_SESSION[&apos;nombre&apos;] → Nombre visible del usuario.
$\_SESSION[&apos;rol&apos;] → Rol principal: admin, recepcionista, laboratorista, cliente, empresa.
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
Todos los includes usan rutas absolutas con **DIR**.
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
Todos los includes usan rutas absolutas con **DIR** para evitar errores de ubicación.
Los archivos de vistas para cada rol están organizados en carpetas específicas.
Ejemplo de Estructura
Copy
BASE-PHP/src/
│
├── componentes/
│ ├── header.php
│ ├── sidebar.php
│ └── footer.php
│
├── config/
│ └── config.php
│
├── usuarios/
│ └── editar_usuario.php
│ └── eliminar_usuario.php
│ └── form_usuarios.php
│ └── tabla_usuarios.php vistas/
│ └── panel_admin.php
│ │** panel_recepcionista.php
│ │** panel_laboratorista.php
│
├── empresas/
│ └── editar_empresa.php
│ └── eliminar_empresa.php
│ └── form_empresa.php
│ └── tabla_uempresas.php vistas/
│ └── panel_empresa.php
|
├── convenios/
│ └── editar_convenio.php
│ └── eliminar_convenio.php
│ └── form_convenios.php
│ └── tabla_convenios.php vistas/
│ └── panel_cconvenio.php
│
├── clientes/
│ └── editar_cliente.php
│ └── eliminar_cliente.php
│ └── form_clientes.php
│ └── tabla_clientes.php vistas/
│ └── panel_cliente.php
├── examenes/
│ └── editar_examen.php
│ └── eliminar_examen.php
│ └── form_examenes.php
│ └── tabla_examenes.php funciones/
| |\_\_examenes_crud.php
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
├── cotizaciones.php # Listado e historial de cotizaciones del cliente
├── form_cotizacion.php # Formulario para crear nueva cotización
├── crear_cotizacion.php # Lógica para crear cotización (procesa el formulario)
├── ver_cotizacion.php # Vista/Detalle de una cotización específica
├── descargar_pdf.php # Descarga la cotización en PDF (si está pagada)
├── imprimir_cotizacion.php # Vista amigable para imprimir
├── data/
│ └── cotizaciones_detalle.php # Gestión AJAX/detalle de exámenes en la cotización (opcional)
└── assets/
├── cotizacion.css # Estilos específicos para cotizaciones (opcional)
└── cotizacion.js # JS específico para formularios/cotizaciones (opcional)

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
Control de acceso: Todas las vistas y acciones verifican el rol y el ID del usuario mediante variables de sesión ($\_SESSION[&apos;rol&apos;], $\_SESSION[&apos;usuario_id&apos;], $\_SESSION[&apos;cliente_id&apos;], etc.).
Redirecciones: Toda lógica que use header() se ejecuta antes de incluir header.php/sidebar.php para evitar errores de headers.
Roles soportados: admin, cliente, empresa, convenio, recepcionista, laboratorista. 2. CRUD de Cotizaciones
Crear: El formulario se accede vía dashboard.php?vista=form_cotizacion y se procesa con dashboard.php?action=crear_cotizacion.
Ver: Detalles completos en ver_cotizacion.php, mostrando exámenes, cantidades y totales.
Editar y Eliminar: Solo para cotizaciones pendientes y según permisos.
Listar: Vista responsiva con DataTables, muestra código, fecha, exámenes, total, estado y acciones. 3. Base de Datos
Campos clave:
codigo (string, generado automáticamente)
creado_por (ID del usuario que crea)
rol_creador (rol del creador)
nombre_examen (en cotizaciones_detalle, obligatorio)
Relaciones: Cada cotización puede estar asociada a cliente, empresa o convenio, y guarda historial de detalles. 4. Interfaz y Usabilidad
Responsividad: Tablas y botones adaptados para PC, tablet y móvil, usando Bootstrap y Bootstrap Icons.
Acciones: En PC/tablet, botones directos; en móvil, menú desplegable.
Mensajes: Uso de $\_SESSION[&apos;mensaje&apos;] para retroalimentación al usuario. 5. Seguridad y Buenas Prácticas
Validación: Todos los formularios validan datos requeridos y roles antes de procesar.
Preparación de consultas: Uso de PDO y consultas preparadas para evitar inyección SQL.
No acceso directo: Todos los archivos lógicos se acceden mediante el dashboard y no directamente. 6. Recomendaciones para Continuar
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
│ ├── autenticacion/
│ ├── configuracion/
│ ├── clientes/
│ ├── componentes/
│ ├── conexión/
│ │ └── conexion.php
│ ├── dashboard.php
│ ├── index.php
│ └── ...
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

----modulo reporte resultados----
Flujo recomendado para el módulo de resultados
Cotización: El cliente/recepcionista cotiza uno o más exámenes.
Asignación: Cada examen cotizado queda pendiente de resultado en una nueva tabla (ej. resultados_examenes).
Laboratorista: Desde su panel, ve una lista de exámenes pendientes (usando DataTable) y puede ingresar resultados.
Guardado: El laboratorista guarda los resultados (vía AJAX) en la tabla resultados_examenes.
Visualización: Cliente y recepcionista pueden ver los resultados completados en sus respectivos paneles.
Archivos sugeridos en /src/resultados/
listado.php
Vista principal con DataTable para mostrar exámenes pendientes/completados según el rol.

formulario.php
Formulario para que el laboratorista ingrese o edite resultados de un examen específico.

guardar.php
Script PHP que recibe los datos (vía AJAX) y guarda los resultados en la base de datos.

ver.php
Vista para mostrar los resultados a cliente/recepcionista, en formato tabla agradable.

api_listado.php
Endpoint PHP que devuelve los exámenes/resultados en formato JSON para DataTable (AJAX).

js/resultados.js
Archivo JavaScript para manejar AJAX, inicializar DataTables, validaciones y acciones dinámicas.

Estructura de archivos sugerida
/src/resultados/
listado.php
formulario.php
guardar.php
ver.php
api_listado.php
js/
resultados.js

Recomendaciones adicionales
Usa Bootstrap 5 en todas las vistas para asegurar el diseño responsive.
Implementa DataTable para la visualización y filtrado eficiente de exámenes/resultados.
Utiliza AJAX para guardar y consultar resultados sin recargar la página.
Separa la lógica PHP (backend) de la presentación (frontend) y los scripts JS.
Aplica validaciones tanto en el frontend (JS) como en el backend (PHP).
Prueba en móvil y tablet para asegurar la experiencia responsive.

Usa includes o templates para la cabecera y pie en las vistas PHP, centralizando el HTML común.
Realiza las validaciones de datos en JS antes de enviar el formulario y, nuevamente, en PHP al recibir los datos.
Aprovecha las herramientas de desarrollo del navegador para probar el diseño en diferentes dispositivos.
Considera utilizar clases o funciones reutilizables para manejar respuestas y errores en el backend.
Documenta cada archivo y función para facilitar el mantenimiento futuro.

dashboard.php análisis de tu enfoque:

Usas control de sesiones y roles desde el inicio, asegurando que solo usuarios autenticados y con el rol correcto accedan al dashboard.
La lógica de permisos está bien organizada en arrays asociativos, tanto para acciones como para vistas, lo que facilita la gestión y escalabilidad.
Implementas includes para componentes comunes (header, sidebar, footer), separando la presentación del contenido dinámico.
Los mensajes de éxito o error se gestionan mediante variables de sesión, mostrando alertas limpias y eliminando los mensajes después de mostrarlos.
Todo el flujo está orientado a la seguridad y claridad, mostrando alertas cuando se intenta acceder a acciones o vistas no autorizadas.
En resumen, tu código es modular, seguro y fácil de mantener, siguiendo buenas prácticas de control de acceso y reutilización de componentes.

Analizando tu tabla examenes:

Tienes una estructura muy completa, cubriendo datos clave como código, nombre, área, metodología, tiempos y precios.
El campo adicional de tipo JSON es un gran acierto: te permite almacenar información flexible y personalizada para cada examen, como parámetros específicos, rangos de referencia, instrucciones extra, etc.
Usar JSON facilita futuras ampliaciones sin modificar la estructura de la tabla, ideal para laboratorios donde los exámenes pueden variar mucho.
El resto de los campos cubre tanto la parte técnica como la operativa y comercial del examen.
Los tipos de datos elegidos (int, varchar, text, decimal, json) son apropiados para cada campo.

sql para traer solo los nombres de la coumnas de una tablas
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'laboratorio'
AND TABLE_NAME = 'examenes';

sql para traer todo los datos del campo json
SELECT
j.nombre,
j.unidad,
j.referencia
FROM examenes,
JSON_TABLE(
adicional, '$[*]'
    COLUMNS (
      nombre VARCHAR(100) PATH '$.nombre',
unidad VARCHAR(20) PATH '$.unidad',
      referencia VARCHAR(50) PATH '$.referencia'
)
) AS j
WHERE id = 1;

CAMPO ADICIONAL EN JSON

[
{
"tipo": "Subtítulo",
"orden": 1,
"nombre": "Perfil lipídico Completo",
"unidad": "",
"formula": "",
"negrita": true,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "",
"referencias": []
},
{
"tipo": "Parámetro",
"orden": 2,
"nombre": "Colesterol Total",
"unidad": "mg/dl",
"formula": "",
"negrita": false,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "Enzimatico Colorimétrico",
"referencias": [{"desc": "", "valor": "(<200)"}]
},
{
"tipo": "Parámetro",
"orden": 3,
"nombre": "Colesterol HDL",
"unidad": "mg/dl",
"formula": "[Colesterol Total]/5",
"negrita": false,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "Enzimatico Colorimétrico",
"referencias": [{"desc": "", "valor": "(35-65)"}]
},
{
"tipo": "Parámetro",
"orden": 4,
"nombre": "Colesterol LDL",
"unidad": "mg/dl",
"formula": "[Colesterol Total]-[Colesterol HDL]-[Colesterol VLDL]",
"negrita": false,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "Enzimatico Colorimétrico",
"referencias": [{"desc": "", "valor": "(<135)"}]
},
{

    "tipo": "Parámetro",
    "orden": 7,
    "nombre": "Trigliceridos",
    "unidad": "mg/dl",
    "formula": "",
    "negrita": false,
    "opciones": [],
    "color_fondo": "#ffffff",
    "color_texto": "#000000",
    "metodologia": "Enzimatico Colorimétrico",
    "referencias": [{"desc": "", "valor": "(<150)"}]

}
{"tipo": "Parámetro",
"orden": 5,
"nombre": "Colesterol VLDL",
"unidad": "mg/dl",
"formula": "[Trigliceridos]/5",
"negrita": false,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "Enzimatico Colorimétrico",
"referencias": [{"desc": "", "valor": "(25-35)"}]
},
{
"tipo": "Parámetro",
"orden": 6,
"nombre": "Riesgo Coronario",
"unidad": "%",
"formula": "[Colesterol Total]/[Colesterol HDL]",
"negrita": false,
"opciones": [],
"color_fondo": "#ffffff",
"color_texto": "#000000",
"metodologia": "Calculo",
"referencias": [{"desc": "", "valor": "(<5.0)"}]
},
{

$(document).ready(function() {
  $.ajax({
    url: 'resultados.php',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
      // Mostrar datos del paciente
      $('#datos-paciente').html(
        `<strong>Nombre:</strong> ${data.paciente.nombre}   
         <strong>Edad:</strong> ${data.paciente.edad}   
         <strong>Sexo:</strong> ${data.paciente.sexo}   
         <strong>Fecha:</strong> ${data.paciente.fecha}   
         <strong>ID:</strong> ${data.paciente.id}`
      );
      // Llenar la tabla de resultados
      let filas = '';
      data.resultados.forEach(function(r) {
        filas += `<tr>
          <td>${r.prueba}</td>
<td>${r.metodologia}</td>
          <td>${r.resultado}</td>
<td>${r.unidades}</td>
          <td>${r.referencia}</td>
</tr>`;
});
$('#tabla-resultados').html(filas);
}
});
});

----COMO RENDERIZA EN MEDIO DE LA PALABRA----

<h4 class="text-center mb-3">Iniciar Sesión en <?= htmlspecialchars($config['nombre']) ?></h4>

----conexion---

<?php $host = 'localhost';
$dbname = 'laboratorio';
$user = 'root';
$pass = '';


<?php $host = 'localhost';
$dbname = 'u330560936_medditechbd';
$user = 'u330560936_medditech';
$pass = 'Medditech123';

<?php $host = 'localhost';
$dbname = 'u330560936_laboratorio';
$user = 'u330560936_inbioslab';
$pass = '41950361Cesar';



pasame el codigo completo de form_cotizacion con todas las actualizaciones y modificaciones desde el principio por partes si es extenso el codigo no modifiques ninguna variable o cambie de nombre lo que ya tiene para no alterar el codigo


{"HCM": "30.0", "VCM": "90.8", "CHCM": "33.0", "RDW-CV": "14.6", "MONOCITOS": "0.3", "PLAQUETAS": "200,000", "BASÓFILOS": "0.1", "LINFOCITOS": "1.3", "MONOCITOS%": "4", "ABASTONADOS": "0.1", "BASÓFILOS%": "1", "HEMATOCRITO": "45.4", "HEMOGLOBINA": "15", "LINFOCITOS%": "19", "SEGMENTADOS": "5.0", "ABASTONADOS%": "1", "EOSINÓFILOS": "0.3", "SEGMENTADOS%": "71", "EOSINÓFILOS%": "4", "R_GLOBULOS_ROJOS": "5.0", "R_GLOBULOS_BLANCOS": "7,000"}


----flujo empresa convenio 29/07/2025---
 Flujo de Registro y Cotización de Clientes para Empresas y Convenios 1. Registro y Búsqueda de Clientes
Evitar duplicados:
Antes de registrar un cliente, el sistema debe buscar por DNI (o correo) en la tabla clientes.
Si el cliente existe:
Mostrar sus datos y permitir asociarlo a la empresa/convenio actual.
O permitir hacer una nueva cotización para ese cliente.
Si el cliente no existe:
Permitir registrar un nuevo cliente, guardando el empresa_id o convenio_id según corresponda.
2. Asociación de Clientes
Un cliente puede estar asociado a varias empresas/convenios.
Si detectas un cliente existente, asócialo a la empresa/convenio actual usando un campo empresa_id o convenio_id en la tabla clientes, o una tabla intermedia si necesitas relaciones múltiples.
3. Creación de Cotización
Al seleccionar un cliente existente:
El formulario de cotización debe autocompletar los datos del cliente.
Al registrar un nuevo cliente:
Después del registro, redirigir al formulario de cotización con los datos del nuevo cliente.
4. Flujo recomendado en la interfaz
Buscar cliente por DNI o nombre.
Si existe:
Mostrar datos y botón “Asociar y cotizar”.
Si no existe:
Mostrar formulario para registrar nuevo cliente.
Una vez seleccionado/registrado el cliente:
Mostrar formulario de cotización autocompletado con los datos del cliente.
Registrar cotización asociando el id_cliente y el id_empresa o id_convenio correspondiente.
5. Listados y permisos
Empresa/convenio:
Puede ver todos sus clientes y sus cotizaciones.
Puede registrar nuevos clientes y cotizaciones.
Puede descargar resultados solo si no hay deuda y los exámenes están completos.
Cliente:
Solo puede ver y descargar sus propias cotizaciones y resultados.
6. Recomendaciones técnicas
Usa la tabla clientes para todos los clientes.
Usa los campos empresa_id o convenio_id para asociar clientes según corresponda.
Evita duplicar clientes, valida siempre por DNI/correo antes de registrar.
Reutiliza los formularios y CRUD existentes adaptando los filtros y relaciones.

Ejemplo de Consulta para Buscar Cliente

SELECT * FROM clientes WHERE dni = ? LIMIT 1;

Ejemplo de Asociación
Si el cliente ya existe, actualiza el campo empresa_id o convenio_id si corresponde, o crea la relación en una tabla intermedia.
Próximos pasos
Implementa la búsqueda y validación de clientes al registrar.
Prepara el formulario de cotización para autocompletar datos si el cliente existe.
Adapta las vistas de empresa y convenio para mostrar y gestionar sus clientes y cotizaciones.

Esto te servirá de guía clara y rápida para implementar el flujo de registro, asociación y cotización de clientes para empresas y convenios.

📊 Diagrama de Flujo (Texto)
[INICIO]
   |
   v
[Buscar cliente por DNI]
   |
   +---[¿Existe cliente?]---+
   |                        |
  Sí                        No
   |                        |
   v                        v
[Mostrar datos]        [Formulario registro]
   |                        |
[¿Asociado a empresa/convenio?]
   |                        |
  No                        Sí
   |                        |
[Asociar]               [Ir a cotización]
   |                        |
   v                        v
[Formulario cotización (autocompletado)]
   |
   v
[Registrar cotización]
   |
   v
[FIN]


 Ejemplo de Código PHP/MySQL 
 
 1. Buscar Cliente por DNI

 $dni = $_POST['dni'];
$sql = "SELECT * FROM clientes WHERE dni = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$dni]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    // Cliente existe, mostrar datos y opción de asociar/cotizar
} else {
    // Mostrar formulario para registrar nuevo cliente
}

2. Asociar Cliente Existente a Empresa/Convenio
Si usas un campo directo en clientes:

// Por ejemplo, para asociar a empresa
$sql = "UPDATE clientes SET empresa_id = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$empresa_id, $cliente['id']]);

Si usas tabla intermedia (ejemplo: clientes_empresas):

$sql = "INSERT IGNORE INTO clientes_empresas (cliente_id, empresa_id) VALUES (?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente['id'], $empresa_id]);

3. Registrar Cotización

$sql = "INSERT INTO cotizaciones (id_cliente, id_empresa, fecha, total, ...) VALUES (?, ?, ?, ?, ...)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente['id'], $empresa_id, date('Y-m-d'), $total, ...]);

4. Autocompletar Formulario de Cotización
En el formulario HTML, si $cliente existe:

<input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" readonly>
<input type="text" name="dni" value="<?= htmlspecialchars($cliente['dni']) ?>" readonly>
<!-- y así con los demás campos -->
📝 Recomendaciones Visuales
En el formulario de búsqueda, usa un campo de texto para DNI y un botón "Buscar".
Si el cliente existe, muestra sus datos en campos readonly y un botón “Cotizar”.
Si no existe, muestra el formulario de registro completo.
Después de registrar o seleccionar el cliente, muestra el formulario de cotización ya rellenado.
📐 Sugerencia de Tablas Relacionales
clientes: todos los clientes.
empresas: todas las empresas.
convenio: todos los convenios.
cotizaciones: todas las cotizaciones.
(Opcional) clientes_empresas o clientes_convenios: si un cliente puede estar en varias empresas/convenios.

para mantener tu proyecto ordenado y escalable.

1. Carpeta y Archivo para Búsqueda de Cliente
Carpeta sugerida:
/empresa/ (y/o /convenio/ si prefieres separar)

Archivo principal:
buscar_cliente.php

Formulario para ingresar DNI y buscar cliente.
Muestra resultado (datos del cliente o formulario de registro).
2. Carpeta y Archivo para Registro de Cliente
Archivo:
registrar_cliente.php

Formulario de registro solo si el cliente no existe.
Guarda el cliente y lo asocia a la empresa/convenio.
3. Carpeta y Archivo para Cotización
Archivo:
form_cotizacion.php

Formulario de cotización, autocompletado con los datos del cliente seleccionado o recién registrado.
4. Carpeta y Archivo para Listado
Archivo:
listado_clientes.php

Muestra todos los clientes asociados a la empresa/convenio logueado.
Archivo:
listado_cotizaciones.php

Muestra todas las cotizaciones de los clientes asociados.
📁 Estructura de carpetas sugerida

/empresa/
    buscar_cliente.php
    registrar_cliente.php
    form_cotizacion.php
    listado_clientes.php
    listado_cotizaciones.php

/convenio/
    buscar_cliente.php
    registrar_cliente.php
    form_cotizacion.php
    listado_clientes.php
    listado_cotizaciones.php
Puedes compartir lógica y plantillas entre empresa y convenio usando includes, si lo deseas.

¿Cómo seguimos?
1. Creamos /empresa/buscar_cliente.php

/src/
  /empresas/
    form_cotizacion_empresa.php
    acciones/
      cotizacion_empresa_accion.php
  /convenios/
    form_cotizacion_convenio.php
    acciones/
      cotizacion_convenio_accion.php

      /src/
  /empresas/
    buscar_cliente.php
    registrar_cliente.php
    form_cotizacion_empresa.php
    listado_clientes.php
    listado_cotizaciones.php
    /acciones/
      buscar_cliente_accion.php
      registrar_cliente_accion.php
      cotizacion_empresa_accion.php
  /convenios/
    buscar_cliente.php
    registrar_cliente.php
    form_cotizacion_convenio.php
    listado_clientes.php
    listado_cotizaciones.php
    /acciones/
      buscar_cliente_accion.php
      registrar_cliente_accion.php
      cotizacion_convenio_accion.php

http://localhost/base-php/src/dashboard.php?vista=form_cotizacion&id=59

http://localhost/base-php/src/dashboard.php?vista=form_cotizacion&id=59

al precionar guardar cotizacion

admin => http://localhost/base-php/src/dashboard.php?vista=agendar_cita&id_cotizacion=204

empresas => http://localhost/base-php/src/dashboard.php?action=crear_cotizacion




ALTER TABLE clientes ADD COLUMN rol_creador VARCHAR(50) AFTER estado;



ALTER TABLE clientes 
ADD COLUMN rol_creador VARCHAR(50) AFTER estado,
ADD COLUMN empresa_nombre VARCHAR(100) DEFAULT NULL AFTER rol_creador,
ADD COLUMN convenio_nombre VARCHAR(100) DEFAULT NULL AFTER empresa_nombre,
ADD COLUMN tipo_registro VARCHAR(20) DEFAULT 'cliente' AFTER convenio_nombre;

meditech conexion.php
<?php $host = 'localhost';
$dbname = 'u330560936_medditechbd';
$user = 'u330560936_medditech';
$pass = 'Medditech123';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Puedes registrar el error en un archivo y mostrar un mensaje genérico al usuario 
    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}



   <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "MEDDITECH",
            "url": "https://www.medditech.es",
            "logo": "https://www.medditech.es/src/images/empresa/logo_empresa.png"
        }
    </script>

      <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "INBIOSLAB",
            "url": "https://www.inbioslabstore.com",
            "logo": "https://www.inbioslabstore.com/src/images/empresa/logo_empresa.png"
        }
    </script>

    inbioslab config.php
    <?php define('BASE_URL', '/src/'); // Para tu entorno local actual 

    conexion.php

    <?php $host = 'localhost';
$dbname = 'u330560936_laboratorio';
$user = 'u330560936_inbioslab';
$pass = '41950361Cesar';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Puedes registrar el error en un archivo y mostrar un mensaje genérico al usuario 
    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}


tecnolab pagina web
config.php
    <?php define('BASE_URL', '/src/'); // Para tu entorno local actual 
conexion.php
    <?php $host = 'localhost';
$dbname = 'u330560936_tecnolabbd';
$user = 'u330560936_tecnolab';
$pass = 'Tecnolab07-09-25';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Puedes registrar el error en un archivo y mostrar un mensaje genérico al usuario 
    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}


utf8mb4_general_ci