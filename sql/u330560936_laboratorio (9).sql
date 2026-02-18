-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 15-02-2026 a las 21:33:01
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u330560936_laboratorio`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

CREATE TABLE `cajas` (
  `id` int(11) NOT NULL,
  `fecha_operacion` date NOT NULL,
  `numero_turno` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `usuario_apertura_id` int(11) NOT NULL,
  `fecha_hora_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacion_apertura` text DEFAULT NULL,
  `usuario_cierre_id` int(11) DEFAULT NULL,
  `fecha_hora_cierre` datetime DEFAULT NULL,
  `monto_contado_efectivo` decimal(10,2) DEFAULT NULL,
  `ingresos_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `egresos_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `caja_teorica_efectivo` decimal(10,2) DEFAULT NULL,
  `diferencia_efectivo` decimal(10,2) DEFAULT NULL,
  `observacion_cierre` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_movimientos`
--

CREATE TABLE `caja_movimientos` (
  `id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso','ajuste') NOT NULL,
  `origen` enum('pago','egreso_manual','apertura','cierre','ajuste_manual','otro') NOT NULL DEFAULT 'otro',
  `metodo_pago` varchar(50) NOT NULL DEFAULT 'efectivo',
  `monto` decimal(10,2) NOT NULL,
  `afecta_efectivo` tinyint(1) NOT NULL DEFAULT 1,
  `referencia_tipo` varchar(50) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `codigo_cliente` varchar(20) DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `edad` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `tipo_documento` enum('dni','carnet','sin_dni') DEFAULT 'dni',
  `sexo` enum('masculino','femenino','macho','hembra','otro') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `procedencia` varchar(100) DEFAULT NULL,
  `promociones` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `rol_creador` varchar(50) DEFAULT NULL,
  `empresa_nombre` varchar(100) DEFAULT NULL,
  `convenio_nombre` varchar(100) DEFAULT NULL,
  `tipo_registro` varchar(20) DEFAULT 'cliente',
  `descuento` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_empresa`
--

CREATE TABLE `config_empresa` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `celular` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ruc` varchar(50) DEFAULT NULL,
  `dominio` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `color_principal` varchar(20) DEFAULT '#0d6efd',
  `color_secundario` varchar(20) DEFAULT '#f8f9fa',
  `color_footer` varchar(20) DEFAULT '#343a40',
  `frase_promocion` varchar(255) DEFAULT NULL,
  `oferta_mes` varchar(255) DEFAULT NULL,
  `imagenes_carrusel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`imagenes_carrusel`)),
  `servicios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`servicios`)),
  `testimonios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`testimonios`)),
  `redes_sociales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`redes_sociales`)),
  `menu_inicio` varchar(50) DEFAULT 'Inicio',
  `menu_servicios` varchar(50) DEFAULT 'Servicios',
  `menu_testimonios` varchar(50) DEFAULT 'Testimonios',
  `menu_contacto` varchar(50) DEFAULT 'Contacto',
  `imagenes_institucionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`imagenes_institucionales`)),
  `color_botones` varchar(20) DEFAULT '#198754',
  `color_texto` varchar(20) DEFAULT '#212529',
  `tamano_letra` varchar(20) DEFAULT '1rem',
  `maps_embed` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenios`
--

CREATE TABLE `convenios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT 0.00,
  `descripcion` text DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenio_cliente`
--

CREATE TABLE `convenio_cliente` (
  `id` int(11) NOT NULL,
  `convenio_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `id_convenio` int(11) DEFAULT NULL,
  `tipo_usuario` enum('cliente','empresa','convenio','recepcionista') DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `modificada` tinyint(1) NOT NULL DEFAULT 0,
  `total` decimal(10,2) NOT NULL,
  `total_bruto` decimal(10,2) DEFAULT 0.00,
  `estado_pago` enum('pendiente','abonado','pagado') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `rol_creador` enum('cliente','recepcionista','laboratorista','admin') NOT NULL,
  `fecha_toma` date DEFAULT NULL,
  `hora_toma` time DEFAULT NULL,
  `tipo_toma` enum('laboratorio','domicilio') DEFAULT NULL,
  `direccion_toma` varchar(255) DEFAULT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT 0.00,
  `estado_muestra` enum('pendiente','realizada') DEFAULT 'pendiente',
  `referencia_personalizada` varchar(100) DEFAULT NULL COMMENT 'Referencia personalizada para mostrar en PDF en lugar de empresa/convenio/particular',
  `comprobante_tipo` varchar(10) DEFAULT NULL COMMENT 'boleta|factura (si es NULL se infiere por id_empresa)',
  `receptor_tipo_documento` varchar(2) DEFAULT NULL COMMENT 'Tipo doc receptor SUNAT (6=RUC, 1=DNI, etc)',
  `receptor_numero_documento` varchar(20) DEFAULT NULL COMMENT 'Número de documento del receptor (RUC/DNI)',
  `receptor_razon_social` varchar(255) DEFAULT NULL COMMENT 'Razón social del receptor (para factura)',
  `receptor_direccion` varchar(255) DEFAULT NULL COMMENT 'Dirección del receptor (opcional)',
  `emitir_comprobante` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_detalle`
--

CREATE TABLE `cotizaciones_detalle` (
  `id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `nombre_examen` varchar(100) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `ruc` varchar(20) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `nombre_comercial` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `representante` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `convenio` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `descuento` decimal(5,2) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_cliente`
--

CREATE TABLE `empresa_cliente` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE `examenes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `metodologia` varchar(100) DEFAULT NULL,
  `tiempo_respuesta` varchar(100) DEFAULT NULL,
  `preanalitica_cliente` text DEFAULT NULL,
  `preanalitica_referencias` text DEFAULT NULL,
  `tipo_muestra` varchar(100) DEFAULT NULL,
  `tipo_tubo` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `precio_publico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adicional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`adicional`)),
  `vigente` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_items`
--

CREATE TABLE `inventario_items` (
  `id` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(30) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `presentacion` varchar(120) DEFAULT NULL,
  `factor_presentacion` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `unidad_medida` varchar(30) NOT NULL,
  `stock_minimo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_critico` decimal(12,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_lotes`
--

CREATE TABLE `inventario_lotes` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `lote_codigo` varchar(80) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cantidad_actual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `lote_id` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `metodo_pago` varchar(30) DEFAULT 'efectivo',
  `observaciones` text DEFAULT NULL COMMENT 'Observaciones adicionales, especialmente para cambios de monto total'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `precio_promocional` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `vigente` tinyint(1) DEFAULT 1,
  `tipo_publico` varchar(20) NOT NULL DEFAULT 'todos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_examenes`
--

CREATE TABLE `resultados_examenes` (
  `id` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`resultados`)),
  `adicional_snapshot` longtext DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `id_laboratorista` int(11) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `sexo` enum('masculino','femenino','otro') NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `profesion` varchar(50) DEFAULT NULL,
  `rol` enum('admin','recepcionista','laboratorista') DEFAULT 'recepcionista',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cajas`
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
-- Indices de la tabla `caja_movimientos`
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
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `codigo_cliente` (`codigo_cliente`);

--
-- Indices de la tabla `config_empresa`
--
ALTER TABLE `config_empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `convenio_id` (`convenio_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_convenio` (`id_convenio`),
  ADD KEY `cotizaciones_ibfk_1` (`id_cliente`),
  ADD KEY `idx_cotizaciones_comprobante_tipo` (`comprobante_tipo`),
  ADD KEY `idx_cotizaciones_receptor_numero_documento` (`receptor_numero_documento`);

--
-- Indices de la tabla `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruc` (`ruc`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empresa_id` (`empresa_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `inventario_items`
--
ALTER TABLE `inventario_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inventario_items_codigo` (`codigo`),
  ADD KEY `idx_inventario_items_nombre` (`nombre`),
  ADD KEY `idx_inventario_items_categoria` (`categoria`),
  ADD KEY `idx_inventario_items_activo` (`activo`);

--
-- Indices de la tabla `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_lotes_item` (`item_id`),
  ADD KEY `idx_inventario_lotes_venc` (`fecha_vencimiento`),
  ADD KEY `idx_inventario_lotes_stock` (`cantidad_actual`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_mov_item` (`item_id`),
  ADD KEY `idx_inventario_mov_lote` (`lote_id`),
  ADD KEY `idx_inventario_mov_tipo` (`tipo`),
  ADD KEY `idx_inventario_mov_fecha` (`fecha_hora`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_pagos_fecha` (`fecha`),
  ADD KEY `idx_pagos_fecha_metodo` (`fecha`,`metodo_pago`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_laboratorista` (`id_laboratorista`),
  ADD KEY `resultados_examenes_ibfk_3` (`id_cotizacion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `config_empresa`
--
ALTER TABLE `config_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_items`
--
ALTER TABLE `inventario_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD CONSTRAINT `fk_caja_movimientos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `convenio_cliente`
--
ALTER TABLE `convenio_cliente`
  ADD CONSTRAINT `convenio_cliente_ibfk_1` FOREIGN KEY (`convenio_id`) REFERENCES `convenios` (`id`),
  ADD CONSTRAINT `convenio_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD CONSTRAINT `cotizaciones_detalle_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizaciones_detalle_ibfk_2` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`);

--
-- Filtros para la tabla `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  ADD CONSTRAINT `empresa_cliente_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `empresa_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD CONSTRAINT `fk_inventario_lotes_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `fk_inventario_mov_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventario_mov_lote` FOREIGN KEY (`lote_id`) REFERENCES `inventario_lotes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resultados_examenes`
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
