<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adicional = $_POST['adicional'] ?? '';
    echo "<pre>Valor recibido:<br>" . htmlspecialchars($adicional) . "</pre>";

    // Validar JSON
    json_decode($adicional);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p style='color:red;'>El formato de parámetros adicionales no es válido.</p>";
    } else {
        echo "<p style='color:green;'>¡JSON válido!</p>";
    }
}
?>
<form method="post">
    <input type="hidden" id="adicional" name="adicional">
    <button type="button" onclick="llenarYEnviar()">Probar guardar</button>
</form>
<script>
function llenarYEnviar() {
    // Sólo un parámetro de ejemplo
    const datos = [
        {
            tipo: "color",
            opciones: ["amarillo"]
        }
    ];
    document.getElementById('adicional').value = JSON.stringify(datos);
    document.forms[0].submit();
}
</script>
