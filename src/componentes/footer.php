<?php
require_once __DIR__ . '/../auth/empresa_config.php';
?>
</main>
<footer class="bg-light text-center text-muted py-3 mt-4" style="font-size: 0.95rem; width: 100%;">
    © <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?>
    . Todos los derechos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>