<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Botón según rol
$rol = $_SESSION['rol'] ?? '';
$botonTexto = '';
$botonUrl   = '';

if ($rol === 'recepcionista' || $rol === 'admin') {
    $botonTexto = 'Nueva Cotización';
    $botonUrl   = 'dashboard.php?vista=clientes';
} elseif ($rol === 'laboratorista') {
    $botonTexto = 'Panel de Laboratorio';
    $botonUrl   = 'dashboard.php?vista=laboratorista';
}


// Filtros recibidos por GET
$dniFiltro      = trim($_GET['dni'] ?? '');
$empresaFiltro  = trim($_GET['empresa'] ?? '');
$convenioFiltro = trim($_GET['convenio'] ?? '');

// Consultar empresas y convenios para los selects
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Construcción dinámica del SQL y parámetros
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni,
        e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        LEFT JOIN empresas e ON c.id_empresa = e.id
        LEFT JOIN convenios v ON c.id_convenio = v.id";
$condiciones = [];
$params = [];

if ($dniFiltro !== '') {
    $condiciones[] = "cl.dni = ?";
    $params[] = $dniFiltro;
}
if ($empresaFiltro !== '') {
    $condiciones[] = "c.id_empresa = ?";
    $params[] = $empresaFiltro;
}
if ($convenioFiltro !== '') {
    $condiciones[] = "c.id_convenio = ?";
    $params[] = $convenioFiltro;
}
if ($condiciones) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para exámenes de cada cotización (sin cambios)
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

// Consulta pagos por cotización (sin cambios)
$pagosPorCotizacion = [];
if ($cotizaciones) {
    $idsCotizaciones = array_column($cotizaciones, 'id');
    if ($idsCotizaciones) {
        $inQuery = implode(',', array_fill(0, count($idsCotizaciones), '?'));
        $sqlPagos = "SELECT id_cotizacion, SUM(monto) AS total_pagado
                     FROM pagos
                     WHERE id_cotizacion IN ($inQuery)
                     GROUP BY id_cotizacion";
        $stmtPagos = $pdo->prepare($sqlPagos);
        $stmtPagos->execute($idsCotizaciones);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pagos as $pago) {
            $pagosPorCotizacion[$pago['id_cotizacion']] = $pago['total_pagado'];
        }
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-2 mb-md-0">Historial de Cotizaciones</h4>
        <?php if ($botonTexto && $botonUrl): ?>
            <a href="<?= $botonUrl ?>" class="btn btn-primary"><?= $botonTexto ?></a>
        <?php endif; ?>
    </div>

    <!-- Filtros combinables -->
    <form method="get" class="mb-3 row g-2 align-items-end">
        <input type="hidden" name="vista" value="cotizaciones">
        <div class="col-auto">
            <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
        </div>
        <div class="col-auto">
            <select name="empresa" class="form-select">
                <option value="">Empresa...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($empresaFiltro == $emp['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="convenio" class="form-select">
                <option value="">Convenio...</option>
                <?php foreach ($convenios as $conv): ?>
                    <option value="<?= $conv['id'] ?>" <?= ($convenioFiltro == $conv['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($conv['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-search"></i> Buscar
            </button>
            <a href="dashboard.php?vista=cotizaciones" class="btn btn-outline-dark">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
        </div>
    </form>

    <div class="table-responsive">
        <table id="tablaCotizaciones" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>DNI</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Referencia</th>
                    <th>Estado Pago</th>
                    <th>Estado Examen</th>
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
                            <!-- Columna de referencia -->
                            <td>
                                <?php
                                if ($cotizacion['tipo_usuario'] === 'empresa' && $cotizacion['nombre_comercial']) {
                                    echo '<span class="badge bg-info text-dark">' . htmlspecialchars($cotizacion['nombre_comercial'] ?: $cotizacion['razon_social']) . '</span>';
                                } elseif ($cotizacion['tipo_usuario'] === 'convenio' && $cotizacion['nombre_convenio']) {
                                    echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Particular</span>';
                                }
                                ?>
                            </td>
                            <!-- Estado Pago calculado -->
                            <td>
                                <?php
                                $total = floatval($cotizacion['total']);
                                $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
                                $saldo = $total - $pagado;

                                if ($saldo <= 0) {
                                    $badgeClassPago = 'bg-success';
                                    $iconPago = 'bi-check-circle-fill';
                                    $textoPago = 'Pagado';
                                } elseif ($pagado > 0) {
                                    $badgeClassPago = 'bg-warning text-dark';
                                    $iconPago = 'bi-hourglass-split';
                                    $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
                                } else {
                                    $badgeClassPago = 'bg-danger';
                                    $iconPago = 'bi-x-circle-fill';
                                    $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
                                }
                                ?>
                                <span class="badge <?= $badgeClassPago ?>">
                                    <i class="bi <?= $iconPago ?>"></i>
                                    <?= $textoPago ?>
                                </span>
                            </td>
                            <!-- Estado Examen -->
                            <td>
                                <?php
                                $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
                                $pendientes = array_filter($examenes, function ($ex) {
                                    return $ex['estado'] === 'pendiente';
                                });
                                if ($pendientes) {
                                    echo "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente</span>";
                                } else {
                                    echo "<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado</span>";
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></td>
                            <td>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                    <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-sm mb-1" title="Ver cotización">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista'): ?>
                                    <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-sm mb-1" title="Editar o agregar resultados">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (($rol === 'admin' || $rol === 'recepcionista') && $saldo > 0): ?>
                                    <a href="dashboard.php?vista=pago_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-warning btn-sm mb-1" title="Registrar pago">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin'): ?>
                                    <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-sm mb-1" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                    <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-1" title="Descargar PDF de todos los resultados" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No hay cotizaciones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- DataTables y dependencias -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaCotizaciones').DataTable({
            "pageLength": 5,
            "lengthMenu": [5, 10, 25, 50],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
        });
    });
</script>