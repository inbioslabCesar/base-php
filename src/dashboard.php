<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

if (isset($_GET['action']) && $_GET['action'] === 'guardar_cotizacion_recepcionista') {
   }


date_default_timezone_set('America/Lima');

if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}


$acciones_por_rol = [
    'admin' => ['crear_cotizacion', 'crear_promocion', 'editar_promocion', 'eliminar_promocion', 'crear_cliente', 'editar_cliente', 'eliminar_cliente', 'crear_empresa', 'editar_empresa', 'eliminar_empresa', 'crear_convenio', 'editar_convenio', 'eliminar_convenio', 'crear_examen', 'editar_examen', 'eliminar_examen', 'crear_usuario', 'editar_usuario', 'eliminar_usuario', 'crear_cotizacion_recepcionista','eliminar_cotizacion', 'config_empresa_guardar', 'buscar_examenes_recepcionista', 'guardar_cotizacion_recepcionista', 'procesar_agenda', 'api_listado', 'guardar' ,'descarga-pdf','pago_cotizacion_guardar', 'actualizar_total_cotizacion', 'egresos_eliminar', 'egresos_actualizar','confirmar_toma','buscar_cliente_accion','asociar_cliente_existente', 'pago_masivo'],

    'laboratorista' => ['api_listado', 'guardar' ],
    'recepcionista' => ['crear_cotizacion', 'crear_cotizacion_recepcionista', 'crear_cliente', 'editar_cliente', 'eliminar_cliente', 'buscar_examenes_recepcionista', 'guardar_cotizacion_recepcionista', 'procesar_agenda','api_listado', 'guardar','descarga-pdf','pago_cotizacion_guardar', 'actualizar_total_cotizacion', 'egresos_eliminar','egresos_actualizar' ,'confirmar_toma', 'pago_masivo'],
    'empresa' => ['crear_cotizacion','buscar_cliente_accion','asociar_cliente_existente','procesar_agenda','crear_cliente','editar_cliente','eliminar_cliente'],
    'cliente' => ['crear_cotizacion', 'procesar_agenda'],
    'convenio' => ['crear_cotizacion','buscar_cliente_accion','asociar_cliente_existente','procesar_agenda','crear_cliente','editar_cliente','eliminar_cliente']
];


$acciones = [
    'crear_usuario' => __DIR__ . '/usuarios/crear_usuario.php',
    'eliminar_usuario' => __DIR__ . '/usuarios/eliminar_usuario.php',
    'editar_usuario' => __DIR__ . '/usuarios/editar_usuario.php',
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
    'crear_cotizacion' => __DIR__ . '/cotizaciones/crear_cotizacion.php',
    'crear_promocion' => __DIR__ . '/promociones/crear_promocion.php',
    'editar_promocion' => __DIR__ . '/promociones/editar_promocion.php',
    'eliminar_promocion' => __DIR__ . '/promociones/eliminar_promocion.php',
    'config_empresa_guardar' => __DIR__ . '/config/config_empresa_guardar.php',
    'procesar_agenda' => __DIR__ . '/cotizaciones/procesar_agenda.php',
    'api_listado' => __DIR__ . '/resultados/api_listado.php',    
    'guardar' => __DIR__ . '/resultados/guardar.php',
    'descarga-pdf' => __DIR__ . '/resultados/descarga-pdf.php',
    'eliminar_cotizacion' => __DIR__ . '/cotizaciones/eliminar_cotizacion.php',
    'pago_cotizacion_guardar' => __DIR__ . '/pagos/pago_cotizacion_guardar.php',
    'actualizar_total_cotizacion' => __DIR__ . '/pagos/actualizar_total_cotizacion.php',
    'egresos_eliminar' => __DIR__ . '/contabilidad/egresos_eliminar.php',
    'egresos_actualizar' => __DIR__ . '/contabilidad/egresos_actualizar.php',
    'confirmar_toma' => __DIR__ . '/cotizaciones/confirmar_toma.php',
    'buscar_cliente_accion'=>  __DIR__ . '/gestion/acciones/buscar_cliente_accion.php',
    'asociar_cliente_existente' => __DIR__ . '/gestion/acciones/asociar_cliente_existente.php',
    'pago_masivo' => __DIR__ . '/cotizaciones/pago_masivo.php',
];

$rol_actual = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action && isset($acciones[$action])) {
    if (isset($acciones_por_rol[$rol_actual]) && in_array($action, $acciones_por_rol[$rol_actual])) {
        include $acciones[$action];
        exit;
    } else {
        echo "<div class='alert alert-warning'>Acceso no permitido para el rol: $rol_actual en la acción: $action</div>";

        exit;
    }
}

include __DIR__ . '/componentes/header.php';
include __DIR__ . '/componentes/sidebar.php';

// Mostrar mensajes de éxito o error
if (isset($_SESSION['mensaje'])) {
    echo '<div class="alert alert-info">' . $_SESSION['mensaje'] . '</div>';
    unset($_SESSION['mensaje']); // Elimina el mensaje para que solo se muestre una vez
}
// Lista de vistas permitidas por rol
$acceso_por_rol = [
    'admin' => ['empresas', 'empresa', 'form_empresa', 'admin', 'usuarios', 'form_usuario', 'clientes', 'cliente', 'form_cliente', 'laboratorista', 'recepcionista', 'convenios', 'convenio', 'form_convenio', 'examenes', 'form_examen', 'cotizaciones', 'form_cotizacion', 'promociones', 'form_promocion', 'boton_cotizar', 'form_cotizacion_recepcionista', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'config_empresa_datos', 'agendar_cita', 'listado','formulario','ver', 'vista-reporte-pdf','pago_cotizacion','contabilidad', 'ingresos', 'egresos','egresos_editar','pendientes_toma','buscar_cliente','cotizaciones_empresas','cotizaciones_convenios','clientes_empresa','clientes_convenio','buscar_paciente'],

    'laboratorista' => ['laboratorista','cotizaciones','listado','formulario','ver'],
    'recepcionista' => ['recepcionista', 'cotizaciones', 'form_cotizacion', 'form_cotizacion_recepcionista', 'clientes', 'cliente', 'form_cliente', 'boton_cotizar', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'agendar_cita', 'listado','formulario','ver', 'vista-reporte-pdf','pago_cotizacion','contabilidad', 'ingresos', 'egresos', 'egresos_editar','pendientes_toma','buscar_paciente'],
    'empresa' => ['empresa', 'empresas','buscar_cliente','form_cotizacion','agendar_cita','cotizaciones_empresas','detalle_cotizacion','form_cliente','clientes_empresa','detalle_promocion'],
    'cliente' => ['clientes', 'cliente', 'cotizaciones', 'form_cotizacion', 'detalle_cotizacion', 'ver_cotizacion', 'descargar_cotizacion', 'cotizaciones_clientes', 'agendar_cita', 'detalle_promocion'],
    'convenio' => ['convenio','buscar_cliente','form_cotizacion','agendar_cita','cotizaciones_convenios','detalle_cotizacion','form_cliente','clientes_convenio','detalle_promocion']
];

$vistas = [
    'admin' => __DIR__ . '/usuarios/vistas/panel_admin.php',
    'usuarios' => __DIR__ . '/usuarios/usuarios.php',
    'form_usuario' => __DIR__ . '/usuarios/form_usuario.php',
    'clientes' => __DIR__ . '/clientes/clientes.php',
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
    'cotizaciones' => __DIR__ . '/cotizaciones/cotizaciones.php',
    'form_cotizacion' => __DIR__ . '/cotizaciones/form_cotizacion.php',
    'detalle_cotizacion' => __DIR__ . '/cotizaciones/detalle_cotizacion.php',
    'promociones' => __DIR__ . '/promociones/promociones.php',
    'form_promocion' => __DIR__ . '/promociones/form_promocion.php',
    'form_cotizacion_recepcionista' => __DIR__ . '/cotizaciones/form_cotizacion_recepcionista.php',
    'descargar_cotizacion' => __DIR__ . '/cotizaciones/descargar_cotizacion.php',
    'config_empresa_datos' => __DIR__ . '/config/config_empresa_datos.php',
    'cotizaciones_clientes' => __DIR__ . '/cotizaciones/cotizaciones_clientes.php',
    'agendar_cita' => __DIR__ . '/cotizaciones/agendar_cita.php',
    'listado' => __DIR__ . '/resultados/listado.php',
    'formulario' => __DIR__ . '/resultados/formulario.php',
    'pago_cotizacion' => __DIR__ . '/pagos/pago_cotizacion.php',
    'contabilidad' => __DIR__ . '/contabilidad/contabilidad.php',
    'ingresos' => __DIR__ . '/contabilidad/ingresos.php',
    'egresos' => __DIR__ . '/contabilidad/egresos.php',
    'egresos_editar' => __DIR__ . '/contabilidad/egresos_editar.php',
    'pendientes_toma' => __DIR__ . '/cotizaciones/pendientes_toma.php',
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
$vista = isset($_GET['vista']) ? strtolower(trim($_GET['vista'])) : '';

// Validar acceso
if ($vista && isset($vistas[$vista])) {
    if (isset($acceso_por_rol[$rol_actual]) && in_array($vista, $acceso_por_rol[$rol_actual])) {
        include $vistas[$vista];
    } else {
        echo "<div class='alert alert-warning'>Acceso no permitido para el rol: $rol_actual en la acción: $vista</div>";

    }
}



include __DIR__ . '/componentes/footer.php';

?>