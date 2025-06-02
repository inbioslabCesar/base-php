<?php require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
// Parámetros de paginación 
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $por_pagina;
// Búsqueda 
$busqueda = $_GET['busqueda'] ?? '';
$where = '';
$params = [];
if ($busqueda) {
    $where = "WHERE nombre LIKE :busqueda OR apellido LIKE :busqueda OR email LIKE :busqueda";
    $params[':busqueda'] = '%' . $busqueda . '%';
}
// Total de clientes para paginación 
$sql_total = "SELECT COUNT(*) FROM clientes $where";
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params);
$total_clientes = $stmt_total->fetchColumn();
$total_paginas = max(1, ceil($total_clientes / $por_pagina));
// Obtener clientes para la página actual 
$sql = "SELECT * FROM clientes $where ORDER BY id DESC LIMIT :inicio, :por_pagina";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':por_pagina', $por_pagina, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC); ?> <h2>Clientes</h2>
<form method="get" action=""> <input type="text" name="busqueda" placeholder="Buscar cliente..." value="<?php echo htmlspecialchars($busqueda); ?>"> <button type="submit">Buscar</button> </form> <br> <a href="<?php echo BASE_URL; ?>dashboard.php?vista=form_clientes"><button type="button">Agregar Cliente</button></a> <br><br>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Código Cliente</th>
        <th>Nombre</th>
        <th>Apellido</th>
        <th>Email</th>
        <th>Acciones</th>
    </tr> <?php foreach ($clientes as $cliente): ?> <tr>
            <td><?php echo htmlspecialchars($cliente['codigo_cliente'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($cliente['apellido'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></td>
            <td> <a href="<?php echo BASE_URL; ?>dashboard.php?vista=editar_cliente&id=<?php echo $cliente['id']; ?>">Editar</a> | <a href="<?php echo BASE_URL; ?>dashboard.php?vista=eliminar_cliente&id=<?php echo $cliente['id']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">Eliminar</a> </td>
        </tr> <?php endforeach; ?>
</table> <!-- Paginación -->
<div style="margin-top:10px;"> <?php if ($total_paginas > 1): ?> <?php for ($i = 1; $i <= $total_paginas; $i++): ?> <?php if ($i == $pagina): ?> <strong><?php echo $i; ?></strong> <?php else: ?> <a href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>"><?php echo $i; ?></a> <?php endif; ?> <?php endfor; ?> <?php endif; ?> </div>
<!-- Paginación -->
<div style="margin-top:20px; text-align:center;"> <?php if ($total_paginas > 1): ?> <?php for ($i = 1; $i <= $total_paginas; $i++): ?> <?php if ($i == $pagina): ?> <span style="background:#007bff; color:#fff; padding:6px 12px; border-radius:3px; margin:2px; font-weight:bold;"><?php echo $i; ?></span> <?php else: ?> <a href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>" style="background:#f2f2f2; color:#007bff; padding:6px 12px; border-radius:3px; margin:2px; text-decoration:none;"> <?php echo $i; ?> </a> <?php endif; ?> <?php endfor; ?> <?php endif; ?> </div>