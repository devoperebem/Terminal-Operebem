<?php
ob_start();
?>
<section class="py-5" style="min-height: calc(100vh - 100px); display:flex; align-items:center;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-7">
        <div class="text-center mb-4">
          <img src="/assets/images/favicon.png" alt="" width="42" height="42" class="rounded mb-2"/>
          <h1 class="h4 mb-0">Secure Admin</h1>
          <div class="text-muted small">Acesso restrito</div>
        </div>
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4 p-md-5">
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">Falha no login (<?= htmlspecialchars($error) ?>)</div>
            <?php endif; ?>
            <form method="POST" action="/secure/adm/login" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
              <div class="mb-3">
                <label class="form-label">Usuário</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"></span>
                  <input type="text" class="form-control" name="username" placeholder="email" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Senha</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"></span>
                  <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirme que não é um robô</label>
                <div id="captcha-admin-login" class="d-flex justify-content-center"></div>
                <input type="hidden" name="captcha_token" id="captcha_token_admin" value="">
                <input type="hidden" name="rc_token" id="rc_token_admin" value="">
              </div>
              <div class="d-grid">
                <button class="btn btn-primary btn-lg" id="admLoginBtn" disabled>Entrar</button>
              </div>
            </form>
            <div class="text-center mt-3">
              <a href="/secure/adm/forgot" class="link-secondary small">Esqueci a senha</a>
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </div>
  </section>
<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script src="/sdk/captcha-sdk.js"></script>
<script>
(function(){
  function init(){
    if (!window.OpereBemCaptcha) { setTimeout(init, 120); return; }
    try { if (window.__ob_captcha_admin && typeof window.__ob_captcha_admin.destroy==='function'){ window.__ob_captcha_admin.destroy(); } } catch(e){}
    var btn = document.getElementById('admLoginBtn'); if (btn) btn.disabled = true;
    window.__ob_captcha_admin = new OpereBemCaptcha({
      container: '#captcha-admin-login',
      mode: 'modal',
      apiBase: '',
      onSuccess: function(tok){ var el=document.getElementById('captcha_token_admin'); if (el) el.value = tok || ''; var b=document.getElementById('admLoginBtn'); if (b) b.disabled = !(!!tok); },
      onExpired: function(){ var el=document.getElementById('captcha_token_admin'); if (el) el.value = ''; var b=document.getElementById('admLoginBtn'); if (b) b.disabled = true; },
      onError: function(){ var el=document.getElementById('captcha_token_admin'); if (el) el.value = ''; var b=document.getElementById('admLoginBtn'); if (b) b.disabled = true; }
    });
  }
  document.addEventListener('DOMContentLoaded', init);
})();
// reCAPTCHA v3 token for admin_login
(function(){
  function updateRc(){
    try {
      if (!window.__RECAPTCHA_V3_SITE_KEY) { return; }
      if (!window.grecaptcha) { setTimeout(updateRc, 250); return; }
      grecaptcha.ready(function(){
        grecaptcha.execute(window.__RECAPTCHA_V3_SITE_KEY, {action: 'admin_login'}).then(function(token){
          var el = document.getElementById('rc_token_admin'); if (el) el.value = token || '';
        });
      });
    } catch(e) { /* no-op */ }
  }
  document.addEventListener('DOMContentLoaded', updateRc);
})();
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
