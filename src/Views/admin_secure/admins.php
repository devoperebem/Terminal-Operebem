<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="fas fa-users-cog me-2"></i>Administradores</h1>
  </div>

  <?php if (!empty($ok)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Ação concluída com sucesso.</div>
  <?php endif; ?>
  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-user-plus me-2"></i>Criar novo admin</h5>
          <form method="POST" action="/secure/adm/admins/create" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="col-12">
              <label class="form-label"><i class="fas fa-user me-2"></i>Usuário</label>
              <input type="text" name="username" class="form-control" required minlength="3">
              <div class="invalid-feedback">Informe um usuário (mín. 3 caracteres)</div>
            </div>
            <div class="col-12">
              <label class="form-label"><i class="fas fa-lock me-2"></i>Senha</label>
              <input type="password" name="password" class="form-control" required minlength="8">
              <div class="invalid-feedback">Informe uma senha forte (mín. 8 caracteres)</div>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Criar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card">
        <div class="card-body table-responsive">
          <h5 class="card-title"><i class="fas fa-list me-2"></i>Admins cadastrados</h5>
          <table class="table align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Criado em</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($admins)): foreach ($admins as $a): ?>
                <tr>
                  <td>#<?= (int)$a['id'] ?></td>
                  <td><?= htmlspecialchars($a['username']) ?></td>
                  <td><?= htmlspecialchars($a['created_at']) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted">Nenhum admin encontrado</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
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
