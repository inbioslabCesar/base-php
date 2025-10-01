<?php
require_once __DIR__ . '/../auth/empresa_config.php';
?>
</main>
<footer class="footer-gradient text-center text-white py-4 mt-4" style="font-size: 1.05rem; width: 100%; letter-spacing:1px;">
    <div class="container">
        <span class="fw-bold">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></span>
        <span class="ms-2">. Todos los derechos reservados.</span>
    </div>
</footer>
<style>
    .footer-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 24px 24px 0 0;
        box-shadow: 0 -2px 16px #764ba233;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>