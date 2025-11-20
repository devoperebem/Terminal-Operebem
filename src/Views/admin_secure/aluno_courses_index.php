<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-graduation-cap"></i> Cursos (Aluno)</h1>
    <a href="/secure/adm/aluno/courses/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Novo Curso</a>
  </div>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
            <th>ID</th>
            <th>Título</th>
            <th class="text-end">Preço (centavos)</th>
            <th>Gratuito</th>
            <th>Atualizado</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($courses ?? []) as $c): ?>
          <tr>
            <td><?= (int)($c['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end"><?= (int)($c['price_cents'] ?? 0) ?></td>
            <td><?= !empty($c['is_free']) ? 'Sim' : 'Não' ?></td>
            <td><?= htmlspecialchars((string)($c['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/lessons?course_id=<?= (int)$c['id'] ?>" title="Aulas"><i class="fas fa-list"></i></a>
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/bunny" title="Bunny Tools"><i class="fas fa-video"></i></a>
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/courses/edit?id=<?= (int)$c['id'] ?>" title="Editar"><i class="fas fa-edit"></i></a>
                <form method="post" action="/secure/adm/aluno/courses/move" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="dir" value="up">
                  <button class="btn btn-outline-secondary" title="Subir" aria-label="Subir"><i class="fas fa-arrow-up"></i></button>
                </form>
                <form method="post" action="/secure/adm/aluno/courses/move" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="dir" value="down">
                  <button class="btn btn-outline-secondary" title="Descer" aria-label="Descer"><i class="fas fa-arrow-down"></i></button>
                </form>
                <form method="post" action="/secure/adm/aluno/courses/delete" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este curso? Todas as aulas associadas também serão excluídas.');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-outline-danger" title="Excluir" aria-label="Excluir"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($courses)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhum curso encontrado</td></tr>
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
