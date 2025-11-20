<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-user-edit me-2"></i>Editar Usuário #<?= (int)$profile['id'] ?></h1>
    <div class="d-flex align-items-center gap-2">
      <a href="/secure/adm/users" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="/secure/adm/users/update" class="row g-3 needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="id" value="<?= (int)$profile['id'] ?>">
        <div class="col-md-6">
          <label class="form-label"><i class="fas fa-user me-2"></i>Nome</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
          <div class="invalid-feedback">Informe o nome.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
          <div class="invalid-feedback">Informe um email válido.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="/secure/adm/users/view?id=<?= (int)$profile['id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-times me-2"></i>Cancelar</a>
          <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
