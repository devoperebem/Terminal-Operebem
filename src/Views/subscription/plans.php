<?php
/**
 * Página de Planos de Assinatura - Sistema de Temas
 */

$title = 'Planos - Terminal Operebem';
ob_start();

$plans = $plans ?? [];
$currentSubscription = $currentSubscription ?? null;
$effectiveTier = $effectiveTier ?? 'FREE';
$stripePublicKey = $stripePublicKey ?? '';
$user = $user ?? null;

// Determine if user is FREE (no subscription = FREE)
$isFreeTier = ($effectiveTier === 'FREE');
?>

<style>
/* Pricing Cards - Theme Aware */
.pricing-card { 
    background: var(--card-bg); 
    border: 1px solid var(--border-color);
    border-radius: 12px; 
    padding: 1.75rem; 
    transition: all 0.3s ease; 
    height: 100%; 
    display: flex; 
    flex-direction: column;
}
.pricing-card:hover { 
    transform: translateY(-4px); 
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); 
}
.pricing-card.featured { 
    border-color: var(--primary-color); 
    border-width: 2px;
    position: relative; 
}
.pricing-card.featured::before { 
    content: attr(data-badge); 
    position: absolute; 
    top: -12px; 
    right: 20px; 
    background: var(--primary-color); 
    color: white; 
    padding: 4px 14px; 
    border-radius: 20px; 
    font-size: 0.7rem; 
    font-weight: 700; 
    letter-spacing: 0.5px;
}
.pricing-card.current { 
    border-color: var(--success-color); 
    border-width: 2px;
}

.pricing-tier { 
    font-size: 0.8rem; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 1px; 
    margin-bottom: 0.5rem; 
    color: var(--text-muted); 
}
.pricing-title { 
    font-size: 2rem; 
    font-weight: 700; 
    margin-bottom: 0.25rem; 
    color: var(--text-color); 
}
.pricing-subtitle { 
    font-size: 0.9rem; 
    color: var(--text-muted); 
    margin-bottom: 1.25rem; 
    min-height: 2.5rem;
}

.pricing-features { 
    list-style: none; 
    padding: 0; 
    margin: 0 0 1.5rem 0; 
    flex-grow: 1; 
}
.pricing-features li { 
    padding: 0.5rem 0; 
    font-size: 0.875rem; 
    color: var(--text-color); 
    display: flex; 
    align-items: flex-start; 
    gap: 0.625rem; 
}
.pricing-features li i { 
    color: var(--success-color); 
    font-size: 0.875rem; 
    margin-top: 3px; 
    flex-shrink: 0; 
}

.pricing-btn { 
    width: 100%; 
    padding: 0.75rem; 
    border-radius: 8px; 
    font-weight: 600; 
    font-size: 0.9375rem; 
    border: none; 
    cursor: pointer; 
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.pricing-btn-primary { 
    background: var(--primary-color); 
    color: white; 
}
.pricing-btn-primary:hover { 
    filter: brightness(1.1); 
    color: white;
}
.pricing-btn-outline { 
    background: transparent; 
    color: var(--text-color); 
    border: 2px solid var(--border-color); 
}
.pricing-btn-outline:hover { 
    border-color: var(--primary-color); 
    color: var(--primary-color);
}
.pricing-btn-current {
    background: var(--success-color);
    color: white;
    cursor: default;
}

/* Section Headers */
.section-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}
.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--text-color);
}
.section-header p {
    color: var(--text-muted);
    margin: 0;
    font-size: 0.9375rem;
}

/* Service Section */
.service-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}
.service-section .section-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1rem;
}
.service-section.diario .section-icon { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.service-section.portal .section-icon { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }

/* Mini Cards for Services */
.mini-pricing-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.mini-pricing-card.current {
    border-color: var(--success-color);
    border-width: 2px;
}
.mini-pricing-card .tier-badge {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
    color: var(--text-muted);
}
.mini-pricing-card .features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem 0;
    flex-grow: 1;
}
.mini-pricing-card .features-list li {
    font-size: 0.8125rem;
    padding: 0.375rem 0;
    color: var(--text-color);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}
.mini-pricing-card .features-list li i {
    color: var(--success-color);
    font-size: 0.75rem;
    margin-top: 3px;
}
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Planos Operebem</h1>
            <p class="text-muted mb-0">Escolha o plano ideal e acesse todo o ecossistema</p>
        </div>
        <a href="/app/dashboard" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <!-- Current Subscription Alert -->
    <?php if ($currentSubscription): ?>
    <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fa-lg"></i>
            <div>
                <strong>Assinatura Ativa:</strong> <?= htmlspecialchars($currentSubscription['plan_name'] ?? $currentSubscription['plan_slug']) ?>
                <?php if ($currentSubscription['status'] === 'trialing'): ?>
                    <span class="badge bg-warning text-dark ms-1">Trial</span>
                <?php endif; ?>
            </div>
        </div>
        <a href="/subscription/manage" class="btn btn-success btn-sm">
            <i class="fas fa-cog me-1"></i>Gerenciar
        </a>
    </div>
    <?php endif; ?>

    <!-- Main Plans Section -->
    <div class="section-header">
        <h2><i class="fas fa-terminal me-2"></i>Terminal Operebem</h2>
        <p>Sua central de operações para day trade</p>
    </div>

    <div class="row g-4 mb-4">
        <?php foreach ($plans as $plan): ?>
            <?php
            $tierClass = strtolower($plan['tier']);
            $isCurrent = $currentSubscription && $currentSubscription['plan_slug'] === $plan['slug'];
            // FREE is current if user has no subscription and tier is FREE
            if ($tierClass === 'free' && !$currentSubscription && $isFreeTier) {
                $isCurrent = true;
            }
            $isFeatured = $plan['is_highlighted'] ?? ($plan['tier'] === 'PRO');
            $features = is_array($plan['features']) ? $plan['features'] : json_decode($plan['features'] ?? '[]', true);
            
            // Price display
            $priceCents = $plan['price_cents'] ?? 0;
            $isInstallment = $plan['is_installment'] ?? false;
            $installmentCount = $plan['installment_count'] ?? 12;
            
            if ($tierClass === 'free') {
                $priceDisplay = 'Grátis';
                $priceSubtitle = 'Comece sua jornada sem compromisso';
            } elseif ($isInstallment) {
                $installmentValue = number_format(($priceCents / 100) / $installmentCount, 2, ',', '.');
                $priceDisplay = "12x R$ {$installmentValue}";
                $priceSubtitle = 'Acesso completo por 1 ano';
            } else {
                $priceValue = number_format($priceCents / 100, 2, ',', '.');
                $priceDisplay = "R$ {$priceValue}";
                $priceSubtitle = $plan['interval_type'] === 'year' ? '/ano' : '/mês';
            }
            
            // Override with front display data
            if (!empty($plan['price_display'])) $priceDisplay = $plan['price_display'];
            if (!empty($plan['description'])) $priceSubtitle = $plan['description'];
            
            $badgeText = $isFeatured ? 'RECOMENDADO' : '';
            if ($plan['interval_type'] === 'year') $badgeText = 'ANUAL';
            ?>
            
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?> <?= $isCurrent ? 'current' : '' ?>" data-badge="<?= $badgeText ?>">
                    <div class="pricing-tier"><?= strtoupper($plan['tier']) ?></div>
                    <div class="pricing-title"><?= htmlspecialchars($priceDisplay) ?></div>
                    <div class="pricing-subtitle"><?= htmlspecialchars($priceSubtitle) ?></div>
                    
                    <ul class="pricing-features">
                        <?php foreach ($features as $feature): ?>
                        <li><i class="fas fa-check"></i><span><?= htmlspecialchars($feature) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if ($isCurrent): ?>
                        <span class="pricing-btn pricing-btn-current">
                            <i class="fas fa-check me-1"></i>Plano Atual
                        </span>
                    <?php elseif ($tierClass === 'free'): ?>
                        <a href="/?modal=register" class="pricing-btn pricing-btn-outline">Criar Conta Grátis</a>
                    <?php else: ?>
                        <button class="pricing-btn <?= $isFeatured ? 'pricing-btn-primary' : 'pricing-btn-outline' ?>" 
                                data-plan="<?= htmlspecialchars($plan['slug']) ?>"
                                onclick="startCheckout('<?= htmlspecialchars($plan['slug']) ?>')">
                            <?= $isFeatured ? 'Assinar Pro' : 'Assinar Plus' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Diário de Trades Section -->
    <div class="service-section diario">
        <div class="section-header d-flex align-items-center">
            <div class="section-icon"><i class="fas fa-book"></i></div>
            <div>
                <h2 class="mb-0">Diário de Trades</h2>
                <p>Registre e analise suas operações</p>
            </div>
        </div>
        
        <div class="row g-3">
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $isFreeTier ? 'current' : '' ?>">
                    <div class="tier-badge">FREE</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Registro básico de trades</li>
                        <li><i class="fas fa-check"></i>Estatísticas simples</li>
                        <li><i class="fas fa-check"></i>Histórico limitado (30 dias)</li>
                    </ul>
                    <?php if ($isFreeTier): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Disponível no FREE</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $effectiveTier === 'PLUS' ? 'current' : '' ?>">
                    <div class="tier-badge">PLUS</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Tudo do FREE</li>
                        <li><i class="fas fa-check"></i>Análise de performance</li>
                        <li><i class="fas fa-check"></i>Gráficos avançados</li>
                        <li><i class="fas fa-check"></i>Histórico completo</li>
                    </ul>
                    <?php if ($effectiveTier === 'PLUS'): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Incluído no PLUS</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $effectiveTier === 'PRO' ? 'current' : '' ?>">
                    <div class="tier-badge">PRO</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Tudo do PLUS</li>
                        <li><i class="fas fa-check"></i>Relatórios exportáveis</li>
                        <li><i class="fas fa-check"></i>Análise por ativo</li>
                        <li><i class="fas fa-check"></i>Insights de IA</li>
                    </ul>
                    <?php if ($effectiveTier === 'PRO'): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Incluído no PRO</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Portal do Aluno Section -->
    <div class="service-section portal">
        <div class="section-header d-flex align-items-center">
            <div class="section-icon"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <h2 class="mb-0">Portal do Aluno</h2>
                <p>Cursos e conteúdos educacionais</p>
            </div>
        </div>
        
        <div class="row g-3">
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $isFreeTier ? 'current' : '' ?>">
                    <div class="tier-badge">FREE</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Curso Introdução ao Trading</li>
                        <li><i class="fas fa-check"></i>Primeira aula de cada curso</li>
                        <li><i class="fas fa-check"></i>Comunidade básica</li>
                    </ul>
                    <?php if ($isFreeTier): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Disponível no FREE</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $effectiveTier === 'PLUS' ? 'current' : '' ?>">
                    <div class="tier-badge">PLUS</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Tudo do FREE</li>
                        <li><i class="fas fa-check"></i>Curso Terminal Operebem</li>
                        <li><i class="fas fa-check"></i>Curso Diário Operebem</li>
                        <li><i class="fas fa-check"></i>Suporte prioritário</li>
                    </ul>
                    <?php if ($effectiveTier === 'PLUS'): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Incluído no PLUS</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-pricing-card <?= $effectiveTier === 'PRO' ? 'current' : '' ?>">
                    <div class="tier-badge">PRO</div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i>Tudo do PLUS</li>
                        <li><i class="fas fa-check"></i>Análise Técnica Avançada</li>
                        <li><i class="fas fa-check"></i>Gestão de Risco Avançada</li>
                        <li><i class="fas fa-check"></i>TTS - Theuska Trading System</li>
                        <li><i class="fas fa-check"></i>Como Operar B3/Cripto/CFDs</li>
                    </ul>
                    <?php if ($effectiveTier === 'PRO'): ?>
                        <span class="pricing-btn pricing-btn-current btn-sm">Incluído</span>
                    <?php else: ?>
                        <span class="pricing-btn pricing-btn-outline btn-sm" style="opacity:0.6">Incluído no PRO</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrf_token ?? '') ?>';

async function startCheckout(planSlug) {
    const btn = document.querySelector(`button[data-plan="${planSlug}"]`);
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
    
    try {
        const formData = new FormData();
        formData.append('plan', planSlug);
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('/subscription/checkout', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            alert(data.error || 'Erro ao iniciar checkout');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
