<?php
/**
 * Página de Planos de Assinatura
 * 
 * Exibe os planos disponíveis seguindo o padrão visual do sistema.
 */

$title = 'Planos - Terminal Operebem';
ob_start();

$plans = $plans ?? [];
$currentSubscription = $currentSubscription ?? null;
$effectiveTier = $effectiveTier ?? 'FREE';
$stripePublicKey = $stripePublicKey ?? '';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-crown me-2"></i>Planos Operebem
                </h1>
                <a href="/app/dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                </a>
            </div>
            <p class="text-muted mb-4">Escolha o plano ideal para você e desbloqueie todo o potencial do Terminal</p>
        </div>
    </div>

    <?php if ($currentSubscription): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-25 rounded-circle p-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Você já tem uma assinatura ativa!</h6>
                                <p class="mb-0 text-muted">
                                    Plano: <strong><?= htmlspecialchars($currentSubscription['plan_name'] ?? $currentSubscription['plan_slug']) ?></strong>
                                    <?php if ($currentSubscription['status'] === 'trialing'): ?>
                                        <span class="badge bg-warning text-dark ms-2">Em Trial</span>
                                    <?php else: ?>
                                        <span class="badge bg-success ms-2">Ativo</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="/subscription/manage" class="btn btn-outline-success">
                            <i class="fas fa-cog me-2"></i>Gerenciar Assinatura
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($plans as $plan): ?>
            <?php
            $tierClass = strtolower($plan['tier']);
            $isCurrent = $currentSubscription && $currentSubscription['plan_slug'] === $plan['slug'];
            $isFeatured = $plan['is_featured'] ?? false;
            $features = is_array($plan['features']) ? $plan['features'] : json_decode($plan['features'] ?? '[]', true);
            
            // Formatar preço
            $priceCents = $plan['price_cents'] ?? 0;
            $priceFormatted = number_format($priceCents / 100, 2, ',', '.');
            
            $interval = $plan['interval_type'] === 'year' ? '/ano' : '/mês';
            
            // Verificar se é parcelado
            $isInstallment = $plan['is_installment'] ?? false;
            $installmentCount = $plan['installment_count'] ?? 12;
            $installmentValue = $isInstallment ? number_format(($priceCents / 100) / $installmentCount, 2, ',', '.') : null;
            
            // Verificar suporte a PIX
            $supportsPix = $plan['supports_pix'] ?? false;
            ?>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 <?= $isFeatured ? 'border-primary' : '' ?> <?= $isCurrent ? 'border-success' : '' ?>">
                    <?php if ($isFeatured && !$isCurrent): ?>
                        <div class="card-header bg-primary text-white text-center py-2">
                            <small class="fw-bold text-uppercase"><i class="fas fa-star me-1"></i>Mais Popular</small>
                        </div>
                    <?php endif; ?>
                    <?php if ($isCurrent): ?>
                        <div class="card-header bg-success text-white text-center py-2">
                            <small class="fw-bold text-uppercase"><i class="fas fa-check-circle me-1"></i>Seu Plano Atual</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <!-- Tier Badge -->
                        <div class="mb-3">
                            <?php if ($tierClass === 'plus'): ?>
                                <span class="badge bg-primary px-3 py-2">PLUS</span>
                            <?php elseif ($tierClass === 'pro'): ?>
                                <span class="badge bg-warning text-dark px-3 py-2">PRO</span>
                            <?php else: ?>
                                <span class="badge bg-secondary px-3 py-2"><?= strtoupper($plan['tier']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Nome do Plano -->
                        <h4 class="card-title mb-3"><?= htmlspecialchars($plan['name']) ?></h4>
                        
                        <!-- Preço -->
                        <div class="mb-4">
                            <?php if ($isInstallment): ?>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="fs-5 text-muted">R$</span>
                                    <span class="display-5 fw-bold"><?= $installmentValue ?></span>
                                    <span class="text-muted">/mês</span>
                                </div>
                                <small class="text-muted">ou R$ <?= $priceFormatted ?> à vista</small>
                            <?php else: ?>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="fs-5 text-muted">R$</span>
                                    <span class="display-5 fw-bold"><?= number_format($priceCents / 100, 0, ',', '.') ?></span>
                                    <span class="text-muted"><?= $interval ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($supportsPix): ?>
                                <span class="badge bg-info mt-2"><i class="fas fa-qrcode me-1"></i>PIX Disponível</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Trial -->
                        <?php if (($plan['trial_days'] ?? 0) > 0 && !$isCurrent): ?>
                            <div class="alert alert-success py-2 px-3 mb-3">
                                <small><i class="fas fa-gift me-1"></i><?= $plan['trial_days'] ?> dias grátis para testar</small>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Features -->
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            <?php foreach ($features as $feature): ?>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small><?= htmlspecialchars($feature) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Botão -->
                        <div class="mt-auto">
                            <?php if ($isCurrent): ?>
                                <a href="/subscription/manage" class="btn btn-outline-success w-100">
                                    <i class="fas fa-cog me-2"></i>Gerenciar
                                </a>
                            <?php else: ?>
                                <button class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline-primary' ?> w-100" 
                                        data-plan="<?= htmlspecialchars($plan['slug']) ?>"
                                        onclick="startCheckout('<?= htmlspecialchars($plan['slug']) ?>')">
                                    <i class="fas fa-rocket me-2"></i>Assinar Agora
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
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
