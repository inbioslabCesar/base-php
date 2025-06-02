<?php session_start();
// Si no está logueado, redirige al login con mensaje 
if (!isset($_SESSION['usuario'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión primero.';
    header('Location: login.php');
    exit();
}
// Función para verificar roles permitidos 
function verificarRol($rolesPermitidos = [])
{
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        header('Location: ../no_autorizado.php');
        exit();
    }
}
