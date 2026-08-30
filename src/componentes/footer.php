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
        background: var(--ui-footer-bg, #0d6efd);
        color: var(--ui-footer-text, #ffffff);
        border-radius: 24px 24px 0 0;
        box-shadow: 0 -2px 16px rgba(0, 0, 0, 0.18);
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(rtrim((string)BASE_URL, '/\\') . '/assets/js/offline-readiness-baseline.js?v=20260822', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>