<?php
ob_start();
$editing = isset($material) && is_array($material);
$id = (string)($material['id'] ?? '');
$courseId = (int)($selected_course_id ?? ($material['course_id'] ?? 0));
$lessonId = (int)($material['lesson_id'] ?? 0);
$titleVal = (string)($material['title'] ?? '');
$descriptionVal = (string)($material['description'] ?? '');
$fileUrlVal = (string)($material['file_url'] ?? '');
$fileTypeVal = (string)($material['file_type'] ?? 'pdf');
$fileSizeVal = (int)($material['file_size'] ?? 0);
$isFree = !empty($material['is_free']);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-file-arrow-up"></i> <?= $editing ? 'Editar material' : 'Novo material' ?></h1>
    <a href="/secure/adm/aluno/materials<?= $courseId > 0 ? '?course_id=' . $courseId : '' ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= $editing ? '/secure/adm/aluno/materials/update' : '/secure/adm/aluno/materials/store' ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Curso</label>
            <select class="form-select" name="course_id" required>
              <option value="">Selecione</option>
              <?php foreach (($courses ?? []) as $course): ?>
                <option value="<?= (int)($course['id'] ?? 0) ?>" <?= $courseId === (int)($course['id'] ?? 0) ? 'selected' : '' ?>>
                  #<?= (int)($course['id'] ?? 0) ?> - <?= htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Aula (opcional)</label>
            <select class="form-select" name="lesson_id">
              <option value="">Material do curso inteiro</option>
              <?php foreach (($lessons ?? []) as $lesson): ?>
                <option value="<?= (int)($lesson['id'] ?? 0) ?>" <?= $lessonId === (int)($lesson['id'] ?? 0) ? 'selected' : '' ?>>
                  Aula #<?= (int)($lesson['id'] ?? 0) ?> - <?= htmlspecialchars((string)($lesson['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Titulo</label>
            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($titleVal, ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <input type="text" class="form-control" name="file_type" value="<?= htmlspecialchars($fileTypeVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="pdf, xlsx, pptx...">
          </div>

          <div class="col-12">
            <label class="form-label">Descricao</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($descriptionVal, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Upload de arquivo (opcional)</label>
            <input type="file" class="form-control" name="file" accept=".pdf,.xlsx,.xls,.ppt,.pptx,.doc,.docx,.zip,.csv,.txt">
            <small class="text-muted">Se enviar arquivo, a URL sera preenchida automaticamente (Hostinger media origin quando configurado; Bunny/local como fallback).</small>
          </div>

          <div class="col-md-8">
            <label class="form-label">URL do arquivo</label>
            <input type="url" class="form-control" name="file_url" value="<?= htmlspecialchars($fileUrlVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://cdn.operebem.com/materials/exemplo.pdf">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tamanho (bytes)</label>
            <input type="number" class="form-control" name="file_size" min="0" step="1" value="<?= $fileSizeVal ?>">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_free" id="is_free" <?= $isFree ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_free">Disponivel para usuarios sem acesso pago</label>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Salvar e sincronizar</button>
          <a href="/secure/adm/aluno/materials<?= $courseId > 0 ? '?course_id=' . $courseId : '' ?>" class="btn btn-secondary">Cancelar</a>
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
