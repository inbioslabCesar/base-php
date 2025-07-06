<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos de la tabla config_empresa
$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

// Valores por defecto
$logo = !empty($empresa['logo']) ? $empresa['logo'] : 'images/empresa/logo_empresa.png';
$firma = !empty($empresa['firma']) ? $empresa['firma'] : 'images/empresa/firma.png';
$color_principal = $empresa['color_principal'] ?? '#0d6efd';
$color_secundario = $empresa['color_secundario'] ?? '#f8f9fa';
$color_footer = $empresa['color_footer'] ?? '#343a40';
$color_botones = $empresa['color_botones'] ?? '#198754';
$color_texto = $empresa['color_texto'] ?? '#212529';
$tamano_letra = $empresa['tamano_letra'] ?? '1rem';
$frase_promocion = $empresa['frase_promocion'] ?? '';
$oferta_mes = $empresa['oferta_mes'] ?? '';

// Arrays seguros
$imagenes_carrusel = [];
if (!empty($empresa['imagenes_carrusel'])) {
    $tmp = json_decode($empresa['imagenes_carrusel'], true);
    if (is_array($tmp)) $imagenes_carrusel = $tmp;
}
$imagenes_institucionales = [];
if (!empty($empresa['imagenes_institucionales'])) {
    $tmp = json_decode($empresa['imagenes_institucionales'], true);
    if (is_array($tmp)) $imagenes_institucionales = $tmp;
}
$servicios = [];
if (!empty($empresa['servicios'])) {
    $tmp = json_decode($empresa['servicios'], true);
    if (is_array($tmp)) $servicios = $tmp;
}
$testimonios = [];
if (!empty($empresa['testimonios'])) {
    $tmp = json_decode($empresa['testimonios'], true);
    if (is_array($tmp)) $testimonios = $tmp;
}
$redes_sociales = [];
if (!empty($empresa['redes_sociales'])) {
    $tmp = json_decode($empresa['redes_sociales'], true);
    if (is_array($tmp)) $redes_sociales = $tmp;
}
$menu_inicio = $empresa['menu_inicio'] ?? 'Inicio';
$menu_servicios = $empresa['menu_servicios'] ?? 'Servicios';
$menu_testimonios = $empresa['menu_testimonios'] ?? 'Testimonios';
$menu_contacto = $empresa['menu_contacto'] ?? 'Contacto';
?>
<div class="container mt-4">
    <h4>Configuración de Empresa</h4>
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>
    <form method="POST" action="config/config_empresa_guardar.php" enctype="multipart/form-data" autocomplete="off">
        <div class="row">
            <!-- Datos básicos -->
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="ruc" class="form-label">RUC *</label>
                <input type="text" class="form-control" id="ruc" name="ruc"
                    value="<?= htmlspecialchars($empresa['ruc'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="direccion" class="form-label">Dirección *</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" class="form-control" id="celular" name="celular"
                    value="<?= htmlspecialchars($empresa['celular'] ?? '') ?>">
            </div>
            <!-- Colores y tipografía -->
            <div class="col-md-3 mb-3">
                <label for="color_principal" class="form-label">Color principal</label>
                <input type="color" class="form-control form-control-color" id="color_principal" name="color_principal"
                    value="<?= htmlspecialchars($color_principal) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_secundario" class="form-label">Color secundario</label>
                <input type="color" class="form-control form-control-color" id="color_secundario" name="color_secundario"
                    value="<?= htmlspecialchars($color_secundario) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_footer" class="form-label">Color del footer</label>
                <input type="color" class="form-control form-control-color" id="color_footer" name="color_footer"
                    value="<?= htmlspecialchars($color_footer) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_botones" class="form-label">Color de los botones</label>
                <input type="color" class="form-control form-control-color" id="color_botones" name="color_botones"
                    value="<?= htmlspecialchars($color_botones) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="color_texto" class="form-label">Color del texto</label>
                <input type="color" class="form-control form-control-color" id="color_texto" name="color_texto"
                    value="<?= htmlspecialchars($color_texto) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="tamano_letra" class="form-label">Tamaño de letra (ej: 1rem, 18px)</label>
                <input type="text" class="form-control" id="tamano_letra" name="tamano_letra"
                    value="<?= htmlspecialchars($tamano_letra) ?>">
            </div>
            <!-- Logo y firma -->
            <div class="col-md-6 mb-3 text-center">
                <label class="form-label fw-bold">Logo actual:</label><br>
                <img src="<?= htmlspecialchars($logo) ?>?v=<?= time() ?>" alt="Logo de la empresa" style="max-height: 80px;">
            </div>
            <div class="col-md-6 mb-3 text-center">
                <label class="form-label fw-bold">Firma actual:</label><br>
                <img src="<?= htmlspecialchars($firma) ?>?v=<?= time() ?>" alt="Firma de la empresa" style="max-height: 80px;">
            </div>
            <div class="col-md-6 mb-3">
                <label for="logo" class="form-label">Actualizar logo (PNG):</label>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/png">
            </div>
            <div class="col-md-6 mb-3">
                <label for="firma" class="form-label">Actualizar firma (PNG):</label>
                <input type="file" class="form-control" id="firma" name="firma" accept="image/png">
            </div>
            <!-- Frase y oferta -->
            <div class="col-md-12 mb-3">
                <label for="frase_promocion" class="form-label">Frase promocional</label>
                <input type="text" class="form-control" id="frase_promocion" name="frase_promocion"
                    value="<?= htmlspecialchars($frase_promocion) ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label for="oferta_mes" class="form-label">Oferta del mes</label>
                <input type="text" class="form-control" id="oferta_mes" name="oferta_mes"
                    value="<?= htmlspecialchars($oferta_mes) ?>">
            </div>
            <!-- Imágenes del carrusel -->
            <div class="col-md-12 mb-3">
                <label class="form-label">Imágenes del carrusel actuales:</label>
                <div class="d-flex flex-wrap">
                    <?php
                    if (!is_array($imagenes_carrusel)) $imagenes_carrusel = [];
                    foreach ($imagenes_carrusel as $idx => $img): ?>
                        <div style="position:relative; margin:5px;">
                            <img src="<?= htmlspecialchars($img) ?>?v=<?= time() ?>" style="max-height:60px;">
                            <input type="checkbox" name="eliminar_carrusel[]" value="<?= $idx ?>" style="position:absolute;top:0;right:0;">
                            <small style="display:block;text-align:center;">Eliminar</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label for="imagenes_carrusel" class="form-label mt-2">Agregar nuevas imágenes (PNG/JPG/JPEG):</label>
                <input type="file" class="form-control" id="imagenes_carrusel" name="imagenes_carrusel[]" accept="image/png, image/jpeg" multiple>
            </div>

            <!-- Imágenes institucionales -->
            <div class="col-md-12 mb-3">
                <label class="form-label">Imágenes institucionales actuales:</label>
                <div class="d-flex flex-wrap">
                    <?php
                    if (!is_array($imagenes_institucionales)) $imagenes_institucionales = [];
                    foreach ($imagenes_institucionales as $idx => $img): ?>
                        <div style="position:relative; margin:5px;">
                            <img src="<?= htmlspecialchars($img) ?>?v=<?= time() ?>" style="max-height:60px; border-radius:8px;">
                            <input type="checkbox" name="eliminar_institucional[]" value="<?= $idx ?>" style="position:absolute;top:0;right:0;">
                            <small style="display:block;text-align:center;">Eliminar</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label for="imagenes_institucionales" class="form-label mt-2">Agregar nuevas imágenes (PNG/JPG/JPEG):</label>
                <input type="file" class="form-control" id="imagenes_institucionales" name="imagenes_institucionales[]" accept="image/png, image/jpeg" multiple>
            </div>

            <!-- Servicios (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="servicios" class="form-label">Servicios (formato JSON: [{"titulo":"...","descripcion":"..."}])</label>
                <textarea class="form-control" id="servicios" name="servicios" rows="3"><?= htmlspecialchars(json_encode($servicios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Testimonios (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="testimonios" class="form-label">Testimonios (formato JSON: [{"texto":"...","autor":"..."}])</label>
                <textarea class="form-control" id="testimonios" name="testimonios" rows="3"><?= htmlspecialchars(json_encode($testimonios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Redes sociales (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="redes_sociales" class="form-label">Redes sociales (formato JSON: [{"nombre":"Facebook","url":"..."},...])</label>
                <textarea class="form-control" id="redes_sociales" name="redes_sociales" rows="2"><?= htmlspecialchars(json_encode($redes_sociales, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Menú personalizable -->
            <div class="col-md-3 mb-3">
                <label for="menu_inicio" class="form-label">Texto menú Inicio</label>
                <input type="text" class="form-control" id="menu_inicio" name="menu_inicio"
                       value="<?= htmlspecialchars($menu_inicio) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_servicios" class="form-label">Texto menú Servicios</label>
                <input type="text" class="form-control" id="menu_servicios" name="menu_servicios"
                       value="<?= htmlspecialchars($menu_servicios) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_testimonios" class="form-label">Texto menú Testimonios</label>
                <input type="text" class="form-control" id="menu_testimonios" name="menu_testimonios"
                       value="<?= htmlspecialchars($menu_testimonios) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_contacto" class="form-label">Texto menú Contacto</label>
                <input type="text" class="form-control" id="menu_contacto" name="menu_contacto"
                       value="<?= htmlspecialchars($menu_contacto) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
