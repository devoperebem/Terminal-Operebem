(function(){
  try {
    var gaIdMeta = document.querySelector('meta[name="ga-id"]');
    if (!gaIdMeta || !gaIdMeta.content) return;
    var gaId = gaIdMeta.content;
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }
    window.gtag = gtag;
    var dnt = false;
    try {
      dnt = (navigator.doNotTrack == '1' || window.doNotTrack == '1' || navigator.msDoNotTrack == '1');
    } catch(e) {}
    gtag('consent', 'default', { 'analytics_storage': dnt ? 'denied' : 'granted' });
    gtag('js', new Date());
    var cfg = { anonymize_ip: true };
    var userIdMeta = document.querySelector('meta[name="ga-user-id"]');
    if (userIdMeta && userIdMeta.content) cfg.user_id = userIdMeta.content;
    gtag('config', gaId, cfg);

    window.GA = window.GA || {};
    window.GA.event = function(name, params){ try { gtag('event', name, params || {}); } catch(_) {} };
    document.addEventListener('click', function(ev){
      var a = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
      if (!a) return;
      var href = a.getAttribute('href') || '';
      if (!href) return;
      var url;
      try { url = new URL(href, location.href); } catch(_) { return; }
      var isExternal = url.origin !== location.origin;
      var evName = a.getAttribute('data-gtag') || '';
      if (evName) { window.GA.event(evName, { link_url: url.href, link_domain: url.hostname, location_path: location.pathname }); return; }
      if (isExternal) { window.GA.event('outbound_click', { link_url: url.href, link_domain: url.hostname, location_path: location.pathname }); }
    }, { passive: true });
  } catch(e) {
    // noop
  }
})();
