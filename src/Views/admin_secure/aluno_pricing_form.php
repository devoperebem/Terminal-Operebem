<?php
ob_start();
$editing = isset($plan) && is_array($plan);
$id = (string)($plan['id'] ?? '');
$name = (string)($plan['name'] ?? '');
$slug = (string)($plan['slug'] ?? '');
$priceDisplay = (string)($plan['price_display'] ?? '');
$priceSubtitle = (string)($plan['price_subtitle'] ?? '');
$description = (string)($plan['description'] ?? '');
$features = '';
if (!empty($plan['features']) && is_array($plan['features'])) {
    $features = implode("\n", array_map(static fn($v) => (string)$v, $plan['features']));
}
$ctaLabel = (string)($plan['cta_label'] ?? 'Assinar');
$ctaUrl = (string)($plan['cta_url'] ?? '');
$isHighlighted = !empty($plan['is_highlighted']);
$badgeText = (string)($plan['badge_text'] ?? '');
$position = (int)($plan['position'] ?? 1);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-tag"></i> <?= $editing ? 'Editar plano' : 'Novo plano' ?></h1>
    <a href="/secure/adm/aluno/pricing" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= $editing ? '/secure/adm/aluno/pricing/update' : '/secure/adm/aluno/pricing/store' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" placeholder="free, plus, pro">
          </div>
          <div class="col-md-6">
            <label class="form-label">Preco exibido</label>
            <input type="text" class="form-control" name="price_display" value="<?= htmlspecialchars($priceDisplay, ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Subtitulo do preco</label>
            <input type="text" class="form-control" name="price_subtitle" value="<?= htmlspecialchars($priceSubtitle, ENT_QUOTES, 'UTF-8') ?>" placeholder="/mes">
          </div>
          <div class="col-12">
            <label class="form-label">Descricao</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Features (uma por linha)</label>
            <textarea class="form-control" name="features" rows="6" required><?= htmlspecialchars($features, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">CTA label</label>
            <input type="text" class="form-control" name="cta_label" value="<?= htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">CTA URL</label>
            <input type="url" class="form-control" name="cta_url" value="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Posicao</label>
            <input type="number" min="1" step="1" class="form-control" name="position" value="<?= (int)$position ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" name="is_highlighted" id="is_highlighted" <?= $isHighlighted ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_highlighted">Plano destacado</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Badge</label>
            <input type="text" class="form-control" name="badge_text" value="<?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?>" placeholder="POPULAR, ANUAL, etc">
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Salvar e sincronizar</button>
          <a href="/secure/adm/aluno/pricing" class="btn btn-secondary">Cancelar</a>
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
