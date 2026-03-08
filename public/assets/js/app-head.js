(function(){
  try {
    var saved = localStorage.getItem('terminal_theme');
    var t = saved ? (saved === 'dark' ? 'all-black' : saved) : ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'all-black' : 'light');
    document.documentElement.className = t;
  } catch(e){}
})();
