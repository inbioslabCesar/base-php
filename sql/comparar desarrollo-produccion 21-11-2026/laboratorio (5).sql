-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 22, 2026 at 12:51 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET FOREIGN_KEY_CHECKS=0;
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
-- Table structure for table `backup_snapshot_resultados_examenes`
--

CREATE TABLE `backup_snapshot_resultados_examenes` (
  `id` int NOT NULL,
  `resultado_id` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_examen` int NOT NULL,
  `adicional_snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `resultados` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `caja_reaperturas`
--

CREATE TABLE `caja_reaperturas` (
  `id` int NOT NULL,
  `fecha_operacion` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `caja_origen_id` int DEFAULT NULL,
  `turno_responsable` int DEFAULT NULL,
  `motivo_solicitud` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `solicitado_por_id` int NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aprobado_por_id` int DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `caja_reabierta_id` int DEFAULT NULL,
  `observacion_aprobacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
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
  `razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad_referida_valor` decimal(10,6) DEFAULT NULL,
  `edad_referida_fecha` datetime DEFAULT NULL,
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
  `descuento` decimal(5,2) DEFAULT NULL,
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT '0'
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
  `modo_operativo` enum('PARTICULAR','SIS','MIXTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PARTICULAR',
  `portal_publico_enable` tinyint(1) NOT NULL DEFAULT '1',
  `promo_web_activa` tinyint(1) NOT NULL DEFAULT '0',
  `promo_web_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `promo_web_aplicar_carrito` tinyint(1) NOT NULL DEFAULT '0',
  `promo_web_mensaje` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_web_fecha_inicio` date DEFAULT NULL,
  `promo_web_fecha_fin` date DEFAULT NULL,
  `share_preview_titulo` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `share_preview_descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `share_preview_imagen` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portal_publico_estilo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'clasico',
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
  `logo_fondo_navbar` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `tamano_letra` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1rem',
  `maps_embed` text COLLATE utf8mb4_unicode_ci,
  `ubicaciones_json` text COLLATE utf8mb4_unicode_ci,
  `mostrar_fecha_ingreso_pdf` tinyint(1) NOT NULL DEFAULT '1',
  `mostrar_fecha_validacion_pdf` tinyint(1) NOT NULL DEFAULT '1'
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
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT '0',
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
  `servicio_id` int DEFAULT NULL,
  `tipo_usuario` enum('cliente','empresa','convenio','recepcionista') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_proceso_inicio` datetime DEFAULT NULL,
  `fecha_validacion_final` datetime DEFAULT NULL,
  `modificada` tinyint(1) NOT NULL DEFAULT '0',
  `total` decimal(10,2) NOT NULL,
  `total_bruto` decimal(10,2) DEFAULT '0.00',
  `estado_pago` enum('pendiente','abonado','pagado','anulada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `pdf_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_por` int NOT NULL,
  `rol_creador` enum('cliente','recepcionista','laboratorista','admin','empresa','convenio','servicio','engineer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_toma` date DEFAULT NULL,
  `hora_toma` time DEFAULT NULL,
  `tipo_toma` enum('laboratorio','domicilio') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_toma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT '0.00',
  `es_sis` tinyint(1) NOT NULL DEFAULT '0',
  `sis_cobertura_id` int DEFAULT NULL,
  `sis_monto_atencion` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sis_registrado_en` datetime DEFAULT NULL,
  `sis_registrado_por` int DEFAULT NULL,
  `estado_muestra` enum('pendiente','realizada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `referencia_personalizada` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Referencia personalizada para mostrar en PDF en lugar de empresa/convenio/particular',
  `comprobante_tipo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'boleta|factura (si es NULL se infiere por id_empresa)',
  `receptor_tipo_documento` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo doc receptor SUNAT (6=RUC, 1=DNI, etc)',
  `receptor_numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de documento del receptor (RUC/DNI)',
  `receptor_razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Razón social del receptor (para factura)',
  `receptor_direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dirección del receptor (opcional)',
  `emitir_comprobante` tinyint(1) NOT NULL DEFAULT '1',
  `anulada_at` datetime DEFAULT NULL,
  `anulada_por` int DEFAULT NULL,
  `anulado_motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profesional_solicitante_id` int DEFAULT NULL,
  `profesional_solicitante_nombre` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profesional_solicitante_tipo` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profesional_solicitante_registro` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_prestacional_id` int DEFAULT NULL,
  `codigo_prestacional` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad_paciente_meses` int DEFAULT NULL,
  `es_gestante` tinyint(1) NOT NULL DEFAULT '0',
  `edad_gestacional_semanas` tinyint UNSIGNED DEFAULT NULL,
  `edad_gestacional_dias` tinyint UNSIGNED DEFAULT NULL,
  `fpp` date DEFAULT NULL
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
  `subtotal` decimal(10,2) NOT NULL,
  `es_referenciado` tinyint(1) NOT NULL DEFAULT '0',
  `laboratorio_referenciado_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo_laboratorio_referenciado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_logistica_extra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado_liquidacion` enum('pendiente','liquidado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_liquidacion` datetime DEFAULT NULL,
  `liquidado_por` int DEFAULT NULL,
  `egreso_laboratorio_id` int DEFAULT NULL,
  `egreso_logistica_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizaciones_express`
--

CREATE TABLE `cotizaciones_express` (
  `id` int NOT NULL,
  `folio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitida',
  `modo_operativo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PARTICULAR',
  `tarifa_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'particular',
  `tarifa_id` int DEFAULT NULL,
  `tarifa_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destinatario_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destinatario_telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_id` int DEFAULT NULL,
  `descuento_extra` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vigencia_dias` smallint NOT NULL DEFAULT '7',
  `vence_en` date NOT NULL,
  `incluir_indicaciones` tinyint(1) NOT NULL DEFAULT '1',
  `canales` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_envio_en` datetime DEFAULT NULL,
  `creado_por` int DEFAULT NULL,
  `rol_creador` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `convertida_cotizacion_id` int DEFAULT NULL,
  `convertida_en` datetime DEFAULT NULL,
  `anulada_en` datetime DEFAULT NULL,
  `anulada_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizaciones_express_items`
--

CREATE TABLE `cotizaciones_express_items` (
  `id` int NOT NULL,
  `express_id` int NOT NULL,
  `examen_id` int NOT NULL,
  `nombre_examen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` smallint NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cotizador_express_plantillas`
--

CREATE TABLE `cotizador_express_plantillas` (
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plantilla` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `actualizado_por` int DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `egresos`
--

CREATE TABLE `egresos` (
  `id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subcategoria` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_cotizacion` int DEFAULT NULL,
  `id_cotizacion_detalle` int DEFAULT NULL,
  `origen_auto` tinyint(1) NOT NULL DEFAULT '0',
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
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT '0',
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
  `precio_convenio` decimal(10,2) DEFAULT NULL,
  `adicional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `vigente` tinyint(1) DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_adicional_backup`
--

CREATE TABLE `examenes_adicional_backup` (
  `id` bigint UNSIGNED NOT NULL,
  `id_examen` int NOT NULL,
  `adicional_anterior` longtext COLLATE utf8mb4_unicode_ci,
  `adicional_nuevo` longtext COLLATE utf8mb4_unicode_ci,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examenes_promocion`
--

CREATE TABLE `examenes_promocion` (
  `id` int NOT NULL,
  `examen_id` int NOT NULL,
  `promocion_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` datetime DEFAULT NULL,
  `controla_stock` tinyint(1) NOT NULL DEFAULT '1'
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
  `origen` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inventario',
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
-- Table structure for table `laboratorios_referenciados`
--

CREATE TABLE `laboratorios_referenciados` (
  `id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laboratorio_turnos`
--

CREATE TABLE `laboratorio_turnos` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `estado` enum('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cerrado_en` datetime DEFAULT NULL,
  `observacion_apertura` varchar(255) DEFAULT NULL,
  `observacion_cierre` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `metodo_pago` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `observaciones` text COLLATE utf8mb4_unicode_ci COMMENT 'Observaciones adicionales, especialmente para cambios de monto total'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profesionales_solicitantes`
--

CREATE TABLE `profesionales_solicitantes` (
  `id` int NOT NULL,
  `nombres` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_profesional` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_documento` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_profesional` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_examen`
--

CREATE TABLE `promociones_examen` (
  `id` int NOT NULL,
  `promocion_id` int NOT NULL,
  `examen_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `observaciones` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resultados_examenes`
--

CREATE TABLE `resultados_examenes` (
  `id` int NOT NULL,
  `id_examen` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `edad_paciente_valor` decimal(10,6) DEFAULT NULL,
  `edad_paciente_texto` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_ref_edad` datetime DEFAULT NULL,
  `orden_impresion` int DEFAULT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `adicional_snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_proceso_en` datetime DEFAULT NULL,
  `fecha_validacion_en` datetime DEFAULT NULL,
  `validado_por` int DEFAULT NULL,
  `estado_validacion` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `id_laboratorista` int DEFAULT NULL,
  `id_turno` int DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `alarma_activa` tinyint(1) NOT NULL DEFAULT '0',
  `alarma_dias` int DEFAULT NULL,
  `alarma_fecha_objetivo` datetime DEFAULT NULL,
  `alarma_estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alarma_ultimo_aviso` datetime DEFAULT NULL,
  `alarma_whatsapp_destino` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `resultados_examenes_backup_fix_hemograma_20260217`
--

CREATE TABLE `resultados_examenes_backup_fix_hemograma_20260217` (
  `id` int NOT NULL DEFAULT '0',
  `id_examen` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adicional_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_laboratorista` int DEFAULT NULL,
  `estado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resultados_sync_operaciones`
--

CREATE TABLE `resultados_sync_operaciones` (
  `id` bigint UNSIGNED NOT NULL,
  `operation_id` varchar(80) NOT NULL,
  `cotizacion_id` int DEFAULT NULL,
  `estado` enum('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
  `payload_json` mediumtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicios`
--

CREATE TABLE `servicios` (
  `id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsable` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicio_cliente`
--

CREATE TABLE `servicio_cliente` (
  `id` int NOT NULL,
  `servicio_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicio_descargas_auditoria`
--

CREATE TABLE `servicio_descargas_auditoria` (
  `id` int NOT NULL,
  `servicio_id` int NOT NULL,
  `cotizacion_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `usuario_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicio_profesional`
--

CREATE TABLE `servicio_profesional` (
  `id` int NOT NULL,
  `servicio_id` int NOT NULL,
  `profesional_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sis_coberturas`
--

CREATE TABLE `sis_coberturas` (
  `id` int NOT NULL,
  `cotizacion_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `numero_afiliacion` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_autorizacion` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_fua` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aseguradora` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_validacion` enum('pendiente','autorizada','observada','rechazada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `monto_atencion` decimal(10,2) NOT NULL DEFAULT '0.00',
  `observaciones` text COLLATE utf8mb4_general_ci,
  `creado_por` int DEFAULT NULL,
  `creada_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizada_en` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sis_codigos_prestacionales`
--

CREATE TABLE `sis_codigos_prestacionales` (
  `id` int NOT NULL,
  `codigo` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_minsa` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_regla` enum('EDAD','GESTANTE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EDAD',
  `edad_min_meses` int DEFAULT NULL,
  `edad_max_meses` int DEFAULT NULL,
  `icono_clase` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icono_ruta` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orden` smallint NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `colegiatura_numero` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ctmp_numero` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('admin','recepcionista','laboratorista') COLLATE utf8mb4_unicode_ci DEFAULT 'recepcionista',
  `privilegios_json` text COLLATE utf8mb4_unicode_ci,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_snapshot_resultados_examenes`
--
ALTER TABLE `backup_snapshot_resultados_examenes`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `caja_reaperturas`
--
ALTER TABLE `caja_reaperturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_caja_reaperturas_fecha_estado` (`fecha_operacion`,`estado`),
  ADD KEY `idx_caja_reaperturas_caja_origen` (`caja_origen_id`),
  ADD KEY `idx_caja_reaperturas_turno_responsable` (`turno_responsable`),
  ADD KEY `idx_caja_reaperturas_solicitante` (`solicitado_por_id`),
  ADD KEY `idx_caja_reaperturas_aprobador` (`aprobado_por_id`),
  ADD KEY `idx_caja_reaperturas_caja` (`caja_reabierta_id`);

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
  ADD UNIQUE KEY `uq_convenio_cliente` (`convenio_id`,`cliente_id`),
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
  ADD KEY `idx_cotizaciones_anulada_por` (`anulada_por`),
  ADD KEY `idx_cotizaciones_es_sis` (`es_sis`),
  ADD KEY `idx_cotizaciones_profesional_solicitante` (`profesional_solicitante_id`),
  ADD KEY `idx_cotizaciones_codprest_fecha` (`codigo_prestacional`,`fecha`);

--
-- Indexes for table `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `idx_cd_referenciado_liquidacion` (`es_referenciado`,`estado_liquidacion`);

--
-- Indexes for table `cotizaciones_express`
--
ALTER TABLE `cotizaciones_express`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cotexp_token` (`token`),
  ADD UNIQUE KEY `uq_cotexp_folio` (`folio`),
  ADD KEY `idx_cotexp_estado_fecha` (`estado`,`creado_en`),
  ADD KEY `idx_cotexp_vence` (`vence_en`),
  ADD KEY `idx_cotexp_cliente` (`cliente_id`);

--
-- Indexes for table `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cotexp_items_express` (`express_id`);

--
-- Indexes for table `cotizador_express_plantillas`
--
ALTER TABLE `cotizador_express_plantillas`
  ADD PRIMARY KEY (`tipo`);

--
-- Indexes for table `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_egresos_categoria_fecha` (`categoria`,`fecha`),
  ADD KEY `idx_egresos_cotizacion_detalle` (`id_cotizacion`,`id_cotizacion_detalle`);

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
  ADD UNIQUE KEY `uq_empresa_cliente` (`empresa_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indexes for table `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `examenes_adicional_backup`
--
ALTER TABLE `examenes_adicional_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_examenes_cliente_examen` (`examen_id`),
  ADD KEY `idx_examenes_cliente_cliente` (`cliente_id`);

--
-- Indexes for table `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_examenes_convenio_examen` (`examen_id`),
  ADD KEY `idx_examenes_convenio_convenio` (`convenio_id`);

--
-- Indexes for table `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_examenes_empresa_examen` (`examen_id`),
  ADD KEY `idx_examenes_empresa_empresa` (`empresa_id`);

--
-- Indexes for table `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_examenes_promocion_examen` (`examen_id`),
  ADD KEY `idx_examenes_promocion_promocion` (`promocion_id`);

--
-- Indexes for table `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_consumo_evento` (`id_cotizacion`,`id_examen`,`item_id`,`origen_evento`),
  ADD KEY `idx_inventario_consumo_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_inventario_consumo_examen` (`id_examen`),
  ADD KEY `idx_inventario_consumo_item` (`item_id`),
  ADD KEY `idx_inventario_consumo_fecha` (`fecha_hora`),
  ADD KEY `idx_consumo_estado_item` (`estado`,`item_id`),
  ADD KEY `idx_consumo_repeticion` (`estado`,`origen_evento`,`fecha_hora`,`id`);

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
  ADD KEY `idx_inventario_items_activo` (`activo`),
  ADD KEY `idx_inventario_items_created_at` (`created_at`),
  ADD KEY `idx_inventario_items_controla_stock` (`controla_stock`);

--
-- Indexes for table `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_lotes_item` (`item_id`),
  ADD KEY `idx_inventario_lotes_venc` (`fecha_vencimiento`),
  ADD KEY `idx_inventario_lotes_stock` (`cantidad_actual`),
  ADD KEY `idx_inventario_lotes_item_stock_venc` (`item_id`,`cantidad_actual`,`fecha_vencimiento`,`id`),
  ADD KEY `idx_inventario_lotes_venc_stock_item` (`fecha_vencimiento`,`cantidad_actual`,`item_id`);

--
-- Indexes for table `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_mov_item` (`item_id`),
  ADD KEY `idx_inventario_mov_lote` (`lote_id`),
  ADD KEY `idx_inventario_mov_tipo` (`tipo`),
  ADD KEY `idx_inventario_mov_fecha` (`fecha_hora`),
  ADD KEY `idx_inventario_mov_fecha_id` (`fecha_hora`,`id`),
  ADD KEY `idx_inventario_mov_origen` (`origen`);

--
-- Indexes for table `inventario_transferencias`
--
ALTER TABLE `inventario_transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transferencias_fecha` (`fecha_hora`),
  ADD KEY `idx_inventario_transferencias_destino` (`destino`),
  ADD KEY `idx_transfer_destino_id` (`destino`,`id`),
  ADD KEY `idx_transfer_fecha_id` (`fecha_hora`,`id`);

--
-- Indexes for table `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transfer_det_transf` (`transferencia_id`),
  ADD KEY `idx_inventario_transfer_det_item` (`item_id`),
  ADD KEY `idx_transfer_det_item_transfer` (`item_id`,`transferencia_id`);

--
-- Indexes for table `laboratorios_referenciados`
--
ALTER TABLE `laboratorios_referenciados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_laboratorios_referenciados_nombre` (`nombre`);

--
-- Indexes for table `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turnos_usuario_estado` (`usuario_id`,`estado`),
  ADD KEY `idx_turnos_abierto_en` (`abierto_en`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_pagos_fecha` (`fecha`),
  ADD KEY `idx_pagos_fecha_metodo` (`fecha`,`metodo_pago`);

--
-- Indexes for table `profesionales_solicitantes`
--
ALTER TABLE `profesionales_solicitantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ps_estado` (`estado`),
  ADD KEY `idx_ps_nombre` (`nombres`,`apellidos`);

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
  ADD KEY `idx_promociones_empresa_promocion` (`promocion_id`),
  ADD KEY `idx_promociones_empresa_empresa` (`empresa_id`);

--
-- Indexes for table `promociones_examen`
--
ALTER TABLE `promociones_examen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promociones_examen_promocion` (`promocion_id`),
  ADD KEY `idx_promociones_examen_examen` (`examen_id`);

--
-- Indexes for table `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resultados_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_resultados_examen` (`id_examen`);

--
-- Indexes for table `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_laboratorista` (`id_laboratorista`),
  ADD KEY `resultados_examenes_ibfk_3` (`id_cotizacion`),
  ADD KEY `idx_resultados_alarmas_estado` (`estado`,`alarma_activa`,`alarma_fecha_objetivo`),
  ADD KEY `idx_resultados_alarmas_cot` (`id_cotizacion`,`alarma_activa`,`estado`),
  ADD KEY `idx_resultados_cot_orden` (`id_cotizacion`,`orden_impresion`,`id`);

--
-- Indexes for table `resultados_sync_operaciones`
--
ALTER TABLE `resultados_sync_operaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resultados_sync_operation_id` (`operation_id`);

--
-- Indexes for table `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicios_codigo` (`codigo`),
  ADD UNIQUE KEY `uq_servicios_email` (`email`);

--
-- Indexes for table `servicio_cliente`
--
ALTER TABLE `servicio_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicio_cliente` (`servicio_id`,`cliente_id`),
  ADD KEY `idx_sc_servicio` (`servicio_id`),
  ADD KEY `idx_sc_cliente` (`cliente_id`);

--
-- Indexes for table `servicio_descargas_auditoria`
--
ALTER TABLE `servicio_descargas_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sda_servicio` (`servicio_id`),
  ADD KEY `idx_sda_cotizacion` (`cotizacion_id`),
  ADD KEY `idx_sda_cliente` (`cliente_id`);

--
-- Indexes for table `servicio_profesional`
--
ALTER TABLE `servicio_profesional`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicio_profesional` (`servicio_id`,`profesional_id`),
  ADD KEY `idx_sp_servicio` (`servicio_id`),
  ADD KEY `idx_sp_profesional` (`profesional_id`);

--
-- Indexes for table `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sis_coberturas_cotizacion_id` (`cotizacion_id`),
  ADD KEY `idx_sis_coberturas_cliente_id` (`cliente_id`),
  ADD KEY `idx_sis_coberturas_numero_autorizacion` (`numero_autorizacion`);

--
-- Indexes for table `sis_codigos_prestacionales`
--
ALTER TABLE `sis_codigos_prestacionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sis_codigos_prestacionales_codigo` (`codigo`);

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
-- AUTO_INCREMENT for table `backup_snapshot_resultados_examenes`
--
ALTER TABLE `backup_snapshot_resultados_examenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `caja_reaperturas`
--
ALTER TABLE `caja_reaperturas`
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
-- AUTO_INCREMENT for table `cotizaciones_express`
--
ALTER TABLE `cotizaciones_express`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
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
-- AUTO_INCREMENT for table `examenes_adicional_backup`
--
ALTER TABLE `examenes_adicional_backup`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `laboratorios_referenciados`
--
ALTER TABLE `laboratorios_referenciados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profesionales_solicitantes`
--
ALTER TABLE `profesionales_solicitantes`
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
-- AUTO_INCREMENT for table `resultados_sync_operaciones`
--
ALTER TABLE `resultados_sync_operaciones`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicio_cliente`
--
ALTER TABLE `servicio_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicio_descargas_auditoria`
--
ALTER TABLE `servicio_descargas_auditoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicio_profesional`
--
ALTER TABLE `servicio_profesional`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sis_codigos_prestacionales`
--
ALTER TABLE `sis_codigos_prestacionales`
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
-- Constraints for table `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
  ADD CONSTRAINT `fk_cotexp_items_express` FOREIGN KEY (`express_id`) REFERENCES `cotizaciones_express` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_examenes_cliente_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_examenes_cliente_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  ADD CONSTRAINT `fk_examenes_convenio_convenio` FOREIGN KEY (`convenio_id`) REFERENCES `convenios` (`id`),
  ADD CONSTRAINT `fk_examenes_convenio_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  ADD CONSTRAINT `fk_examenes_empresa_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_examenes_empresa_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  ADD CONSTRAINT `fk_examenes_promocion_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `fk_examenes_promocion_promocion` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

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
-- Constraints for table `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  ADD CONSTRAINT `fk_turnos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  ADD CONSTRAINT `fk_promociones_empresa_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_promociones_empresa_promocion` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Constraints for table `promociones_examen`
--
ALTER TABLE `promociones_examen`
  ADD CONSTRAINT `fk_promociones_examen_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `fk_promociones_examen_promocion` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Constraints for table `resultados`
--
ALTER TABLE `resultados`
  ADD CONSTRAINT `fk_resultados_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resultados_examen` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`);

--
-- Constraints for table `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  ADD CONSTRAINT `resultados_examenes_ibfk_1` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id`),
  ADD CONSTRAINT `resultados_examenes_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `resultados_examenes_ibfk_3` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultados_examenes_ibfk_4` FOREIGN KEY (`id_laboratorista`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  ADD CONSTRAINT `fk_sis_coberturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sis_coberturas_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
