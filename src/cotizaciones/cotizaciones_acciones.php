<?php
// Este componente muestra los botones de acciones para cada cotización
// Requiere: $cotizacion, $rol, $saldo
?>
<?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
    <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-sm mb-1" title="Ver cotización">
        <i class="bi bi-eye"></i>
    </a>
<?php endif; ?>
<?php if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista'): ?>
    <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-sm mb-1" title="Editar o agregar resultados">
        <i class="bi bi-pencil-square"></i>
    </a>
<?php endif; ?>
<?php
// Mostrar botón según estado de pago
if (($rol === 'admin' || $rol === 'recepcionista')) {
    if ($saldo <= 0) {
        // Pagado: mostrar botón gris historial
        echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-secondary btn-sm mb-1" title="Ver historial de pagos"><i class="bi bi-clock-history"></i></a>';
    } else {
        // Pendiente o parcial: mostrar botón amarillo registrar pago
        echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-warning btn-sm mb-1" title="Registrar pago"><i class="bi bi-cash-coin"></i></a>';
    }
}
?>
<?php if ($rol === 'admin'): ?>
    <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-sm mb-1" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
        <i class="bi bi-trash"></i>
    </a>
<?php endif; ?>
<?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
    <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-1" title="Descargar PDF de todos los resultados" target="_blank">
        <i class="bi bi-file-earmark-pdf"></i>
    </a>
<?php endif; ?>
