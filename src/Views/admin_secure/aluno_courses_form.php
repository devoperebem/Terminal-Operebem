<?php
ob_start();
$editing = isset($course) && is_array($course);
$id = (int)($course['id'] ?? 0);
$titleVal = (string)($course['title'] ?? '');
$descVal = (string)($course['description'] ?? '');
$isFree = !empty($course['is_free']);
$price = (int)($course['price_cents'] ?? 0);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2">
      
      <?= $editing ? 'Editar Curso (Aluno)' : 'Novo Curso (Aluno)' ?>
    </h1>
    <a href="/secure/adm/aluno/courses" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>
  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= $editing ? '/secure/adm/aluno/courses/update' : '/secure/adm/aluno/courses/store' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>" />
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= (int)$id ?>" />
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($titleVal, ENT_QUOTES, 'UTF-8') ?>" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($descVal, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="row g-3">
          <div class="col-sm-4">
            <label class="form-label">Preço (centavos)</label>
            <input type="number" class="form-control" name="price_cents" value="<?= (int)$price ?>" min="0" step="1" />
          </div>
          <div class="col-sm-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_free" id="is_free" <?= $isFree ? 'checked' : '' ?> />
              <label class="form-check-label" for="is_free">Gratuito</label>
            </div>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Salvar</button>
          <a href="/secure/adm/aluno/courses" class="btn btn-secondary">Cancelar</a>
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
