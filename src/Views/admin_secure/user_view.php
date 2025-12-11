<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">Usuário #<?= (int)$profile['id'] ?></h1>
    <div class="d-flex align-items-center gap-2">
      <a href="/secure/adm/users" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
  </div>

  <?php
    // Resolver avatar do usuário
    $avatarUrl = '';
    if (!empty($profile['id'])) {
      $root = dirname(__DIR__, 2); // src/Views -> novo_public_html
      $publicPath = $root . DIRECTORY_SEPARATOR . 'public';
      $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
      $cands = [ $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.png', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.jpg', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.jpeg', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.webp' ];
      foreach ($cands as $c) { if (is_file($c)) { $mtime = @filemtime($c) ?: time(); $avatarUrl = '/uploads/avatars/' . basename($c) . '?v=' . $mtime; break; } }
    }
    $avatarFallbackSvg = '/assets/images/user_image.png';
  ?>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-body d-flex flex-column align-items-center text-center">
          <img src="<?= htmlspecialchars($avatarUrl ?: $avatarFallbackSvg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;border:1px solid var(--border-color);"/>
          <div class="w-100">
            <div class="form-floating mb-2">
              <input type="text" class="form-control" id="uv_name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" readonly>
              <label for="uv_name">Nome</label>
            </div>
            <div class="form-floating mb-2">
              <input type="email" class="form-control" id="uv_email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" readonly>
              <label for="uv_email">Email</label>
            </div>
            <div class="form-floating mb-2">
              <input type="text" class="form-control" id="uv_cpf" value="<?= htmlspecialchars($profile['cpf'] ?? '') ?>" readonly>
              <label for="uv_cpf">CPF</label>
            </div>
            <div class="form-floating mb-2">
              <input type="text" class="form-control" id="uv_created" value="<?= htmlspecialchars($profile['created_at'] ?? '') ?>" readonly>
              <label for="uv_created">Criado em</label>
            </div>
            <div class="form-floating">
              <input type="text" class="form-control" id="uv_lastlogin" value="<?= htmlspecialchars($profile['last_login_at'] ?? '-') ?>" readonly>
              <label for="uv_lastlogin">Último login</label>
            </div>
          </div>
          <div class="d-grid gap-2 w-100 mt-3">
            <a href="/secure/adm/users/edit?id=<?= (int)$profile['id'] ?>" class="btn btn-primary">Editar</a>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteUserModal" data-user-id="<?= (int)$profile['id'] ?>" data-user-name="<?= htmlspecialchars($profile['name'] ?? '') ?>">
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-header">Estatísticas</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Tickets</div>
                  <div class="h5 mb-0"><?= (int)($stats['tickets_total'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Abertos</div>
                  <div class="h5 mb-0"><?= (int)($stats['tickets_open'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Logins total</div>
                  <div class="h5 mb-0"><?= (int)($stats['logins_total'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Logins (30d)</div>
                  <div class="h5 mb-0"><?= (int)($stats['logins_success_30d'] ?? 0) ?></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gamificação -->
          <div class="border-top pt-3">
            <h6 class="mb-3">Gamificação</h6>
            <div class="row g-3">
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-primary bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">XP Total</div>
                    <div class="h5 mb-0 text-primary"><?= number_format((int)($profile['xp'] ?? 0)) ?></div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-warning bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">Nível</div>
                    <div class="h5 mb-0 text-warning"><?= (int)($profile['level'] ?? 1) ?></div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-danger bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">Streak</div>
                    <div class="h5 mb-0 text-danger"><?= (int)($profile['streak'] ?? 0) ?> 🔥</div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100">
                  <div class="card-body py-3 text-center">
                    <a href="/secure/adm/gamification/user/<?= (int)$profile['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                      Ver Detalhes
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center"><span>Acessos por Curso</span><small class="text-muted">Usuário #<?= (int)$profile['id'] ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso</th><th>Expira</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
              <?php if (!empty($courseGrants)): foreach ($courseGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php $exp = $g['expires_at'] ?? null; $active = empty($exp) || (strtotime((string)$exp) > time()); ?>
                    <?php if ($active): ?><span class="badge text-bg-success">Ativo</span><?php else: ?><span class="badge text-bg-secondary">Expirado</span><?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="lifetime">
                      <button type="submit" class="btn btn-sm btn-outline-success">Vitalício</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted">Sem concessões</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center"><span>Acessos por Aula</span><small class="text-muted">Usuário #<?= (int)$profile['id'] ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso · Aula</th><th>Expira</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
              <?php if (!empty($lessonGrants)): foreach ($lessonGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['course_title'], ENT_QUOTES, 'UTF-8') ?> · #<?= (int)$g['position'] ?> <?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php $exp = $g['expires_at'] ?? null; $active = empty($exp) || (strtotime((string)$exp) > time()); ?>
                    <?php if ($active): ?><span class="badge text-bg-success">Ativo</span><?php else: ?><span class="badge text-bg-secondary">Expirado</span><?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="lifetime">
                      <button type="submit" class="btn btn-sm btn-outline-success">Vitalício</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted">Sem concessões</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card">
        <div class="card-header">Progresso recente</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso</th><th>Aula</th><th>Posição</th><th>Duração</th><th>Último segundo</th><th>Concluído</th><th>Atualizado</th></tr></thead>
              <tbody>
              <?php if (!empty($progress)): foreach ($progress as $p): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$p['course_title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td>#<?= (int)$p['position'] ?></td>
                  <td><?= (int)floor(((int)($p['duration_seconds'] ?? 0))/60) ?> min</td>
                  <td><?= (int)($p['last_second'] ?? 0) ?> s</td>
                  <td><?= !empty($p['completed']) ? 'Sim' : 'Não' ?></td>
                  <td><?= htmlspecialchars((string)$p['updated_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center text-muted">Sem progresso</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
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
