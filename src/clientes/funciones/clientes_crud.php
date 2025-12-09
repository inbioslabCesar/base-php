<?php
require_once __DIR__ . '/../../conexion/conexion.php';

function clientes_count($search = '') {
    global $pdo;
    if ($search !== '') {
        $sql = "SELECT COUNT(*) FROM clientes WHERE dni LIKE ? OR nombre LIKE ? OR apellido LIKE ?";
        $stmt = $pdo->prepare($sql);
        $searchLike = "%$search%";
        $stmt->execute([$searchLike, $searchLike, $searchLike]);
        return (int)$stmt->fetchColumn();
    } else {
        $sql = "SELECT COUNT(*) FROM clientes";
        return (int)$pdo->query($sql)->fetchColumn();
    }
}

function clientes_listar($orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','codigo_cliente','nombre','apellido','dni','edad','email','telefono','estado','fecha_registro']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM clientes ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clientes_buscar($search, $orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','codigo_cliente','nombre','apellido','dni','edad','email','telefono','estado','fecha_registro']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM clientes WHERE dni LIKE :search OR nombre LIKE :search OR apellido LIKE :search ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $searchLike = "%$search%";
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
