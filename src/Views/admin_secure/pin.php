<?php
ob_start();
?>
<section class="py-5" style="min-height: calc(100vh - 100px); display:flex; align-items:center;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6">
        <div class="text-center mb-4">
          <img src="/assets/images/favicon.png" alt="" width="42" height="42" class="rounded mb-2"/>
          <h1 class="h4 mb-0">Secure Admin</h1>
          <div class="text-muted small">PIN de Seguranca</div>
        </div>
        <?php if (!empty($error)): ?>
          <?php
          $pinErrors = [
            'invalid' => 'PIN incorreto. Tente novamente.',
            'csrf'    => 'Token CSRF invalido. Recarregue a pagina.',
            'ratelimit' => 'Muitas tentativas. Aguarde 15 minutos.',
            'missing' => 'PIN de seguranca nao configurado no servidor. Contate o administrador.',
          ];
          $msg = $pinErrors[$error] ?? 'Erro desconhecido.';
          ?>
          <div class="alert alert-danger alert-auto-dismiss">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $msg ?>
          </div>
        <?php endif; ?>
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4 p-md-5">
            <p class="text-secondary text-center mb-4" style="font-size:0.9rem;">
              <i class="fas fa-shield-alt me-1"></i>
              Digite o PIN de 6 digitos para acessar a area de login.
            </p>
            <form method="POST" action="/secure/adm/pin" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <div class="mb-4">
                <div class="d-flex justify-content-center gap-2" id="pinInputs">
                  <?php for ($i = 0; $i < 6; $i++): ?>
                  <input type="password" inputmode="numeric" maxlength="1" class="form-control form-control-lg text-center pin-digit" 
                         style="width:48px; height:56px; font-size:1.5rem; font-weight:700; border-radius:12px; -webkit-text-security:disc;"
                         data-index="<?= $i ?>" autocomplete="off" required>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="pin" id="pinValue">
              </div>
              <div class="d-grid">
                <button class="btn btn-primary btn-lg" type="submit" id="pinSubmitBtn" disabled>
                  <i class="fas fa-arrow-right me-2"></i>Continuar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script>
(function(){
  var digits = document.querySelectorAll('.pin-digit');
  var hidden = document.getElementById('pinValue');
  var btn = document.getElementById('pinSubmitBtn');
  
  function updateHidden(){
    var val = '';
    digits.forEach(function(d){ val += d.value; });
    hidden.value = val;
    btn.disabled = val.length < 6;
  }

  digits.forEach(function(input, idx){
    input.addEventListener('input', function(e){
      this.value = this.value.replace(/\D/g, '').slice(0,1);
      updateHidden();
      if (this.value && idx < 5) digits[idx+1].focus();
    });
    input.addEventListener('keydown', function(e){
      if (e.key === 'Backspace' && !this.value && idx > 0) {
        digits[idx-1].focus();
        digits[idx-1].value = '';
        updateHidden();
      }
    });
    input.addEventListener('paste', function(e){
      e.preventDefault();
      var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0,6);
      for (var i = 0; i < 6; i++) { digits[i].value = paste[i] || ''; }
      updateHidden();
      var next = Math.min(paste.length, 5);
      digits[next].focus();
    });
  });

  if (digits[0]) digits[0].focus();
})();
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
