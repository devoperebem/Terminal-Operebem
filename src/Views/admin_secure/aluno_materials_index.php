<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-paperclip"></i> Materiais do Portal</h1>
    <div class="d-flex gap-2">
      <a href="/secure/adm/aluno/materials/create<?= !empty($selected_course_id) ? '?course_id=' . (int)$selected_course_id : '' ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Novo material</a>
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
      <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-end gap-2">
      <form method="get" action="/secure/adm/aluno/materials" class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label class="form-label mb-1">Curso</label>
          <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach (($courses ?? []) as $course): ?>
              <option value="<?= (int)($course['id'] ?? 0) ?>" <?= ((int)($selected_course_id ?? 0) === (int)($course['id'] ?? 0)) ? 'selected' : '' ?>>
                #<?= (int)($course['id'] ?? 0) ?> - <?= htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <?php if (!empty($selected_course_id)): ?>
      <form method="post" action="/secure/adm/aluno/materials/sync-course" class="ms-auto d-inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="course_id" value="<?= (int)$selected_course_id ?>">
        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-rotate me-1"></i>Sincronizar curso</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>Titulo</th>
            <th>Escopo</th>
            <th>Tipo</th>
            <th>Tamanho</th>
            <th>Gratis</th>
            <th>Arquivo</th>
            <th class="text-end">Acoes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($materials ?? []) as $material): ?>
            <tr>
              <td><?= htmlspecialchars((string)($material['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if (($material['lesson_id'] ?? null) === null): ?>
                  Curso
                <?php else: ?>
                  Aula #<?= (int)$material['lesson_id'] ?>
                <?php endif; ?>
              </td>
              <td><code><?= htmlspecialchars((string)($material['file_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
              <td><?= (int)($material['file_size'] ?? 0) ?></td>
              <td><?= !empty($material['is_free']) ? 'Sim' : 'Nao' ?></td>
              <td>
                <?php $fileUrl = (string)($material['preview_url'] ?? ($material['file_url'] ?? '')); ?>
                <?php if ($fileUrl !== ''): ?>
                  <a href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                    <?= !empty($material['is_free']) ? 'Abrir' : 'Abrir (assinado)' ?>
                  </a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <a class="btn btn-outline-secondary" href="/secure/adm/aluno/materials/edit?id=<?= urlencode((string)($material['id'] ?? '')) ?>" title="Editar"><i class="fas fa-edit"></i></a>
                  <form method="post" action="/secure/adm/aluno/materials/delete" class="d-inline" onsubmit="return confirm('Confirma excluir este material?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($material['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-outline-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($materials)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Nenhum material cadastrado para o curso selecionado.</td>
            </tr>
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
