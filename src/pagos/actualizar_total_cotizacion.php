<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cotizacion = $_POST['id_cotizacion'] ?? null;
    $nuevo_total = $_POST['nuevo_total'] ?? null;
    $motivo_cambio = $_POST['motivo_cambio'] ?? '';
    
    if (!$id_cotizacion || !$nuevo_total || $nuevo_total <= 0) {
        header("Location: dashboard.php?vista=pago_cotizacion&id=$id_cotizacion&msg=error");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Obtener el total actual
        $stmt = $pdo->prepare("SELECT total FROM cotizaciones WHERE id = ?");
        $stmt->execute([$id_cotizacion]);
        $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cotizacion) {
            throw new Exception("Cotización no encontrada");
        }
        
        $total_anterior = $cotizacion['total'];
        
        // Actualizar el total de la cotización
        $stmt = $pdo->prepare("UPDATE cotizaciones SET total = ? WHERE id = ?");
        $stmt->execute([$nuevo_total, $id_cotizacion]);
        
        // Registrar el cambio en el historial de pagos como referencia
        $motivo_completo = "CAMBIO DE MONTO TOTAL: De S/ " . number_format($total_anterior, 2) . 
                          " a S/ " . number_format($nuevo_total, 2);
        if (!empty($motivo_cambio)) {
            $motivo_completo .= " - Motivo: " . $motivo_cambio;
        }
        
        // Insertar registro de auditoría en la tabla de pagos con monto 0 para indicar que es un cambio de total
        $stmt = $pdo->prepare("INSERT INTO pagos (id_cotizacion, monto, metodo_pago, fecha, observaciones) 
                              VALUES (?, 0, 'cambio_total', NOW(), ?)");
        $stmt->execute([$id_cotizacion, $motivo_completo]);

        // Recalcular estado de pago según el nuevo total y pagos acumulados
        $stmtPagos = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = ?");
        $stmtPagos->execute([$id_cotizacion]);
        $totalPagado = floatval($stmtPagos->fetchColumn());
        $nuevoEstado = ($totalPagado >= $nuevo_total) ? 'pagado' : (($totalPagado > 0) ? 'abonado' : 'pendiente');
        $stmtUpdEstado = $pdo->prepare("UPDATE cotizaciones SET estado_pago = ? WHERE id = ?");
        $stmtUpdEstado->execute([$nuevoEstado, $id_cotizacion]);
        
        $pdo->commit();
        
        header("Location: dashboard.php?vista=pago_cotizacion&id=$id_cotizacion&msg=total_updated");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al actualizar total de cotización: " . $e->getMessage());
        header("Location: dashboard.php?vista=pago_cotizacion&id=$id_cotizacion&msg=error");
        exit;
    }
} else {
    header("Location: dashboard.php?vista=cotizaciones");
    exit;
}
?>