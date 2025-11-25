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

    // Nuevo método para obtener datos del paciente
    public function obtenerDatosPaciente($cotizacion_id) {
        $sql = "SELECT cl.nombre, cl.apellido, cl.edad, cl.sexo, cl.dni, cl.id AS cliente_id
                FROM cotizaciones c
                LEFT JOIN clientes cl ON c.id_cliente = cl.id
                WHERE c.id = :cotizacion_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
