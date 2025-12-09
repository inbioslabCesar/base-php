</script>
<script>
// Recarga la página al cruzar el breakpoint de Bootstrap (768px)
let lastIsMobile = window.innerWidth < 768;
window.addEventListener('resize', function() {
    const isMobile = window.innerWidth < 768;
    if (isMobile !== lastIsMobile) {
        lastIsMobile = isMobile;
        window.location.reload();
    }
});
</script>
<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener todos los usuarios
$stmt = $pdo->query("SELECT * FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<!-- Incluye CSS de Bootstrap y DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container mt-4">
        <!-- Buscador para móvil -->
        <div class="mb-3 d-block d-md-none">
            <div class="input-group">
                <input type="text" id="buscadorUsuarioMovil" class="form-control" placeholder="Buscar por nombre, apellido o DNI...">
                <button class="btn btn-primary" type="button" onclick="document.getElementById('buscadorUsuarioMovil').value = ''; filtrarCardsUsuarios('');"><i class="bi bi-x"></i></button>
            </div>
        </div>
    <h2>Lista de Usuarios</h2>
    <a href="dashboard.php?vista=form_usuario" class="btn btn-primary mb-3">Agregar Usuario</a>
    <!-- Cards para móvil -->
    <div class="d-block d-md-none">
        <?php
        // Paginación móvil
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $por_pagina = isset($_GET['por_pagina']) ? max(3, (int)$_GET['por_pagina']) : 3;
        $total_usuarios = count($usuarios);
        $total_paginas = ceil($total_usuarios / $por_pagina);
        $inicio = ($pagina - 1) * $por_pagina;
        // Renderiza todas las tarjetas, JS controla la visibilidad por página
        ?>
        <form method="get" class="mb-2 d-flex justify-content-end align-items-center gap-2">
            <label for="por_pagina" class="form-label mb-0">Mostrar:</label>
            <select name="por_pagina" id="por_pagina_movil" class="form-select form-select-sm" style="width: auto;">
                <option value="3"<?= $por_pagina==3?' selected':'' ?>>3</option>
                <option value="5"<?= $por_pagina==5?' selected':'' ?>>5</option>
                <option value="10"<?= $por_pagina==10?' selected':'' ?>>10</option>
            </select>
        </form>
        <?php if ($usuarios): ?>
            <?php foreach ($usuarios as $usuario): ?>
                <div class="card mb-3 shadow-sm usuario-card" data-nombre="<?= htmlspecialchars($usuario['nombre']) ?>" data-apellido="<?= htmlspecialchars($usuario['apellido']) ?>" data-dni="<?= htmlspecialchars($usuario['dni']) ?>">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($usuario['nombre']) ?> <?= htmlspecialchars($usuario['apellido']) ?></span>
                        <span class="badge bg-secondary">#<?= htmlspecialchars($usuario['id']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><strong>DNI:</strong> <?= htmlspecialchars($usuario['dni']) ?></div>
                            <div class="col-6"><strong>Sexo:</strong> <?= htmlspecialchars($usuario['sexo']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></div>
                            <div class="col-6"><strong>Teléfono:</strong> <?= htmlspecialchars($usuario['telefono']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12"><strong>Dirección:</strong> <?= htmlspecialchars($usuario['direccion']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Cargo:</strong> <?= htmlspecialchars($usuario['cargo']) ?></div>
                            <div class="col-6"><strong>Profesión:</strong> <?= htmlspecialchars($usuario['profesion']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Rol:</strong> <?= htmlspecialchars($usuario['rol']) ?></div>
                            <div class="col-6"><strong>Estado:</strong> <?= htmlspecialchars($usuario['estado']) ?></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="dashboard.php?vista=form_usuario&id=<?= $usuario['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="dashboard.php?action=eliminar_usuario&id=<?= $usuario['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <!-- Paginación móvil -->
            <nav class="mobile-pagination d-flex justify-content-center align-items-center gap-2 mb-3" id="paginacionUsuarioMovil">
                <button class="btn btn-outline-primary btn-sm" id="btnAnteriorUsuario" type="button">&#8592;</button>
                <span id="paginacionUsuarioPaginas"></span>
                <button class="btn btn-outline-primary btn-sm" id="btnSiguienteUsuario" type="button">&#8594;</button>
            </nav>
        <?php else: ?>
            <div class="alert alert-info">No hay usuarios registrados.</div>
        <?php endif; ?>
    </div>
    <!-- Selector de filas para desktop: solo el nativo de DataTables -->
    <!-- Tabla para desktop -->
    <div class="table-responsive d-none d-md-block">
        <table id="tabla-usuarios" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>DNI</th>
                    <th>Sexo</th>
                    <th>Fecha Nacimiento</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Cargo</th>
                    <th>Profesión</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
            </tbody>
        </table>
    </div>
</div>

<!-- Incluye JS de Bootstrap y DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
// DataTables para desktop
$(document).ready(function() {
    $('#tabla-usuarios').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=usuarios_api',
            type: 'GET'
        },
        pageLength: 3,
        lengthMenu: [[3, 5, 10], [3, 5, 10]],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columnDefs: [
            { targets: [8, 10, 11, 12], visible: false } // direccion(8), profesion(10), rol(11), estado(12)
        ],
        columns: [
            { data: 'id' },
            { data: 'nombre', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'apellido', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'dni' },
            { data: 'sexo', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'fecha_nacimiento' },
            { data: 'email' },
            { data: 'telefono' },
            { data: 'direccion' },
            { data: 'cargo' },
            { data: 'profesion' },
            { data: 'rol', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'estado', render: function(data) {
                if (data === 'activo') {
                    return `<span class='badge bg-success'>Activo</span>`;
                } else {
                    return `<span class='badge bg-danger'>Inactivo</span>`;
                }
            } },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<a href='dashboard.php?vista=form_usuario&id=${row.id}' class='btn btn-warning btn-sm'>Editar</a>
                            <a href='dashboard.php?action=eliminar_usuario&id=${row.id}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\");'>Eliminar</a>`;
                }
            }
        ],
        dom: 'lBfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Exportar Excel',
                className: 'btn btn-success'
            },
            {
                extend: 'pdfHtml5',
                text: 'Exportar PDF',
                className: 'btn btn-danger'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-info'
            }
        ]
    });
});
</script>

<script>
// Paginación y buscador móvil
function normalizarTextoUsuario(txt) {
    return txt
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
let paginaUsuario = 1;
let porPaginaUsuario = <?= $por_pagina ?>;
let totalUsuarios = document.querySelectorAll('.usuario-card').length;
let totalPaginasUsuario = Math.ceil(totalUsuarios / porPaginaUsuario);

function mostrarPaginaActualUsuarios() {
    var cards = document.querySelectorAll('.usuario-card');
    var inicio = (paginaUsuario - 1) * porPaginaUsuario;
    var fin = inicio + porPaginaUsuario;
    cards.forEach(function(card, idx) {
        if (idx >= inicio && idx < fin) {
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    });
    document.getElementById('paginacionUsuarioMovil').style.display = '';
    actualizarPaginacionUsuario();
}

function actualizarPaginacionUsuario() {
    let paginacion = document.getElementById('paginacionUsuarioPaginas');
    paginacion.innerHTML = '';
    let pages = [];
    if (paginaUsuario > 1) pages.push(paginaUsuario - 1);
    pages.push(paginaUsuario);
    if (paginaUsuario < totalPaginasUsuario) pages.push(paginaUsuario + 1);
    pages.forEach(function(p) {
        let btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm' + (p === paginaUsuario ? ' active' : '');
        btn.textContent = p;
        btn.onclick = function() {
            paginaUsuario = p;
            mostrarPaginaActualUsuarios();
        };
        paginacion.appendChild(btn);
    });
    document.getElementById('btnAnteriorUsuario').disabled = (paginaUsuario <= 1);
    document.getElementById('btnSiguienteUsuario').disabled = (paginaUsuario >= totalPaginasUsuario);
}

function filtrarCardsUsuarios(valor) {
    var filtro = normalizarTextoUsuario(valor);
    var cards = document.querySelectorAll('.usuario-card');
    var algunoVisible = false;
    if (!filtro) {
        mostrarPaginaActualUsuarios();
    } else {
        cards.forEach(function(card) {
            var nombre = normalizarTextoUsuario(card.getAttribute('data-nombre') || '');
            var apellido = normalizarTextoUsuario(card.getAttribute('data-apellido') || '');
            var dni = normalizarTextoUsuario(card.getAttribute('data-dni') || '');
            if (nombre.includes(filtro) || apellido.includes(filtro) || dni.includes(filtro)) {
                card.classList.remove('d-none');
                algunoVisible = true;
            } else {
                card.classList.add('d-none');
            }
        });
        document.getElementById('paginacionUsuarioMovil').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    mostrarPaginaActualUsuarios();
    document.getElementById('btnAnteriorUsuario').onclick = function() {
        if (paginaUsuario > 1) {
            paginaUsuario--;
            mostrarPaginaActualUsuarios();
        }
    };
    document.getElementById('btnSiguienteUsuario').onclick = function() {
        if (paginaUsuario < totalPaginasUsuario) {
            paginaUsuario++;
            mostrarPaginaActualUsuarios();
        }
    };
    document.getElementById('por_pagina_movil').addEventListener('change', function(e) {
        porPaginaUsuario = parseInt(this.value);
        totalUsuarios = document.querySelectorAll('.usuario-card').length;
        totalPaginasUsuario = Math.ceil(totalUsuarios / porPaginaUsuario);
        paginaUsuario = 1;
        mostrarPaginaActualUsuarios();
    });
});
document.getElementById('buscadorUsuarioMovil').addEventListener('input', function(e) {
    filtrarCardsUsuarios(e.target.value);
});
</script>
