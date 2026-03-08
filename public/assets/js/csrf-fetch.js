(function(){
  // Init CSRF token from meta
  try {
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.content) { window.__CSRF_TOKEN = m.content; }
  } catch(e) {}

  const origFetch = window.fetch;

  async function refreshCsrfToken() {
    try {
      const r = await origFetch('/csrf/token', { method: 'GET', credentials: 'same-origin' });
      if (!r.ok) return false;
      const j = await r.json();
      if (j && j.token) {
        window.__CSRF_TOKEN = j.token;
        document.querySelectorAll('meta[name="csrf-token"]').forEach(function(el){ el.setAttribute('content', j.token); });
        document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){ el.value = j.token; });
        return true;
      }
    } catch(e) { /* ignore */ }
    return false;
  }

  window.fetch = function(input, init){
    init = init || {};
    let urlStr = '';
    if (typeof input === 'string') { urlStr = input; }
    else if (input && typeof input === 'object' && typeof input.url === 'string') { urlStr = input.url; }

    let isCrossOrigin = false;
    try {
      const u = new URL(urlStr, window.location.origin);
      isCrossOrigin = (u.origin !== window.location.origin);
    } catch(e) { /* ignore */ }

    const headers = (init.headers instanceof Headers) ? init.headers : new Headers(init.headers || {});
    if (!isCrossOrigin && !headers.has('X-CSRF-Token')) {
      headers.set('X-CSRF-Token', window.__CSRF_TOKEN || '');
    }
    if (!isCrossOrigin && !headers.has('X-Requested-With')) {
      headers.set('X-Requested-With', 'XMLHttpRequest');
    }
    init.headers = headers;

    return origFetch(input, init).then(async function(res){
      if (!isCrossOrigin && res.status === 403) {
        const ok = await refreshCsrfToken();
        if (!ok) return res;
        const retryInit = Object.assign({}, init);
        const retryHeaders = (retryInit.headers instanceof Headers) ? retryInit.headers : new Headers(retryInit.headers || {});
        retryHeaders.set('X-CSRF-Token', window.__CSRF_TOKEN || '');
        retryInit.headers = retryHeaders;
        return origFetch(input, retryInit);
      }
      // 401 refresh flows handled in app-specific endpoints — left intact
      return res;
    });
  };
})();
