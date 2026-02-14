<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id_cotizacion']) ? intval($_GET['id_cotizacion']) : 0;
if ($id <= 0) {
    echo '<div class="alert alert-warning">ID de cotización inválido.</div>';
    return;
}

try {
    // Obtener cabecera de cotización (opcional)
    $stmtCot = $pdo->prepare("SELECT c.id, c.codigo, c.total, CONCAT(cl.nombre, ' ', cl.apellido) AS cliente_nombre
                               FROM cotizaciones c
                               LEFT JOIN clientes cl ON cl.id = c.id_cliente
                               WHERE c.id = ?");
    $stmtCot->execute([$id]);
    $cot = $stmtCot->fetch(PDO::FETCH_ASSOC);

    // Obtener detalle de exámenes
    $stmtDet = $pdo->prepare("SELECT nombre_examen, cantidad, precio_unitario, subtotal
                               FROM cotizaciones_detalle
                               WHERE id_cotizacion = ?");
    $stmtDet->execute([$id]);
    $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    if (!$cot) {
        echo '<div class="alert alert-danger">No se encontró la cotización.</div>';
        return;
    }

    echo '<div class="mb-2"><strong>Cotización:</strong> ' . htmlspecialchars($cot['codigo'] ?? ('#' . $cot['id'])) . ' · <strong>Cliente:</strong> ' . htmlspecialchars($cot['cliente_nombre'] ?? '—') . '</div>';

    if (!$detalles) {
        echo '<div class="alert alert-info">No hay exámenes registrados en esta cotización.</div>';
        return;
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Examen</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio Unitario</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>';

    $total = 0.0;
    foreach ($detalles as $d) {
        $nombre = htmlspecialchars($d['nombre_examen']);
        $cantidad = (int)($d['cantidad'] ?? 1);
        $pu = (float)($d['precio_unitario'] ?? 0);
        $sub = (float)($d['subtotal'] ?? ($cantidad * $pu));
        $total += $sub;
        echo '<tr>';
        echo '<td>' . $nombre . '</td>';
        echo '<td class="text-end">' . number_format($cantidad) . '</td>';
        echo '<td class="text-end">' . number_format($pu, 2) . '</td>';
        echo '<td class="text-end">' . number_format($sub, 2) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>
          <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">' . number_format($total, 2) . '</th>
            </tr>
          </tfoot>
        </table>';
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar el detalle: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
