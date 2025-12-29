<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-user-edit me-2"></i>Editar Usuário #<?= (int)$profile['id'] ?></h1>
    <div class="d-flex align-items-center gap-2">
      <a href="/secure/adm/users" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="/secure/adm/users/update" class="row g-3 needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="id" value="<?= (int)$profile['id'] ?>">
        
        <div class="col-md-6">
          <label class="form-label"><i class="fas fa-user me-2"></i>Nome</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
          <div class="invalid-feedback">Informe o nome.</div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
          <div class="invalid-feedback">Informe um email válido.</div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label"><i class="fas fa-crown me-2"></i>Tier (Assinatura)</label>
          <?php 
          $currentTier = strtoupper($profile['tier'] ?? 'FREE');
          $tiers = [
            'FREE' => ['label' => 'FREE - Gratuito', 'badge' => 'secondary'],
            'PLUS' => ['label' => 'PLUS - Intermediário', 'badge' => 'primary'],
            'PRO' => ['label' => 'PRO - Completo', 'badge' => 'warning']
          ];
          ?>
          <select name="tier" class="form-select" id="tierSelect">
            <?php foreach ($tiers as $tierValue => $tierInfo): ?>
            <option value="<?= $tierValue ?>" <?= $currentTier === $tierValue ? 'selected' : '' ?>>
              <?= $tierInfo['label'] ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            Nível de assinatura do usuário. Atual: 
            <span class="badge bg-<?= $tiers[$currentTier]['badge'] ?? 'secondary' ?>"><?= $currentTier ?></span>
          </div>
        </div>
        
        <div class="col-md-6" id="expiresAtContainer">
          <label class="form-label"><i class="fas fa-calendar-alt me-2"></i>Expiração da Assinatura</label>
          <?php 
          $expiresAt = $profile['subscription_expires_at'] ?? null;
          $expiresAtDate = '';
          if ($expiresAt) {
            $expiresAtDate = date('Y-m-d', strtotime($expiresAt));
          }
          ?>
          <input type="date" name="subscription_expires_at" class="form-control" id="expiresAtInput" value="<?= htmlspecialchars($expiresAtDate) ?>">
          <div class="form-text">
            <?php if ($expiresAt): ?>
              Expira em: <strong><?= date('d/m/Y H:i', strtotime($expiresAt)) ?></strong>
              <?php if (strtotime($expiresAt) < time()): ?>
                <span class="badge bg-danger">Expirado</span>
              <?php else: ?>
                <span class="badge bg-success">Ativo</span>
              <?php endif; ?>
            <?php else: ?>
              Deixe em branco para assinatura sem expiração.
            <?php endif; ?>
          </div>
        </div>
        
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="/secure/adm/users/view?id=<?= (int)$profile['id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-times me-2"></i>Cancelar</a>
          <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Mostrar/ocultar campo de expiração baseado no tier
document.addEventListener('DOMContentLoaded', function() {
  const tierSelect = document.getElementById('tierSelect');
  const expiresContainer = document.getElementById('expiresAtContainer');
  const expiresInput = document.getElementById('expiresAtInput');
  
  function toggleExpires() {
    if (tierSelect.value === 'FREE') {
      expiresContainer.style.opacity = '0.5';
      expiresInput.disabled = true;
      expiresInput.value = '';
    } else {
      expiresContainer.style.opacity = '1';
      expiresInput.disabled = false;
    }
  }
  
  tierSelect.addEventListener('change', toggleExpires);
  toggleExpires();
});
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
