<?php
/**
 * Página de Sucesso após Checkout
 */

ob_start();
$subscription = $subscription ?? null;
$plan = $plan ?? null;
?>

<style>
.success-container {
    max-width: 600px;
    margin: 60px auto;
    padding: 0 20px;
    text-align: center;
}

.success-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    animation: scaleIn 0.5s ease-out;
}

.success-icon i {
    font-size: 3.5rem;
    color: #fff;
}

@keyframes scaleIn {
    from { transform: scale(0); }
    to { transform: scale(1); }
}

.success-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary, #1e293b);
    margin-bottom: 15px;
}

.success-message {
    font-size: 1.1rem;
    color: var(--text-secondary, #64748b);
    margin-bottom: 30px;
}

.subscription-card {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-align: left;
    margin-bottom: 30px;
}

.subscription-card h3 {
    margin-bottom: 20px;
    color: var(--text-primary, #1e293b);
}

.subscription-detail {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.subscription-detail:last-child {
    border-bottom: none;
}

.subscription-detail .label {
    color: var(--text-secondary, #64748b);
}

.subscription-detail .value {
    font-weight: 600;
    color: var(--text-primary, #1e293b);
}

.tier-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
}

.tier-badge.plus {
    background: #3b82f6;
    color: #fff;
}

.tier-badge.pro {
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: #fff;
}

.success-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.success-actions a {
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-dashboard {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: #fff;
}

.btn-dashboard:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-manage {
    background: transparent;
    border: 2px solid var(--primary-color, #667eea);
    color: var(--primary-color, #667eea);
}

.btn-manage:hover {
    background: var(--primary-color, #667eea);
    color: #fff;
}

.confetti {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 1000;
}
</style>

<div class="success-container">
    <div class="success-icon">
        <i class="fas fa-check"></i>
    </div>
    
    <h1 class="success-title">Bem-vindo à família Operebem!</h1>
    <p class="success-message">
        Sua assinatura foi ativada com sucesso. Agora você tem acesso a todas as funcionalidades premium!
    </p>
    
    <?php if ($subscription): ?>
    <div class="subscription-card">
        <h3><i class="fas fa-receipt me-2"></i>Detalhes da Assinatura</h3>
        
        <div class="subscription-detail">
            <span class="label">Plano</span>
            <span class="value"><?= htmlspecialchars($plan['name'] ?? $subscription['plan_slug']) ?></span>
        </div>
        
        <div class="subscription-detail">
            <span class="label">Tier</span>
            <span class="value">
                <span class="tier-badge <?= strtolower($subscription['tier']) ?>">
                    <?= htmlspecialchars($subscription['tier']) ?>
                </span>
            </span>
        </div>
        
        <div class="subscription-detail">
            <span class="label">Status</span>
            <span class="value">
                <?php if ($subscription['status'] === 'trialing'): ?>
                    <span style="color: #f59e0b;"><i class="fas fa-hourglass-half me-1"></i>Em Trial</span>
                <?php else: ?>
                    <span style="color: #22c55e;"><i class="fas fa-check-circle me-1"></i>Ativo</span>
                <?php endif; ?>
            </span>
        </div>
        
        <?php if ($subscription['status'] === 'trialing' && $subscription['trial_end']): ?>
        <div class="subscription-detail">
            <span class="label">Trial até</span>
            <span class="value"><?= date('d/m/Y', strtotime($subscription['trial_end'])) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($subscription['current_period_end']): ?>
        <div class="subscription-detail">
            <span class="label">Próxima cobrança</span>
            <span class="value"><?= date('d/m/Y', strtotime($subscription['current_period_end'])) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="success-actions">
        <a href="/app/dashboard" class="btn-dashboard">
            <i class="fas fa-rocket me-2"></i>Acessar Dashboard
        </a>
        <a href="/dev/subscription/manage" class="btn-manage">
            <i class="fas fa-cog me-2"></i>Gerenciar Assinatura
        </a>
    </div>
</div>

<canvas id="confetti" class="confetti"></canvas>

<script>
// Simple confetti effect
(function() {
    const canvas = document.getElementById('confetti');
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    const particles = [];
    const colors = ['#667eea', '#764ba2', '#22c55e', '#f59e0b', '#ef4444'];
    
    for (let i = 0; i < 100; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            r: Math.random() * 6 + 4,
            d: Math.random() * 100,
            color: colors[Math.floor(Math.random() * colors.length)],
            tilt: Math.random() * 10 - 10,
            tiltAngle: 0,
            tiltAngleInc: Math.random() * 0.07 + 0.05
        });
    }
    
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        particles.forEach((p, i) => {
            ctx.beginPath();
            ctx.lineWidth = p.r / 2;
            ctx.strokeStyle = p.color;
            ctx.moveTo(p.x + p.tilt + p.r / 3, p.y);
            ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 5);
            ctx.stroke();
        });
        
        update();
    }
    
    function update() {
        particles.forEach((p, i) => {
            p.tiltAngle += p.tiltAngleInc;
            p.y += (Math.cos(p.d) + 3 + p.r / 2) / 2;
            p.tilt = Math.sin(p.tiltAngle) * 15;
            
            if (p.y > canvas.height) {
                particles[i] = {
                    ...p,
                    x: Math.random() * canvas.width,
                    y: -20,
                    tilt: Math.random() * 10 - 10
                };
            }
        });
    }
    
    let frames = 0;
    function animate() {
        draw();
        frames++;
        if (frames < 200) {
            requestAnimationFrame(animate);
        } else {
            canvas.style.display = 'none';
        }
    }
    
    animate();
})();
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
