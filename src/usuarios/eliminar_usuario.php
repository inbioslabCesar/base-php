<?php require_once __DIR__ . '/../clases/Crud.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$id = $_GET['id'] ?? null;
$crud = new Crud($pdo, 'usuarios');
if ($id) {
    $crud->eliminar($id);
}
// Redirige de vuelta a la tabla de usuarios 
header('Location: ' . BASE_URL . 'dashboard.php?vista=tabla_usuarios');
exit();
