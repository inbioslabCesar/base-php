<?php
// Script para actualizar parámetros antiguos y agregar id_parametro único
require_once __DIR__ . '/../src/conexion/conexion.php'; // Ajusta la ruta si es necesario

function generarIdParametro() {
    return 'param_' . time() . '_' . rand(100000, 999999);
}

// Reemplaza 'examenes' y 'adicional' por el nombre real de tu tabla y columna
// Cambiar a uso de PDO
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
            if (!isset($param['id_parametro']) || !$param['id_parametro']) {
                $param['id_parametro'] = generarIdParametro();
                $actualizado = true;
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
