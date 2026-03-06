<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-shield-halved"></i> Auditoria de Sync</h1>
    <a href="/secure/adm/aluno" class="btn btn-outline-secondary btn-sm">Voltar ao Portal Aluno</a>
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
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
      <form method="post" action="/secure/adm/aluno/sync-audit/check" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-heart-pulse me-1"></i>Check Portal</button>
      </form>

      <form method="post" action="/secure/adm/aluno/sync-audit/retry-pricing" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-rotate me-1"></i>Retry Pricing</button>
      </form>

      <form method="post" action="/secure/adm/aluno/sync-audit/retry-materials" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <select name="course_id" class="form-select form-select-sm" required>
          <option value="">Curso para retry de materiais</option>
          <?php foreach (($courses ?? []) as $course): ?>
            <option value="<?= (int)($course['id'] ?? 0) ?>">#<?= (int)($course['id'] ?? 0) ?> - <?= htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-rotate me-1"></i>Retry Materiais</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th>Data/Hora</th>
            <th>Endpoint</th>
            <th>Status</th>
            <th>Sucesso</th>
            <th>Payload Hash</th>
            <th>Erro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($entries ?? []) as $entry): ?>
            <tr>
              <td><?= htmlspecialchars((string)($entry['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><code><?= htmlspecialchars((string)($entry['endpoint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
              <td><?= (int)($entry['status'] ?? 0) ?></td>
              <td><?= !empty($entry['success']) ? 'Sim' : 'Nao' ?></td>
              <td><code><?= htmlspecialchars((string)($entry['payload_hash'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
              <td><?= htmlspecialchars((string)($entry['error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($entries)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Sem registros de sync ainda.</td>
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
