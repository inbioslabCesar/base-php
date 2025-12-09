<?php
require_once __DIR__ . '/../../conexion/conexion.php';

function empresas_count($search = '') {
    global $pdo;
    if ($search !== '') {
        $sql = "SELECT COUNT(*) FROM empresas WHERE ruc LIKE ? OR direccion LIKE ? OR telefono LIKE ? OR email LIKE ? OR razon_social LIKE ? OR nombre_comercial LIKE ? OR representante LIKE ? OR convenio LIKE ? OR descuento LIKE ?";
        $stmt = $pdo->prepare($sql);
        $searchLike = "%$search%";
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
        return (int)$stmt->fetchColumn();
    } else {
        $sql = "SELECT COUNT(*) FROM empresas";
        return (int)$pdo->query($sql)->fetchColumn();
    }
}

function empresas_listar($orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','ruc','razon_social','nombre_comercial','direccion','telefono','email','representante','convenio','estado','descuento']) ? $orderBy : 'id';
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
    $orderBy = in_array($orderBy, ['id','ruc','razon_social','nombre_comercial','direccion','telefono','email','representante','convenio','estado','descuento']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM empresas WHERE ruc LIKE :search OR direccion LIKE :search OR telefono LIKE :search OR email LIKE :search OR razon_social LIKE :search OR nombre_comercial LIKE :search OR representante LIKE :search OR convenio LIKE :search OR descuento LIKE :search ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $searchLike = "%$search%";
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
