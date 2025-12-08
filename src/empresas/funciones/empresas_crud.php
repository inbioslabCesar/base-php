<?php
require_once __DIR__ . '/../../conexion/conexion.php';

function empresas_count($search = '') {
    global $pdo;
    if ($search !== '') {
        $sql = "SELECT COUNT(*) FROM empresas WHERE nombre LIKE ? OR ruc LIKE ? OR direccion LIKE ? OR telefono LIKE ? OR email LIKE ?";
        $stmt = $pdo->prepare($sql);
        $searchLike = "%$search%";
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
        return (int)$stmt->fetchColumn();
    } else {
        $sql = "SELECT COUNT(*) FROM empresas";
        return (int)$pdo->query($sql)->fetchColumn();
    }
}

function empresas_listar($orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','ruc','direccion','telefono','email','estado','fecha_creacion']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM empresas ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function empresas_buscar($search, $orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','ruc','direccion','telefono','email','estado','fecha_creacion']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM empresas WHERE nombre LIKE :search OR ruc LIKE :search OR direccion LIKE :search OR telefono LIKE :search OR email LIKE :search ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $searchLike = "%$search%";
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
