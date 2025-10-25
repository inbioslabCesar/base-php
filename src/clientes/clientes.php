<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$rol = $_SESSION['rol'] ?? '';

// Filtro por DNI
$busqueda = trim($_GET['dni'] ?? '');

// Consulta principal con JOINs para traer los nombres correctos de empresa y convenio
// Filtro por DNI, nombre o apellido
$sql = "
SELECT c.*, 
    (SELECT e.nombre_comercial FROM empresa_cliente ec 
     JOIN empresas e ON ec.empresa_id = e.id 
     WHERE ec.cliente_id = c.id LIMIT 1) AS nombre_empresa,
    (SELECT v.nombre FROM convenio_cliente cc 
     JOIN convenios v ON cc.convenio_id = v.id 
     WHERE cc.cliente_id = c.id LIMIT 1) AS nombre_convenio
FROM clientes c
";
$params = [];
if ($busqueda !== '') {
    $sql .= " WHERE c.dni LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
$sql .= " ORDER BY c.fecha_registro DESC, c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Paginación para cards móviles
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = 3;
$total_clientes = count($clientes);
$total_paginas = ceil($total_clientes / $por_pagina);
$inicio = ($pagina - 1) * $por_pagina;
$clientes_pagina = array_slice($clientes, $inicio, $por_pagina);

function capitalize($string) {
    return mb_convert_case(strtolower(trim((string)$string)), MB_CASE_TITLE, "UTF-8");
}
?>

<style>
/* Estilos para vista responsiva de clientes */
.clientes-container {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 1rem;
}

.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.search-section {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e3e6f0;
}

.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: end;
}

.search-form .form-group {
    min-width: 200px;
    flex: 1;
}

.btn-search {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    transition: transform 0.2s;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-clear {
    background: #6c757d;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-nuevo {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: transform 0.2s;
}

.btn-nuevo:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

/* Vista de Cards para móvil */
.cards-container {
    display: none;
}

.cliente-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e3e6f0;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.cliente-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.cliente-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.cliente-nombre {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.cliente-codigo {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 0.95rem;
    color: #2c3e50;
    font-weight: 500;
}

.badges-section {
    margin: 1rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.badge-custom {
    padding: 0.4rem 0.8rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    border: none;
}

.badge-rol {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.badge-empresa {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    color: white;
}

.badge-convenio {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.badge-estado {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: #212529;
}

.card-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.action-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-edit {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.btn-delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.btn-cotizar {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    flex: 1;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}

/* Vista de tabla para desktop */
.table-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e3e6f0;
}

.table-modern {
    margin-bottom: 0;
}

.table-modern thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table-modern thead th {
    border: none;
    padding: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.table-modern tbody tr {
    transition: all 0.2s;
}

.table-modern tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .table-container {
        display: none;
    }
    
    .cards-container {
        display: block;
    }
    
    .card-body {
        grid-template-columns: 1fr;
    }
    
    .card-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .card-actions .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-form .form-group {
        min-width: auto;
    }
    
    .header-section {
        text-align: center;
        padding: 1.5rem;
    }
    
    .header-section h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .clientes-container {
        padding: 0.5rem;
    }
    
    .cliente-card {
        padding: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cliente-card {
    animation: fadeInUp 0.3s ease-out;
}

.search-section {
    animation: fadeInUp 0.3s ease-out 0.1s both;
}

.table-container {
    animation: fadeInUp 0.3s ease-out 0.2s both;
}
</style>

<div class="clientes-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="mb-2">
                    <i class="bi bi-people-fill me-2"></i>
                    Gestión de Pacientes
                </h3>
                <p class="mb-0 opacity-75">Administra la información de todos los pacientes registrados</p>
            </div>
            <a href="dashboard.php?vista=form_cliente" class="btn-nuevo">
                <i class="bi bi-person-plus"></i>
                Nuevo Paciente
            </a>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section d-none d-md-block">
        <!-- Buscador DataTables integrado en la tabla -->
    </div>

    <!-- Mensajes de alerta -->
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>


    <!-- Vista Cards para Móvil con paginación -->
    <div class="mb-3 d-block d-md-none">
        <div class="input-group">
            <input type="text" id="buscadorClienteMovil" class="form-control" placeholder="Buscar por nombre o DNI...">
            <button class="btn btn-primary" type="button" onclick="document.getElementById('buscadorClienteMovil').value = ''; filtrarCardsClientes('');"><i class="bi bi-x"></i></button>
        </div>
    </div>
    <div class="cards-container">
        <?php if ($clientes): ?>
            <?php foreach ($clientes as $cliente): ?>
                <div class="cliente-card" data-nombre="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" data-apellido="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>" data-dni="<?= htmlspecialchars($cliente['dni'] ?? '') ?>">
                    <div class="card-header">
                        <h5 class="cliente-nombre">
                            <?= capitalize($cliente['nombre'] ?? '') ?> <?= capitalize($cliente['apellido'] ?? '') ?>
                        </h5>
                        <span class="cliente-codigo">
                            #<?= htmlspecialchars($cliente['codigo_cliente'] ?? $cliente['id']) ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="info-item">
                            <span class="info-label">DNI</span>
                            <span class="info-value"><?= htmlspecialchars($cliente['dni'] ?? 'No especificado') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Edad</span>
                            <span class="info-value"><?= htmlspecialchars($cliente['edad'] ?? 'No especificada') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($cliente['email'] ?? 'No especificado') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"><?= htmlspecialchars($cliente['telefono'] ?? 'No especificado') ?></span>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <span class="info-label">Dirección</span>
                            <span class="info-value"><?= htmlspecialchars($cliente['direccion'] ?? 'No especificada') ?></span>
                        </div>
                    </div>

                    <div class="badges-section">
                        <?php
                            $rol_creador = strtolower(trim($cliente['rol_creador'] ?? ''));
                            $roles_validos = ['admin', 'recepcionista', 'empresa', 'convenio'];
                            $rol_mostrar = in_array($rol_creador, $roles_validos) && $rol_creador !== '' 
                                ? ucfirst($rol_creador) 
                                : 'Paciente';
                        ?>
                        <span class="badge-custom badge-rol">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= $rol_mostrar ?>
                        </span>

                        <span class="badge-custom badge-estado">
                            <i class="bi bi-circle-fill me-1"></i>
                            <?= htmlspecialchars($cliente['estado'] ?? 'Activo') ?>
                        </span>

                        <?php
                            $emp = $cliente['nombre_empresa'] ?? '';
                            $conv = $cliente['nombre_convenio'] ?? '';
                            if ($emp): ?>
                                <span class="badge-custom badge-empresa">
                                    <i class="bi bi-building me-1"></i>
                                    <?= htmlspecialchars($emp) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($conv): ?>
                                <span class="badge-custom badge-convenio">
                                    <i class="bi bi-handshake me-1"></i>
                                    <?= htmlspecialchars($conv) ?>
                                </span>
                            <?php endif; ?>
                    </div>

                    <div class="card-actions">
                        <div class="d-flex gap-2">
                            <a href="dashboard.php?vista=form_cliente&id=<?= $cliente['id'] ?>" 
                               class="action-btn btn-edit" 
                               title="Editar">
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>
                            <a href="clientes/eliminar.php?id=<?= $cliente['id'] ?>" 
                               class="action-btn btn-delete" 
                               title="Eliminar" 
                               onclick="return confirm('¿Estás seguro de eliminar este paciente?');">
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </a>
                        </div>
                        
                        <?php if ($rol === 'recepcionista' || $rol === 'admin'): ?>
                            <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] ?>" 
                               class="action-btn btn-cotizar" 
                               title="Crear Cotización">
                                <i class="bi bi-file-earmark-plus me-1"></i>
                                Cotizar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

                        <!-- Paginación para cards -->
                        <style>
                        @media (max-width: 768px) {
                            .pagination, .pagination ul { display: none !important; }
                            .mobile-pagination {
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                gap: 6px;
                                margin: 1.5rem 0 2rem 0;
                            }
                            .mobile-pagination .page-btn {
                                background: #764ba2;
                                color: #fff;
                                border: none;
                                border-radius: 8px;
                                min-width: 38px;
                                min-height: 38px;
                                font-weight: 700;
                                font-size: 1.1rem;
                                box-shadow: 0 2px 8px #764ba233;
                                transition: background 0.2s, color 0.2s;
                            }
                            .mobile-pagination .page-btn.active {
                                background: #fff;
                                color: #764ba2;
                                border: 2px solid #764ba2;
                            }
                            .mobile-pagination .page-btn:disabled {
                                opacity: 0.5;
                                cursor: not-allowed;
                            }
                        }
                        </style>
                        <nav class="mobile-pagination" id="paginacionClienteMovil">
                            <?php
                            $params = $_GET;
                            unset($params['pagina']);
                            $baseUrl = '';
                            if (!empty($params)) {
                                foreach ($params as $key => $value) {
                                    $baseUrl .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                                }
                            }
                            ?>
                            <form method="get" style="display:inline">
                                <?= $baseUrl ?>
                                <button class="page-btn" type="submit" name="pagina" value="<?= max(1, $pagina-1) ?>" <?= $pagina <= 1 ? 'disabled' : '' ?>>&#8592;</button>
                            </form>
                            <?php
                            $pages = [];
                            if ($pagina > 1) $pages[] = $pagina-1;
                            $pages[] = $pagina;
                            if ($pagina < $total_paginas) $pages[] = $pagina+1;
                            foreach ($pages as $p): ?>
                                <form method="get" style="display:inline">
                                    <?= $baseUrl ?>
                                    <button class="page-btn<?= $p == $pagina ? ' active' : '' ?>" type="submit" name="pagina" value="<?= $p ?>"><?= $p ?></button>
                                </form>
                            <?php endforeach; ?>
                            <form method="get" style="display:inline">
                                <?= $baseUrl ?>
                                <button class="page-btn" type="submit" name="pagina" value="<?= min($total_paginas, $pagina+1) ?>" <?= $pagina >= $total_paginas ? 'disabled' : '' ?>>&#8594;</button>
                            </form>
                        </nav>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-people" style="font-size: 4rem; color: #6c757d;"></i>
                <h4 class="mt-3 text-muted">No hay pacientes registrados</h4>
                <p class="text-muted">Comienza agregando tu primer paciente</p>
                <a href="dashboard.php?vista=form_cliente" class="btn-nuevo">
                    <i class="bi bi-person-plus"></i>
                    Agregar Paciente
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vista Tabla para Desktop -->
    <div class="table-container">
        <div class="table-responsive">
            <table id="tablaClientes" class="table table-modern">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>DNI</th>
                        <th>Edad</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><strong><?= (int)$cliente['id'] ?></strong></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($cliente['codigo_cliente'] ?? $cliente['id']) ?></span></td>
                                <td><?= capitalize($cliente['nombre'] ?? '') ?></td>
                                <td><?= capitalize($cliente['apellido'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($cliente['dni'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($cliente['edad'] ?? '') ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($cliente['email'] ?? 'No especificado') ?></small></td>
                                <td><?= htmlspecialchars($cliente['telefono'] ?? '') ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($cliente['estado'] ?? 'Activo') ?></span></td>
                                <td>
                                    <div class="btn-group gap-2" role="group" style="display: flex;">
                                        <a href="dashboard.php?vista=form_cliente&id=<?= $cliente['id'] ?>" 
                                           class="btn btn-warning btn-sm" 
                                           title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="clientes/eliminar.php?id=<?= $cliente['id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           title="Eliminar" 
                                           onclick="return confirm('¿Seguro de eliminar este paciente?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php if ($rol === 'recepcionista' || $rol === 'admin'): ?>
                                            <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Cotizar">
                                                <i class="bi bi-file-earmark-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="bi bi-people" style="font-size: 2rem; color: #6c757d;"></i>
                                <br>
                                <strong>No hay pacientes registrados</strong>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables y Bootstrap JS -->
<script>
function normalizarTexto(txt) {
    return txt
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // quita tildes y diacríticos
        .replace(/\s+/g, ' ')
        .trim();
}
function mostrarPaginaActualClientes() {
    var cards = document.querySelectorAll('.cliente-card');
    var inicio = <?= ($pagina - 1) * $por_pagina ?>;
    var fin = inicio + <?= $por_pagina ?>;
    cards.forEach(function(card, idx) {
        if (idx >= inicio && idx < fin) {
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    });
    document.getElementById('paginacionClienteMovil').style.display = '';
}
function filtrarCardsClientes(valor) {
    var filtro = normalizarTexto(valor);
    var cards = document.querySelectorAll('.cliente-card');
    var algunoVisible = false;
    if (!filtro) {
        mostrarPaginaActualClientes();
    } else {
        cards.forEach(function(card) {
            var nombre = normalizarTexto(card.getAttribute('data-nombre') || '');
            var apellido = normalizarTexto(card.getAttribute('data-apellido') || '');
            var dni = normalizarTexto(card.getAttribute('data-dni') || '');
            if (nombre.includes(filtro) || apellido.includes(filtro) || dni.includes(filtro)) {
                card.classList.remove('d-none');
                algunoVisible = true;
            } else {
                card.classList.add('d-none');
            }
        });
        document.getElementById('paginacionClienteMovil').style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    mostrarPaginaActualClientes();
});
document.getElementById('buscadorClienteMovil').addEventListener('input', function(e) {
    filtrarCardsClientes(e.target.value);
});
</script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaClientes').DataTable({
        "pageLength": 3, // Por defecto mostrar 3 filas
        "lengthMenu": [[3, 5, 10, 25, 50], [3, 5, 10, 25, 50]], // Agregado opción de 3 filas
        "order": [], // No aplicar ordenamiento inicial, mantener el orden de la consulta
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "responsive": true,
        "columnDefs": [
            { "responsivePriority": 1, "targets": [2, 3, 4] }, // Prioridad alta para nombre, apellido, DNI
            { "responsivePriority": 2, "targets": [9] }, // Prioridad alta para acciones
            { "responsivePriority": 3, "targets": [0, 1] } // Prioridad media para ID y código
        ]
    });
});
</script>
