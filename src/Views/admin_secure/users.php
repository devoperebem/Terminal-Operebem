<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">Usuários</h1>
    <div class="d-flex align-items-center gap-2">
      <?php $qs = http_build_query(['q' => $q ?? '', 'from' => $from ?? '', 'to' => $to ?? '', 'limit' => $limit ?? 100]); ?>
      <a href="/secure/adm/users/export<?= $qs ? ('?'.$qs) : '' ?>" class="btn btn-success btn-sm">Exportar Excel</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <form class="row g-2 mb-3" method="GET" action="/secure/adm/users">
        <div class="col-12 col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Buscar por nome ou email" value="<?= htmlspecialchars($q ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
          <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
          <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
          <select name="limit" class="form-select">
            <?php foreach ([50,100,200,500,1000] as $opt): ?>
              <option value="<?= $opt ?>" <?= (int)($limit ?? 100) === $opt ? 'selected' : '' ?>><?= $opt ?> por página</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2 d-grid">
          <button class="btn btn-primary">Filtrar</button>
        </div>
      </form>
      <div class="d-flex flex-wrap align-items-stretch gap-2 mb-3">
        <div class="card flex-fill" style="min-width: 220px;">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Total de usuários</div>
                <div class="h5 mb-0"><?= isset($stats['total']) ? (int)$stats['total'] : (int)count($users ?? []) ?></div>
              </div>
              <i class="fas fa-users fa-2x text-primary opacity-25"></i>
            </div>
          </div>
        </div>
        <div class="card flex-fill" style="min-width: 220px;">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Ativos (7 dias)</div>
                <div class="h5 mb-0"><?= (int)($stats['active_7d'] ?? 0) ?></div>
              </div>
              <i class="fas fa-user-check fa-2x text-success opacity-25"></i>
            </div>
          </div>
        </div>
        <div class="card flex-fill" style="min-width: 220px;">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Ativos (48h)</div>
                <div class="h5 mb-0"><?= (int)($stats['active_48h'] ?? 0) ?></div>
              </div>
              <i class="fas fa-clock fa-2x text-info opacity-25"></i>
            </div>
          </div>
        </div>
        <div class="card flex-fill" style="min-width: 220px;">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Novos (7 dias)</div>
                <div class="h5 mb-0"><?= (int)($stats['new_7d'] ?? 0) ?></div>
              </div>
              <i class="fas fa-user-plus fa-2x text-warning opacity-25"></i>
            </div>
          </div>
        </div>
        <div class="card flex-fill" style="min-width: 220px;">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Novos (48h)</div>
                <div class="h5 mb-0"><?= (int)($stats['new_48h'] ?? 0) ?></div>
              </div>
              <i class="fas fa-star fa-2x text-danger opacity-25"></i>
            </div>
          </div>
        </div>
      </div>
      <h5 class="card-title">Lista de usuários</h5>
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Criado em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($users)): foreach ($users as $u): ?>
            <tr>
              <td>#<?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['name'] ?? '') ?></td>
              <td><a href="mailto:<?= htmlspecialchars($u['email'] ?? '') ?>"><?= htmlspecialchars($u['email'] ?? '') ?></a></td>
              <td><?= htmlspecialchars($u['created_at'] ?? '') ?></td>
              <td class="text-end">
                <!-- Ações desktop/tablet -->
                <div class="btn-group btn-group-sm d-none d-sm-inline-flex" role="group">
                  <a href="/secure/adm/users/view?id=<?= (int)$u['id'] ?>" class="btn btn-outline-secondary" title="Ver detalhes"><i class="fas fa-eye"></i></a>
                  <a href="/secure/adm/users/edit?id=<?= (int)$u['id'] ?>" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                  <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteUserModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= htmlspecialchars($u['name'] ?? '') ?>" title="Excluir">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <!-- Ações mobile -->
                <div class="d-sm-none">
                  <div class="d-grid gap-2 mt-2">
                    <a href="/secure/adm/users/view?id=<?= (int)$u['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eye me-2"></i>Ver</a>
                    <a href="/secure/adm/users/edit?id=<?= (int)$u['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-2"></i>Editar</a>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteUserModal" data-user-id="<?= (int)$u['id'] ?>" data-user-name="<?= htmlspecialchars($u['name'] ?? '') ?>">
                      <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted">Nenhum usuário encontrado</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$csrf = htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8');
$scripts = <<<SCRIPTS
<div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" aria-labelledby="confirmDeleteUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="/secure/adm/users/delete">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteUserLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir o usuário <span id="deleteUserName" class="fw-semibold">#</span>? Esta ação não pode ser desfeita.
      </div>
      <div class="modal-footer">
        <input type="hidden" name="csrf_token" value="{$csrf}">
        <input type="hidden" name="id" id="deleteUserId" value="0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Excluir</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('confirmDeleteUserModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var uid = button.getAttribute('data-user-id');
    var uname = button.getAttribute('data-user-name') || ('#'+uid);
    var idInput = modal.querySelector('#deleteUserId');
    var nameSpan = modal.querySelector('#deleteUserName');
    if (idInput) idInput.value = uid || '0';
    if (nameSpan) nameSpan.textContent = uname;
  });
})();
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
