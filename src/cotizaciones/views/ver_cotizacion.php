<?php
require_once __DIR__ . '/../../conexion/conexion.php';

// Recibe id de cotización y rol del usuario
$id_cotizacion = $_GET['id'] ?? $_POST['id'] ?? null;
$rol = $_GET['rol'] ?? $_POST['rol'] ?? '';

// Validación básica
if (!$id_cotizacion) {
    echo "No se recibió el ID de la cotización.";
    exit;
}

// Trae los detalles de la cotización
$stmt = $pdo->prepare("
    SELECT cd.*, e.nombre AS nombre_examen, e.preanalitica_cliente
    FROM cotizaciones_detalle cd
    LEFT JOIN examenes e ON cd.id_examen = e.id
    WHERE cd.id_cotizacion = ?
");
$stmt->execute([$id_cotizacion]);
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Opcional: puedes traer aquí datos de la cotización principal o cliente si lo deseas

$total = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Cotización</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f5f5f5; }
        .total { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
<h2>Resumen de Cotización</h2>
<p><strong>ID de Cotización:</strong> <?php echo htmlspecialchars($id_cotizacion); ?></p>
<p><strong>Rol del usuario:</strong> <?php echo htmlspecialchars($rol); ?></p>

<table>
    <tr>
        <th>Examen</th>
        <th>Condiciones</th>
        <th>Precio Unit</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
    </tr>
    <?php foreach ($examenes as $examen): 
        $precio = isset($examen['precio_unitario']) ? floatval($examen['precio_unitario']) : 0;
        $cantidad = isset($examen['cantidad']) ? intval($examen['cantidad']) : 0;
        $subtotal = $precio * $cantidad;
        $total += $subtotal;
    ?>
    <tr>
        <td><?php echo htmlspecialchars($examen['nombre_examen']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($examen['preanalitica_cliente'])); ?></td>
        <td><?php echo number_format($precio, 2); ?></td>
        <td><?php echo $cantidad; ?></td>
        <td><?php echo number_format($subtotal, 2); ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="4" class="total">Total</td>
        <td class="total"><?php echo number_format($total, 2); ?></td>
    </tr>
</table>

<!-- Opcional: puedes mostrar botones o enlaces según el rol -->
 <?php
$rol = strtolower(trim($rol)); // Normaliza el rol
if ($rol == 'cliente'): ?>
    <a href="dashboard.php?vista=cotizaciones_clientes" class="btn btn-secondary">Volver</a>
<?php elseif ($rol == 'recepcionista'): ?>
    <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary">Volver</a>
<?php else: ?>
    <a href="dashboard.php" class="btn btn-secondary">Volver</a>
<?php endif; ?>

</body>
</html>
