<?php
/**
 * Página da Comunidade Discord
 * Exibe código de verificação e status da conexão
 */

$pageTitle = 'Comunidade Discord';
$title = 'Comunidade Discord - Terminal Operebem';
$isVerified = $discord['is_verified'] ?? false;
$discordId = $discord['discord_id'] ?? null;
$verificationCode = $discord['verification_code'] ?? '';
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            
            <!-- Header -->
            <div class="text-center mb-5">
                <div class="community-icon-wrapper mb-3">
                    <i class="fab fa-discord community-icon-simple"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3 community-title">
                    Comunidade Discord
                </h1>
                <p class="lead text-muted mb-4">
                    Conecte-se com outros traders e tenha acesso a conteúdos exclusivos
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <span class="badge badge-stat"><i class="fas fa-users me-1"></i>Comunidade Ativa</span>
                    <span class="badge badge-stat"><i class="fas fa-shield-alt me-1"></i>Verificação Segura</span>
                    <span class="badge badge-stat"><i class="fas fa-bolt me-1"></i>Sync Automático</span>
                </div>
            </div>

            <!-- Status: Conectado -->
            <div id="connected-section" class="card card-premium shadow-lg border-0 mb-4" style="<?= ($isVerified && $discordId) ? '' : 'display:none;' ?>">
                <div class="card-body p-4">
                    <!-- Badge Centralizado -->
                    <div class="text-center mb-4">
                        <span class="badge badge-success-modern">
                            <i class="fas fa-check-circle me-1"></i>
                            Conectado
                        </span>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <!-- Avatar e Info -->
                        <div class="col-12 col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="avatar-wrapper-fixed position-relative flex-shrink-0">
                                    <img id="discord-avatar-img"
                                         src="<?= !empty($discord['discord_avatar']) ? htmlspecialchars($discord['discord_avatar']) : '' ?>"
                                         alt="Avatar Discord"
                                         class="rounded-circle"
                                         style="width: 80px; height: 80px; object-fit: cover; <?= empty($discord['discord_avatar']) ? 'display:none;' : '' ?>">
                                    <div id="discord-avatar-fallback" class="avatar-fallback rounded-circle d-flex align-items-center justify-content-center"
                                         style="<?= !empty($discord['discord_avatar']) ? 'display:none;' : '' ?>">
                                        <i class="fab fa-discord fa-2x text-white"></i>
                                    </div>
                                    
                                    <!-- Indicador online -->
                                    <span class="status-indicator bg-success border border-white rounded-circle"
                                          style="animation: pulse 2s infinite;">
                                        <span class="visually-hidden">Online</span>
                                    </span>
                                </div>
                                
                                <div class="ms-3 flex-grow-1 min-w-0">
                                    <h5 id="discord-username" class="mb-2 fw-bold text-truncate"><?= htmlspecialchars($discord['discord_username'] ?? 'Usuário Discord') ?></h5>
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-clock me-1"></i>
                                        <span class="d-none d-sm-inline">Verificado em </span><span id="verified-at"><?= !empty($discord['verified_at']) ? date('d/m/Y \à\s H:i', strtotime($discord['verified_at'])) : '-' ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botão Desconectar -->
                        <div class="col-12 col-md-4 d-flex align-items-center justify-content-md-end">
                            <button type="button" class="btn btn-outline-danger w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#disconnectModal">
                                <i class="fas fa-unlink me-2"></i>Desconectar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estatísticas de Gamificação -->
                    <div class="border-top pt-4 mt-4">
                        <h6 class="text-uppercase fw-semibold mb-4 text-center" style="letter-spacing: 0.5px;">
                            <i class="fas fa-chart-line me-2"></i>Seu Progresso
                        </h6>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="stat-card-new stat-primary">
                                    <div class="stat-icon-new">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-label-new">Nível</div>
                                        <div id="user-level" class="stat-value-new"><?= (int)($user['level'] ?? 0) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card-new stat-warning">
                                    <div class="stat-icon-new">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-label-new">XP Total</div>
                                        <div id="user-xp" class="stat-value-new"><?= number_format((int)($user['xp'] ?? 0)) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card-new stat-danger">
                                    <div class="stat-icon-new">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-label-new">Streak</div>
                                        <div id="user-streak" class="stat-value-new"><?= (int)($user['streak'] ?? 0) ?> 🔥</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card-new stat-success">
                                    <div class="stat-icon-new">
                                        <i class="fas fa-arrow-trend-up"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-label-new">XP (7 dias)</div>
                                        <div id="user-recent-xp" class="stat-value-new">+<?= number_format((int)($recentXP['total_xp'] ?? 0)) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-sync-alt me-1"></i>
                            Última sincronização: <span id="last-sync-at"><?= !empty($discord['last_sync_at']) ? date('d/m/Y H:i', strtotime($discord['last_sync_at'])) : '-' ?></span>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Status: Não Conectado -->
            <div id="not-connected-section" class="card card-premium shadow-lg border-0 mb-4" style="<?= ($isVerified && $discordId) ? 'display:none;' : '' ?>">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <span class="badge badge-warning-modern fs-6">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Não Conectado
                            </span>
                        </div>
                        <h4>Conecte sua conta ao Discord</h4>
                        <p class="text-muted">
                            Use o código abaixo para verificar sua conta no servidor Discord da Operebem
                        </p>
                    </div>
                    
                    <!-- Código de Verificação -->
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8">
                            <div class="card code-card border-0">
                                <div class="card-body p-4">
                                    <label class="form-label fw-bold text-center d-block mb-3">
                                        <i class="fas fa-key me-2 text-primary"></i>Seu Código de Verificação
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control form-control-lg text-center fw-bold font-monospace" 
                                               id="verificationCode"
                                               value="<?= htmlspecialchars($verificationCode) ?>" 
                                               readonly
                                               style="letter-spacing: 2px; font-size: 1.1rem; height: 50px;">
                                        <button class="btn btn-primary btn-lg" type="button" onclick="copyCode()" style="height: 50px; padding: 0 1.5rem;">
                                            <i class="fas fa-copy me-2"></i>Copiar
                                        </button>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="regenerateCode()">
                                            <i class="fas fa-redo me-1"></i>Gerar Novo Código
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instruções -->
                    <div class="mt-4">
                        <h5 class="mb-3 text-center">
                            <i class="fas fa-list-ol me-2 text-primary"></i>Como Verificar
                        </h5>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="instruction-card">
                                    <div class="instruction-number">1</div>
                                    <div class="instruction-content">
                                        <h6 class="fw-bold mb-2">Entre no servidor Discord</h6>
                                        <p class="text-muted small mb-2">Clique no link abaixo para acessar</p>
                                        <a href="<?= htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fab fa-discord me-1"></i>Entrar no Discord
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="instruction-card">
                                    <div class="instruction-number">2</div>
                                    <div class="instruction-content">
                                        <h6 class="fw-bold mb-2">Vá até o canal #verificação</h6>
                                        <p class="text-muted small mb-0">Procure pelo canal de verificação no servidor</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="instruction-card">
                                    <div class="instruction-number">3</div>
                                    <div class="instruction-content">
                                        <h6 class="fw-bold mb-2">Clique no botão "✅ Verificar"</h6>
                                        <p class="text-muted small mb-0">Um modal será aberto para você inserir o código</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="instruction-card">
                                    <div class="instruction-number">4</div>
                                    <div class="instruction-content">
                                        <h6 class="fw-bold mb-2">Cole o código acima</h6>
                                        <p class="text-muted small mb-0">Após validar, você receberá acesso aos canais exclusivos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4 text-center">
                        <i class="fas fa-question-circle me-2 text-primary"></i>
                        Perguntas Frequentes
                    </h5>
                    
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-trophy me-2 text-warning"></i>
                                    Como ganhar XP e subir de nível?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Você ganha XP pelas ações abaixo (valores podem ser ajustados pelo admin):
                                    <ul class="mb-0">
                                        <li><strong>Login diário:</strong> +<?= (int)($xp_daily_login ?? 5) ?> XP no primeiro login do dia</li>
                                        <li><strong>Mensagens no Discord:</strong> +<?= (int)($xp_discord_msg_amount ?? 1) ?> XP por mensagem
                                            <small class="text-muted">(cooldown de <?= (int)($xp_discord_msg_cooldown_minutes ?? 10) ?> min por usuário, máx. <?= (int)($xp_discord_msg_daily_cap ?? 25) ?> XP/dia)</small>
                                        </li>
                                        <li><strong>Aulas concluídas:</strong> +<?= (int)($xp_lesson_base ?? 10) ?> XP base por aula
                                            <small class="text-muted">(bônus: +<?= (int)($xp_lesson_bonus_30min ?? 5) ?> XP para aulas ≥ 30min, +<?= (int)($xp_lesson_bonus_1h ?? 10) ?> XP para aulas ≥ 1h)</small>
                                        </li>
                                        <li><strong>Curso completo:</strong> +<?= (int)($xp_course_complete ?? 50) ?> XP ao atingir 100% do curso</li>
                                    </ul>
                                    <small class="text-muted d-block mt-2">Observações: Contam apenas mensagens em canais do servidor (bots e DMs não contam). Outras fontes de XP poderão ser adicionadas futuramente.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-fire me-2 text-danger"></i>
                                    O que é Streak e como funciona?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Streak é sua sequência de <strong>logins diários consecutivos</strong> no Terminal.
                                    <ul class="mb-2">
                                        <li><strong>Aumenta</strong> quando você faz login em um novo dia e seu último login foi há até 48 horas</li>
                                        <li><strong>Quebra</strong> se você ficar mais de 48 horas sem logar (reinicia em 1 no próximo login)</li>
                                        <li><strong>Observação:</strong> O streak não concede XP extra por si só; o XP diário vem do login</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-sync me-2 text-info"></i>
                                    Com que frequência os dados são sincronizados?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Seus dados são sincronizados automaticamente:
                                    <ul class="mb-0">
                                        <li><strong>Nível e XP no Discord:</strong> verificados e sincronizados a cada 5 minutos</li>
                                        <li><strong>Nickname no Discord:</strong> atualizado periodicamente (até a cada 5 minutos)</li>
                                        <li><strong>Cargo de verificado:</strong> concedido no momento da verificação</li>
                                        <li><strong>Cargos por nível (se ativado):</strong> atualizados a cada 5 minutos</li>
                                        <li><strong>Esta página:</strong> atualiza a cada 15 segundos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    <i class="fas fa-shield-alt me-2 text-success"></i>
                                    É seguro conectar minha conta?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! A conexão é 100% segura:
                                    <ul class="mb-0">
                                        <li>Não solicitamos sua senha do Discord</li>
                                        <li>Usamos apenas código de verificação único</li>
                                        <li>Você pode desconectar a qualquer momento</li>
                                        <li>Seus dados são protegidos e criptografados</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Desconexão -->
<div class="modal fade" id="disconnectModal" tabindex="-1" aria-labelledby="disconnectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="disconnectModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Desconexão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Tem certeza que deseja desconectar sua conta do Discord?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Atenção:</strong> Ao desconectar, você perderá:
                    <ul class="mb-0 mt-2">
                        <li>Acesso aos canais exclusivos</li>
                        <li>Cargo de verificado no servidor</li>
                        <li>Sincronização automática de XP</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmDisconnect()">
                    <i class="fas fa-unlink me-2"></i>Sim, Desconectar
                </button>
            </div>
        </div>
    </div>
</div>
            
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
@keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }

/* Community Icon Header */
.community-icon-wrapper {
  display: inline-block;
  animation: float 3s ease-in-out infinite;
}
.community-icon-simple {
  font-size: 4rem;
  color: #5865F2;
}
.community-title {
  background: linear-gradient(135deg, var(--text-primary) 0%, #5865F2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Modern Badges */
.badge-stat {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.3s ease;
}
.badge-stat:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.badge-success-modern {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.badge-warning-modern {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* Premium Cards */
.card-premium {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}
.card-premium::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.5s;
}
.card-premium:hover::before {
  left: 100%;
}

/* New Stat Cards - Responsive to themes */
.stat-card-new {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.stat-card-new:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.stat-card-new::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  transition: width 0.3s ease;
}
.stat-card-new:hover::before {
  width: 6px;
}
.stat-icon-new {
  font-size: 2rem;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  flex-shrink: 0;
  transition: all 0.3s ease;
}
.stat-content {
  flex: 1;
  min-width: 0;
}
.stat-label-new {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
  font-weight: 600;
}
.stat-value-new {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: var(--text-primary);
}

/* Primary Theme */
.stat-primary::before { background: #3b82f6; }
.stat-primary .stat-icon-new { 
  background: rgba(59, 130, 246, 0.1); 
  color: #3b82f6; 
}
.stat-primary:hover .stat-icon-new {
  background: rgba(59, 130, 246, 0.2);
  transform: scale(1.05);
}

/* Warning Theme */
.stat-warning::before { background: #f59e0b; }
.stat-warning .stat-icon-new { 
  background: rgba(245, 158, 11, 0.1); 
  color: #f59e0b; 
}
.stat-warning:hover .stat-icon-new {
  background: rgba(245, 158, 11, 0.2);
  transform: scale(1.05);
}

/* Danger Theme */
.stat-danger::before { background: #ef4444; }
.stat-danger .stat-icon-new { 
  background: rgba(239, 68, 68, 0.1); 
  color: #ef4444; 
}
.stat-danger:hover .stat-icon-new {
  background: rgba(239, 68, 68, 0.2);
  transform: scale(1.05);
}

/* Success Theme */
.stat-success::before { background: #10b981; }
.stat-success .stat-icon-new { 
  background: rgba(16, 185, 129, 0.1); 
  color: #10b981; 
}
.stat-success:hover .stat-icon-new {
  background: rgba(16, 185, 129, 0.2);
  transform: scale(1.05);
}

/* Discord Button */
.btn-discord {
  background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%);
  border: none;
  color: white;
  font-weight: 600;
  border-radius: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 16px rgba(88, 101, 242, 0.4);
}
.btn-discord:hover {
  background: linear-gradient(135deg, #4752C4 0%, #3c45a5 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(88, 101, 242, 0.5);
  color: white;
}

/* Code Card */
.code-card {
  background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--card-bg) 100%);
  border: 2px solid var(--border-color);
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

/* Instruction Cards */
.instruction-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  transition: all 0.3s ease;
  height: 100%;
}
.instruction-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-color: var(--primary);
}
.instruction-number {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}
.instruction-content {
  flex: 1;
}
.instruction-content h6 {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}
.instruction-content p {
  color: var(--text-secondary);
}

/* Avatar overlay and theme-friendly list-group */
.avatar-wrapper-fixed { 
  width: 80px; 
  height: 80px; 
  position: relative;
  display: inline-block;
  flex-shrink: 0;
}
.avatar-wrapper-fixed img { 
  width: 80px; 
  height: 80px; 
  border-radius: 50%; 
  object-fit: cover;
  display: block;
}
.avatar-wrapper-fixed img[style*="display:none"],
.avatar-wrapper-fixed img[style*="display: none"] {
  display: none !important;
}
.avatar-fallback { 
  width: 80px; 
  height: 80px; 
  background: #5865F2; 
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}
.avatar-fallback[style*="display:none"],
.avatar-fallback[style*="display: none"] {
  display: none !important;
}
.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 18px;
  height: 18px;
  z-index: 3;
  border-width: 2px !important;
}

.list-group .list-group-item { background-color: var(--card-bg); color: var(--text-primary); border-color: var(--border-color); }
.list-group-numbered > .list-group-item::before {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .community-icon-simple { font-size: 3rem; }
  .stat-value-new { font-size: 1.25rem; }
  .stat-icon-new { 
    font-size: 1.5rem; 
    width: 40px; 
    height: 40px; 
  }
  .btn-discord { padding: 0.75rem 2rem !important; }
  .avatar-wrapper-fixed,
  .avatar-wrapper-fixed img,
  .avatar-fallback {
    width: 60px;
    height: 60px;
  }
  .status-indicator {
    width: 14px;
    height: 14px;
  }
}
</style>

<script>
// Atualização automática do status (15s)
document.addEventListener('DOMContentLoaded', () => {
  const fmt = (iso) => {
    if (!iso) return '-';
    const d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    const pad = (n) => n.toString().padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
  };

  async function refreshStatus() {
    try {
      const res = await fetch('/app/community/status', { headers: { 'Accept': 'application/json' }});
      if (!res.ok) return;
      const data = await res.json();
      if (!data.success) return;
      const d = data.discord || {};
      const u = data.user || {};
      const isVerified = !!d.is_verified && !!d.discord_id;

      // Toggle seções
      const connected = document.getElementById('connected-section');
      const notConnected = document.getElementById('not-connected-section');
      if (connected && notConnected) {
        connected.style.display = isVerified ? '' : 'none';
        notConnected.style.display = isVerified ? 'none' : '';
      }

      // Atualizar campos conectados
      const userEl = document.getElementById('discord-username');
      if (userEl) userEl.textContent = d.discord_username || 'Usuário Discord';
      const va = document.getElementById('verified-at');
      if (va) va.textContent = d.verified_at ? fmt(d.verified_at) : '-';
      const ls = document.getElementById('last-sync-at');
      if (ls) ls.textContent = d.last_sync_at ? fmt(d.last_sync_at) : '-';
      const lvl = document.getElementById('user-level');
      if (lvl) lvl.textContent = (u.level ?? 0);
      const xp = document.getElementById('user-xp');
      if (xp) xp.textContent = (u.xp ?? 0).toLocaleString('pt-BR');
      const streak = document.getElementById('user-streak');
      if (streak) streak.textContent = `${u.streak ?? 0} 🔥`;
      const recent = document.getElementById('user-recent-xp');
      if (recent) recent.textContent = `+${(u.recent_xp ?? 0).toLocaleString('pt-BR')}`;

      // Avatar com tratamento de erro de carregamento
      const img = document.getElementById('discord-avatar-img');
      const fb = document.getElementById('discord-avatar-fallback');
      if (img && fb) {
        const showImg = () => { img.style.display = ''; fb.style.display = 'none'; };
        const showFallback = () => { img.style.display = 'none'; fb.style.display = ''; };
        if (d.discord_avatar) {
          if (img.src !== d.discord_avatar) {
            img.onload = showImg;
            img.onerror = showFallback;
            img.src = d.discord_avatar;
          }
          if (img.complete && img.naturalWidth > 0) {
            showImg();
          }
        } else {
          showFallback();
        }
      }

      // Atualizar código quando não conectado
      if (!isVerified) {
        const codeInput = document.getElementById('verificationCode');
        if (codeInput && d.verification_code) codeInput.value = d.verification_code;
      }
    } catch (_) {
      // silenciar erros intermitentes
    }
  }

  refreshStatus();
  setInterval(refreshStatus, 15000);
});

function copyCode() {
    const codeInput = document.getElementById('verificationCode');
    codeInput.select();
    document.execCommand('copy');
    
    // Feedback visual
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-primary');
    }, 2000);
}

function regenerateCode() {
    if (!confirm('Tem certeza que deseja gerar um novo código? O código atual será invalidado.')) {
        return;
    }
    
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/app/community/regenerate-code', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('verificationCode').value = data.code;
            alert('✅ Novo código gerado com sucesso!');
        } else {
            alert('❌ ' + (data.message || 'Erro ao gerar novo código'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('❌ Erro ao gerar novo código');
    });
}

function confirmDisconnect() {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const modal = bootstrap.Modal.getInstance(document.getElementById('disconnectModal'));
    
    fetch('/app/community/disconnect', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            alert('✅ Conta desconectada com sucesso!');
            // Deixa o auto-refresh atualizar a UI; como fallback, força toggle imediato
            const connected = document.getElementById('connected-section');
            const notConnected = document.getElementById('not-connected-section');
            if (connected && notConnected) { connected.style.display = 'none'; notConnected.style.display = ''; }
        } else {
            modal.hide();
            alert('❌ ' + (data.message || 'Erro ao desconectar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        modal.hide();
        alert('❌ Erro ao desconectar conta');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
