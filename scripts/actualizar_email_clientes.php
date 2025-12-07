<?php
// Script para actualizar todos los emails de clientes que tengan '@medditech.com' a '@inbioslab.com'
require_once __DIR__ . '/../src/conexion/conexion.php';

try {
    $sql = "UPDATE clientes SET email = CONCAT(SUBSTRING_INDEX(email, '@', 1), '@inbioslab.com') WHERE email LIKE '%@medditech.com'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "Correos actualizados correctamente. Total afectados: " . $stmt->rowCount();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
