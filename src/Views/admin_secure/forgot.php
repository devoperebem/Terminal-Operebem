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
          <div class="text-muted small">Recuperação de acesso</div>
        </div>
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4 p-md-5">
            <?php if (!empty($ok)): ?>
              <div class="alert alert-success">Se o usuário existir, enviamos um código de verificação para o email cadastrado.</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">Não foi possível processar (<?= htmlspecialchars($error) ?>)</div>
            <?php endif; ?>
            <form method="POST" action="/secure/adm/forgot" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <div class="mb-3">
                <label class="form-label">Usuário (email)</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"></span>
                  <input type="email" class="form-control" name="username" placeholder="email@dominio.com" required autocomplete="username"/>
                </div>
              </div>
              <div class="d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Enviar código</button>
              </div>
            </form>
            <div class="text-center mt-3">
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
