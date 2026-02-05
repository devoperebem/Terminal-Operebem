<?php
ob_start();
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">CRM - Gestão de Leads</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
      <i class="fas fa-plus me-2"></i>Adicionar Lead
    </button>
  </div>

  <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible alert-auto-dismiss fade show">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible alert-auto-dismiss fade show">
      <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-address-book fa-2x text-primary mb-2"></i>
          <div class="h5 mb-0"><?= $stats['total'] ?? 0 ?></div>
          <small class="text-muted">Total</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
          <div class="h5 mb-0 text-primary"><?= $stats['new'] ?? 0 ?></div>
          <small class="text-muted">Novos</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-phone fa-2x text-info mb-2"></i>
          <div class="h5 mb-0 text-info"><?= $stats['contacted'] ?? 0 ?></div>
          <small class="text-muted">Contatados</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-star fa-2x text-warning mb-2"></i>
          <div class="h5 mb-0 text-warning"><?= $stats['qualified'] ?? 0 ?></div>
          <small class="text-muted">Qualificados</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
          <div class="h5 mb-0 text-success"><?= $stats['converted'] ?? 0 ?></div>
          <small class="text-muted">Convertidos</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center">
        <div class="card-body">
          <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
          <div class="h5 mb-0 text-danger"><?= $stats['lost'] ?? 0 ?></div>
          <small class="text-muted">Perdidos</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-4">
          <input type="text" class="form-control" name="q" placeholder="Buscar por nome, email ou telefone..." value="<?= htmlspecialchars($searchQuery ?? '') ?>">
        </div>
        <div class="col-md-3">
          <select class="form-select" name="status">
            <option value="all" <?= ($currentStatus ?? 'all') === 'all' ? 'selected' : '' ?>>Todos os Status</option>
            <option value="new" <?= ($currentStatus ?? '') === 'new' ? 'selected' : '' ?>>Novos</option>
            <option value="contacted" <?= ($currentStatus ?? '') === 'contacted' ? 'selected' : '' ?>>Contatados</option>
            <option value="qualified" <?= ($currentStatus ?? '') === 'qualified' ? 'selected' : '' ?>>Qualificados</option>
            <option value="converted" <?= ($currentStatus ?? '') === 'converted' ? 'selected' : '' ?>>Convertidos</option>
            <option value="lost" <?= ($currentStatus ?? '') === 'lost' ? 'selected' : '' ?>>Perdidos</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
        <div class="col-md-2">
          <a href="/secure/adm/crm" class="btn btn-secondary w-100">Limpar</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Leads Table -->
  <div class="card">
    <div class="card-body">
      <?php if (empty($leads)): ?>
        <div class="text-center py-5">
          <p class="text-muted mb-3">Nenhum lead encontrado</p>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
            Adicionar Primeiro Lead
          </button>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leads as $lead): ?>
                <tr>
                  <td><?= $lead['id'] ?></td>
                  <td><?= htmlspecialchars($lead['name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lead['email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($lead['phone'] ?? '-') ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($lead['source'] ?? 'Desconhecido') ?></span></td>
                  <td>
                    <?php
                    $statusColors = [
                      'new' => 'primary',
                      'contacted' => 'info',
                      'qualified' => 'warning',
                      'converted' => 'success',
                      'lost' => 'danger',
                    ];
                    $statusLabels = [
                      'new' => 'Novo',
                      'contacted' => 'Contatado',
                      'qualified' => 'Qualificado',
                      'converted' => 'Convertido',
                      'lost' => 'Perdido',
                    ];
                    $status = $lead['status'] ?? 'new';
                    $color = $statusColors[$status] ?? 'secondary';
                    $label = $statusLabels[$status] ?? $status;
                    ?>
                    <span class="badge bg-<?= $color ?>"><?= $label ?></span>
                  </td>
                  <td><?= date('d/m/Y H:i', strtotime($lead['created_at'] ?? 'now')) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewLead(<?= $lead['id'] ?>)">Ver</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Lead Modal -->
<div class="modal fade" id="addLeadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Adicionar Lead</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/secure/adm/crm/add">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Telefone</label>
            <input type="text" class="form-control" name="phone">
          </div>
          <div class="mb-3">
            <label class="form-label">Origem</label>
            <select class="form-select" name="source">
              <option value="website">Website</option>
              <option value="referral">Indicação</option>
              <option value="social">Redes Sociais</option>
              <option value="email">Email Marketing</option>
              <option value="other">Outro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea class="form-control" name="notes" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Adicionar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function viewLead(id) {
  window.location.href = '/secure/adm/crm/view/' + id;
}
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
