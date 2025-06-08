<?php
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
// Convierte solo la primera letra en mayúscula, el resto en minúscula
$nombreFormateado = ucfirst(mb_strtolower($nombreUsuario, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - Inbioslab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar-custom {
            background: #143a51;
            color: #fff;
        }

        .sidebar-custom .nav-link,
        .sidebar-custom .nav-link i {
            color: #fff;
        }

        .sidebar-custom .nav-link.active,
        .sidebar-custom .nav-link:hover {
            background: #1e5270;
            color: #fff;
        }

        .sidebar-custom .nav-link {
            font-size: 1.15rem;
            margin-bottom: 0.7rem;
            padding: 1rem 1.5rem;
            border-radius: 0.7rem;
            transition: background 0.2s;
        }

        .sidebar-custom .nav-link i {
            font-size: 1.6rem;
            vertical-align: middle;
            margin-right: 0.7rem;
        }

        @media (max-width: 991.98px) {
            #sidebarMenu {
                position: fixed;
                top: 0;
                left: -270px;
                width: 270px;
                height: 100%;
                z-index: 1045;
                transition: left 0.3s;
            }

            #sidebarMenu.show {
                left: 0;
            }

            header {
                z-index: 1050;
                position: relative;
            }

            @media (max-width: 767.98px) {
                main[style] {
                    margin-left: 0 !important;
                }
            }

        }
    </style>
</head>

<body>
    <header class="bg-white shadow-sm mb-3 position-relative" style="z-index: 1050;">
        <div class="container-fluid d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center">
                <img src="<?= BASE_URL ?>images/inbioslab-logo.png" alt="Inbioslab Logo" style="height:74px; margin-right:24px;">
                <span class="fw-bold" style="font-size:1.3rem;">
                    Bienvenido, <?= htmlspecialchars($nombreFormateado) ?>!
                </span>
            </div>
            <!-- Botón solo visible en móvil -->
            <button class="btn d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarToggle" aria-controls="sidebarToggle" aria-label="Menú">
                <i class="bi bi-list fs-2"></i>
            </button>

        </div>
    </header>