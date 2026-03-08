<?php 
$title = "Redefinir Senha - Terminal Operebem";
$footerVariant = 'public';
ob_start(); 
?>

<div class="auth-page-container">
  <div class="auth-card<?= !($token_valid ?? false) ? ' token-expired' : '' ?>">
    <div class="auth-card-header">
      <img src="/assets/images/favicon.png" alt="Terminal Operebem" class="auth-logo">
      <h5><?= !($token_valid ?? false) ? 'Link Inválido' : 'Redefinir Senha' ?></h5>
      <p class="subtitle"><?= !($token_valid ?? false) ? 'Este link expirou ou é inválido' : 'Defina uma nova senha segura para sua conta' ?></p>
    </div>
    <div class="auth-card-body">
      <div id="alertContainer"></div>

      <?php if (!($token_valid ?? false)) : ?>
        <div class="alert alert-danger mb-4" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>Link inválido ou expirado.</strong>
          <br><small>Este link pode ter sido usado ou expirou. Solicite um novo link de recuperação.</small>
        </div>
        <div class="d-grid gap-2">
          <a href="/?modal=forgot" class="btn btn-primary btn-lg">
            <i class="fas fa-redo me-2"></i>Solicitar Novo Link
          </a>
          <a href="/" class="btn btn-outline-secondary">
            <i class="fas fa-home me-2"></i>Voltar à Página Inicial
          </a>
        </div>
      <?php else: ?>
      <form id="resetPasswordForm" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($reset_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-4">
          <label for="password" class="form-label fw-semibold">Nova Senha</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
            <button class="btn btn-outline-secondary" type="button" id="togglePass1" aria-label="Mostrar/ocultar senha">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="invalid-feedback">A senha deve ter pelo menos 8 caracteres.</div>
          <div class="password-strength-meter">
            <div id="passStrength" class="password-strength-bar"></div>
          </div>
          <div class="form-text mt-2">
            <i class="fas fa-info-circle me-1"></i>
            Use letras maiúsculas, minúsculas, números e caracteres especiais.
          </div>
        </div>

        <div class="mb-4">
          <label for="password_confirmation" class="form-label fw-semibold">Confirmar Senha</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Digite a senha novamente" autocomplete="new-password">
            <button class="btn btn-outline-secondary" type="button" id="togglePass2" aria-label="Mostrar/ocultar senha">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="invalid-feedback">As senhas devem ser idênticas.</div>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
            <i class="fas fa-check-circle me-2"></i>Redefinir Senha
          </button>
        </div>
      </form>
      
      <div class="auth-footer-links">
        <a href="/?modal=forgot">
          <i class="fas fa-redo"></i>
          Solicitar novo link
        </a>
        <span class="separator">•</span>
        <a href="/support">
          <i class="fas fa-headset"></i>
          Precisa de ajuda?
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('resetPasswordForm');
  const submitBtn = document.getElementById('submitBtn');
  const pass = document.getElementById('password');
  const confirmPass = document.getElementById('password_confirmation');
  const meter = document.getElementById('passStrength');
  const t1 = document.getElementById('togglePass1');
  const t2 = document.getElementById('togglePass2');
  
  // Toggle password visibility
  function togglePassword(btn, input) {
    if (!btn || !input) return;
    btn.addEventListener('click', function() {
      const icon = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon?.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon?.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  }
  togglePassword(t1, pass);
  togglePassword(t2, confirmPass);

  // Password strength calculator
  function scorePassword(p) {
    if (!p) return 0;
    let score = 0;
    if (p.length >= 8) score += 25;
    if (p.length >= 12) score += 10;
    if (/[a-z]/.test(p)) score += 20;
    if (/[A-Z]/.test(p)) score += 20;
    if (/\d/.test(p)) score += 15;
    if (/[^a-zA-Z0-9]/.test(p)) score += 10;
    return Math.min(100, score);
  }
  
  function updateMeter() {
    if (!meter || !pass) return;
    const score = scorePassword(pass.value);
    meter.classList.remove('weak', 'fair', 'good', 'strong');
    if (score < 40) meter.classList.add('weak');
    else if (score < 60) meter.classList.add('fair');
    else if (score < 80) meter.classList.add('good');
    else meter.classList.add('strong');
  }
  
  pass?.addEventListener('input', updateMeter);
  updateMeter();

  // Form submission
  if (!form) return;
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validate form
    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add('was-validated');
      return;
    }
    
    // Check password match
    if (pass.value !== confirmPass.value) {
      confirmPass.setCustomValidity('As senhas não coincidem');
      form.classList.add('was-validated');
      return;
    }
    confirmPass.setCustomValidity('');
    
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Redefinindo...';
    
    try {
      const formData = new FormData(form);
      const response = await fetch('/reset-password', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      const alertContainer = document.getElementById('alertContainer');
      
      if (result.success) {
        alertContainer.innerHTML = `
          <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Senha redefinida com sucesso!</strong>
            <br><small>Redirecionando para a página de login...</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
        
        // Analytics
        try {
          if (typeof gtag === 'function') {
            gtag('event', 'password_reset_success', { method: 'email_link' });
          }
        } catch(e) {}
        
        // Redirect after brief delay
        setTimeout(() => {
          window.location.href = result.redirect || '/?modal=login';
        }, 1500);
      } else {
        alertContainer.innerHTML = `
          <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erro ao redefinir senha</strong>
            <br><small>${result.message || 'Tente novamente ou solicite um novo link.'}</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    } catch (error) {
      console.error('Reset password error:', error);
      const alertContainer = document.getElementById('alertContainer');
      alertContainer.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>Erro de conexão</strong>
          <br><small>Verifique sua internet e tente novamente.</small>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  });
});
</script>

<?php 
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
