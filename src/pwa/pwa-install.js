(function () {
  var VISITS_KEY = 'public_pwa_visits_v1';
  var DISMISS_KEY = 'public_pwa_install_dismissed_v1';
  var MIN_VISITS = 2;
  var MAX_DISMISS_DAYS = 7;
  var installPromptEvent = null;

  function safeInt(value) {
    var n = parseInt(String(value || '0'), 10);
    return Number.isFinite(n) ? n : 0;
  }

  function bumpVisits() {
    var current = safeInt(localStorage.getItem(VISITS_KEY));
    var next = current + 1;
    localStorage.setItem(VISITS_KEY, String(next));
    return next;
  }

  function dismissedRecently() {
    var raw = localStorage.getItem(DISMISS_KEY);
    if (!raw) {
      return false;
    }
    var last = safeInt(raw);
    if (!last) {
      return false;
    }
    var days = (Date.now() - last) / 86400000;
    return days < MAX_DISMISS_DAYS;
  }

  function markDismissed() {
    localStorage.setItem(DISMISS_KEY, String(Date.now()));
  }

  function createButton() {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'pwa-install-btn';
    btn.textContent = 'Instalar acceso rapido';
    btn.style.cssText = [
      'position:fixed',
      'left:16px',
      'bottom:16px',
      'z-index:10020',
      'border:0',
      'border-radius:999px',
      'padding:10px 14px',
      'font-size:13px',
      'font-weight:700',
      'background:#0ea5e9',
      'color:#ffffff',
      'box-shadow:0 10px 22px rgba(2,132,199,0.36)',
      'cursor:pointer',
      'max-width:min(80vw,260px)'
    ].join(';');
    return btn;
  }

  function showInstallButton() {
    if (!installPromptEvent) {
      return;
    }
    if (document.getElementById('pwa-install-btn')) {
      return;
    }
    if (dismissedRecently()) {
      return;
    }

    var visits = safeInt(localStorage.getItem(VISITS_KEY));
    if (visits < MIN_VISITS) {
      return;
    }

    var btn = createButton();

    btn.addEventListener('click', function () {
      installPromptEvent.prompt();
      installPromptEvent.userChoice
        .then(function () {
          installPromptEvent = null;
          if (btn.parentNode) {
            btn.parentNode.removeChild(btn);
          }
        })
        .catch(function () {
          markDismissed();
        });
    });

    btn.addEventListener('contextmenu', function (ev) {
      ev.preventDefault();
      markDismissed();
      if (btn.parentNode) {
        btn.parentNode.removeChild(btn);
      }
    });

    document.body.appendChild(btn);
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    installPromptEvent = event;
    showInstallButton();
  });

  window.addEventListener('appinstalled', function () {
    installPromptEvent = null;
    var btn = document.getElementById('pwa-install-btn');
    if (btn && btn.parentNode) {
      btn.parentNode.removeChild(btn);
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    bumpVisits();
    showInstallButton();
  });
})();
