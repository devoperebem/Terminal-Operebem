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
          <div class="text-muted small">Verificação de código</div>
        </div>
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4 p-md-5">
            <?php if (!empty($ok)): ?>
              <div class="alert alert-success">
                <?php if ($ok === 'sent'): ?>
                  Código enviado com sucesso! Verifique seu email e insira o código abaixo.
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">
                <?php if ($error === 'csrf'): ?>
                  Erro de validação. Por favor, tente novamente.
                <?php elseif ($error === 'ratelimit'): ?>
                  Muitas tentativas. Aguarde alguns minutos.
                <?php elseif ($error === 'invalid'): ?>
                  Código inválido ou expirado. Solicite um novo código.
                <?php elseif ($error === 'server'): ?>
                  Erro no servidor. Tente novamente mais tarde.
                <?php else: ?>
                  Não foi possível processar (<?= htmlspecialchars($error) ?>)
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <form method="POST" action="/secure/adm/forgot/verify" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <div class="mb-3">
                <label class="form-label">Código de verificação</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text">🔑</span>
                  <input type="text" class="form-control" name="code" placeholder="000000" required autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}"/>
                </div>
                <div class="form-text">Digite o código de 6 dígitos recebido por email</div>
              </div>
              <div class="alert alert-info">
                <small>
                  <strong>ℹ️ Importante:</strong> Após verificar o código, uma nova senha será gerada automaticamente e enviada para o seu email.
                </small>
              </div>
              <div class="d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Verificar código</button>
              </div>
            </form>

            <div class="text-center mt-3">
              <a href="/secure/adm/forgot" class="btn btn-link">Solicitar novo código</a>
              <span class="text-muted mx-2">|</span>
              <a href="/secure/adm/login" class="btn btn-link">Voltar ao login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
