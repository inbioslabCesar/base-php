(function () {
  var STORAGE_KEY = 'app_network_events_v1';
  var STORAGE_LIMIT = 200;
  var BANNER_ID = 'app-network-status-banner';

  function nowIso() {
    return new Date().toISOString();
  }

  function safeParse(jsonText, fallback) {
    try {
      var parsed = JSON.parse(jsonText);
      return parsed;
    } catch (err) {
      return fallback;
    }
  }

  function readEvents() {
    var raw = localStorage.getItem(STORAGE_KEY);
    var list = safeParse(raw || '[]', []);
    return Array.isArray(list) ? list : [];
  }

  function saveEvents(events) {
    var normalized = Array.isArray(events) ? events.slice(-STORAGE_LIMIT) : [];
    localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
  }

  function pushEvent(type, extra) {
    var data = {
      type: type,
      at: nowIso(),
      online: navigator.onLine,
      path: location.pathname + location.search
    };

    if (extra && typeof extra === 'object') {
      for (var key in extra) {
        if (Object.prototype.hasOwnProperty.call(extra, key)) {
          data[key] = extra[key];
        }
      }
    }

    var events = readEvents();
    events.push(data);
    saveEvents(events);
  }

  function formatBannerText() {
    return navigator.onLine ? 'Conexion activa' : 'Sin internet: los cambios pueden no guardarse';
  }

  function formatBannerStyle(isOnline) {
    return isOnline
      ? 'background:#e8f7ed;color:#0f5132;border:1px solid #badbcc;'
      : 'background:#fff3cd;color:#664d03;border:1px solid #ffecb5;';
  }

  function ensureBanner() {
    var existing = document.getElementById(BANNER_ID);
    if (existing) {
      return existing;
    }

    var banner = document.createElement('div');
    banner.id = BANNER_ID;
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.style.cssText = [
      'position:fixed',
      'right:16px',
      'bottom:16px',
      'z-index:1090',
      'padding:8px 12px',
      'border-radius:12px',
      'font-size:13px',
      'font-weight:600',
      'box-shadow:0 8px 20px rgba(0,0,0,0.12)',
      'max-width:min(80vw,360px)',
      'line-height:1.35'
    ].join(';');

    document.body.appendChild(banner);
    return banner;
  }

  function renderStatus() {
    var banner = ensureBanner();
    var online = navigator.onLine;
    banner.textContent = formatBannerText();
    banner.style.cssText += ';' + formatBannerStyle(online);
  }

  function collectNavMetrics() {
    if (!window.performance || typeof performance.getEntriesByType !== 'function') {
      return;
    }

    var navEntries = performance.getEntriesByType('navigation');
    if (!navEntries || !navEntries[0]) {
      return;
    }

    var nav = navEntries[0];
    var payload = {
      domContentLoadedMs: Math.round(nav.domContentLoadedEventEnd),
      loadEventMs: Math.round(nav.loadEventEnd),
      transferSize: typeof nav.transferSize === 'number' ? nav.transferSize : null,
      encodedBodySize: typeof nav.encodedBodySize === 'number' ? nav.encodedBodySize : null
    };
    pushEvent('page_metrics', payload);
  }

  window.appNetworkBaseline = {
    getEvents: function () {
      return readEvents();
    },
    clearEvents: function () {
      localStorage.removeItem(STORAGE_KEY);
    },
    pushManualEvent: function (type, extra) {
      pushEvent(type, extra || {});
    }
  };

  document.addEventListener('DOMContentLoaded', function () {
    renderStatus();
    collectNavMetrics();
    pushEvent('page_open');
  });

  window.addEventListener('online', function () {
    pushEvent('online');
    renderStatus();
  });

  window.addEventListener('offline', function () {
    pushEvent('offline');
    renderStatus();
  });
})();
