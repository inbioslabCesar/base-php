<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../conexion/conexion.php';

// Obtener todos los convenios
function obtenerTodosLosConvenios($pdo) {
    $sql = "SELECT * FROM convenios";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener un convenio por ID
function obtenerConvenioPorId($pdo, $id) {
    $sql = "SELECT * FROM convenios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Registrar un nuevo convenio
function registrarConvenio($pdo, $data) {
    $sql = "INSERT INTO convenios (nombre, dni, especialidad, descuento, descripcion) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['dni'],
        $data['especialidad'] ?? null,
        $data['descuento'] === '' ? null : $data['descuento'],
        $data['descripcion'] ?? null
    ]);
}

// Actualizar un convenio existente
function actualizarConvenio($pdo, $id, $data) {
    $sql = "UPDATE convenios SET nombre=?, dni=?, especialidad=?, descuento=?, descripcion=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nombre'],
        $data['dni'],
        $data['especialidad'] ?? null,
        $data['descuento'] === '' ? null : $data['descuento'],
        $data['descripcion'] ?? null,
        $id
    ]);
}

// Eliminar un convenio
function eliminarConvenio($pdo, $id) {
    $sql = "DELETE FROM convenios WHERE id=?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}
?>
