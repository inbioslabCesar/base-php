<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT id, nombre, apellido, email, rol, estado FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Usuarios</h2>
<a href="<?= BASE_URL ?>dashboard.php?vista=crear_usuario" style="display:inline-block;padding:8px 16px;background:#28a745;color:#fff;text-decoration:none;border-radius:4px;margin-bottom:10px;">Agregar Usuario</a>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Apellido</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($usuarios as $usuario): ?>
    <tr>
        <td><?= htmlspecialchars($usuario['id']) ?></td>
        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
        <td><?= htmlspecialchars($usuario['apellido']) ?></td>
        <td><?= htmlspecialchars($usuario['email']) ?></td>
        <td><?= htmlspecialchars($usuario['rol']) ?></td>
        <td><?= htmlspecialchars($usuario['estado']) ?></td>
        <td>
            <a href="<?= BASE_URL ?>dashboard.php?vista=editar_usuario&id=<?= $usuario['id'] ?>">Editar</a> |
            <a href="<?= BASE_URL ?>dashboard.php?vista=eliminar_usuario&id=<?= $usuario['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">Eliminar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
