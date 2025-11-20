<?php
ob_start();
$lesson = $lesson ?? null;
$editing = is_array($lesson);
$course = $course ?? ['id'=>0,'title'=>'Curso'];
$id = (int)($lesson['id'] ?? 0);
$courseId = (int)($course['id'] ?? (int)($lesson['course_id'] ?? 0));
$titleVal = (string)($lesson['title'] ?? '');
$descVal = (string)($lesson['description'] ?? '');
$posVal = (int)($lesson['position'] ?? 0);
$bvidVal = (string)($lesson['bunny_video_id'] ?? '');
$durVal = (int)($lesson['duration_seconds'] ?? 0);
$thumbVal = (string)($lesson['thumbnail_url'] ?? '');
$prevAnimVal = (string)($lesson['preview_animation_url'] ?? '');
$isFree = !empty($lesson['is_free_preview']);
$isEnabled = array_key_exists('is_enabled', (array)$lesson) ? !empty($lesson['is_enabled']) : true;
$playerOpts = (string)($lesson['player_options'] ?? '');
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2">
      
      <?= $editing ? 'Editar Aula' : 'Nova Aula' ?> · <?= htmlspecialchars((string)($course['title'] ?? '')) ?>
    </h1>
    <a href="/secure/adm/aluno/lessons?course_id=<?= (int)$courseId ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>
  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= $editing ? '/secure/adm/aluno/lessons/update' : '/secure/adm/aluno/lessons/store' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
        <input type="hidden" name="course_id" value="<?= (int)$courseId ?>"/>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$id ?>"/><?php endif; ?>
        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <div class="mb-3">
              <label class="form-label">Título</label>
              <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($titleVal) ?>" required/>
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição</label>
              <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($descVal) ?></textarea>
            </div>
            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label">Posição</label>
                <input type="number" class="form-control" name="position" value="<?= (int)$posVal ?>" min="0" step="1"/>
              </div>
              <div class="col-sm-4">
                <label class="form-label">Duração (seg)</label>
                <input type="number" class="form-control" name="duration_seconds" value="<?= (int)$durVal ?>" min="0" step="1"/>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Opções do Player (JSON opcional)</label>
              <textarea class="form-control" name="player_options" rows="4" placeholder='{"autoplay":true}'><?= htmlspecialchars($playerOpts) ?></textarea>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="mb-3">
              <label class="form-label">Bunny Video ID</label>
              <input type="text" class="form-control" name="bunny_video_id" value="<?= htmlspecialchars($bvidVal) ?>" required/>
            </div>
            <div class="mb-3">
              <label class="form-label">Thumbnail URL (opcional)</label>
              <input type="url" class="form-control" name="thumbnail_url" value="<?= htmlspecialchars($thumbVal) ?>"/>
            </div>
            <div class="mb-3">
              <label class="form-label">Preview Animation URL (opcional)</label>
              <input type="url" class="form-control" name="preview_animation_url" value="<?= htmlspecialchars($prevAnimVal) ?>"/>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="is_free_preview" id="is_free_preview" <?= $isFree ? 'checked' : '' ?>/>
              <label class="form-check-label" for="is_free_preview">Prévia grátis</label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" <?= $isEnabled ? 'checked' : '' ?>/>
              <label class="form-check-label" for="is_enabled">Ativo</label>
            </div>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Salvar</button>
          <a href="/secure/adm/aluno/lessons?course_id=<?= (int)$courseId ?>" class="btn btn-secondary">Cancelar</a>
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
