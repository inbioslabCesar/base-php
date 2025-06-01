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
