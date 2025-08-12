<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

// Suponiendo que tienes el rol en sesión:
$rol = $_SESSION['rol'] ?? 'clientes'; // Ejemplo: 'admin', 'recepcionista', 'clientes', 'convenios'

// Si eres admin o recepcionista, ves todas las promociones
if ($rol === 'admin' || $rol === 'recepcionista') {
    $stmt = $pdo->query("SELECT * FROM promociones ORDER BY id DESC");
} else {
    // Si eres clientes/convenios/empresas, ves solo las que te corresponden o son para todos
    $tipo_usuario = $rol; // Ajusta según tu lógica si el rol no es igual al tipo_publico
    $stmt = $pdo->prepare("SELECT * FROM promociones WHERE tipo_publico = 'todos' OR tipo_publico = ? ORDER BY id DESC");
    $stmt->execute([$tipo_usuario]);
}
$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Promociones</h3>
        <a href="<?= BASE_URL ?>dashboard.php?vista=form_promocion" class="btn btn-success">Nueva Promoción</a>
    </div>
    <div class="table-responsive">
        <table id="tablaPromociones" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Imagen</th>
                    <th>Precio</th>
                    <th>Descuento</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Activo</th>
                    <th>Vigente</th>
                    <th>Visible para</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promociones as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['titulo']) ?></td>
                        <td><?= htmlspecialchars($p['descripcion']) ?></td>
                        <td>
                            <?php if ($p['imagen']): ?>
                                <img src="<?= BASE_URL ?>promociones/assets/<?= htmlspecialchars($p['imagen']) ?>" width="60" class="img-thumbnail">
                            <?php endif; ?>
                        </td>
                        <td><?= $p['precio_promocional'] ?></td>
                        <td><?= $p['descuento'] ?></td>
                        <td><?= $p['fecha_inicio'] ?></td>
                        <td><?= $p['fecha_fin'] ?></td>
                        <td>
                            <span class="badge bg-<?= $p['activo'] ? 'success' : 'secondary' ?>">
                                <?= $p['activo'] ? 'Sí' : 'No' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $p['vigente'] ? 'info' : 'secondary' ?>">
                                <?= $p['vigente'] ? 'Sí' : 'No' ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            if ($p['tipo_publico'] === 'todos') {
                                echo 'Todos';
                            } elseif ($p['tipo_publico'] === 'convenios') {
                                echo 'Convenios';
                            } elseif ($p['tipo_publico'] === 'clientes') {
                                echo 'Clientes';
                            } elseif ($p['tipo_publico'] === 'empresas') {
                                echo 'Empresas';
                            } else {
                                echo ucfirst($p['tipo_publico']);
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>dashboard.php?action=editar_promocion&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm mb-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= BASE_URL ?>dashboard.php?action=eliminar_promocion&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm mb-1"
                                onclick="return confirm('¿Eliminar promoción?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaPromociones').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    });
</script>