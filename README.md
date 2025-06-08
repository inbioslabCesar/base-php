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
BASE-PHP/
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
│   └── vistas/
│       └── panel_cliente.php
│
├── empresas/
│   └── vistas/
│       └── panel_empresa.php
│
├── recepcionista/
│   └── vistas/
│       └── panel_recepcionista.php
│
├── laboratorista/
│   └── vistas/
│       └── panel_laboratorista.php
│
├── dashboard.php
└── ...
Notas
Asegúrate de tener Bootstrap 5 y Bootstrap Icons correctamente cargados en tu header.
Para agregar nuevos roles o vistas, sigue la estructura y lógica de los módulos existentes.
Todos los cambios de diseño deben hacerse en los archivos de componentes para mantener la modularidad.

