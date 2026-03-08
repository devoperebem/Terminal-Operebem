<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-tags"></i> Pricing do Portal do Aluno</h1>
    <div class="d-flex gap-2">
      <form method="post" action="/secure/adm/aluno/pricing/sync" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-rotate me-1"></i>Sincronizar agora</button>
      </form>
      <a href="/secure/adm/aluno/pricing/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Novo plano</a>
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

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>Pos.</th>
            <th>Nome</th>
            <th>Slug</th>
            <th>Preco</th>
            <th>Destaque</th>
            <th>CTA URL</th>
            <th class="text-end">Acoes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($plans ?? []) as $plan): ?>
            <tr>
              <td><?= (int)($plan['position'] ?? 0) ?></td>
              <td><?= htmlspecialchars((string)($plan['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><code><?= htmlspecialchars((string)($plan['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
              <td>
                <?= htmlspecialchars((string)($plan['price_display'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($plan['price_subtitle'])): ?>
                  <span class="text-muted ms-1"><?= htmlspecialchars((string)$plan['price_subtitle'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
              </td>
              <td><?= !empty($plan['is_highlighted']) ? 'Sim' : 'Nao' ?></td>
              <td>
                <?php $ctaUrl = (string)($plan['cta_url'] ?? ''); ?>
                <?php if ($ctaUrl !== ''): ?>
                  <a href="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Link</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <a class="btn btn-outline-secondary" href="/secure/adm/aluno/pricing/edit?id=<?= urlencode((string)($plan['id'] ?? '')) ?>" title="Editar"><i class="fas fa-edit"></i></a>
                  <form method="post" action="/secure/adm/aluno/pricing/delete" class="d-inline" onsubmit="return confirm('Confirma excluir este plano?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($plan['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-outline-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($plans)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Nenhum plano configurado.</td>
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
