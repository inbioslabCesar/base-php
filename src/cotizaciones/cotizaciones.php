<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Botón según rol
$rol = $_SESSION['rol'] ?? '';
$botonTexto = '';
$botonUrl   = '';
if ($rol === 'cliente') {
    $botonTexto = 'Nueva Cotización';
    $botonUrl   = 'dashboard.php?vista=form_cotizacion';
} elseif ($rol === 'recepcionista') {
    $botonTexto = 'Nueva Cotización';
    $botonUrl   = 'dashboard.php?vista=clientes';
}

// Filtro por DNI (si se envía por GET o POST)
$dniFiltro = trim($_GET['dni'] ?? '');

// Consulta base
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni 
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id";

$params = [];
if ($dniFiltro !== '') {
    $sql .= " WHERE cl.dni = ?";
    $params[] = $dniFiltro;
}
$sql .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para exámenes de cada cotización
$examenesPorCotizacion = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        $sqlExamenes = "SELECT re.id AS id_resultado, re.id_cotizacion, re.id_examen, re.estado, e.nombre AS nombre_examen
                        FROM resultados_examenes re
                        JOIN examenes e ON re.id_examen = e.id
                        WHERE re.id_cotizacion IN ($inQuery)";
        $stmtEx = $pdo->prepare($sqlExamenes);
        $stmtEx->execute($idsCotizaciones);
        $examenes = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
        foreach ($examenes as $ex) {
            $examenesPorCotizacion[$ex['id_cotizacion']][] = $ex;
        }
    }
}
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-2 mb-md-0">Historial de Cotizaciones</h4>
        <?php if ($botonTexto && $botonUrl): ?>
            <a href="<?= $botonUrl ?>" class="btn btn-primary"><?= $botonTexto ?></a>
        <?php endif; ?>
    </div>

    <!-- Filtro por DNI -->
    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="hidden" name="vista" value="cotizaciones">
            <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
            <a href="dashboard.php?vista=cotizaciones" class="btn btn-outline-dark">Limpiar</a>
        </div>
    </form>

    <div class="table-responsive">
        <table id="tablaCotizaciones" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>DNI</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Rol Creador</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cotizaciones): ?>
                    <?php foreach ($cotizaciones as $cotizacion): ?>
                        <tr>
                            <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['dni'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                            <td>S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></td>
                            <td>
                                <?php
                                $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
                                $pendientes = array_filter($examenes, function ($ex) {
                                    return $ex['estado'] === 'pendiente';
                                });
                                echo $pendientes ? "<span class='badge bg-warning text-dark'>Pendiente</span>" : "<span class='badge bg-success'>Completado</span>";
                                ?>
                            </td>
                            <td><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></td>
                            <td>
                                <!-- Botón para ver la cotización -->
                                <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>"
                                    class="btn btn-info btn-sm"
                                    title="Ver cotización">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Botón único para editar/agregar resultados -->
                                <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>"
                                    class="btn btn-primary btn-sm"
                                    title="Editar o agregar resultados">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <!-- Botón único para descargar PDF de todos los resultados -->
                                <a href="resultados/descarga-pdf.html?cotizacion_id=<?= $cotizacion['id'] ?>"
                                    class="btn btn-success btn-sm mb-1"
                                    title="Descargar PDF de todos los resultados"
                                    target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay cotizaciones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>