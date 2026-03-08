(function(){
  try {
    var urlMeta = document.querySelector('meta[name="ws-url"]');
    var keyMeta = document.querySelector('meta[name="ws-key"]');
    var cfg = {};
    if (urlMeta && urlMeta.content) cfg.url = urlMeta.content;
    if (keyMeta && keyMeta.content) cfg.apiKey = keyMeta.content;
    // Fallback: same-origin /ws
    if (!cfg.url) {
      try {
        var loc = window.location;
        var scheme = (loc.protocol === 'https:') ? 'wss://' : 'ws://';
        cfg.url = scheme + loc.host + '/ws';
      } catch(e) {}
    }
    window.WEBSOCKET_CONFIG = cfg;
  } catch(e) { window.WEBSOCKET_CONFIG = {}; }
})();
