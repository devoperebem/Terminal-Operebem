(function(){
  function getCurrentTheme() {
    var cls = document.documentElement.classList;
    if (cls.contains('dark-blue') || cls.contains('all-black')) return 'dark';
    return 'light';
  }
  var __tvTapeRendering = false;
  var __tvTapeTimer = null;
  function renderWidgetLong() {
    var container = document.getElementById('widget_long');
    if (!container) return;
    if (__tvTapeRendering) {
      clearTimeout(__tvTapeTimer);
      __tvTapeTimer = setTimeout(renderWidgetLong, 200);
      return;
    }
    __tvTapeRendering = true;
    try {
      container.innerHTML = '';
      var inner = document.createElement('div');
      inner.className = 'tradingview-widget-container__widget';
      container.appendChild(inner);
      var theme = getCurrentTheme();
      var override = container.getAttribute('data-symbols');
      var symbolsCfg;
      if (override) {
        try { symbolsCfg = JSON.parse(override); } catch(_) { symbolsCfg = null; }
      }
      if (Array.isArray(symbolsCfg)) {
        try {
          symbolsCfg = symbolsCfg.map(function(s){
            return { proName: s.proName, description: (s.description || s.title || '') };
          });
        } catch(_){ /* noop */ }
      }
      var cfg = {
        symbols: symbolsCfg || [
          { description: "Bitcoin",  proName: "CRYPTO:BTCUSD" },
          { description: "Etherium", proName: "CRYPTO:ETHUSD" },
          { description: "XRP",       proName: "CRYPTO:XRPUSD" },
          { description: "Solana",    proName: "CRYPTO:SOLUSD" },
          { description: "BNB",       proName: "CRYPTO:BNBUSD" },
          { description: "Dogecoin",  proName: "CRYPTO:DOGEUSD" },
          { description: "USDT",      proName: "COINBASE:USDTUSD" },
          { description: "USDC",      proName: "BITSTAMP:USDCUSD" },
          { description: "DXY",       proName: "INDEX:DXY" }
        ],
        showSymbolLogo: true,
        displayMode: "compact",
        locale: "br",
        colorTheme: theme,
        isTransparent: theme !== 'light'
      };
      var script = document.createElement('script');
      script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
      script.async = true;
      script.text = JSON.stringify(cfg);
      script.onload = function(){ setTimeout(function(){ __tvTapeRendering = false; }, 50); };
      script.onerror = function(){ __tvTapeRendering = false; };
      container.appendChild(script);
    } catch (e) {
      __tvTapeRendering = false;
    }
  }
  document.addEventListener('DOMContentLoaded', function(){
    clearTimeout(__tvTapeTimer);
    __tvTapeTimer = setTimeout(renderWidgetLong, 50);
  });
  new MutationObserver(function(muts){
    if (muts.some(function(m){ return m.attributeName === 'class'; })) {
      clearTimeout(__tvTapeTimer);
      __tvTapeTimer = setTimeout(renderWidgetLong, 120);
    }
  }).observe(document.documentElement, { attributes: true });
})();
