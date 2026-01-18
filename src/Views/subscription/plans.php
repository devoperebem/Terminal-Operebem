<?php
/**
 * Página de Planos de Assinatura
 * 
 * Exibe os planos disponíveis e permite iniciar o checkout.
 */

ob_start();

$plans = $plans ?? [];
$currentSubscription = $currentSubscription ?? null;
$effectiveTier = $effectiveTier ?? 'FREE';
$stripePublicKey = $stripePublicKey ?? '';
?>

<style>
.plans-hero {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: #fff;
    padding: 60px 20px;
    text-align: center;
    margin: -20px -20px 40px -20px;
}

.plans-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.plans-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.plans-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}

.plan-card {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 2px solid transparent;
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.plan-card.featured {
    border-color: var(--primary-color, #667eea);
    transform: scale(1.02);
}

.plan-card.featured:hover {
    transform: scale(1.02) translateY(-5px);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: #fff;
    padding: 5px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.plan-tier {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 15px;
}

.plan-tier.tier-plus {
    background: #3b82f6;
    color: #fff;
}

.plan-tier.tier-pro {
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: #fff;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #1e293b);
    margin-bottom: 10px;
}

.plan-price {
    margin: 20px 0;
}

.plan-price .amount {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color, #667eea);
}

.plan-price .currency {
    font-size: 1.5rem;
    vertical-align: super;
}

.plan-price .period {
    font-size: 1rem;
    color: var(--text-secondary, #64748b);
}

.plan-price .installment {
    font-size: 0.9rem;
    color: var(--text-secondary, #64748b);
    margin-top: 5px;
}

.plan-trial {
    background: #ecfdf5;
    color: #059669;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    margin: 15px 0;
    text-align: center;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 25px 0;
}

.plan-features li {
    padding: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-primary, #1e293b);
}

.plan-features li i {
    color: #22c55e;
    font-size: 1.1rem;
}

.plan-btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.plan-btn.btn-primary {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: #fff;
}

.plan-btn.btn-primary:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.plan-btn.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color, #667eea);
    color: var(--primary-color, #667eea);
}

.plan-btn.btn-outline:hover {
    background: var(--primary-color, #667eea);
    color: #fff;
}

.plan-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.current-plan-badge {
    background: #22c55e;
    color: #fff;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    margin-bottom: 15px;
    display: inline-block;
}

.coupon-section {
    max-width: 500px;
    margin: 40px auto;
    padding: 0 20px;
}

.coupon-input-group {
    display: flex;
    gap: 10px;
}

.coupon-input-group input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    font-size: 1rem;
}

.coupon-input-group button {
    padding: 12px 25px;
    background: var(--primary-color, #667eea);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.coupon-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    display: none;
}

.coupon-result.success {
    background: #ecfdf5;
    color: #059669;
    display: block;
}

.coupon-result.error {
    background: #fef2f2;
    color: #dc2626;
    display: block;
}

.pix-badge {
    background: #32bcad;
    color: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 8px;
}

@media (max-width: 768px) {
    .plans-hero h1 {
        font-size: 1.8rem;
    }
    
    .plan-price .amount {
        font-size: 2.5rem;
    }
    
    .plans-container {
        grid-template-columns: 1fr;
    }
    
    .plan-card.featured {
        transform: none;
    }
}
</style>

<div class="plans-hero">
    <h1><i class="fas fa-crown me-2"></i>Planos Operebem</h1>
    <p>Escolha o plano ideal para você e desbloqueie todo o potencial do Terminal</p>
</div>

<div class="plans-container">
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
        
        <div class="plan-card <?= $isFeatured ? 'featured' : '' ?>">
            <?php if ($isFeatured): ?>
                <div class="plan-badge">Mais Popular</div>
            <?php endif; ?>
            
            <?php if ($isCurrent): ?>
                <div class="current-plan-badge"><i class="fas fa-check me-1"></i>Seu Plano Atual</div>
            <?php endif; ?>
            
            <span class="plan-tier tier-<?= $tierClass ?>"><?= htmlspecialchars($plan['tier']) ?></span>
            
            <h2 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h2>
            
            <div class="plan-price">
                <?php if ($isInstallment): ?>
                    <span class="currency">R$</span>
                    <span class="amount"><?= $installmentValue ?></span>
                    <span class="period">/mês</span>
                    <div class="installment">ou R$ <?= $priceFormatted ?> à vista</div>
                <?php else: ?>
                    <span class="currency">R$</span>
                    <span class="amount"><?= number_format($priceCents / 100, 0, ',', '.') ?></span>
                    <span class="period"><?= $interval ?></span>
                <?php endif; ?>
                
                <?php if ($supportsPix): ?>
                    <span class="pix-badge"><i class="fas fa-qrcode me-1"></i>PIX</span>
                <?php endif; ?>
            </div>
            
            <?php if (($plan['trial_days'] ?? 0) > 0): ?>
                <div class="plan-trial">
                    <i class="fas fa-gift me-1"></i>
                    <?= $plan['trial_days'] ?> dias grátis para testar
                </div>
            <?php endif; ?>
            
            <ul class="plan-features">
                <?php foreach ($features as $feature): ?>
                    <li><i class="fas fa-check-circle"></i><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($isCurrent): ?>
                <a href="/subscription/manage" class="plan-btn btn-outline">
                    <i class="fas fa-cog me-2"></i>Gerenciar
                </a>
            <?php else: ?>
                <button class="plan-btn btn-primary" 
                        data-plan="<?= htmlspecialchars($plan['slug']) ?>"
                        onclick="startCheckout('<?= htmlspecialchars($plan['slug']) ?>')">
                    <i class="fas fa-rocket me-2"></i>Assinar Agora
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="coupon-section">
    <h4 class="text-center mb-3">Tem um cupom?</h4>
    <div class="coupon-input-group">
        <input type="text" id="couponCode" placeholder="Digite seu cupom" maxlength="20">
        <button onclick="validateCoupon()">Aplicar</button>
    </div>
    <div id="couponResult" class="coupon-result"></div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrf_token ?? '') ?>';
let appliedCoupon = null;
let selectedPlan = null;

async function startCheckout(planSlug) {
    selectedPlan = planSlug;
    
    const btn = document.querySelector(`button[data-plan="${planSlug}"]`);
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
    
    try {
        const formData = new FormData();
        formData.append('plan', planSlug);
        formData.append('csrf_token', csrfToken);
        
        if (appliedCoupon) {
            formData.append('coupon', appliedCoupon);
        }
        
        const response = await fetch('checkout', {
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

async function validateCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    const resultDiv = document.getElementById('couponResult');
    
    if (!code) {
        resultDiv.className = 'coupon-result error';
        resultDiv.textContent = 'Digite um código de cupom';
        return;
    }
    
    if (!selectedPlan) {
        resultDiv.className = 'coupon-result error';
        resultDiv.textContent = 'Selecione um plano primeiro';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('code', code);
        formData.append('plan', selectedPlan);
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('validate-coupon', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.valid) {
            appliedCoupon = code;
            const discount = data.discount_type === 'percent' 
                ? `${data.discount_value}%` 
                : `R$ ${(data.discount_amount_cents / 100).toFixed(2).replace('.', ',')}`;
            
            resultDiv.className = 'coupon-result success';
            resultDiv.innerHTML = `<i class="fas fa-check-circle me-1"></i>Cupom aplicado! Desconto de ${discount}`;
        } else {
            appliedCoupon = null;
            resultDiv.className = 'coupon-result error';
            resultDiv.textContent = data.error || 'Cupom inválido';
        }
    } catch (error) {
        resultDiv.className = 'coupon-result error';
        resultDiv.textContent = 'Erro ao validar cupom';
    }
}

// Se clicar em um plano, guardar qual é
document.querySelectorAll('.plan-btn[data-plan]').forEach(btn => {
    btn.addEventListener('click', () => {
        selectedPlan = btn.dataset.plan;
    });
});
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
