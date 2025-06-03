<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT id, ruc, razon_social, nombre_comercial, direccion, telefono, email, representante, convenio, estado FROM empresas");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Empresas</h2>
<a href="<?= BASE_URL ?>dashboard.php?vista=crear_empresa" style="display:inline-block;padding:8px 16px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;margin-bottom:10px;">Agregar Empresa</a>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>RUC</th>
        <th>Razón Social</th>
        <th>Nombre Comercial</th>
        <th>Dirección</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Representante</th>
        <th>Convenio</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($empresas as $empresa): ?>
    <tr>
        <td><?= htmlspecialchars($empresa['id']) ?></td>
        <td><?= htmlspecialchars($empresa['ruc']) ?></td>
        <td><?= htmlspecialchars($empresa['razon_social']) ?></td>
        <td><?= htmlspecialchars($empresa['nombre_comercial']) ?></td>
        <td><?= htmlspecialchars($empresa['direccion']) ?></td>
        <td><?= htmlspecialchars($empresa['telefono']) ?></td>
        <td><?= htmlspecialchars($empresa['email']) ?></td>
        <td><?= htmlspecialchars($empresa['representante']) ?></td>
        <td><?= htmlspecialchars($empresa['convenio']) ?></td>
        <td><?= htmlspecialchars($empresa['estado']) ?></td>
        <td>
            <a href="<?= BASE_URL ?>dashboard.php?vista=editar_empresa&id=<?= $empresa['id'] ?>">Editar</a> |
            <a href="<?= BASE_URL ?>dashboard.php?vista=eliminar_empresa&id=<?= $empresa['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar esta empresa?')">Eliminar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
