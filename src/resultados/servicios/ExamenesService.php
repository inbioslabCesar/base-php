<?php
class ExamenesService {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function obtenerExamenesPorCotizacion($cotizacion_id) {
        $sql = "SELECT re.id as id_resultado, re.resultados, e.adicional, e.nombre as nombre_examen
                FROM resultados_examenes re
                JOIN examenes e ON re.id_examen = e.id
                WHERE re.id_cotizacion = :cotizacion_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
