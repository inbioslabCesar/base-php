<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener la fila actual de config_empresa
$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);
$id = $empresa ? $empresa['id'] : null;

// Recoge los datos del formulario
// ...existing code...
$nombre    = trim($_POST['nombre'] ?? '');
$ruc       = trim($_POST['ruc'] ?? '');
$dominio   = trim($_POST['dominio'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$celular   = trim($_POST['celular'] ?? '');

// Validación básica
if (!$nombre || !$ruc || !$direccion || !$email) {
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: /dashboard.php?vista=config_empresa_datos');
    exit;
}

// Procesamiento del logo (solo PNG, sobrescribe archivo)
$logo = $empresa['logo'] ?? 'images/empresa/logo_empresa.png';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $logoPath = 'images/empresa/logo_empresa.png';
        $destino = __DIR__ . '/../' . $logoPath;
        if (file_exists($destino)) {
            unlink($destino);
        }
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $logo = $logoPath;
        } else {
            $_SESSION['msg'] = 'Error al subir el logo.';
            header('Location: /dashboard.php?vista=config_empresa_datos');
            exit;
        }
    } else {
        $_SESSION['msg'] = 'El logo debe ser una imagen PNG.';
        header('Location: /dashboard.php?vista=config_empresa_datos');
        exit;
    }
}

// Procesamiento de la firma (solo PNG, sobrescribe archivo)
$firma = $empresa['firma'] ?? 'images/empresa/firma.png';
if (isset($_FILES['firma']) && $_FILES['firma']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $firmaPath = 'images/empresa/firma.png';
        $destinoFirma = __DIR__ . '/../' . $firmaPath;
        if (file_exists($destinoFirma)) {
            unlink($destinoFirma);
        }
        if (move_uploaded_file($_FILES['firma']['tmp_name'], $destinoFirma)) {
            $firma = $firmaPath;
        } else {
            $_SESSION['msg'] = 'Error al subir la firma.';
            header('Location: /dashboard.php?vista=config_empresa_datos');
            exit;
        }
    } else {
        $_SESSION['msg'] = 'La firma debe ser una imagen PNG.';
        header('Location: /dashboard.php?vista=config_empresa_datos');
        exit;
    }
}
// Colores y textos
$color_principal  = trim($_POST['color_principal'] ?? '#0d6efd');
$color_secundario = trim($_POST['color_secundario'] ?? '#f8f9fa');
$color_footer     = trim($_POST['color_footer'] ?? '#343a40');
$color_botones    = trim($_POST['color_botones'] ?? '#198754');
$color_texto      = trim($_POST['color_texto'] ?? '#212529');
$tamano_letra     = trim($_POST['tamano_letra'] ?? '1rem');
$frase_promocion  = trim($_POST['frase_promocion'] ?? '');
$oferta_mes       = trim($_POST['oferta_mes'] ?? '');

// --- Imágenes del carrusel ---
$imagenes_carrusel = [];
if (!empty($empresa['imagenes_carrusel'])) {
    $tmp = json_decode($empresa['imagenes_carrusel'], true);
    if (is_array($tmp)) {
        $imagenes_carrusel = $tmp;
    }
}
if (!empty($_POST['eliminar_carrusel']) && is_array($_POST['eliminar_carrusel'])) {
    foreach ($_POST['eliminar_carrusel'] as $idx) {
        $idx = (int)$idx;
        if (isset($imagenes_carrusel[$idx])) {
            $ruta = __DIR__ . '/../' . $imagenes_carrusel[$idx];
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_carrusel[$idx]);
        }
    }
    $imagenes_carrusel = array_values($imagenes_carrusel);
}
if (!empty($_FILES['imagenes_carrusel']['name'][0])) {
    $uploadDir = __DIR__ . '/../images/empresa/carrusel/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_carrusel']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_carrusel']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('carrusel_') . '.' . $ext;
                $rutaRelativa = 'images/empresa/carrusel/' . $nuevoNombre;
                if (move_uploaded_file($tmp_name, $uploadDir . $nuevoNombre)) {
                    $imagenes_carrusel[] = $rutaRelativa;
                }
            }
        }
    }
}

// --- Imágenes institucionales ---
$imagenes_institucionales = [];
if (!empty($empresa['imagenes_institucionales'])) {
    $tmp = json_decode($empresa['imagenes_institucionales'], true);
    if (is_array($tmp)) {
        $imagenes_institucionales = $tmp;
    }
}
if (!empty($_POST['eliminar_institucional']) && is_array($_POST['eliminar_institucional'])) {
    foreach ($_POST['eliminar_institucional'] as $idx) {
        $idx = (int)$idx;
        if (isset($imagenes_institucionales[$idx])) {
            $ruta = __DIR__ . '/../' . $imagenes_institucionales[$idx];
            if (file_exists($ruta)) unlink($ruta);
            unset($imagenes_institucionales[$idx]);
        }
    }
    $imagenes_institucionales = array_values($imagenes_institucionales);
}
if (!empty($_FILES['imagenes_institucionales']['name'][0])) {
    $uploadDir = __DIR__ . '/../images/empresa/institucional/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['imagenes_institucionales']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $nombreArchivo = basename($_FILES['imagenes_institucionales']['name'][$key]);
            $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nuevoNombre = uniqid('institucional_') . '.' . $ext;
                $rutaRelativa = 'images/empresa/institucional/' . $nuevoNombre;
                if (move_uploaded_file($tmp_name, $uploadDir . $nuevoNombre)) {
                    $imagenes_institucionales[] = $rutaRelativa;
                }
            }
        }
    }
}

// Servicios, testimonios y redes sociales (JSON)
$servicios      = json_decode($_POST['servicios'] ?? '[]', true);
if (!is_array($servicios)) $servicios = [];
$testimonios    = json_decode($_POST['testimonios'] ?? '[]', true);
if (!is_array($testimonios)) $testimonios = [];
$redes_sociales = json_decode($_POST['redes_sociales'] ?? '[]', true);
if (!is_array($redes_sociales)) $redes_sociales = [];

// Menú personalizable
$menu_inicio      = trim($_POST['menu_inicio'] ?? 'Inicio');
$menu_servicios   = trim($_POST['menu_servicios'] ?? 'Servicios');
$menu_testimonios = trim($_POST['menu_testimonios'] ?? 'Testimonios');
$menu_contacto    = trim($_POST['menu_contacto'] ?? 'Contacto');
try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE config_empresa SET 
            nombre=?, ruc=?, dominio=?, direccion=?, email=?, telefono=?, celular=?, logo=?, firma=?,
            color_principal=?, color_secundario=?, color_footer=?, color_botones=?, color_texto=?, tamano_letra=?,
            frase_promocion=?, oferta_mes=?,
            imagenes_carrusel=?, imagenes_institucionales=?, servicios=?, testimonios=?, redes_sociales=?,
            menu_inicio=?, menu_servicios=?, menu_testimonios=?, menu_contacto=?
            WHERE id=?");
        $stmt->execute([
            $nombre, $ruc, $dominio, $direccion, $email, $telefono, $celular, $logo, $firma,
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto, $tamano_letra,
            $frase_promocion, $oferta_mes,
            json_encode($imagenes_carrusel, JSON_UNESCAPED_UNICODE),
            json_encode($imagenes_institucionales, JSON_UNESCAPED_UNICODE),
            json_encode($servicios, JSON_UNESCAPED_UNICODE),
            json_encode($testimonios, JSON_UNESCAPED_UNICODE),
            json_encode($redes_sociales, JSON_UNESCAPED_UNICODE),
            $menu_inicio, $menu_servicios, $menu_testimonios, $menu_contacto,
            $id
        ]);
        $_SESSION['msg'] = 'Datos de la empresa actualizados correctamente.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO config_empresa (
            nombre, ruc, dominio, direccion, email, telefono, celular, logo, firma,
            color_principal, color_secundario, color_footer, color_botones, color_texto, tamano_letra,
            frase_promocion, oferta_mes,
            imagenes_carrusel, imagenes_institucionales, servicios, testimonios, redes_sociales,
            menu_inicio, menu_servicios, menu_testimonios, menu_contacto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nombre, $ruc, $dominio, $direccion, $email, $telefono, $celular, $logo, $firma,
            $color_principal, $color_secundario, $color_footer, $color_botones, $color_texto, $tamano_letra,
            $frase_promocion, $oferta_mes,
            json_encode($imagenes_carrusel, JSON_UNESCAPED_UNICODE),
            json_encode($imagenes_institucionales, JSON_UNESCAPED_UNICODE),
            json_encode($servicios, JSON_UNESCAPED_UNICODE),
            json_encode($testimonios, JSON_UNESCAPED_UNICODE),
            json_encode($redes_sociales, JSON_UNESCAPED_UNICODE),
            $menu_inicio, $menu_servicios, $menu_testimonios, $menu_contacto
        ]);
        $_SESSION['msg'] = 'Datos de la empresa registrados correctamente.';
    }
    header('Location: ../dashboard.php?vista=config_empresa_datos');
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = 'Error al guardar: ' . $e->getMessage();
    header('Location: /dashboard.php?vista=config_empresa_datos');
    exit;
}
