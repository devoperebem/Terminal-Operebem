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
          <div class="text-muted small">Verificação em duas etapas</div>
        </div>
        <?php if (!empty($error)): ?>
          <?php if ($error === 'email'): ?>
            <div class="alert alert-warning">Não foi possível enviar o email com o código 2FA. Verifique sua caixa de spam ou tente novamente mais tarde.</div>
          <?php else: ?>
            <div class="alert alert-danger">Falha na verificação (<?= htmlspecialchars($error) ?>)</div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4 p-md-5">
            <p class="text-secondary">Enviamos um código de 6 dígitos para o e-mail do administrador. Digite abaixo. O código é válido por 5 minutos.</p>
            <form method="POST" action="/secure/adm/2fa" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
              <div class="mb-3">
                <label class="form-label">Código</label>
                <div class="input-group input-group-lg">
                  <input type="text" name="code" id="admin2faCode" inputmode="numeric" pattern="\\d{6}" maxlength="6" class="form-control form-control-lg" placeholder="000000" required>
                  <button class="btn btn-outline-secondary" type="button" id="paste2faBtn" aria-label="Colar código" title="Colar código"><i class="fas fa-clipboard"></i></button>
                </div>
                <div class="form-text">Apenas números. Ex.: 123456</div>
                <div class="invalid-feedback">Informe o código de 6 dígitos.</div>
              </div>
              <div class="d-grid">
                <button class="btn btn-primary btn-lg">Verificar</button>
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
    function onlyDigits(s){ return String(s||'').replace(/\D+/g,''); }
    var pasteBtn = document.getElementById('paste2faBtn');
    if (pasteBtn && navigator.clipboard && navigator.clipboard.readText) {
      pasteBtn.addEventListener('click', async function(){
        try {
          var txt = await navigator.clipboard.readText();
          var code = onlyDigits(txt).slice(0,6);
          var input = document.getElementById('admin2faCode');
          if (input) { input.value = code; input.focus(); }
        } catch(e) {
          try {
            var input = document.getElementById('admin2faCode');
            if (input) input.focus();
          } catch(_){}}
      });
    }
  })();
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
