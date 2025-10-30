-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 30, 2025 at 03:02 AM
-- Server version: 8.0.42
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laboratorio`
--

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `codigo_cliente` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexo` enum('masculino','femenino','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `procedencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promociones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `rol_creador` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `empresa_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `convenio_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_registro` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'cliente',
  `descuento` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `configuracion_clinica`
--

CREATE TABLE `configuracion_clinica` (
  `id` int NOT NULL,
  `nombre_clinica` varchar(255) NOT NULL,
  `direccion` text NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `horario_atencion` text,
  `logo_url` varchar(500) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config_empresa`
--

CREATE TABLE `config_empresa` (
  `id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruc` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_principal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#0d6efd',
  `color_secundario` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f9fa',
  `color_footer` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#343a40',
  `frase_promocion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oferta_mes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagenes_carrusel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `servicios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `testimonios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `redes_sociales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `menu_inicio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Inicio',
  `menu_servicios` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Servicios',
  `menu_testimonios` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Testimonios',
  `menu_contacto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Contacto',
  `imagenes_institucionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `color_botones` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#198754',
  `color_texto` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#212529',
  `tamano_letra` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1rem'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `convenios`
--

CREATE TABLE `convenios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT '0.00',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `convenio_cliente`
--

CREATE TABLE `convenio_cliente` (
  `id` int NOT NULL,
  `convenio_id` int NOT NULL,
  `cliente_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_empresa` int DEFAULT NULL,
  `id_convenio` int DEFAULT NULL,
  `tipo_usuario` enum('cliente','empresa','convenio','recepcionista') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) NOT NULL,
  `total_bruto` decimal(10,2) DEFAULT '0.00',
  `estado_pago` enum('pendiente','pagado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `pdf_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_por` int NOT NULL,
  `rol_creador` enum('cliente','recepcionista','laboratorista','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_toma` date DEFAULT NULL,
  `hora_toma` time DEFAULT NULL,
  `tipo_toma` enum('laboratorio','domicilio') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_toma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT '0.00',
  `estado_muestra` enum('pendiente','realizada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizaciones_detalle`
--

CREATE TABLE `cotizaciones_detalle` (
  `id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_examen` int NOT NULL,
  `nombre_examen` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `egresos`
--

CREATE TABLE `egresos` (
  `id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `ruc` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_comercial` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `representante` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `convenio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `descuento` decimal(5,2) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `empresa_cliente`
--

CREATE TABLE `empresa_cliente` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `cliente_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examenes`
--

CREATE TABLE `examenes` (
  `id` int NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodologia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiempo_respuesta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preanalitica_cliente` text COLLATE utf8mb4_unicode_ci,
  `preanalitica_referencias` text COLLATE utf8mb4_unicode_ci,
  `tipo_muestra` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_tubo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `precio_publico` decimal(10,2) NOT NULL DEFAULT '0.00',
  `adicional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `vigente` tinyint(1) DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_cliente`
--

CREATE TABLE `examenes_cliente` (
  `id` int NOT NULL,
  `examen_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_convenio`
--

CREATE TABLE `examenes_convenio` (
  `id` int NOT NULL,
  `examen_id` int NOT NULL,
  `convenio_id` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descuento` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_empresa`
--

CREATE TABLE `examenes_empresa` (
  `id` int NOT NULL,
  `examen_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_promocion`
--

CREATE TABLE `examenes_promocion` (
  `id` int NOT NULL,
  `examen_id` int NOT NULL,
  `promocion_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicamentos`
--

CREATE TABLE `medicamentos` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `presentacion` varchar(50) DEFAULT NULL,
  `concentracion` varchar(50) DEFAULT NULL,
  `laboratorio` varchar(100) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movimientos_medicamento`
--

CREATE TABLE `movimientos_medicamento` (
  `id` int NOT NULL,
  `medicamento_id` int NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones`
--

CREATE TABLE `promociones` (
  `id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio_promocional` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `vigente` tinyint(1) DEFAULT '1',
  `tipo_publico` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_empresa`
--

CREATE TABLE `promociones_empresa` (
  `id` int NOT NULL,
  `promocion_id` int NOT NULL,
  `empresa_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_examen`
--

CREATE TABLE `promociones_examen` (
  `id` int NOT NULL,
  `promocion_id` int NOT NULL,
  `examen_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resultados`
--

CREATE TABLE `resultados` (
  `id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_examen` int NOT NULL,
  `resultados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` int DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci
) ;

-- --------------------------------------------------------

--
-- Table structure for table `resultados_examenes`
--

CREATE TABLE `resultados_examenes` (
  `id` int NOT NULL,
  `id_examen` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_laboratorista` int DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci
) ;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexo` enum('masculino','femenino','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profesion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('admin','recepcionista','laboratorista') COLLATE utf8mb4_unicode_ci DEFAULT 'recepcionista',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `codigo_cliente` (`codigo_cliente`);

--
-- Indexes for table `configuracion_clinica`
--
ALTER TABLE `configuracion_clinica`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `config_empresa`
--
ALTER TABLE `config_empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `convenio_id` (`convenio_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indexes for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_convenio` (`id_convenio`),
  ADD KEY `cotizaciones_ibfk_1` (`id_cliente`);

--
-- Indexes for table `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`);

--
-- Indexes for table `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruc` (`ruc`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empresa_id` (`empresa_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indexes for table `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indexes for table `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `convenio_id` (`convenio_id`);

--
-- Indexes for table `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `examenes_empresa_ibfk_1` (`examen_id`);

--
-- Indexes for table `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `promocion_id` (`promocion_id`);

--
-- Indexes for table `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movimientos_medicamento`
--
ALTER TABLE `movimientos_medicamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicamento_id` (`medicamento_id`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`);

--
-- Indexes for table `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `promociones_examen`
--
ALTER TABLE `promociones_examen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `examen_id` (`examen_id`);

--
-- Indexes for table `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`);

--
-- Indexes for table `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_laboratorista` (`id_laboratorista`),
  ADD KEY `resultados_examenes_ibfk_3` (`id_cotizacion`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `configuracion_clinica`
--
ALTER TABLE `configuracion_clinica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `config_empresa`
--
ALTER TABLE `config_empresa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examenes`
--
ALTER TABLE `examenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movimientos_medicamento`
--
ALTER TABLE `movimientos_medicamento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_examen`
--
ALTER TABLE `promociones_examen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  ADD CONSTRAINT `convenio_cliente_ibfk_1` FOREIGN KEY (`convenio_id`) REFERENCES `convenios` (`id`),
  ADD CONSTRAINT `convenio_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Constraints for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD CONSTRAINT `cotizaciones_detalle_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_detalle_ibfk_2` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  ADD CONSTRAINT `empresa_cliente_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `empresa_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Constraints for table `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  ADD CONSTRAINT `examenes_cliente_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `examenes_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Constraints for table `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  ADD CONSTRAINT `examenes_convenio_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `examenes_convenio_ibfk_2` FOREIGN KEY (`convenio_id`) REFERENCES `convenios` (`id`);

--
-- Constraints for table `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  ADD CONSTRAINT `examenes_empresa_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `examenes_empresa_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  ADD CONSTRAINT `examenes_promocion_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `examenes_promocion_ibfk_2` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Constraints for table `movimientos_medicamento`
--
ALTER TABLE `movimientos_medicamento`
  ADD CONSTRAINT `movimientos_medicamento_ibfk_1` FOREIGN KEY (`medicamento_id`) REFERENCES `medicamentos` (`id`);

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  ADD CONSTRAINT `promociones_empresa_ibfk_1` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_empresa_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `promociones_examen`
--
ALTER TABLE `promociones_examen`
  ADD CONSTRAINT `promociones_examen_ibfk_1` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_examen_ibfk_2` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `resultados`
--
ALTER TABLE `resultados`
  ADD CONSTRAINT `resultados_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`),
  ADD CONSTRAINT `resultados_ibfk_2` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  ADD CONSTRAINT `resultados_examenes_ibfk_1` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `resultados_examenes_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `resultados_examenes_ibfk_3` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultados_examenes_ibfk_4` FOREIGN KEY (`id_laboratorista`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
