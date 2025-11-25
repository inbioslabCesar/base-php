<?php
// Script para agregar edad_min y edad_max a todas las referencias de parámetros en examenes
require_once __DIR__ . '/../src/conexion/conexion.php'; // Ajusta la ruta si es necesario

$sql = "SELECT id, adicional FROM examenes WHERE adicional IS NOT NULL AND adicional != ''";
$stmt = $pdo->query($sql);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados as $row) {
    $id = $row['id'];
    $adicional = $row['adicional'];
    $parametros = json_decode($adicional, true);
    $actualizado = false;

    if (is_array($parametros)) {
        foreach ($parametros as &$param) {
            // Asigna edad_min y edad_max a cada referencia
            if (isset($param['referencias']) && is_array($param['referencias'])) {
                foreach ($param['referencias'] as &$ref) {
                    if (
                        !isset($ref['edad_min']) || $ref['edad_min'] === '' ||
                        !isset($ref['edad_max']) || $ref['edad_max'] === '' ||
                        $ref['edad_min'] != 17 || $ref['edad_max'] != 75
                    ) {
                        $ref['edad_min'] = 17;
                        $ref['edad_max'] = 75;
                        $actualizado = true;
                    }
                }
                unset($ref);
            }
        }
        unset($param);

        if ($actualizado) {
            $nuevoAdicional = json_encode($parametros, JSON_UNESCAPED_UNICODE);
            $updateSql = "UPDATE examenes SET adicional = :adicional WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':adicional' => $nuevoAdicional,
                ':id' => $id
            ]);
            echo "Examen $id actualizado.\n";
        }
    }
}

echo "Proceso finalizado.\n";
?>
