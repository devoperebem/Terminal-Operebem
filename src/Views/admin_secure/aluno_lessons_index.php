<?php
ob_start();
$course = $course ?? ['id'=>0,'title'=>'Curso'];
$lessons = $lessons ?? [];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-book-open"></i> Aulas · <?= htmlspecialchars((string)($course['title'] ?? '')) ?></h1>
    <div class="d-flex gap-2">
      <a href="/secure/adm/aluno/bunny" class="btn btn-outline-secondary btn-sm"><i class="fas fa-video me-1"></i>Bunny Tools</a>
      <a href="/secure/adm/aluno/lessons/create?course_id=<?= (int)$course['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Nova Aula</a>
      <a href="/secure/adm/aluno/courses" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>
  </div>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible alert-auto-dismiss fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible alert-auto-dismiss fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Título</th>
            <th>Video ID</th>
            <th>Prévia</th>
            <th>Ativo</th>
            <th>Duração</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lessons as $L): ?>
            <tr>
              <td style="width:72px;">
                <div class="btn-group btn-group-sm" role="group">
                  <form method="post" action="/secure/adm/aluno/lessons/reorder">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="id" value="<?= (int)$L['id'] ?>"/>
                    <input type="hidden" name="dir" value="up"/>
                    <button class="btn btn-outline-secondary" title="Acima" type="submit"><i class="fas fa-arrow-up"></i></button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/lessons/reorder">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="id" value="<?= (int)$L['id'] ?>"/>
                    <input type="hidden" name="dir" value="down"/>
                    <button class="btn btn-outline-secondary" title="Abaixo" type="submit"><i class="fas fa-arrow-down"></i></button>
                  </form>
                </div>
              </td>
              <td><?= htmlspecialchars((string)$L['title']) ?></td>
              <td class="text-truncate" style="max-width:280px;"><?= htmlspecialchars((string)$L['bunny_video_id']) ?></td>
              <td><?= !empty($L['is_free_preview']) ? '<span class="badge text-bg-success">Sim</span>' : '<span class="badge text-bg-secondary">Não</span>' ?></td>
              <td><?= !empty($L['is_enabled']) ? '<span class="badge text-bg-primary">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
              <td><?= (int)($L['duration_seconds'] ?? 0) ?>s</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <form method="post" action="/secure/adm/aluno/lessons/toggle">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="id" value="<?= (int)$L['id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"/>
                    <button class="btn btn-outline-secondary" type="submit" title="<?= !empty($L['is_enabled']) ? 'Desativar' : 'Ativar' ?>"><i class="fas fa-<?= !empty($L['is_enabled']) ? 'toggle-on' : 'toggle-off' ?>"></i></button>
                  </form>
                  <form method="post" action="/secure/adm/aluno/lessons/set-preview">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="id" value="<?= (int)$L['id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"/>
                    <button class="btn btn-outline-success" type="submit" title="Definir como prévia"><i class="fas fa-eye"></i></button>
                  </form>
                  <a href="/secure/adm/aluno/lessons/edit?id=<?= (int)$L['id'] ?>" class="btn btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
                  <form method="post" action="/secure/adm/aluno/lessons/delete" onsubmit="return confirm('Excluir aula?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                    <input type="hidden" name="id" value="<?= (int)$L['id'] ?>"/>
                    <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"/>
                    <button class="btn btn-outline-danger" type="submit" title="Excluir"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($lessons)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma aula</td></tr>
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
