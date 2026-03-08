<?php
ob_start();
$lead = $lead ?? [];
$linkedUser = $linkedUser ?? null;
$interactions = $interactions ?? [];

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
$interactionTypes = [
    'note' => ['label' => 'Nota', 'icon' => 'fas fa-sticky-note', 'color' => 'secondary'],
    'call' => ['label' => 'Ligação', 'icon' => 'fas fa-phone', 'color' => 'info'],
    'email' => ['label' => 'Email', 'icon' => 'fas fa-envelope', 'color' => 'primary'],
    'meeting' => ['label' => 'Reunião', 'icon' => 'fas fa-handshake', 'color' => 'success'],
    'status_change' => ['label' => 'Status', 'icon' => 'fas fa-exchange-alt', 'color' => 'warning'],
];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Lead #<?= (int)$lead['id'] ?></h1>
    <a href="/secure/adm/crm" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
  </div>

  <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Lead Info -->
    <div class="col-lg-5">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-user me-2"></i>Informações do Lead</span>
          <span class="badge bg-<?= $statusColors[$lead['status'] ?? 'new'] ?? 'secondary' ?>">
            <?= $statusLabels[$lead['status'] ?? 'new'] ?? 'Novo' ?>
          </span>
        </div>
        <div class="card-body">
          <form method="POST" action="/secure/adm/crm/update">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
            
            <div class="mb-3">
              <label class="form-label fw-bold">Nome</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($lead['name'] ?? '') ?>" readonly>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold">Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($lead['email'] ?? '') ?>" readonly>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold">Telefone</label>
              <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($lead['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold">Origem</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($lead['source'] ?? 'Desconhecido') ?>" readonly>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold">Status</label>
              <select class="form-select" name="status">
                <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($lead['status'] ?? 'new') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold">Notas</label>
              <textarea class="form-control" name="notes" rows="4"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-bold text-muted">Criado em</label>
              <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($lead['created_at'] ?? 'now')) ?>" readonly>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-save me-2"></i>Salvar Alterações
            </button>
          </form>
        </div>
      </div>

      <?php if ($linkedUser): ?>
      <div class="card">
        <div class="card-header">
          <i class="fas fa-link me-2"></i>Usuário Vinculado
        </div>
        <div class="card-body">
          <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars($linkedUser['name'] ?? '') ?></p>
          <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($linkedUser['email'] ?? '') ?></p>
          <p class="mb-1"><strong>Tier:</strong> <span class="badge bg-info"><?= htmlspecialchars($linkedUser['tier'] ?? 'FREE') ?></span></p>
          <p class="mb-0"><strong>Cadastro:</strong> <?= date('d/m/Y', strtotime($linkedUser['created_at'] ?? 'now')) ?></p>
          <a href="/secure/adm/users/view?id=<?= (int)$linkedUser['id'] ?>" class="btn btn-outline-primary btn-sm mt-3 w-100">
            <i class="fas fa-external-link-alt me-1"></i>Ver Perfil Completo
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Interactions -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-history me-2"></i>Histórico de Interações
        </div>
        <div class="card-body">
          <!-- Add Interaction Form -->
          <form method="POST" action="/secure/adm/crm/interaction" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
            <div class="row g-2">
              <div class="col-md-3">
                <select class="form-select" name="type">
                  <option value="note">Nota</option>
                  <option value="call">Ligação</option>
                  <option value="email">Email</option>
                  <option value="meeting">Reunião</option>
                </select>
              </div>
              <div class="col-md-7">
                <input type="text" class="form-control" name="description" placeholder="Descreva a interação..." required>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
          </form>

          <!-- Interactions List -->
          <?php if (empty($interactions)): ?>
          <div class="text-center text-muted py-4">
            <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
            <p>Nenhuma interação registrada</p>
          </div>
          <?php else: ?>
          <div class="timeline">
            <?php foreach ($interactions as $int): 
              $type = $int['type'] ?? 'note';
              $typeInfo = $interactionTypes[$type] ?? $interactionTypes['note'];
            ?>
            <div class="timeline-item d-flex mb-3">
              <div class="timeline-icon me-3">
                <span class="badge bg-<?= $typeInfo['color'] ?> rounded-circle p-2">
                  <i class="<?= $typeInfo['icon'] ?>"></i>
                </span>
              </div>
              <div class="timeline-content flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                  <span class="badge bg-<?= $typeInfo['color'] ?> mb-1"><?= $typeInfo['label'] ?></span>
                  <small class="text-muted"><?= date('d/m/Y H:i', strtotime($int['created_at'] ?? 'now')) ?></small>
                </div>
                <p class="mb-0"><?= htmlspecialchars($int['description'] ?? '') ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.timeline-item { border-left: 2px solid var(--border-color); padding-left: 1rem; margin-left: 0.75rem; }
.timeline-icon { margin-left: -1.5rem; }
</style>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
