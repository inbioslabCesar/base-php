<?php
// ... (tu bloque de consulta ya corregido para $cotizaciones)

// Ejemplo de tabla:
?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Rol Creador</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($cotizaciones)): ?>
        <?php foreach ($cotizaciones as $cotizacion): ?>
            <tr>
                <td><?php echo isset($cotizacion['codigo']) ? htmlspecialchars($cotizacion['codigo']) : ''; ?></td>
                <td>
                    <?php
                    // Si solo tienes id_cliente, muestra el ID o haz una consulta para traer el nombre real
                    echo isset($cotizacion['id_cliente']) ? htmlspecialchars($cotizacion['id_cliente']) : 'Sin cliente';
                    ?>
                </td>
                <td><?php echo isset($cotizacion['fecha']) ? htmlspecialchars($cotizacion['fecha']) : ''; ?></td>
                <td><?php echo isset($cotizacion['total']) ? number_format($cotizacion['total'], 2) : '0.00'; ?></td>
                <td><?php echo isset($cotizacion['estado_pago']) ? htmlspecialchars($cotizacion['estado_pago']) : ''; ?></td>
                <td>
                    <?php
                    $rol = isset($cotizacion['rol_creador']) && $cotizacion['rol_creador'] !== null
                        ? strtolower($cotizacion['rol_creador'])
                        : '';
                    echo htmlspecialchars($rol);
                    ?>
                </td>
                <td>
                    <!-- Aquí tus botones de acciones (ver, editar, eliminar, etc.) -->
                    <a href="dashboard.php?vista=ver_cotizacion&id=<?php echo $cotizacion['id']; ?>" class="btn btn-info btn-sm">Ver</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center">No hay cotizaciones registradas.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
