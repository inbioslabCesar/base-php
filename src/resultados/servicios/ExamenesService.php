<?php
class ExamenesService {
    private $pdo;
    private $hasSnapshotCol = null;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function hasSnapshotColumn() {
        if ($this->hasSnapshotCol !== null) {
            return $this->hasSnapshotCol;
        }
        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $this->hasSnapshotCol = !empty($col);
        } catch (Exception $e) {
            $this->hasSnapshotCol = false;
        }
        return $this->hasSnapshotCol;
    }

    public function obtenerExamenesPorCotizacion($cotizacion_id) {
        if ($this->hasSnapshotColumn()) {
            $sql = "SELECT re.id as id_resultado, re.resultados,
                           COALESCE(re.adicional_snapshot, e.adicional) AS adicional,
                           e.nombre as nombre_examen
                    FROM resultados_examenes re
                    JOIN examenes e ON re.id_examen = e.id
                    WHERE re.id_cotizacion = :cotizacion_id";
        } else {
            $sql = "SELECT re.id as id_resultado, re.resultados,
                           e.adicional AS adicional,
                           e.nombre as nombre_examen
                    FROM resultados_examenes re
                    JOIN examenes e ON re.id_examen = e.id
                    WHERE re.id_cotizacion = :cotizacion_id";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAreasDisponibles() {
        $sql = "SELECT DISTINCT area FROM examenes WHERE area IS NOT NULL AND TRIM(area) <> '' ORDER BY area";
        $stmt = $this->pdo->query($sql);
        $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Reflejar exactamente lo guardado en BD (respetar mayúsculas/minúsculas del CRUD).
        $out = [];
        foreach ($areas as $a) {
            $a = trim((string) $a);
            if ($a === '') continue;
            $out[] = $a;
        }
        return $out;
    }
}
