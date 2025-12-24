<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$examen_detalle = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM examenes WHERE id = ?');
    $stmt->execute([$id]);
    $examen_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$examen_detalle) {
    echo '<div class="modal-header"><h5 class="modal-title">Detalle del Examen</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>';
    echo '<div class="modal-body"><div class="alert alert-warning">No se encontró información del examen.</div></div>';
    echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>';
    exit;
}

// Renderiza el contenido del modal usando la vista parcial existente
include __DIR__ . '/vistas/detalle_examen.php';
