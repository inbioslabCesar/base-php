<?php
class CotizacionService {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function obtenerReferenciaPersonalizada($cotizacion_id) {
        $sql = "SELECT referencia_personalizada FROM cotizaciones WHERE id = :cotizacion_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        $cotizacion_data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cotizacion_data['referencia_personalizada'] ?? '';
    }
}
