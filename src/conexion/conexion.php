<?php
require_once __DIR__ . '/../config/config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Puedes registrar el error en un archivo y mostrar un mensaje genérico al usuario 
    $empresaActual = isset($empresa) ? (string)$empresa : 'desconocida';
    error_log(
        'Error de conexión [' . $empresaActual . ']: ' . $e->getMessage()
        . ' | host=' . DB_HOST
        . ' | db=' . DB_NAME
        . ' | user=' . DB_USER
    );
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}