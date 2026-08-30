<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/conexion/conexion.php';
require_once __DIR__ . '/usuarios/funciones/usuarios_privilegios.php';

if (isset($_GET['action']) && $_GET['action'] === 'guardar_cotizacion_recepcionista') {
   }


date_default_timezone_set('America/Lima');

$operacionContext = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [
    'modo_operativo' => 'PARTICULAR',
    'portal_publico_enable' => true,
    'es_particular' => true,
    'es_sis' => false,
    'es_mixto' => false,
];
$modoOperativoActual = strtoupper((string)($operacionContext['modo_operativo'] ?? 'PARTICULAR'));
$esModoSis = !empty($operacionContext['es_sis']);

if (!function_exists('dashboard_render_acceso_denegado')) {
    function dashboard_render_acceso_denegado(string $titulo, string $mensaje, string $volverUrl = 'dashboard.php'): void
    {
        if (!headers_sent()) {
            http_response_code(403);
        }
        echo '<!DOCTYPE html>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Acceso restringido</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';
        echo '<style>';
        echo 'body{min-height:100vh;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 55%,#7c3aed 100%);}';
        echo '.deny-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}';
        echo '.deny-card{max-width:560px;width:100%;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border:0;border-radius:24px;box-shadow:0 25px 70px rgba(15,23,42,.28);}';
        echo '.deny-icon{width:72px;height:72px;border-radius:22px;display:grid;place-items:center;background:linear-gradient(135deg,#dc2626,#f97316);color:#fff;font-size:2rem;box-shadow:0 16px 35px rgba(220,38,38,.35);}';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="deny-wrap">';
        echo '<div class="card deny-card">';
        echo '<div class="card-body p-4 p-md-5">';
        echo '<div class="d-flex align-items-start gap-3 mb-3">';
        echo '<div class="deny-icon"><i class="bi bi-shield-lock"></i></div>';
        echo '<div>'; 
        echo '<p class="text-uppercase text-danger fw-semibold mb-1" style="letter-spacing:.08em;">Acceso restringido</p>';
        echo '<h1 class="h3 mb-2">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p class="text-muted mb-0">' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="d-flex flex-wrap gap-2 mt-4">';
        echo '<a class="btn btn-primary btn-lg" href="' . htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') . '"><i class="bi bi-arrow-left me-2"></i>Volver</a>';
        echo '<button class="btn btn-outline-secondary btn-lg" type="button" onclick="history.back()"><i class="bi bi-arrow-counterclockwise me-2"></i>Regresar</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</body>';
        echo '</html>';
        exit;
    }
}

if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
    $nombre = trim((string)($_SESSION['usuario']['nombre'] ?? ''));
    $apellido = trim((string)($_SESSION['usuario']['apellido'] ?? ''));
    $usuarioTexto = trim($nombre . ' ' . $apellido);
    if ($usuarioTexto === '') {
        $usuarioTexto = trim((string)($_SESSION['usuario']['usuario'] ?? ''));
    }
    $_SESSION['usuario'] = $usuarioTexto !== '' ? $usuarioTexto : 'Usuario';
}


$acciones_por_rol = [
    'admin' => ['crear_cotizacion', 'crear_promocion', 'editar_promocion', 'eliminar_promocion', 'crear_cliente', 'editar_cliente', 'eliminar_cliente', 'crear_empresa', 'editar_empresa', 'eliminar_empresa', 'crear_convenio', 'editar_convenio', 'eliminar_convenio', 'crear_examen', 'editar_examen', 'eliminar_examen', 'crear_usuario', 'editar_usuario', 'eliminar_usuario', 'abrir_turno_laboratorio', 'cerrar_turno_laboratorio', 'crear_cotizacion_recepcionista','eliminar_cotizacion', 'config_empresa_guardar', 'buscar_examenes_recepcionista', 'guardar_cotizacion_recepcionista', 'procesar_agenda', 'api_listado', 'guardar' ,'descarga-pdf', 'actualizar_snapshot_resultados', 'resultados_repetir_prueba', 'pago_cotizacion_guardar', 'actualizar_total_cotizacion', 'egresos_eliminar', 'egresos_actualizar','confirmar_toma','buscar_cliente_accion','asociar_cliente_existente', 'pago_masivo', 'editar_cotizacion', 'clientes_api', 'cotizaciones_api', 'examenes_api', 'usuarios_api', 'empresas_api', 'convenios_api', 'ingresos_api', 'ingresos_export', 'ingresos_detalle', 'estadisticas_api', 'estadisticas_export', 'descargar_cotizacion', 'emitir_comprobante', 'estado_comprobante', 'descargar_comprobante', 'caja_abrir', 'caja_cerrar', 'caja_ajuste', 'caja_reapertura_solicitar', 'caja_reapertura_aprobar', 'liquidar_referenciado', 'comparar_resultados_export', 'inventario_item_guardar', 'inventario_item_actualizar', 'inventario_movimiento_guardar', 'inventario_export', 'inventario_receta_guardar', 'inventario_receta_eliminar', 'inventario_receta_reactivar', 'inventario_transferencia_guardar', 'crear_servicio', 'editar_servicio', 'eliminar_servicio', 'toggle_servicio'],

    'laboratorista' => ['api_listado', 'guardar', 'descarga-pdf', 'actualizar_snapshot_resultados', 'resultados_repetir_prueba', 'cotizaciones_api', 'examenes_api', 'abrir_turno_laboratorio', 'cerrar_turno_laboratorio'],
    'recepcionista' => ['crear_cotizacion', 'crear_cotizacion_recepcionista', 'crear_cliente', 'editar_cliente', 'eliminar_cliente', 'buscar_examenes_recepcionista', 'guardar_cotizacion_recepcionista', 'procesar_agenda','api_listado', 'guardar','descarga-pdf', 'actualizar_snapshot_resultados', 'resultados_repetir_prueba', 'pago_cotizacion_guardar', 'actualizar_total_cotizacion', 'egresos_eliminar','egresos_actualizar' ,'confirmar_toma', 'pago_masivo', 'editar_cotizacion', 'clientes_api', 'cotizaciones_api', 'examenes_api','ingresos_api', 'ingresos_export', 'ingresos_detalle', 'estadistica_api', 'estadisticas_export', 'descargar_cotizacion', 'emitir_comprobante', 'estado_comprobante', 'descargar_comprobante', 'caja_abrir', 'caja_cerrar', 'caja_ajuste', 'caja_reapertura_solicitar', 'liquidar_referenciado', 'comparar_resultados_export', 'inventario_item_guardar', 'inventario_item_actualizar', 'inventario_movimiento_guardar', 'inventario_export', 'inventario_receta_guardar', 'inventario_receta_eliminar', 'inventario_receta_reactivar', 'inventario_transferencia_guardar'],
    'empresa' => ['crear_cotizacion','buscar_cliente_accion','asociar_cliente_existente','procesar_agenda','crear_cliente','editar_cliente','eliminar_cliente', 'cotizaciones_api', 'examenes_api','descargar_cotizacion'],
    'cliente' => ['crear_cotizacion', 'procesar_agenda', 'cotizaciones_api', 'examenes_api','descargar_cotizacion'],
    'convenio' => ['crear_cotizacion','buscar_cliente_accion','asociar_cliente_existente','procesar_agenda','crear_cliente','editar_cliente','eliminar_cliente', 'cotizaciones_api', 'examenes_api','descargar_cotizacion'],
    'servicio' => ['descarga-pdf'],
    'engineer' => ['config_operacion_engineer_guardar', 'config_personalizacion_engineer_guardar']
];

$acciones_por_privilegio = [
    'crear_usuario' => 'menu_usuarios',
    'editar_usuario' => 'menu_usuarios',
    'eliminar_usuario' => 'menu_usuarios',
    'crear_cliente' => 'menu_pacientes',
    'editar_cliente' => 'menu_pacientes',
    'eliminar_cliente' => 'menu_pacientes',
    'crear_empresa' => 'menu_empresas',
    'editar_empresa' => 'menu_empresas',
    'eliminar_empresa' => 'menu_empresas',
    'crear_convenio' => 'menu_convenios',
    'editar_convenio' => 'menu_convenios',
    'eliminar_convenio' => 'menu_convenios',
    'crear_examen' => 'examenes_crear',
    'editar_examen' => 'examenes_editar',
    'eliminar_examen' => 'examenes_eliminar',
    'crear_cotizacion' => 'cotizaciones_crear',
    'crear_cotizacion_recepcionista' => 'cotizaciones_crear',
    'guardar_cotizacion_recepcionista' => 'cotizaciones_crear',
    'buscar_examenes_recepcionista' => 'cotizaciones_crear',
    'procesar_agenda' => 'cotizaciones_crear',
    'editar_cotizacion' => 'cotizaciones_editar',
    'eliminar_cotizacion' => 'cotizaciones_eliminar',
    'pago_cotizacion_guardar' => 'cotizaciones_pagar',
    'actualizar_total_cotizacion' => 'cotizaciones_pagar',
    'pago_masivo' => 'cotizaciones_pagar',
    'api_listado' => 'resultados_ver',
    'guardar' => 'resultados_editar',
    'descarga-pdf' => 'resultados_ver',
    'actualizar_snapshot_resultados' => 'resultados_editar',
    'resultados_repetir_prueba' => 'resultados_editar',
    'comparar_resultados_export' => 'resultados_comparar',
    'crear_servicio' => 'menu_servicios',
    'editar_servicio' => 'menu_servicios',
    'eliminar_servicio' => 'menu_servicios',
    'toggle_servicio' => 'menu_servicios',
    'crear_profesional_solicitante' => 'menu_servicios',
    'editar_profesional_solicitante' => 'menu_servicios',
    'eliminar_profesional_solicitante' => 'menu_servicios',
    'abrir_turno_laboratorio' => 'menu_turnos',
    'cerrar_turno_laboratorio' => 'menu_turnos',
];


// ...existing code...
$acciones = [
    'ingresos_api' => __DIR__ . '/contabilidad/ingresos_api.php',
    'ingresos_export' => __DIR__ . '/contabilidad/ingresos_export.php',
    'ingresos_detalle' => __DIR__ . '/contabilidad/ingresos_detalle.php',
    'estadisticas_api' => __DIR__ . '/contabilidad/estadisticas_api.php',
    'estadisticas_export' => __DIR__ . '/contabilidad/estadisticas_export.php',
    'convenios_api' => __DIR__ . '/convenios/convenios_api.php',
    'empresas_api' => __DIR__ . '/empresas/empresas_api.php',
    'crear_usuario' => __DIR__ . '/usuarios/crear_usuario.php',
    'eliminar_usuario' => __DIR__ . '/usuarios/eliminar_usuario.php',
    'editar_usuario' => __DIR__ . '/usuarios/editar_usuario.php',
    'abrir_turno_laboratorio' => __DIR__ . '/usuarios/abrir_turno_laboratorio.php',
    'cerrar_turno_laboratorio' => __DIR__ . '/usuarios/cerrar_turno_laboratorio.php',
    'crear_cliente' => __DIR__ . '/clientes/crear.php',
    'eliminar_cliente' => __DIR__ . '/clientes/eliminar.php',
    'editar_cliente' => __DIR__ . '/clientes/editar.php',
    'crear_empresa' => __DIR__ . '/empresas/crear_empresa.php',
    'eliminar_empresa' => __DIR__ . '/empresas/eliminar_empresa.php',
    'editar_empresa' => __DIR__ . '/empresas/editar_empresa.php',
    'crear_convenio' => __DIR__ . '/convenios/crear_convenio.php',
    'eliminar_convenio' => __DIR__ . '/convenios/eliminar_convenio.php',
    'editar_convenio' => __DIR__ . '/convenios/editar_convenio.php',
    'crear_examen' => __DIR__ . '/examenes/crear_examen.php',
    'eliminar_examen' => __DIR__ . '/examenes/eliminar_examen.php',
    'editar_examen' => __DIR__ . '/examenes/editar_examen.php',
    'crear_cotizacion' => __DIR__ . '/cotizaciones/forms/crear_cotizacion.php',
    'crear_promocion' => __DIR__ . '/promociones/crear_promocion.php',
    'editar_promocion' => __DIR__ . '/promociones/editar_promocion.php',
    'eliminar_promocion' => __DIR__ . '/promociones/eliminar_promocion.php',
    'config_empresa_guardar' => __DIR__ . '/config/config_empresa_guardar.php',
    'config_operacion_engineer_guardar' => __DIR__ . '/config/config_operacion_engineer_guardar.php',
    'config_personalizacion_engineer_guardar' => __DIR__ . '/config/config_personalizacion_engineer_guardar.php',
    'crear_servicio' => __DIR__ . '/servicios/crear_servicio.php',
    'editar_servicio' => __DIR__ . '/servicios/editar_servicio.php',
    'eliminar_servicio' => __DIR__ . '/servicios/eliminar_servicio.php',
    'crear_profesional_solicitante' => __DIR__ . '/servicios/crear_profesional_solicitante.php',
    'editar_profesional_solicitante' => __DIR__ . '/servicios/editar_profesional_solicitante.php',
    'eliminar_profesional_solicitante' => __DIR__ . '/servicios/eliminar_profesional_solicitante.php',
    'toggle_servicio' => __DIR__ . '/servicios/toggle_servicio.php',
    'procesar_agenda' => __DIR__ . '/cotizaciones/api/procesar_agenda.php',
    'api_listado' => __DIR__ . '/resultados/api_listado.php',    
    'guardar' => __DIR__ . '/resultados/guardar.php',
    'actualizar_snapshot_resultados' => __DIR__ . '/resultados/actualizar_snapshot_resultados.php',
    'resultados_repetir_prueba' => __DIR__ . '/resultados/repetir_prueba.php',
    'descarga-pdf' => __DIR__ . '/resultados/descarga-pdf.php',
    'eliminar_cotizacion' => __DIR__ . '/cotizaciones/forms/eliminar_cotizacion.php',
    'pago_cotizacion_guardar' => __DIR__ . '/pagos/pago_cotizacion_guardar.php',
    'actualizar_total_cotizacion' => __DIR__ . '/pagos/actualizar_total_cotizacion.php',
    'egresos_eliminar' => __DIR__ . '/contabilidad/egresos_eliminar.php',
    'egresos_actualizar' => __DIR__ . '/contabilidad/egresos_actualizar.php',
    'confirmar_toma' => __DIR__ . '/cotizaciones/citas/confirmar_toma.php',
    'buscar_cliente_accion'=>  __DIR__ . '/gestion/acciones/buscar_cliente_accion.php',
    'asociar_cliente_existente' => __DIR__ . '/gestion/acciones/asociar_cliente_existente.php',
    'pago_masivo' => __DIR__ . '/cotizaciones/api/pago_masivo.php',
    'editar_cotizacion' => __DIR__ . '/cotizaciones/forms/editar_cotizacion.php',
    'clientes_api' => __DIR__ . '/clientes/clientes_api.php',
    'cotizaciones_api' => __DIR__ . '/cotizaciones/api/cotizaciones_api.php',
    'examenes_api' => __DIR__ . '/examenes/examenes_api.php',
    'usuarios_api' => __DIR__ . '/usuarios/usuarios_api.php',
    'descargar_cotizacion' => __DIR__ . '/cotizaciones/views/descargar_cotizacion.php',
    'emitir_comprobante' => __DIR__ . '/cotizaciones/api/emitir_comprobante.php',
    'estado_comprobante' => __DIR__ . '/cotizaciones/api/estado_comprobante.php',
    'descargar_comprobante' => __DIR__ . '/cotizaciones/api/descargar_comprobante.php',
    'comparar_resultados_export' => __DIR__ . '/clientes/comparar_resultados.php',
    'inventario_item_guardar' => __DIR__ . '/inventario/item_guardar.php',
    'inventario_item_actualizar' => __DIR__ . '/inventario/item_actualizar.php',
    'inventario_movimiento_guardar' => __DIR__ . '/inventario/movimiento_guardar.php',
    'inventario_export' => __DIR__ . '/inventario/inventario_export.php',
    'inventario_receta_guardar' => __DIR__ . '/inventario/receta_guardar.php',
    'inventario_receta_eliminar' => __DIR__ . '/inventario/receta_eliminar.php',
    'inventario_receta_reactivar' => __DIR__ . '/inventario/receta_reactivar.php',
    'inventario_transferencia_guardar' => __DIR__ . '/inventario/transferencia_guardar.php',
    'caja_abrir' => __DIR__ . '/contabilidad/caja_abrir.php',
    'caja_cerrar' => __DIR__ . '/contabilidad/caja_cerrar.php',
    'caja_ajuste' => __DIR__ . '/contabilidad/caja_ajuste.php',
    'caja_reapertura_solicitar' => __DIR__ . '/contabilidad/caja_reapertura_solicitar.php',
    'caja_reapertura_aprobar' => __DIR__ . '/contabilidad/caja_reapertura_aprobar.php',
    'liquidar_referenciado' => __DIR__ . '/contabilidad/liquidar_referenciado.php',
];

$rol_actual = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
$privilegiosActuales = usuarios_privilegios_usuario_actual($pdo);
$puedeVista = static function (string $clave) use ($privilegiosActuales): bool {
    return usuarios_tiene_privilegio($privilegiosActuales, $clave);
};
$action = isset($_GET['action']) ? $_GET['action'] : null;
$vista = isset($_GET['vista']) ? strtolower(trim((string)$_GET['vista'])) : '';

$accionesSoloParticular = ['pago_cotizacion_guardar', 'actualizar_total_cotizacion', 'pago_masivo'];
$vistasSoloParticular = ['pago_cotizacion'];
$accionesBloqueadasSis = [
    'crear_empresa', 'editar_empresa', 'eliminar_empresa',
    'crear_convenio', 'editar_convenio', 'eliminar_convenio',
    'empresas_api', 'convenios_api'
];
$vistasBloqueadasSis = [
    'empresas', 'empresa', 'form_empresa',
    'convenios', 'convenio', 'form_convenio',
    'cotizaciones_empresas', 'cotizaciones_convenios', 'clientes_empresa', 'clientes_convenio'
];
$accionesBloqueadasParticular = [
    'crear_servicio', 'editar_servicio', 'eliminar_servicio', 'toggle_servicio',
    'crear_profesional_solicitante', 'editar_profesional_solicitante', 'eliminar_profesional_solicitante'
];
$vistasBloqueadasParticular = [
    'servicios', 'form_servicio', 'profesionales_solicitantes', 'form_profesional_solicitante',
    'servicio', 'servicio_clientes', 'servicio_resultados', 'servicio_auditoria'
];

if ($esModoSis && $action && in_array($action, $accionesSoloParticular, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo SIS deshabilita cobros, pagos y ajuste de totales.</div></div>';
    exit;
}

if ($esModoSis && $action && in_array($action, $accionesBloqueadasSis, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo SIS deshabilita operaciones de empresas y convenios.</div></div>';
    exit;
}

if ($esModoSis && $vista && in_array($vista, $vistasSoloParticular, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo SIS deshabilita la vista de pagos.</div></div>';
    exit;
}

if ($esModoSis && $vista && in_array($vista, $vistasBloqueadasSis, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo SIS deshabilita vistas de empresas y convenios.</div></div>';
    exit;
}

if ($modoOperativoActual === 'PARTICULAR' && $action && in_array($action, $accionesBloqueadasParticular, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo Particular deshabilita operaciones de servicios.</div></div>';
    exit;
}

if ($modoOperativoActual === 'PARTICULAR' && $vista && in_array($vista, $vistasBloqueadasParticular, true)) {
    echo '<div class="container mt-5"><div class="alert alert-warning">El modo Particular deshabilita vistas de servicios.</div></div>';
    exit;
}

$titulosPorVista = [
    'formulario' => 'Resultados de Examenes',
    'listado' => 'Listado de Resultados',
    'cotizaciones' => 'Cotizaciones',
    'detalle_cotizacion' => 'Detalle de Cotizacion',
    'ver_cotizacion' => 'Resumen de Cotizacion',
    'pago_cotizacion' => 'Pago de Cotizacion',
    'ingresos' => 'Ingresos',
    'ingresos_diario' => 'Ingresos Diarios',
    'contabilidad' => 'Contabilidad',
    'inventario' => 'Inventario',
    'inventario_interno' => 'Inventario Interno',
    'estadisticas' => 'Estadisticas',
    'offline_monitor' => 'Monitoreo Offline',
];
$pageTitle = $titulosPorVista[$vista] ?? 'Panel de Administracion';

if ($action && isset($acciones[$action])) {
    $permisoAccion = $acciones_por_privilegio[$action] ?? null;
    if (
        (isset($acciones_por_rol[$rol_actual]) && in_array($action, $acciones_por_rol[$rol_actual], true))
        || ($permisoAccion !== null && $puedeVista($permisoAccion))
    ) {
        include $acciones[$action];
        exit; // Evita que se mezcle el JSON con el layout
    } else {
        $volverAccion = 'dashboard.php';
        if (in_array($action, ['crear_servicio', 'editar_servicio', 'eliminar_servicio', 'toggle_servicio'], true)) {
            $volverAccion = 'dashboard.php?vista=servicios';
        } elseif (in_array($action, ['crear_examen', 'editar_examen', 'eliminar_examen'], true)) {
            $volverAccion = 'dashboard.php?vista=examenes';
        }
        dashboard_render_acceso_denegado(
            'No tienes permisos para esta acción',
            'El rol actual no puede ejecutar ' . $action . '. Si necesitas acceso, pide a un administrador que revise tus privilegios.',
            $volverAccion
        );
    }
}

include __DIR__ . '/componentes/header.php';
include __DIR__ . '/componentes/sidebar.php';

// Mostrar mensajes de éxito o error
if (isset($_SESSION['mensaje'])) {
    $flashMensaje = (string)$_SESSION['mensaje'];
    $flashTipo = isset($_SESSION['mensaje_tipo']) ? (string)$_SESSION['mensaje_tipo'] : 'info';

    $tiposValidos = ['success', 'error', 'warning', 'info', 'question'];
    if (!in_array($flashTipo, $tiposValidos, true)) {
        $flashTipo = 'info';
    }

    if ($flashTipo === 'info') {
        $mensajeNormalizado = function_exists('mb_strtolower')
            ? mb_strtolower($flashMensaje, 'UTF-8')
            : strtolower($flashMensaje);

        if (
            strpos($mensajeNormalizado, 'error') !== false ||
            strpos($mensajeNormalizado, 'no se pudo') !== false ||
            strpos($mensajeNormalizado, 'no se puede') !== false
        ) {
            $flashTipo = 'error';
        } elseif (
            strpos($mensajeNormalizado, 'no válido') !== false ||
            strpos($mensajeNormalizado, 'invalido') !== false ||
            strpos($mensajeNormalizado, 'faltan') !== false ||
            strpos($mensajeNormalizado, 'pendiente') !== false
        ) {
            $flashTipo = 'warning';
        } elseif (
            strpos($mensajeNormalizado, 'exitosamente') !== false ||
            strpos($mensajeNormalizado, 'correctamente') !== false ||
            strpos($mensajeNormalizado, 'registrado') !== false ||
            strpos($mensajeNormalizado, 'actualizado') !== false ||
            strpos($mensajeNormalizado, 'eliminado') !== false ||
            strpos($mensajeNormalizado, 'guardado') !== false ||
            strpos($mensajeNormalizado, 'abierta') !== false ||
            strpos($mensajeNormalizado, 'cerrada') !== false
        ) {
            $flashTipo = 'success';
        }
    }

    $duracionesToast = [
        'success' => 3200,
        'info' => 4200,
        'question' => 4200,
        'warning' => 5200,
        'error' => 6200,
    ];
    $duracionToast = $duracionesToast[$flashTipo] ?? 4200;

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function () {';
    echo 'Swal.fire({';
    echo 'toast: true,';
    echo 'position: "top-end",';
    echo 'icon: ' . json_encode($flashTipo, JSON_UNESCAPED_UNICODE) . ',';
    echo 'title: ' . json_encode($flashMensaje, JSON_UNESCAPED_UNICODE) . ',';
    echo 'padding: "0.9rem 1rem",';
    echo 'showCloseButton: true,';
    echo 'showConfirmButton: false,';
    echo 'timer: ' . (int)$duracionToast . ',';
    echo 'timerProgressBar: true,';
    echo 'didOpen: function (toast) {';
    echo 'toast.addEventListener("mouseenter", Swal.stopTimer);';
    echo 'toast.addEventListener("mouseleave", Swal.resumeTimer);';
    echo '}';
    echo '});';
    echo '});';
    echo '</script>';

    unset($_SESSION['mensaje']); // Elimina el mensaje para que solo se muestre una vez
    unset($_SESSION['mensaje_tipo']);
}
// Lista de vistas permitidas por rol

$acceso_por_rol = [
    'admin' => ['empresas', 'empresa', 'form_empresa', 'admin', 'usuarios', 'form_usuario', 'clientes', 'cliente', 'form_cliente', 'laboratorista', 'recepcionista', 'convenios', 'convenio', 'form_convenio', 'examenes', 'form_examen', 'cotizaciones', 'cotizaciones_anuladas', 'form_cotizacion', 'promociones', 'form_promocion', 'boton_cotizar', 'form_cotizacion_recepcionista', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'config_empresa_datos', 'agendar_cita', 'listado','formulario','ver', 'vista-reporte-pdf','pago_cotizacion','contabilidad', 'ingresos', 'ingresos_diario', 'comparar_resultados_cliente', 'inventario', 'inventario_interno', 'egresos','egresos_editar','estadisticas','offline_monitor','referenciados_liquidacion','pendientes_toma','buscar_cliente','cotizaciones_empresas','cotizaciones_convenios','clientes_empresa','clientes_convenio','buscar_paciente', 'servicios', 'form_servicio'],

    'laboratorista' => ['laboratorista','cotizaciones','listado','formulario','ver'],
    'recepcionista' => ['recepcionista', 'cotizaciones', 'form_cotizacion', 'form_cotizacion_recepcionista', 'clientes', 'cliente', 'form_cliente', 'boton_cotizar', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'agendar_cita', 'listado','formulario','ver', 'vista-reporte-pdf','pago_cotizacion','contabilidad', 'ingresos', 'ingresos_diario', 'comparar_resultados_cliente', 'inventario', 'inventario_interno', 'egresos', 'egresos_editar','estadisticas','offline_monitor','referenciados_liquidacion','pendientes_toma','buscar_paciente'],
    'empresa' => ['empresa', 'empresas','buscar_cliente','form_cotizacion','agendar_cita','cotizaciones_empresas','detalle_cotizacion','form_cliente','clientes_empresa','detalle_promocion'],
    'cliente' => ['clientes', 'cliente', 'cotizaciones', 'form_cotizacion', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'cotizaciones_clientes', 'agendar_cita', 'detalle_promocion'],
    'convenio' => ['convenio','buscar_cliente','form_cotizacion','agendar_cita','cotizaciones_convenios','detalle_cotizacion','form_cliente','clientes_convenio','detalle_promocion'],
    'servicio' => ['servicio', 'servicio_clientes', 'servicio_resultados', 'servicio_auditoria'],
    'engineer' => ['config_operacion_engineer', 'config_personalizacion_engineer']
];

$vistas = [
    'admin' => __DIR__ . '/usuarios/vistas/panel_admin.php',
    'usuarios' => __DIR__ . '/usuarios/usuarios.php',
    'form_usuario' => __DIR__ . '/usuarios/form_usuario.php',
    'clientes' => __DIR__ . '/clientes/clientes.php',
    'comparar_resultados_cliente' => __DIR__ . '/clientes/comparar_resultados.php',
    'inventario' => __DIR__ . '/inventario/inventario.php',
    'inventario_interno' => __DIR__ . '/inventario/inventario_interno.php',
    'form_cliente' => __DIR__ . '/clientes/form_cliente.php',
    'empresas' => __DIR__ . '/empresas/empresas.php',
    'form_empresa' => __DIR__ . '/empresas/form_empresa.php',
    'convenios' => __DIR__ . '/convenios/convenios.php',
    'form_convenio' => __DIR__ . '/convenios/form_convenio.php',
    'examenes' => __DIR__ . '/examenes/examenes.php',
    'form_examen' => __DIR__ . '/examenes/form_examen.php',
    'empresa' => __DIR__ . '/empresas/vistas/panel_empresa.php',
    'recepcionista' => __DIR__ . '/usuarios/vistas/panel_recepcionista.php',
    'laboratorista' => __DIR__ . '/usuarios/vistas/panel_laboratorista.php',
    'cliente' => __DIR__ . '/clientes/vistas/panel_cliente.php',
    'convenio' => __DIR__ . '/convenios/vistas/panel_convenio.php',
    'servicio' => __DIR__ . '/servicios/vistas/panel_servicio.php',
    'cotizaciones' => __DIR__ . '/cotizaciones/views/cotizaciones.php',
    'cotizaciones_anuladas' => __DIR__ . '/cotizaciones/views/cotizaciones_anuladas.php',
    'form_cotizacion' => __DIR__ . '/cotizaciones/forms/form_cotizacion.php',
    'detalle_cotizacion' => __DIR__ . '/cotizaciones/views/detalle_cotizacion.php',
    'promociones' => __DIR__ . '/promociones/promociones.php',
    'form_promocion' => __DIR__ . '/promociones/form_promocion.php',
    'form_cotizacion_recepcionista' => __DIR__ . '/cotizaciones/form_cotizacion_recepcionista.php',
    'descargar_cotizacion' => __DIR__ . '/cotizaciones/views/descargar_cotizacion.php',
    'cotizaciones_clientes' => __DIR__ . '/cotizaciones/views/cotizaciones_clientes.php',
    'ver_cotizacion' => __DIR__ . '/cotizaciones/views/ver_cotizacion.php',
    'config_empresa_datos' => __DIR__ . '/config/config_empresa_datos.php',
    'config_operacion_engineer' => __DIR__ . '/config/config_operacion_engineer.php',
    'config_personalizacion_engineer' => __DIR__ . '/config/config_personalizacion_engineer.php',
    'servicios' => __DIR__ . '/servicios/servicios.php',
    'form_servicio' => __DIR__ . '/servicios/form_servicio.php',
    'profesionales_solicitantes' => __DIR__ . '/servicios/profesionales_solicitantes.php',
    'form_profesional_solicitante' => __DIR__ . '/servicios/form_profesional_solicitante.php',
    'servicio_clientes' => __DIR__ . '/servicios/clientes_servicio.php',
    'servicio_resultados' => __DIR__ . '/servicios/resultados_servicio.php',
    'servicio_auditoria' => __DIR__ . '/servicios/auditoria_descargas_servicio.php',
    'agendar_cita' => __DIR__ . '/cotizaciones/citas/agendar_cita.php',
    'listado' => __DIR__ . '/resultados/listado.php',
    'formulario' => __DIR__ . '/resultados/formulario.php',
    'pago_cotizacion' => __DIR__ . '/pagos/pago_cotizacion.php',
    'contabilidad' => __DIR__ . '/contabilidad/contabilidad.php',
    'ingresos' => __DIR__ . '/contabilidad/ingresos.php',
    'ingresos_diario' => __DIR__ . '/contabilidad/ingresos_diario.php',
    'estadisticas' => __DIR__ . '/contabilidad/estadisticas.php',
    'offline_monitor' => __DIR__ . '/offline/monitor.php',
    'egresos' => __DIR__ . '/contabilidad/egresos.php',
    'egresos_editar' => __DIR__ . '/contabilidad/egresos_editar.php',
    'referenciados_liquidacion' => __DIR__ . '/contabilidad/referenciados_liquidacion.php',
    'pendientes_toma' => __DIR__ . '/cotizaciones/api/pendientes_toma.php',
    'buscar_cliente'=> __DIR__ . '/gestion/buscar_cliente.php',
    'cotizar_cliente' => __DIR__ . '/gestion/cotizar_cliente.php',
    'cotizaciones_empresas' => __DIR__ . '/gestion/cotizaciones_empresas.php',
    'cotizaciones_convenios' => __DIR__ . '/gestion/cotizaciones_convenios.php',
    'clientes_empresa' => __DIR__ . '/empresas/clientes_empresa.php',
    'clientes_convenio' => __DIR__ . '/convenios/clientes_convenio.php',
    'detalle_promocion' => __DIR__ . '/promociones/detalle_promocion.php',
    'buscar_paciente' => __DIR__ . '/gestion/buscar_paciente.php'
    
];

// Obtener rol y vista
$rol_actual = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';

// Validar acceso
if ($vista && isset($vistas[$vista])) {
    $permisoVista = [
        'admin' => 'menu_admin',
        'usuarios' => 'menu_usuarios',
        'form_usuario' => 'menu_usuarios',
        'empresas' => 'menu_empresas',
        'form_empresa' => 'menu_empresas',
        'convenios' => 'menu_convenios',
        'form_convenio' => 'menu_convenios',
        'clientes' => 'menu_pacientes',
        'form_cliente' => 'menu_pacientes',
        'servicios' => 'menu_servicios',
        'form_servicio' => 'menu_servicios',
        'profesionales_solicitantes' => 'menu_servicios',
        'form_profesional_solicitante' => 'menu_servicios',
        'examenes' => 'menu_examenes',
        'form_examen' => 'examenes_editar',
        'cotizaciones' => 'menu_cotizaciones',
        'cotizaciones_anuladas' => 'menu_cotizaciones',
        'form_cotizacion' => 'cotizaciones_crear',
        'form_cotizacion_recepcionista' => 'cotizaciones_crear',
        'detalle_cotizacion' => 'cotizaciones_ver',
        'ver_cotizacion' => 'cotizaciones_ver',
        'descargar_cotizacion' => 'cotizaciones_ver',
        'pago_cotizacion' => 'cotizaciones_pagar',
        'contabilidad' => 'menu_contabilidad',
        'ingresos' => 'menu_contabilidad',
        'ingresos_diario' => 'menu_contabilidad',
        'estadisticas' => 'menu_estadisticas',
        'offline_monitor' => 'menu_estadisticas',
        'inventario' => 'menu_inventario',
        'inventario_interno' => 'menu_inventario',
        'laboratorista' => 'menu_laboratorio',
        'recepcionista' => 'menu_recepcion',
        'formulario' => 'resultados_ver',
        'listado' => 'resultados_ver',
        'comparar_resultados_cliente' => 'resultados_comparar',
    ];
    $claveVista = $permisoVista[$vista] ?? null;
    if ($vista === 'form_cotizacion' || $vista === 'form_cotizacion_recepcionista') {
        $esEdicionCotizacion = !empty($_GET['edit']) || !empty($_GET['id']);
        $claveVista = $esEdicionCotizacion ? 'cotizaciones_editar' : 'cotizaciones_crear';
    }
    if (
        isset($acceso_por_rol[$rol_actual]) && in_array($vista, $acceso_por_rol[$rol_actual], true)
        || ($claveVista !== null && $puedeVista($claveVista))
    ) {
        include $vistas[$vista];
    } else {
        dashboard_render_acceso_denegado(
            'No tienes acceso a esta pantalla',
            'La vista solicitada no está disponible para tu rol actual o te faltan privilegios asignados.',
            'dashboard.php'
        );

    }
}



include __DIR__ . '/componentes/footer.php';

?>