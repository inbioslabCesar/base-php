<?php
require_once __DIR__ . '/../../conexion/conexion.php';

function convenios_count($search = '') {
    global $pdo;
    if ($search !== '') {
        $sql = "SELECT COUNT(*) FROM convenios WHERE nombre LIKE ? OR dni LIKE ? OR especialidad LIKE ? OR email LIKE ?";
        $stmt = $pdo->prepare($sql);
        $searchLike = "%$search%";
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike]);
        return (int)$stmt->fetchColumn();
    } else {
        $sql = "SELECT COUNT(*) FROM convenios";
        return (int)$pdo->query($sql)->fetchColumn();
    }
}

function convenios_listar($orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','dni','especialidad','descuento','descripcion','email']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM convenios ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function convenios_buscar($search, $orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','dni','especialidad','descuento','descripcion','email']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM convenios WHERE nombre LIKE :search OR dni LIKE :search OR especialidad LIKE :search OR email LIKE :search ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $searchLike = "%$search%";
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
