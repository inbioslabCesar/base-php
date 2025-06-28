<?php
if (isset($_POST['adicional'])) {
    $parametros = json_decode($_POST['adicional'], true);
    echo "<h2>Datos recibidos:</h2>";
    echo "<ul>";
    foreach ($parametros as $fila) {
        echo "<li>Nombre: " . htmlspecialchars($fila['nombre']) . " | Valor: " . htmlspecialchars($fila['valor']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "No se recibieron datos.";
}
?>
