(function(){
  function siteKey(){
    try {
      if (window.__RECAPTCHA_V3_SITE_KEY) return window.__RECAPTCHA_V3_SITE_KEY;
      var meta = document.querySelector('meta[name="recaptcha-site-key"]');
      if (meta && meta.content) return meta.content;
    } catch(e) {}
    return '';
  }

  function generate(elId, action){
    try {
      var el = document.getElementById(elId);
      if (!el) return;
      var key = siteKey();
      if (!key) return;
      if (!window.grecaptcha) { setTimeout(function(){ generate(elId, action); }, 250); return; }
      grecaptcha.ready(function(){
        try {
          grecaptcha.execute(key, { action: action }).then(function(token){
            el.value = token || '';
          });
        } catch(e) { /* no-op */ }
      });
    } catch(e) { /* no-op */ }
  }

  function init(){
    generate('rc_token_login', 'user_login');
    generate('rc_token_forgot', 'forgot_password');
    generate('rc_token_admin', 'admin_login');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Regenerate tokens when relevant modals open
  try {
    document.addEventListener('shown.bs.modal', function(ev){
      var id = ev.target && ev.target.id;
      if (id === 'loginModal') generate('rc_token_login', 'user_login');
      if (id === 'forgotPasswordModal') generate('rc_token_forgot', 'forgot_password');
    });
  } catch(e) { /* ignore */ }
})();
