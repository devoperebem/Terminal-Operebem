<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2">Cursos (Aluno)</h1>
    <a href="/secure/adm/aluno/courses/create" class="btn btn-primary btn-sm">Novo Curso</a>
  </div>
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
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/lessons?course_id=<?= (int)$c['id'] ?>">Aulas</a>
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/bunny">Bunny</a>
                <a class="btn btn-outline-secondary" href="/secure/adm/aluno/courses/edit?id=<?= (int)$c['id'] ?>">Editar</a>
                <form method="post" action="/secure/adm/aluno/courses/move" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="dir" value="up">
                  <button class="btn btn-outline-secondary" title="Subir" aria-label="Subir"></button>
                </form>
                <form method="post" action="/secure/adm/aluno/courses/move" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="dir" value="down">
                  <button class="btn btn-outline-secondary" title="Descer" aria-label="Descer"></button>
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
