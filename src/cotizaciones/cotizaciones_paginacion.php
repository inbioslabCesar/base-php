<?php
// Lógica de paginación para cotizaciones
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = 3;
$total_cotizaciones = count($cotizaciones);
$total_paginas = ceil($total_cotizaciones / $por_pagina);
$inicio = ($pagina - 1) * $por_pagina;
$cotizaciones_pagina = array_slice($cotizaciones, $inicio, $por_pagina);
