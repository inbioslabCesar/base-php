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
                        $valor = trim($ref['valor']);
                        // Quitar paréntesis si existen
                        if (preg_match('/^\((.*)\)$/', $valor, $matches)) {
                            $valor = trim($matches[1]);
                        }
                        $nuevo_min = null;
                        $nuevo_max = null;
                        // Rango min-max
                        if (preg_match('/^([\d.,]+)\s*-\s*([\d.,]+)$/', $valor, $m)) {
                            $nuevo_min = str_replace(',', '.', $m[1]);
                            $nuevo_max = str_replace(',', '.', $m[2]);
                        }
                        // <N
                        elseif (preg_match('/^<\s*([\d.,]+)/', $valor, $m)) {
                            $nuevo_min = 0;
                            $nuevo_max = str_replace(',', '.', $m[1]);
                            // Si es entero, restar 1
                            if (is_numeric($nuevo_max) && strpos($nuevo_max, '.') === false) {
                                $nuevo_max = (string)($nuevo_max - 1);
                            }
                        }
                        // <=N o ≤N
                        elseif (preg_match('/^(<=|≤)\s*([\d.,]+)/u', $valor, $m)) {
                            $nuevo_min = 0;
                            $nuevo_max = str_replace(',', '.', $m[2]);
                        }
                        // >N
                        elseif (preg_match('/^>\s*([\d.,]+)/', $valor, $m)) {
                            $nuevo_min = str_replace(',', '.', $m[1]);
                            // Si es entero, sumar 1
                            if (is_numeric($nuevo_min) && strpos($nuevo_min, '.') === false) {
                                $nuevo_min = (string)($nuevo_min + 1);
                            }
                            $nuevo_max = '999';
                        }
                        // >=N o ≥N
                        elseif (preg_match('/^(>=|≥)\s*([\d.,]+)/u', $valor, $m)) {
                            $nuevo_min = str_replace(',', '.', $m[2]);
                            $nuevo_max = '999';
                        }
                        // Si no es ninguno de los anteriores, poner el valor en ambos
                        else {
                            $nuevo_min = $valor;
                            $nuevo_max = $valor;
                        }
                        if ($ref['valor_min'] != $nuevo_min) {
                            $ref['valor_min'] = $nuevo_min;
                            $actualizado = true;
                        }
                        if ($ref['valor_max'] != $nuevo_max) {
                            $ref['valor_max'] = $nuevo_max;
                            $actualizado = true;
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
