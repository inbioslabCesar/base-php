<?php
class ExamenesService {
    private $pdo;
    private $hasSnapshotCol = null;
    private $alarmCols = null;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function hasSnapshotColumn() {
        if ($this->hasSnapshotCol !== null) {
            return $this->hasSnapshotCol;
        }
        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(\PDO::FETCH_ASSOC);
            $this->hasSnapshotCol = !empty($col);
        } catch (\Exception $e) {
            $this->hasSnapshotCol = false;
        }
        return $this->hasSnapshotCol;
    }

    private function getAlarmColumnMap() {
        if ($this->alarmCols !== null) {
            return $this->alarmCols;
        }

        $cols = [
            'alarma_activa' => false,
            'alarma_dias' => false,
            'alarma_fecha_objetivo' => false,
            'alarma_estado' => false,
            'alarma_ultimo_aviso' => false,
            'alarma_whatsapp_destino' => false,
        ];

        try {
            $stmt = $this->pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resultados_examenes'");
            $dbCols = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($cols as $key => $_) {
                $cols[$key] = in_array($key, $dbCols, true);
            }
        } catch (\Exception $e) {
        }

        $this->alarmCols = $cols;
        return $this->alarmCols;
    }

    public function obtenerExamenesPorCotizacion($cotizacion_id) {
        $alarmCols = $this->getAlarmColumnMap();
        $selectAlarmaActiva = $alarmCols['alarma_activa'] ? 're.alarma_activa' : '0 AS alarma_activa';
        $selectAlarmaDias = $alarmCols['alarma_dias'] ? 're.alarma_dias' : 'NULL AS alarma_dias';
        $selectAlarmaFechaObjetivo = $alarmCols['alarma_fecha_objetivo'] ? 're.alarma_fecha_objetivo' : 'NULL AS alarma_fecha_objetivo';
        $selectAlarmaEstado = $alarmCols['alarma_estado'] ? 're.alarma_estado' : 'NULL AS alarma_estado';

        if ($this->hasSnapshotColumn()) {
            $sql = "SELECT re.id as id_resultado, re.id_examen, re.resultados,
                           COALESCE(re.adicional_snapshot, e.adicional) AS adicional,
                           CASE WHEN EXISTS (
                               SELECT 1 FROM inventario_examen_recetas r
                               WHERE r.id_examen = re.id_examen AND r.activo = 1
                           ) THEN 1 ELSE 0 END AS has_receta,
                           e.nombre as nombre_examen,
                           e.tiempo_respuesta,
                           {$selectAlarmaActiva},
                           {$selectAlarmaDias},
                           {$selectAlarmaFechaObjetivo},
                           {$selectAlarmaEstado}
                    FROM resultados_examenes re
                    JOIN examenes e ON re.id_examen = e.id
                    WHERE re.id_cotizacion = :cotizacion_id";
        } else {
            $sql = "SELECT re.id as id_resultado, re.id_examen, re.resultados,
                           e.adicional AS adicional,
                           CASE WHEN EXISTS (
                               SELECT 1 FROM inventario_examen_recetas r
                               WHERE r.id_examen = re.id_examen AND r.activo = 1
                           ) THEN 1 ELSE 0 END AS has_receta,
                           e.nombre as nombre_examen,
                           e.tiempo_respuesta,
                           {$selectAlarmaActiva},
                           {$selectAlarmaDias},
                           {$selectAlarmaFechaObjetivo},
                           {$selectAlarmaEstado}
                    FROM resultados_examenes re
                    JOIN examenes e ON re.id_examen = e.id
                    WHERE re.id_cotizacion = :cotizacion_id";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerAreasDisponibles() {
        $sql = "SELECT DISTINCT area FROM examenes WHERE area IS NOT NULL AND TRIM(area) <> '' ORDER BY area";
        $stmt = $this->pdo->query($sql);
        $areas = $stmt->fetchAll(\PDO::FETCH_COLUMN);

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
