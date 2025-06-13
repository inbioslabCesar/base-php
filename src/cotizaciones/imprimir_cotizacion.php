<?php
// imprimir_cotizacion.php

session_start();
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos de la cotización
$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->execute([$id]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    echo "<p>Cotización no encontrada.</p>";
    exit;
}

// Obtener detalles de la cotización
$stmt_det = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = ?");
$stmt_det->execute([$id]);
$detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Cotización <?php echo htmlspecialchars($cotizacion['codigo']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { text-align: center; }
        .info, .detalle { width: 100%; margin-bottom: 20px; }
        .info td { padding: 4px 8px; }
        .detalle th, .detalle td { border: 1px solid #ccc; padding: 8px; }
        .detalle { border-collapse: collapse; }
        .total { text-align: right; font-weight: bold; }
        @media print {
            .noprint { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="noprint" style="text-align:right;">
        <button onclick="window.print()">Imprimir</button>
        <a href="javascript:history.back()">Volver</a>
    </div>
    <h1>Cotización <?php echo htmlspecialchars($cotizacion['codigo']); ?></h1>
    <table class="info">
        <tr>
            <td><strong>Fecha:</strong></td>
            <td><?php echo htmlspecialchars($cotizacion['fecha']); ?></td>
        </tr>
        <tr>
            <td><strong>Estado de Pago:</strong></td>
            <td><?php echo htmlspecialchars($cotizacion['estado_pago']); ?></td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td>S/ <?php echo number_format($cotizacion['total'], 2); ?></td>
        </tr>
        <?php if (!empty($cotizacion['observaciones'])): ?>
        <tr>
            <td><strong>Observaciones:</strong></td>
            <td><?php echo htmlspecialchars($cotizacion['observaciones']); ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <h2>Detalles</h2>
    <table class="detalle">
        <thead>
            <tr>
                <th>Examen</th>
                <th>Precio Unitario</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nombre_examen']); ?></td>
                <td>S/ <?php echo number_format($item['precio_unitario'], 2); ?></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td>S/ <?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="total">Total: S/ <?php echo number_format($cotizacion['total'], 2); ?></p>
</body>
</html>
