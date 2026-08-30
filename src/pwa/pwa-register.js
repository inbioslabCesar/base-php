(function () {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  var cfg = window.APP_PWA || {};
  var swUrl = String(cfg.swUrl || '');
  var scope = String(cfg.scope || '/');

  if (!swUrl) {
    return;
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.register(swUrl, { scope: scope }).catch(function (err) {
      console.warn('No se pudo registrar service worker:', err);
    });
  });
})();
