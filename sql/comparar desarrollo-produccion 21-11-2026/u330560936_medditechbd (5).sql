-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 22-11-2026 a las 00:42:03
-- Versión del servidor: 11.8.9-MariaDB-log
-- Versión de PHP: 7.2.34
use medditech;

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u330560936_medditechbd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_snapshot_resultados_examenes`
--

CREATE TABLE `backup_snapshot_resultados_examenes` (
  `id` int(11) NOT NULL,
  `resultado_id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `adicional_snapshot` longtext DEFAULT NULL,
  `resultados` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estructura de tabla para la tabla `caja_reaperturas`
--

CREATE TABLE `caja_reaperturas` (
  `id` int(11) NOT NULL,
  `fecha_operacion` date NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `caja_origen_id` int(11) DEFAULT NULL,
  `turno_responsable` int(11) DEFAULT NULL,
  `motivo_solicitud` varchar(255) NOT NULL,
  `solicitado_por_id` int(11) NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `aprobado_por_id` int(11) DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `caja_reabierta_id` int(11) DEFAULT NULL,
  `observacion_aprobacion` varchar(255) DEFAULT NULL
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
  `razon_social` varchar(255) DEFAULT NULL,
  `edad` varchar(20) DEFAULT NULL,
  `edad_referida_valor` decimal(10,6) DEFAULT NULL,
  `edad_referida_fecha` datetime DEFAULT NULL,
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
  `descuento` decimal(5,2) DEFAULT NULL,
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT 0
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
  `modo_operativo` enum('PARTICULAR','SIS','MIXTO') NOT NULL DEFAULT 'PARTICULAR',
  `portal_publico_enable` tinyint(1) NOT NULL DEFAULT 1,
  `promo_web_activa` tinyint(1) NOT NULL DEFAULT 0,
  `promo_web_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `promo_web_aplicar_carrito` tinyint(1) NOT NULL DEFAULT 0,
  `promo_web_mensaje` varchar(255) DEFAULT NULL,
  `promo_web_fecha_inicio` date DEFAULT NULL,
  `promo_web_fecha_fin` date DEFAULT NULL,
  `share_preview_titulo` varchar(160) DEFAULT NULL,
  `share_preview_descripcion` varchar(255) DEFAULT NULL,
  `share_preview_imagen` varchar(600) DEFAULT NULL,
  `portal_publico_estilo` varchar(20) DEFAULT 'clasico',
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
  `imagenes_carrusel` longtext DEFAULT NULL CHECK (json_valid(`imagenes_carrusel`)),
  `servicios` longtext DEFAULT NULL CHECK (json_valid(`servicios`)),
  `testimonios` longtext DEFAULT NULL CHECK (json_valid(`testimonios`)),
  `redes_sociales` longtext DEFAULT NULL CHECK (json_valid(`redes_sociales`)),
  `menu_inicio` varchar(50) DEFAULT 'Inicio',
  `menu_servicios` varchar(50) DEFAULT 'Servicios',
  `menu_testimonios` varchar(50) DEFAULT 'Testimonios',
  `menu_contacto` varchar(50) DEFAULT 'Contacto',
  `imagenes_institucionales` longtext DEFAULT NULL CHECK (json_valid(`imagenes_institucionales`)),
  `color_botones` varchar(20) DEFAULT '#198754',
  `color_texto` varchar(20) DEFAULT '#212529',
  `logo_fondo_navbar` varchar(20) DEFAULT '#ffffff',
  `tamano_letra` varchar(20) DEFAULT '1rem',
  `maps_embed` text DEFAULT NULL,
  `ubicaciones_json` text DEFAULT NULL,
  `mostrar_fecha_ingreso_pdf` tinyint(1) NOT NULL DEFAULT 1,
  `mostrar_fecha_validacion_pdf` tinyint(1) NOT NULL DEFAULT 1
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
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT 0,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `servicio_id` int(11) DEFAULT NULL,
  `tipo_usuario` enum('cliente','empresa','convenio','recepcionista') DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_proceso_inicio` datetime DEFAULT NULL,
  `fecha_validacion_final` datetime DEFAULT NULL,
  `modificada` tinyint(1) NOT NULL DEFAULT 0,
  `total` decimal(10,2) NOT NULL,
  `total_bruto` decimal(10,2) DEFAULT 0.00,
  `estado_pago` enum('pendiente','abonado','pagado','anulada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `rol_creador` enum('cliente','recepcionista','laboratorista','admin','empresa','convenio','servicio','engineer') NOT NULL DEFAULT 'cliente',
  `fecha_toma` date DEFAULT NULL,
  `hora_toma` time DEFAULT NULL,
  `tipo_toma` enum('laboratorio','domicilio') DEFAULT NULL,
  `direccion_toma` varchar(255) DEFAULT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT 0.00,
  `es_sis` tinyint(1) NOT NULL DEFAULT 0,
  `sis_cobertura_id` int(11) DEFAULT NULL,
  `sis_monto_atencion` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sis_registrado_en` datetime DEFAULT NULL,
  `sis_registrado_por` int(11) DEFAULT NULL,
  `estado_muestra` enum('pendiente','realizada') DEFAULT 'pendiente',
  `referencia_personalizada` varchar(100) DEFAULT NULL COMMENT 'Referencia personalizada para mostrar en PDF en lugar de empresa/convenio/particular',
  `comprobante_tipo` varchar(10) DEFAULT NULL,
  `receptor_tipo_documento` varchar(2) DEFAULT NULL,
  `receptor_numero_documento` varchar(20) DEFAULT NULL,
  `receptor_razon_social` varchar(255) DEFAULT NULL,
  `receptor_direccion` varchar(255) DEFAULT NULL,
  `emitir_comprobante` tinyint(1) NOT NULL DEFAULT 1,
  `anulada_at` datetime DEFAULT NULL,
  `anulada_por` int(11) DEFAULT NULL,
  `anulado_motivo` varchar(255) DEFAULT NULL,
  `profesional_solicitante_id` int(11) DEFAULT NULL,
  `profesional_solicitante_nombre` varchar(190) DEFAULT NULL,
  `profesional_solicitante_tipo` varchar(80) DEFAULT NULL,
  `profesional_solicitante_registro` varchar(80) DEFAULT NULL,
  `codigo_prestacional_id` int(11) DEFAULT NULL,
  `codigo_prestacional` char(3) DEFAULT NULL,
  `edad_paciente_meses` int(11) DEFAULT NULL,
  `es_gestante` tinyint(1) NOT NULL DEFAULT 0,
  `edad_gestacional_semanas` tinyint(3) UNSIGNED DEFAULT NULL,
  `edad_gestacional_dias` tinyint(3) UNSIGNED DEFAULT NULL,
  `fpp` date DEFAULT NULL
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
  `subtotal` decimal(10,2) NOT NULL,
  `es_referenciado` tinyint(1) NOT NULL DEFAULT 0,
  `laboratorio_referenciado_nombre` varchar(150) DEFAULT NULL,
  `costo_laboratorio_referenciado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_logistica_extra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado_liquidacion` enum('pendiente','liquidado') NOT NULL DEFAULT 'pendiente',
  `fecha_liquidacion` datetime DEFAULT NULL,
  `liquidado_por` int(11) DEFAULT NULL,
  `egreso_laboratorio_id` int(11) DEFAULT NULL,
  `egreso_logistica_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_express`
--

CREATE TABLE `cotizaciones_express` (
  `id` int(11) NOT NULL,
  `folio` varchar(20) DEFAULT NULL,
  `token` char(40) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'emitida',
  `modo_operativo` varchar(20) NOT NULL DEFAULT 'PARTICULAR',
  `tarifa_tipo` varchar(20) NOT NULL DEFAULT 'particular',
  `tarifa_id` int(11) DEFAULT NULL,
  `tarifa_nombre` varchar(150) DEFAULT NULL,
  `destinatario_nombre` varchar(150) DEFAULT NULL,
  `destinatario_telefono` varchar(20) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `descuento_extra` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vigencia_dias` smallint(6) NOT NULL DEFAULT 7,
  `vence_en` date NOT NULL,
  `incluir_indicaciones` tinyint(1) NOT NULL DEFAULT 1,
  `canales` varchar(100) DEFAULT NULL,
  `ultimo_envio_en` datetime DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `rol_creador` varchar(30) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `convertida_cotizacion_id` int(11) DEFAULT NULL,
  `convertida_en` datetime DEFAULT NULL,
  `anulada_en` datetime DEFAULT NULL,
  `anulada_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones_express_items`
--

CREATE TABLE `cotizaciones_express_items` (
  `id` int(11) NOT NULL,
  `express_id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `nombre_examen` varchar(255) NOT NULL,
  `cantidad` smallint(6) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizador_express_plantillas`
--

CREATE TABLE `cotizador_express_plantillas` (
  `tipo` varchar(20) NOT NULL,
  `plantilla` text NOT NULL,
  `actualizado_por` int(11) DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `categoria` varchar(60) DEFAULT NULL,
  `subcategoria` varchar(120) DEFAULT NULL,
  `id_cotizacion` int(11) DEFAULT NULL,
  `id_cotizacion_detalle` int(11) DEFAULT NULL,
  `origen_auto` tinyint(1) NOT NULL DEFAULT 0,
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
  `usar_precio_convenio` tinyint(1) NOT NULL DEFAULT 0,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `precio_convenio` decimal(10,2) DEFAULT NULL,
  `precio_publico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adicional` longtext DEFAULT NULL CHECK (json_valid(`adicional`)),
  `vigente` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_adicional_backup`
--

CREATE TABLE `examenes_adicional_backup` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_examen` int(11) NOT NULL,
  `adicional_anterior` longtext DEFAULT NULL,
  `adicional_nuevo` longtext DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_cliente`
--

CREATE TABLE `examenes_cliente` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_convenio`
--

CREATE TABLE `examenes_convenio` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `convenio_id` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descuento` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_empresa`
--

CREATE TABLE `examenes_empresa` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_promocion`
--

CREATE TABLE `examenes_promocion` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_consumos_examen`
--

CREATE TABLE `inventario_consumos_examen` (
  `id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `cantidad_consumida` decimal(12,4) NOT NULL,
  `origen_evento` varchar(30) NOT NULL DEFAULT 'resultado',
  `estado` varchar(20) NOT NULL DEFAULT 'aplicado',
  `usuario_id` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_examen_recetas`
--

CREATE TABLE `inventario_examen_recetas` (
  `id` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `cantidad_por_prueba` decimal(12,4) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
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
  `controla_stock` tinyint(1) NOT NULL DEFAULT 1,
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
  `origen` varchar(30) NOT NULL DEFAULT 'inventario',
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_transferencias`
--

CREATE TABLE `inventario_transferencias` (
  `id` int(11) NOT NULL,
  `origen` varchar(50) NOT NULL DEFAULT 'almacen_principal',
  `destino` varchar(50) NOT NULL DEFAULT 'laboratorio',
  `usuario_id` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_transferencias_detalle`
--

CREATE TABLE `inventario_transferencias_detalle` (
  `id` int(11) NOT NULL,
  `transferencia_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laboratorios_referenciados`
--

CREATE TABLE `laboratorios_referenciados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laboratorio_turnos`
--

CREATE TABLE `laboratorio_turnos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `estado` enum('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_en` datetime NOT NULL DEFAULT current_timestamp(),
  `cerrado_en` datetime DEFAULT NULL,
  `observacion_apertura` varchar(255) DEFAULT NULL,
  `observacion_cierre` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Estructura de tabla para la tabla `profesionales_solicitantes`
--

CREATE TABLE `profesionales_solicitantes` (
  `id` int(11) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) DEFAULT NULL,
  `tipo_profesional` varchar(80) NOT NULL,
  `numero_documento` varchar(30) DEFAULT NULL,
  `registro_profesional` varchar(80) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
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
-- Estructura de tabla para la tabla `promociones_empresa`
--

CREATE TABLE `promociones_empresa` (
  `id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones_examen`
--

CREATE TABLE `promociones_examen` (
  `id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados`
--

CREATE TABLE `resultados` (
  `id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `resultados_json` longtext NOT NULL CHECK (json_valid(`resultados_json`)),
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
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
  `orden_impresion` int(11) DEFAULT NULL,
  `resultados` longtext NOT NULL CHECK (json_valid(`resultados`)),
  `adicional_snapshot` longtext DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `fecha_proceso_en` datetime DEFAULT NULL,
  `fecha_validacion_en` datetime DEFAULT NULL,
  `validado_por` int(11) DEFAULT NULL,
  `estado_validacion` varchar(20) DEFAULT 'pendiente',
  `id_laboratorista` int(11) DEFAULT NULL,
  `id_turno` int(11) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `alarma_activa` tinyint(1) NOT NULL DEFAULT 0,
  `alarma_dias` int(11) DEFAULT NULL,
  `alarma_fecha_objetivo` datetime DEFAULT NULL,
  `alarma_estado` varchar(20) DEFAULT NULL,
  `alarma_ultimo_aviso` datetime DEFAULT NULL,
  `alarma_whatsapp_destino` varchar(32) DEFAULT NULL,
  `edad_paciente_valor` decimal(10,6) DEFAULT NULL,
  `edad_paciente_texto` varchar(120) DEFAULT NULL,
  `fecha_ref_edad` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_examenes_backup_fix_hemograma_20260217`
--

CREATE TABLE `resultados_examenes_backup_fix_hemograma_20260217` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_examen` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`resultados`)),
  `adicional_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `id_laboratorista` int(11) DEFAULT NULL,
  `estado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_sync_operaciones`
--

CREATE TABLE `resultados_sync_operaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `operation_id` varchar(80) NOT NULL,
  `cotizacion_id` int(11) DEFAULT NULL,
  `estado` enum('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
  `payload_json` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(80) NOT NULL,
  `responsable` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_cliente`
--

CREATE TABLE `servicio_cliente` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_descargas_auditoria`
--

CREATE TABLE `servicio_descargas_auditoria` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_email` varchar(190) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_profesional`
--

CREATE TABLE `servicio_profesional` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `profesional_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_coberturas`
--

CREATE TABLE `sis_coberturas` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `numero_afiliacion` varchar(80) DEFAULT NULL,
  `numero_autorizacion` varchar(80) DEFAULT NULL,
  `numero_fua` varchar(80) DEFAULT NULL,
  `aseguradora` varchar(150) DEFAULT NULL,
  `plan` varchar(150) DEFAULT NULL,
  `estado_validacion` enum('pendiente','autorizada','observada','rechazada') NOT NULL DEFAULT 'pendiente',
  `monto_atencion` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizada_en` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_codigos_prestacionales`
--

CREATE TABLE `sis_codigos_prestacionales` (
  `id` int(11) NOT NULL,
  `codigo` char(3) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion_minsa` varchar(255) DEFAULT NULL,
  `tipo_regla` enum('EDAD','GESTANTE') NOT NULL DEFAULT 'EDAD',
  `edad_min_meses` int(11) DEFAULT NULL,
  `edad_max_meses` int(11) DEFAULT NULL,
  `icono_clase` varchar(80) DEFAULT NULL,
  `icono_ruta` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `orden` smallint(6) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT NULL ON UPDATE current_timestamp()
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
  `colegiatura_numero` varchar(60) DEFAULT NULL,
  `ctmp_numero` varchar(60) DEFAULT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `rol` enum('admin','recepcionista','laboratorista') DEFAULT 'recepcionista',
  `privilegios_json` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `backup_snapshot_resultados_examenes`
--
ALTER TABLE `backup_snapshot_resultados_examenes`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_cmov_ref` (`referencia_tipo`,`referencia_id`);

--
-- Indices de la tabla `caja_reaperturas`
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
  ADD KEY `idx_cotizaciones_anulada_at` (`anulada_at`),
  ADD KEY `idx_cotizaciones_anulada_por` (`anulada_por`),
  ADD KEY `idx_cotizaciones_es_sis` (`es_sis`),
  ADD KEY `idx_cotizaciones_profesional_solicitante` (`profesional_solicitante_id`);

--
-- Indices de la tabla `cotizaciones_detalle`
--
ALTER TABLE `cotizaciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `idx_cd_referenciado_liquidacion` (`es_referenciado`,`estado_liquidacion`);

--
-- Indices de la tabla `cotizaciones_express`
--
ALTER TABLE `cotizaciones_express`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cotexp_token` (`token`),
  ADD UNIQUE KEY `uq_cotexp_folio` (`folio`),
  ADD KEY `idx_cotexp_estado_fecha` (`estado`,`creado_en`),
  ADD KEY `idx_cotexp_vence` (`vence_en`),
  ADD KEY `idx_cotexp_cliente` (`cliente_id`);

--
-- Indices de la tabla `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cotexp_items_express` (`express_id`);

--
-- Indices de la tabla `cotizador_express_plantillas`
--
ALTER TABLE `cotizador_express_plantillas`
  ADD PRIMARY KEY (`tipo`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_egresos_categoria_fecha` (`categoria`,`fecha`),
  ADD KEY `idx_egresos_cotizacion_detalle` (`id_cotizacion`,`id_cotizacion_detalle`);

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
-- Indices de la tabla `examenes_adicional_backup`
--
ALTER TABLE `examenes_adicional_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `convenio_id` (`convenio_id`);

--
-- Indices de la tabla `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `examenes_empresa_ibfk_1` (`examen_id`);

--
-- Indices de la tabla `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `promocion_id` (`promocion_id`);

--
-- Indices de la tabla `inventario_consumos_examen`
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
-- Indices de la tabla `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inventario_receta_examen_item` (`id_examen`,`item_id`),
  ADD KEY `idx_inventario_receta_examen` (`id_examen`),
  ADD KEY `idx_inventario_receta_item` (`item_id`),
  ADD KEY `idx_inventario_receta_activo` (`activo`);

--
-- Indices de la tabla `inventario_items`
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
-- Indices de la tabla `inventario_lotes`
--
ALTER TABLE `inventario_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_lotes_item` (`item_id`),
  ADD KEY `idx_inventario_lotes_venc` (`fecha_vencimiento`),
  ADD KEY `idx_inventario_lotes_stock` (`cantidad_actual`),
  ADD KEY `idx_inventario_lotes_item_stock_venc` (`item_id`,`cantidad_actual`,`fecha_vencimiento`,`id`),
  ADD KEY `idx_inventario_lotes_venc_stock_item` (`fecha_vencimiento`,`cantidad_actual`,`item_id`);

--
-- Indices de la tabla `inventario_movimientos`
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
-- Indices de la tabla `inventario_transferencias`
--
ALTER TABLE `inventario_transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transferencias_fecha` (`fecha_hora`),
  ADD KEY `idx_inventario_transferencias_destino` (`destino`),
  ADD KEY `idx_transfer_destino_id` (`destino`,`id`),
  ADD KEY `idx_transfer_fecha_id` (`fecha_hora`,`id`);

--
-- Indices de la tabla `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventario_transfer_det_transf` (`transferencia_id`),
  ADD KEY `idx_inventario_transfer_det_item` (`item_id`),
  ADD KEY `idx_transfer_det_item_transfer` (`item_id`,`transferencia_id`);

--
-- Indices de la tabla `laboratorios_referenciados`
--
ALTER TABLE `laboratorios_referenciados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_laboratorios_referenciados_nombre` (`nombre`);

--
-- Indices de la tabla `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turnos_usuario_estado` (`usuario_id`,`estado`),
  ADD KEY `idx_turnos_abierto_en` (`abierto_en`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_pagos_fecha` (`fecha`),
  ADD KEY `idx_pagos_fecha_metodo` (`fecha`,`metodo_pago`);

--
-- Indices de la tabla `profesionales_solicitantes`
--
ALTER TABLE `profesionales_solicitantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ps_estado` (`estado`),
  ADD KEY `idx_ps_nombre` (`nombres`,`apellidos`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `idx_promociones_empresa_empresa` (`empresa_id`),
  ADD KEY `idx_promociones_empresa_promocion` (`promocion_id`);

--
-- Indices de la tabla `promociones_examen`
--
ALTER TABLE `promociones_examen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `examen_id` (`examen_id`),
  ADD KEY `idx_promociones_examen_examen` (`examen_id`),
  ADD KEY `idx_promociones_examen_promocion` (`promocion_id`);

--
-- Indices de la tabla `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `idx_resultados_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_resultados_examen` (`id_examen`);

--
-- Indices de la tabla `resultados_examenes`
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
-- Indices de la tabla `resultados_sync_operaciones`
--
ALTER TABLE `resultados_sync_operaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resultados_sync_operation_id` (`operation_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicios_codigo` (`codigo`),
  ADD UNIQUE KEY `uq_servicios_email` (`email`);

--
-- Indices de la tabla `servicio_cliente`
--
ALTER TABLE `servicio_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicio_cliente` (`servicio_id`,`cliente_id`),
  ADD KEY `idx_sc_servicio` (`servicio_id`),
  ADD KEY `idx_sc_cliente` (`cliente_id`);

--
-- Indices de la tabla `servicio_descargas_auditoria`
--
ALTER TABLE `servicio_descargas_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sda_servicio` (`servicio_id`),
  ADD KEY `idx_sda_cotizacion` (`cotizacion_id`),
  ADD KEY `idx_sda_cliente` (`cliente_id`);

--
-- Indices de la tabla `servicio_profesional`
--
ALTER TABLE `servicio_profesional`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicio_profesional` (`servicio_id`,`profesional_id`),
  ADD KEY `idx_sp_servicio` (`servicio_id`),
  ADD KEY `idx_sp_profesional` (`profesional_id`);

--
-- Indices de la tabla `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sis_coberturas_cotizacion_id` (`cotizacion_id`),
  ADD KEY `idx_sis_coberturas_cliente_id` (`cliente_id`),
  ADD KEY `idx_sis_coberturas_numero_autorizacion` (`numero_autorizacion`);

--
-- Indices de la tabla `sis_codigos_prestacionales`
--
ALTER TABLE `sis_codigos_prestacionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sis_codigos_prestacionales_codigo` (`codigo`);

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
-- AUTO_INCREMENT de la tabla `backup_snapshot_resultados_examenes`
--
ALTER TABLE `backup_snapshot_resultados_examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de la tabla `caja_reaperturas`
--
ALTER TABLE `caja_reaperturas`
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
-- AUTO_INCREMENT de la tabla `cotizaciones_express`
--
ALTER TABLE `cotizaciones_express`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
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
-- AUTO_INCREMENT de la tabla `examenes_adicional_backup`
--
ALTER TABLE `examenes_adicional_backup`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes_cliente`
--
ALTER TABLE `examenes_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes_convenio`
--
ALTER TABLE `examenes_convenio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes_empresa`
--
ALTER TABLE `examenes_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes_promocion`
--
ALTER TABLE `examenes_promocion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
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
-- AUTO_INCREMENT de la tabla `inventario_transferencias`
--
ALTER TABLE `inventario_transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laboratorios_referenciados`
--
ALTER TABLE `laboratorios_referenciados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesionales_solicitantes`
--
ALTER TABLE `profesionales_solicitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones_empresa`
--
ALTER TABLE `promociones_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones_examen`
--
ALTER TABLE `promociones_examen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados_examenes`
--
ALTER TABLE `resultados_examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados_sync_operaciones`
--
ALTER TABLE `resultados_sync_operaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicio_cliente`
--
ALTER TABLE `servicio_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicio_descargas_auditoria`
--
ALTER TABLE `servicio_descargas_auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicio_profesional`
--
ALTER TABLE `servicio_profesional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sis_codigos_prestacionales`
--
ALTER TABLE `sis_codigos_prestacionales`
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
-- Filtros para la tabla `cotizaciones_express_items`
--
ALTER TABLE `cotizaciones_express_items`
  ADD CONSTRAINT `fk_cotexp_items_express` FOREIGN KEY (`express_id`) REFERENCES `cotizaciones_express` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empresa_cliente`
--
ALTER TABLE `empresa_cliente`
  ADD CONSTRAINT `empresa_cliente_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `empresa_cliente_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `inventario_consumos_examen`
--
ALTER TABLE `inventario_consumos_examen`
  ADD CONSTRAINT `fk_inventario_consumo_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventario_examen_recetas`
--
ALTER TABLE `inventario_examen_recetas`
  ADD CONSTRAINT `fk_inventario_receta_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `inventario_transferencias_detalle`
--
ALTER TABLE `inventario_transferencias_detalle`
  ADD CONSTRAINT `fk_inventario_transfer_det_item` FOREIGN KEY (`item_id`) REFERENCES `inventario_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventario_transfer_det_transferencia` FOREIGN KEY (`transferencia_id`) REFERENCES `inventario_transferencias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `laboratorio_turnos`
--
ALTER TABLE `laboratorio_turnos`
  ADD CONSTRAINT `fk_turnos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

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

--
-- Filtros para la tabla `sis_coberturas`
--
ALTER TABLE `sis_coberturas`
  ADD CONSTRAINT `fk_sis_coberturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sis_coberturas_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
