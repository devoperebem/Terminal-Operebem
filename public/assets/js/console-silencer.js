(function(){
  const PATTERNS = [
    /sslecal2\.investing\.com/i,
    /streaming\.forexpros\.com/i,
    /fxindex2\.js/i,
    /ecaltool/i,
    /tradingview-widget/i,
    /widget-sheriff/i,
    /google-analytics\.com/i
  ];
  function shouldBlock(args){
    try{ return args.some(a => typeof a === 'string' && PATTERNS.some(rx => rx.test(a))); }catch(_){ return false; }
  }
  ['log','info','warn','error'].forEach(fn => {
    const orig = console[fn];
    console[fn] = function(){ if (!shouldBlock(Array.from(arguments))) { return orig.apply(console, arguments); } };
  });
  window.addEventListener('error', function(e){ if (e && e.message && PATTERNS.some(rx => rx.test(e.message))) { e.preventDefault(); } }, true);
  window.addEventListener('unhandledrejection', function(e){ try{ const msg = (e.reason && (e.reason.message || e.reason)) + ''; if (PATTERNS.some(rx => rx.test(msg))) { e.preventDefault(); } }catch(_){ } }, true);
})();
