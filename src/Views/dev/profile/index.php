<?php
$title = 'Perfil [TESTE] - Terminal Operebem';
ob_start();
?>

<!-- 🧪 BANNER DE AMBIENTE DE TESTE -->
<div class="alert alert-warning m-3 text-center fw-bold shadow-sm" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #000; border: 3px dashed #ff5722; border-radius: 12px;">
  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
    <span style="font-size: 2rem;">🧪</span>
    <div>
      <div class="fs-4">AMBIENTE DE TESTE</div>
      <div class="small mt-1">Esta é uma versão de desenvolvimento (/dev/). Alterações aqui NÃO afetam a produção.</div>
    </div>
    <span style="font-size: 2rem;">🧪</span>
  </div>
</div>

<div class="container py-4 profile-page">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-cog me-2"></i>Perfil
                </h1>
                <a href="/app/dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                </a>
            </div>

        </div>
    </div>

    <div class="row g-4">
        <!-- Informações do Usuário -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="profile-avatar-wrapper position-relative d-inline-block">
                            <?php $avatarSrc = isset($avatar_url) && $avatar_url ? $avatar_url : ''; ?>
                            <?php if ($avatarSrc): ?>
                                <?php $fallbackSvg = '/assets/images/user_image.png'; ?>
                                <img id="profileAvatarImg" src="<?= htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle" style="width: 96px; height: 96px; object-fit: cover; border: 2px solid var(--border-color);" onerror="this.onerror=null; this.src='<?= $fallbackSvg ?>';"/>
                            <?php else: ?>
                                <div id="profileAvatarPlaceholder" class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 96px; height: 96px;">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                            <?php endif; ?>
                            <button id="avatarChangeBtn" type="button" class="btn btn-sm btn-outline-primary position-absolute" style="right: -6px; bottom: -6px;">
                                <i class="fas fa-camera"></i>
                            </button>
                            <input id="avatarInput" type="file" name="avatar" accept="image/*" class="d-none" />
                        </div>
                    </div>
                    <h5 class="card-title"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Ativo
                        </span>
                        <?php if ($user['email_verified']): ?>
                        <span class="badge bg-info">
                            <i class="fas fa-envelope-check me-1"></i>Verificado
                        </span>
                        <?php else: ?>
                        <span class="badge bg-warning">
                            <i class="fas fa-envelope me-1"></i>Não Verificado
                        </span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">
                        Membro desde <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </small>
                </div>
            </div>

            <!-- Plano Atual -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-crown me-2"></i>Plano Atual
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $tier = strtoupper($user['tier'] ?? 'FREE');
                    $tierConfig = [
                        'FREE' => [
                            'name' => 'Gratuito',
                            'badge' => 'secondary',
                            'icon' => 'fas fa-user',
                            'description' => 'Acesso básico ao Terminal'
                        ],
                        'PLUS' => [
                            'name' => 'Plus',
                            'badge' => 'primary',
                            'icon' => 'fas fa-star',
                            'description' => 'Recursos avançados desbloqueados'
                        ],
                        'PRO' => [
                            'name' => 'Pro',
                            'badge' => 'warning',
                            'icon' => 'fas fa-crown',
                            'description' => 'Acesso completo a todas as funcionalidades'
                        ]
                    ];
                    $currentTier = $tierConfig[$tier] ?? $tierConfig['FREE'];
                    ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <i class="<?= $currentTier['icon'] ?> me-2"></i>
                                <?= htmlspecialchars($currentTier['name']) ?>
                            </h6>
                            <small class="text-muted">
                                <?= $currentTier['description'] ?>
                            </small>
                        </div>
                        <span class="badge bg-<?= $currentTier['badge'] ?>"><?= $tier ?></span>
                    </div>
                    <?php if ($tier === 'FREE'): ?>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Em breve: planos Plus e Pro disponíveis
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estatísticas do Usuário -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Estatísticas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-4">
                            <div class="stat-box">
                                <i class="fas fa-trophy text-warning mb-2" style="font-size: 1.5rem;"></i>
                                <h4 class="mb-0">Nível <?= (int)($user['level'] ?? 1) ?></h4>
                                <small class="text-muted">Level</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <i class="fas fa-star text-primary mb-2" style="font-size: 1.5rem;"></i>
                                <h4 class="mb-0"><?= number_format((int)($user['xp'] ?? 0)) ?></h4>
                                <small class="text-muted">XP Total</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <i class="fas fa-fire text-danger mb-2" style="font-size: 1.5rem;"></i>
                                <h4 class="mb-0"><?= (int)($user['streak'] ?? 0) ?></h4>
                                <small class="text-muted">Dias Seguidos</small>
                            </div>
                        </div>
                        <?php if (isset($_GET['debug'])): ?>
                        <div class="col-12 mt-3">
                            <div class="alert alert-info">
                                <strong>DEBUG:</strong><br>
                                User ID: <?= $user['id'] ?? 'N/A' ?><br>
                                Tier: <?= var_export($user['tier'] ?? null, true) ?><br>
                                XP (raw): <?= var_export($user['xp'] ?? null, true) ?><br>
                                Streak (raw): <?= var_export($user['streak'] ?? null, true) ?><br>
                                Level (raw): <?= var_export($user['level'] ?? null, true) ?><br>
                                Has 'tier' key: <?= array_key_exists('tier', $user) ? 'YES' : 'NO' ?><br>
                                Has 'xp' key: <?= array_key_exists('xp', $user) ? 'YES' : 'NO' ?><br>
                                Has 'streak' key: <?= array_key_exists('streak', $user) ? 'YES' : 'NO' ?><br>
                                Has 'level' key: <?= array_key_exists('level', $user) ? 'YES' : 'NO' ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurações -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Preferências
                    </h6>
                </div>
                <div class="card-body">
                    <form id="preferencesForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <!-- Tema -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-palette me-2"></i>Tema da Interface
                            </label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check theme-option">
                                        <input class="form-check-input" type="radio" name="theme" id="themeLight" value="light" <?= $user['theme'] === 'light' ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100" for="themeLight">
                                            <div class="theme-preview light-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-body">
                                                    <div class="theme-card"></div>
                                                    <div class="theme-card"></div>
                                                </div>
                                            </div>
                                            <div class="text-center mt-2">
                                                <strong>Claro</strong>
                                                <br><small class="text-muted">Tema padrão</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check theme-option">
                                        <input class="form-check-input" type="radio" name="theme" id="themeDarkBlue" value="dark-blue" <?= $user['theme'] === 'dark-blue' ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100" for="themeDarkBlue">
                                            <div class="theme-preview dark-blue-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-body">
                                                    <div class="theme-card"></div>
                                                    <div class="theme-card"></div>
                                                </div>
                                            </div>
                                            <div class="text-center mt-2">
                                                <strong>Azul Escuro</strong>
                                                <br><small class="text-muted">Profissional</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check theme-option">
                                        <input class="form-check-input" type="radio" name="theme" id="themeAllBlack" value="all-black" <?= $user['theme'] === 'all-black' ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100" for="themeAllBlack">
                                            <div class="theme-preview all-black-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-body">
                                                    <div class="theme-card"></div>
                                                    <div class="theme-card"></div>
                                                </div>
                                            </div>
                                            <div class="text-center mt-2">
                                                <strong>Preto Total</strong>
                                                <br><small class="text-muted">OLED friendly</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Timezone -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-clock me-2"></i>Fuso Horário
                            </label>
                            <select class="form-select" name="timezone" id="timezone">
                                <?php foreach ($timezones as $tz => $label): ?>
                                    <option value="<?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>" 
                                            <?= ($user['timezone'] ?? 'America/Sao_Paulo') === $tz ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Define o fuso horário para exibição de horários no sistema. Padrão: Brasília (UTC-3)
                            </div>
                        </div>

                        <hr>

                        <!-- Media Card -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-tv me-2"></i>Media Card
                                    </label>
                                    <div class="form-text">
                                        Exibir widgets adicionais de notícias e agenda econômica no dashboard
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="media_card" id="mediaCard" <?= $user['media_card'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mediaCard"></label>
                                </div>
                            </div>
                        </div>

                        <!-- Snapshot Avançado -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-camera me-2"></i>Snapshot Avançado
                                    </label>
                                    <div class="form-text">
                                        Quando ativado, mostra ícone de câmera com tooltip completo. Quando desativado, mostra apenas a variação percentual no horário do snapshot
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="advanced_snapshot" id="advancedSnapshot" <?= ($user['advanced_snapshot'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="advancedSnapshot"></label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Botões de Ação -->
                        <div class="d-flex justify-content-start">
                            <button type="button" class="btn btn-outline-danger" onclick="showChangePasswordModal()">
                                <i class="fas fa-key me-2"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alteração de Senha -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>Alterar Senha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="8">
                        <div class="form-text">Mínimo de 8 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmNewPassword" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.theme-option {
    cursor: pointer;
}

.theme-preview {
    width: 100%;
    height: 80px;
    border-radius: 0.375rem;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.theme-option input:checked + label .theme-preview {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.theme-header {
    height: 20px;
    width: 100%;
}

.theme-body {
    height: 60px;
    padding: 8px;
    display: flex;
    gap: 4px;
}

.theme-card {
    flex: 1;
    border-radius: 4px;
}

/* Light Theme Preview */
.light-theme {
    background: #f8f9fa;
}

.light-theme .theme-header {
    background: #ffffff;
    border-bottom: 1px solid #dee2e6;
}

.light-theme .theme-card {
    background: #ffffff;
    border: 1px solid #dee2e6;
}

/* Dark Blue Theme Preview */
.dark-blue-theme {
    background: #0a1628;
}

.dark-blue-theme .theme-header {
    background: #1e2a3a;
    border-bottom: 1px solid #2d3748;
}

.dark-blue-theme .theme-card {
    background: #1a252f;
    border: 1px solid #2d3748;
}

/* All Black Theme Preview */
.all-black-theme {
    background: #000000;
}

.all-black-theme .theme-header {
    background: #1a1a1a;
    border-bottom: 1px solid #333333;
}

.all-black-theme .theme-card {
    background: #111111;
    border: 1px solid #333333;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}
</style>

<?php
$content = ob_get_clean();
$scripts = '<script src="/assets/js/profile.js"></script>';
include __DIR__ . '/../../layouts/app.php';
?>
