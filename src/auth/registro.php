<?php session_start();
$_SESSION['usuario'] = 'Cesar';
echo "Usuario guardado en la sesión."; ?> 



<?php session_start();
if (isset($_SESSION['usuario'])) {
    echo "Bienvenido, " . $_SESSION['usuario'];
} else {
    echo "No has iniciado sesión.";
} ?> 



<?php session_start(); session_unset(); 
// Borra todas las variables de sesión 
session_destroy(); 
// Destruye la sesión 
echo "Sesión cerrada."; ?> 