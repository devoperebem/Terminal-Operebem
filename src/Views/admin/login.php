<?php
ob_start();
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-3 text-center"><i class="fas fa-shield-alt me-2"></i>Admin - Login</h1>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Falha na autenticação. Tente novamente.</div>
          <?php endif; ?>
          <form method="POST" action="/admin/login" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="mb-3">
              <label class="form-label">Usuário</label>
              <input type="text" name="username" class="form-control" required>
              <div class="invalid-feedback">Informe o usuário.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Senha</label>
              <input type="password" name="password" class="form-control" required>
              <div class="invalid-feedback">Informe a senha.</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Entrar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
