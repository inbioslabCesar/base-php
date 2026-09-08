<?php
// Configuracion de seguridad para QR de resultados (EJEMPLO)
// Copia este archivo a `qr_verificacion_config.php` y define una clave larga y unica.
// Recomendado: 32+ caracteres aleatorios.
// Rotacion segura:
// - RESULTADOS_QR_SECRET_PREVIOUS: clave anterior (opcional)
// - RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL: fin de ventana para aceptar la clave anterior
//   Puede ser timestamp Unix (ej. 1788883200) o fecha parseable por strtotime (ej. 2026-10-15 23:59:59).

if (!defined('RESULTADOS_QR_SECRET')) {
    define('RESULTADOS_QR_SECRET', 'CAMBIAR_ESTE_SECRETO_LARGO_Y_UNICO_2026');
}

if (!defined('RESULTADOS_QR_SECRET_PREVIOUS')) {
    define('RESULTADOS_QR_SECRET_PREVIOUS', '');
}

if (!defined('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL')) {
    define('RESULTADOS_QR_SECRET_PREVIOUS_VALID_UNTIL', '');
}
