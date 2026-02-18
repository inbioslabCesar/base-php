-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 15, 2026 at 09:12 PM
-- Server version: 8.0.42
-- PHP Version: 8.3.28

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
-- Table structure for table `cajas`
--

CREATE TABLE `cajas` (
  `id` int NOT NULL,
  `fecha_operacion` date NOT NULL,
  `numero_turno` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `estado` enum('abierta','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `usuario_apertura_id` int NOT NULL,
  `fecha_hora_apertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT '0.00',
  `observacion_apertura` text COLLATE utf8mb4_unicode_ci,
  `usuario_cierre_id` int DEFAULT NULL,
  `fecha_hora_cierre` datetime DEFAULT NULL,
  `monto_contado_efectivo` decimal(10,2) DEFAULT NULL,
  `ingresos_efectivo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `egresos_efectivo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `caja_teorica_efectivo` decimal(10,2) DEFAULT NULL,
  `diferencia_efectivo` decimal(10,2) DEFAULT NULL,
  `observacion_cierre` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caja_movimientos`
--

CREATE TABLE `caja_movimientos` (
  `id` int NOT NULL,
  `caja_id` int NOT NULL,
  `tipo` enum('ingreso','egreso','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `origen` enum('pago','egreso_manual','apertura','cierre','ajuste_manual','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `metodo_pago` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `monto` decimal(10,2) NOT NULL,
  `afecta_efectivo` tinyint(1) NOT NULL DEFAULT '1',
  `referencia_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` int DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `tipo_documento` enum('dni','carnet','sin_dni') COLLATE utf8mb4_unicode_ci DEFAULT 'dni',
  `sexo` enum('masculino','femenino','macho','hembra','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `dominio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `tamano_letra` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1rem',
  `maps_embed` text COLLATE utf8mb4_unicode_ci
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
  `modificada` tinyint(1) NOT NULL DEFAULT '0',
  `total` decimal(10,2) NOT NULL,
  `total_bruto` decimal(10,2) DEFAULT '0.00',
  `estado_pago` enum('pendiente','abonado','pagado','anulada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `pdf_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_por` int NOT NULL,
  `rol_creador` enum('cliente','recepcionista','laboratorista','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_toma` date DEFAULT NULL,
  `hora_toma` time DEFAULT NULL,
  `tipo_toma` enum('laboratorio','domicilio') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_toma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT '0.00',
  `estado_muestra` enum('pendiente','realizada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `referencia_personalizada` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Referencia personalizada para mostrar en PDF en lugar de empresa/convenio/particular',
  `emitir_comprobante` tinyint(1) NOT NULL DEFAULT '1',
  `comprobante_tipo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'boleta|factura (si es NULL se infiere por id_empresa)',
  `receptor_tipo_documento` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo doc receptor SUNAT (6=RUC, 1=DNI, etc)',
  `receptor_numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de documento del receptor (RUC/DNI)',
  `receptor_razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Razón social del receptor (para factura)',
  `receptor_direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dirección del receptor (opcional)',
  `anulada_at` datetime DEFAULT NULL,
  `anulada_por` int DEFAULT NULL,
  `anulado_motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
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
-- Table structure for table `inventario_consumos_examen`
--

CREATE TABLE `inventario_consumos_examen` (
  `id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_examen` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad_consumida` decimal(12,4) NOT NULL,
  `origen_evento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'resultado',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aplicado',
  `usuario_id` int DEFAULT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_examen_recetas`
--

CREATE TABLE `inventario_examen_recetas` (
  `id` int NOT NULL,
  `id_examen` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad_por_prueba` decimal(12,4) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_items`
--

CREATE TABLE `inventario_items` (
  `id` int NOT NULL,
  `codigo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentacion` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `factor_presentacion` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unidad_medida` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_critico` decimal(12,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_lotes`
--

CREATE TABLE `inventario_lotes` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `lote_codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cantidad_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `lote_id` int DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_transferencias`
--

CREATE TABLE `inventario_transferencias` (
  `id` int NOT NULL,
  `origen` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'almacen_principal',
  `destino` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laboratorio',
  `usuario_id` int DEFAULT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_transferencias_detalle`
--

CREATE TABLE `inventario_transferencias_detalle` (
  `id` int NOT NULL,
  `transferencia_id` int NOT NULL,
  `item_id` int NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `observaciones` text COLLATE utf8mb4_unicode_ci COMMENT 'Observaciones adicionales, especialmente para cambios de monto total'
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
-- Table structure for table `resultados_examenes`
--

CREATE TABLE `resultados_examenes` (
  `id` int NOT NULL,
  `id_examen` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `adicional_snapshot` longtext COLLATE utf8mb4_unicode_ci,
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
-- Indexes for table `cajas`
--
ALTER TABLE `cajas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cajas_fecha_turno` (`fecha_operacion`,`numero_turno`),
  ADD KEY `idx_cajas_estado` (`estado`),
  ADD KEY `idx_cajas_fecha_operacion` (`fecha_operacion`),
  ADD KEY `idx_cajas_apertura` (`fecha_hora_apertura`),
  ADD KEY `idx_cajas_usuario_apertura` (`usuario_apertura_id`),
  ADD KEY `idx_cajas_usuario_cierre` (`usuario_cierre_id`);

--
-- Indexes for table `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cmov_caja` (`caja_id`),
  ADD KEY `idx_cmov_fecha` (`fecha_hora`),
  ADD KEY `idx_cmov_metodo` (`metodo_pago`),
  ADD KEY `idx_cmov_ref` (`referencia_tipo`,`referencia_id`),
  ADD KEY `idx_mov_ref_tipo_origen` (`referencia_id`,`tipo`,`origen`,`referencia_tipo`),
  ADD KEY `idx_mov_fecha_hora` (`fecha_hora`),
  ADD KEY `idx_mov_caja_tipo_efectivo` (`caja_id`,`tipo`,`afecta_efectivo`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `codigo_cliente` (`codigo_cliente`);

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
  ADD KEY `cotizaciones_ibfk_1` (`id_cliente`),
  ADD KEY `idx_cotizaciones_comprobante_tipo` (`comprobante_tipo`),
  ADD KEY `idx_cotizaciones_receptor_numero_documento` (`receptor_numero_documento`),
  ADD KEY `idx_cotizaciones_anulada_at` (`anulada_at`),
  ADD KEY `idx_cotizaciones_anulada_por` (`anulada_por`);

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
-- Indexes for table `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_consumo_evento` (`id_cotizacion`,`id_examen`,`item_id`,`origen_evento`),
  ADD KEY `idx_inventario_consumo_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_inventario_consumo_examen` (`id_examen`),
  ADD KEY `idx_inventario_consumo_item` (`item_id`),
  ADD KEY `idx_inventario_consumo_fecha` (`fecha_hora`);

--
-- Indexes for table `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inventario_receta_examen_item` (`id_examen`,`item_id`),
  ADD KEY `idx_inventario_receta_examen` (`id_examen`),
  ADD KEY `idx_inventario_receta_item` (`item_id`),
  ADD KEY `idx_inventario_receta_activo` (`activo`);

--
-- Indexes for table `inventario_items`
--
ALTER TABLE `inventario_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inventario_items_codigo` (`codigo`),
  ADD KEY `idx_inventario_items_nombre` (`nombre`),
  ADD KEY `idx_inventario_items_categoria` (`categoria`),
  ADD KEY `idx_inventario_items_activo` (`activo`);

--
-- Indexes for table `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_lotes_item` (`item_id`),
  ADD KEY `idx_inventario_lotes_venc` (`fecha_vencimiento`),
  ADD KEY `idx_inventario_lotes_stock` (`cantidad_actual`);

--
-- Indexes for table `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_mov_item` (`item_id`),
  ADD KEY `idx_inventario_mov_lote` (`lote_id`),
  ADD KEY `idx_inventario_mov_tipo` (`tipo`),
  ADD KEY `idx_inventario_mov_fecha` (`fecha_hora`);

--
-- Indexes for table `inventario_transferencias`
--
ALTER TABLE `inventario_transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transferencias_fecha` (`fecha_hora`),
  ADD KEY `idx_inventario_transferencias_destino` (`destino`);

--
-- Indexes for table `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transfer_det_transf` (`transferencia_id`),
  ADD KEY `idx_inventario_transfer_det_item` (`item_id`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_pagos_fecha` (`fecha`),
  ADD KEY `idx_pagos_fecha_metodo` (`fecha`,`metodo_pago`);

--
-- Indexes for table `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
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
-- AUTO_INCREMENT for table `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_items`
--
ALTER TABLE `inventario_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_transferencias`
--
ALTER TABLE `inventario_transferencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
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
-- Constraints for table `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD CONSTRAINT `fk_caja_movimientos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  ADD CONSTRAINT `fk_inventario_consumo_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
  ADD CONSTRAINT `fk_inventario_receta_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD CONSTRAINT `fk_inventario_lotes_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `fk_inventario_mov_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventario_mov_lote` FOREIGN KEY (`lote_id`) REFERENCES `inventario_lotes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  ADD CONSTRAINT `fk_inventario_transfer_det_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventario_transfer_det_transferencia` FOREIGN KEY (`transferencia_id`) REFERENCES `inventario_transferencias` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE;

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
