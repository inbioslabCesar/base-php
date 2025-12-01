<?php
// Script para copiar valor en valor_min y valor_max si están vacíos en referencias de parámetros
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
            if (isset($param['referencias']) && is_array($param['referencias'])) {
                foreach ($param['referencias'] as &$ref) {
                    if (isset($ref['valor']) && $ref['valor'] !== '') {
                        // Si el valor tiene formato "min-max"
                        if (strpos($ref['valor'], '-') !== false) {
                            list($min, $max) = explode('-', $ref['valor'], 2);
                            $min = trim($min);
                            $max = trim($max);
                            if ($ref['valor_min'] != $min || $ref['valor_max'] != $max) {
                                $ref['valor_min'] = $min;
                                $ref['valor_max'] = $max;
                                $actualizado = true;
                            }
                        } else {
                            // Si solo hay un valor, lo pone en ambos si son diferentes
                            if ($ref['valor_min'] != $ref['valor']) {
                                $ref['valor_min'] = $ref['valor'];
                                $actualizado = true;
                            }
                            if ($ref['valor_max'] != $ref['valor']) {
                                $ref['valor_max'] = $ref['valor'];
                                $actualizado = true;
                            }
                        }
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
