<?php
ob_start();
$items = $items ?? [];
$q = (string)($q ?? '');
$page = (int)($page ?? 1);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-users"></i> Matrículas (Aluno)</h1>
    <a href="/secure/adm/aluno/courses" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
  </div>
  <form method="get" class="mb-3">
    <div class="input-group">
      <input type="text" name="q" class="form-control" placeholder="Buscar por email, nome, ID de usuário ou título do curso" value="<?= htmlspecialchars($q) ?>"/>
      <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
    </div>
  </form>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Curso</th>
            <th>Status</th>
            <th>Expira</th>
            <th>Criado</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $r): $active = ((string)($r['status'] ?? '') === 'paid') && (empty($r['expires_at']) || strtotime((string)$r['expires_at']) > time()); ?>
            <tr>
              <td>
                <div class="fw-semibold">#<?= (int)$r['user_id'] ?> · <?= htmlspecialchars((string)($r['user_name'] ?? '')) ?></div>
                <div class="small text-muted"><?= htmlspecialchars((string)($r['user_email'] ?? '')) ?></div>
              </td>
              <td>
                <div class="fw-semibold">#<?= (int)$r['course_id'] ?> · <?= htmlspecialchars((string)($r['course_title'] ?? '')) ?></div>
              </td>
              <td><?= $active ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Expirado/Cancelado</span>' ?></td>
              <td><?= htmlspecialchars((string)($r['expires_at'] ?? '—')) ?></td>
              <td><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <form method="post" action="/secure/adm/aluno/enrollments/extend">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>"/>
                    <input type="hidden" name="mode" value="7d"/>
                    <button class="btn btn-outline-secondary" type="submit">+7d</button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/enrollments/extend">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>"/>
                    <input type="hidden" name="mode" value="30d"/>
                    <button class="btn btn-outline-secondary" type="submit">+30d</button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/enrollments/extend">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>"/>
                    <input type="hidden" name="mode" value="lifetime"/>
                    <button class="btn btn-outline-success" type="submit">Vitalício</button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/enrollments/cancel" onsubmit="return confirm('Cancelar matrícula?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>"/>
                    <button class="btn btn-outline-danger" type="submit"></button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/enrollments/reactivate">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>"/>
                    <button class="btn btn-outline-primary" type="submit"></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma matrícula encontrada</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
