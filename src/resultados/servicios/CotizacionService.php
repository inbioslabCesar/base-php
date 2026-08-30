<?php
require_once __DIR__ . '/EdadPacienteService.php';

class CotizacionService {
    private $pdo;
    private $clientesColumnCache = [];
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function hasClientesColumn(string $column): bool {
        if (array_key_exists($column, $this->clientesColumnCache)) {
            return $this->clientesColumnCache[$column];
        }
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM clientes LIKE ?");
            $stmt->execute([$column]);
            $this->clientesColumnCache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->clientesColumnCache[$column] = false;
        }
        return $this->clientesColumnCache[$column];
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
        $selectEdadReferidaValor = $this->hasClientesColumn('edad_referida_valor')
            ? 'cl.edad_referida_valor AS edad_referida_valor'
            : 'NULL AS edad_referida_valor';
        $selectEdadReferidaFecha = $this->hasClientesColumn('edad_referida_fecha')
            ? 'cl.edad_referida_fecha AS edad_referida_fecha'
            : 'NULL AS edad_referida_fecha';

        $sql = "SELECT cl.nombre, cl.apellido, cl.edad, cl.sexo, cl.dni, cl.fecha_nacimiento,
                       {$selectEdadReferidaValor}, {$selectEdadReferidaFecha}, cl.id AS cliente_id,
                       c.fecha, c.fecha_toma
                FROM cotizaciones c
                LEFT JOIN clientes cl ON c.id_cliente = cl.id
                WHERE c.id = :cotizacion_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }

        $fechaRef = '';
        if (!empty($row['fecha_toma'])) {
            $fechaRef = trim((string)$row['fecha_toma']) . ' 00:00:00';
        } elseif (!empty($row['fecha'])) {
            $fechaRef = (string)$row['fecha'];
        }

        $edad = EdadPacienteService::resolverEdadParaEvento(
            $row['fecha_nacimiento'] ?? null,
            $fechaRef,
            ($row['edad_referida_valor'] ?? null) !== null && (string)$row['edad_referida_valor'] !== ''
                ? (string)$row['edad_referida_valor']
                : ($row['edad'] ?? null),
            $row['edad_referida_fecha'] ?? null
        );

        $row['edad_valor'] = $edad['edad_valor'];
        $row['edad_texto'] = $edad['edad_texto'];
        $row['edad'] = $edad['edad_valor'];

        return $row;
    }
}
