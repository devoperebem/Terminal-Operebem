<?php
/**
 * Página de Gerenciamento de Assinatura
 */

$title = 'Minha Assinatura - Terminal Operebem';
ob_start();
$subscription = $subscription ?? null;
$plan = $plan ?? null;
$payments = $payments ?? [];
$effectiveTier = $effectiveTier ?? 'FREE';
?>

<style>
.manage-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.manage-header {
    margin-bottom: 30px;
}

.manage-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary, #1e293b);
    margin-bottom: 10px;
}

.manage-card {
    background: var(--card-bg, #fff);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.manage-card h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--text-primary, #1e293b);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.manage-card h2 i {
    color: var(--primary-color, #667eea);
}

.subscription-status {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.status-item {
    padding: 15px;
    background: var(--bg-secondary, #f8fafc);
    border-radius: 10px;
}

.status-item .label {
    font-size: 0.85rem;
    color: var(--text-secondary, #64748b);
    margin-bottom: 5px;
}

.status-item .value {
    font-size: 1.1rem;
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

.tier-badge.free { background: #e2e8f0; color: #64748b; }
.tier-badge.plus { background: #3b82f6; color: #fff; }
.tier-badge.pro { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); color: #fff; }

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-badge.active { background: #dcfce7; color: #16a34a; }
.status-badge.trialing { background: #fef3c7; color: #d97706; }
.status-badge.canceled { background: #fecaca; color: #dc2626; }
.status-badge.past_due { background: #fed7aa; color: #ea580c; }

.no-subscription {
    text-align: center;
    padding: 40px;
    color: var(--text-secondary, #64748b);
}

.no-subscription i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-subscription a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 30px;
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
}

.cancel-section {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid var(--border-color, #e2e8f0);
}

.cancel-btn {
    background: transparent;
    border: 2px solid #dc2626;
    color: #dc2626;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cancel-btn:hover {
    background: #dc2626;
    color: #fff;
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
}

.payments-table th,
.payments-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.payments-table th {
    font-weight: 600;
    color: var(--text-secondary, #64748b);
    font-size: 0.85rem;
    text-transform: uppercase;
}

.payments-table td {
    color: var(--text-primary, #1e293b);
}

.payment-status {
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 0.85rem;
}

.payment-status.succeeded { background: #dcfce7; color: #16a34a; }
.payment-status.failed { background: #fecaca; color: #dc2626; }
.payment-status.pending { background: #fef3c7; color: #d97706; }

.cancel-warning {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.cancel-warning h4 {
    color: #dc2626;
    margin-bottom: 10px;
}

.cancel-warning p {
    color: #7f1d1d;
    margin: 0;
}

.warning-banner {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.warning-banner i {
    font-size: 1.5rem;
    color: #d97706;
}

.warning-banner p {
    margin: 0;
    color: #92400e;
}

@media (max-width: 600px) {
    .payments-table {
        font-size: 0.9rem;
    }
    
    .payments-table th,
    .payments-table td {
        padding: 8px;
    }
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-credit-card me-2"></i>Minha Assinatura
                </h1>
                <a href="/app/dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($subscription): ?>
        <?php if ($subscription['cancel_at_period_end']): ?>
        <div class="warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <p>
                Sua assinatura foi cancelada e expira em 
                <strong><?= date('d/m/Y', strtotime($subscription['current_period_end'])) ?></strong>.
                Você ainda tem acesso até essa data.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="manage-card">
            <h2><i class="fas fa-star"></i>Status da Assinatura</h2>
            
            <div class="subscription-status">
                <div class="status-item">
                    <div class="label">Plano</div>
                    <div class="value"><?= htmlspecialchars($plan['name'] ?? $subscription['plan_slug']) ?></div>
                </div>
                
                <div class="status-item">
                    <div class="label">Tier Atual</div>
                    <div class="value">
                        <span class="tier-badge <?= strtolower($effectiveTier) ?>"><?= $effectiveTier ?></span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="status-badge <?= $subscription['status'] ?>">
                            <?php
                            $statusLabels = [
                                'active' => 'Ativo',
                                'trialing' => 'Em Trial',
                                'canceled' => 'Cancelado',
                                'past_due' => 'Pagamento Pendente',
                            ];
                            echo $statusLabels[$subscription['status']] ?? $subscription['status'];
                            ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($subscription['status'] === 'trialing' && $subscription['trial_end']): ?>
                <div class="status-item">
                    <div class="label">Trial até</div>
                    <div class="value"><?= date('d/m/Y H:i', strtotime($subscription['trial_end'])) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($subscription['current_period_end']): ?>
                <div class="status-item">
                    <div class="label"><?= $subscription['cancel_at_period_end'] ? 'Expira em' : 'Próxima Cobrança' ?></div>
                    <div class="value"><?= date('d/m/Y', strtotime($subscription['current_period_end'])) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="status-item">
                    <div class="label">Origem</div>
                    <div class="value">
                        <?= $subscription['source'] === 'admin' ? 'Concedido pelo Admin' : 'Stripe' ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$subscription['cancel_at_period_end'] && $subscription['status'] !== 'canceled'): ?>
            <div class="cancel-section">
                <h3 style="margin-bottom: 15px;">Cancelar Assinatura</h3>
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                    Ao cancelar, você continuará tendo acesso até o fim do período atual.
                </p>
                <button class="cancel-btn" onclick="showCancelModal()">
                    <i class="fas fa-times me-2"></i>Cancelar Assinatura
                </button>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="manage-card">
            <div class="no-subscription">
                <i class="fas fa-inbox"></i>
                <h3>Você não tem uma assinatura ativa</h3>
                <p>Assine um plano para desbloquear funcionalidades premium!</p>
                <a href="/dev/subscription/plans">
                    <i class="fas fa-rocket me-2"></i>Ver Planos
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($payments)): ?>
    <div class="manage-card">
        <h2><i class="fas fa-history"></i>Histórico de Pagamentos</h2>
        
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Método</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($payment['created_at'])) ?></td>
                    <td>R$ <?= number_format($payment['amount_cents'] / 100, 2, ',', '.') ?></td>
                    <td>
                        <span class="payment-status <?= $payment['status'] ?>">
                            <?php
                            $paymentLabels = [
                                'succeeded' => 'Pago',
                                'failed' => 'Falhou',
                                'pending' => 'Pendente',
                                'refunded' => 'Reembolsado',
                            ];
                            echo $paymentLabels[$payment['status']] ?? $payment['status'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($payment['payment_method_type'] === 'card'): ?>
                            <i class="fas fa-credit-card me-1"></i>
                            <?= strtoupper($payment['card_brand'] ?? '') ?> ****<?= $payment['card_last4'] ?? '' ?>
                        <?php elseif ($payment['payment_method_type'] === 'pix'): ?>
                            <i class="fas fa-qrcode me-1"></i> PIX
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmação de cancelamento -->
<div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--card-bg, #fff); border-radius: 16px; padding: 30px; max-width: 450px; margin: 20px;">
        <div class="cancel-warning">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Tem certeza?</h4>
            <p>
                Ao cancelar, você perderá acesso às funcionalidades premium quando o período atual expirar.
            </p>
        </div>
        
        <p style="margin-bottom: 20px; color: var(--text-secondary);">
            Você continuará tendo acesso até 
            <strong><?= $subscription ? date('d/m/Y', strtotime($subscription['current_period_end'])) : '--' ?></strong>.
        </p>
        
        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <button onclick="hideCancelModal()" style="padding: 10px 20px; background: transparent; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                Voltar
            </button>
            <button onclick="confirmCancel()" style="padding: 10px 20px; background: #dc2626; color: #fff; border: none; border-radius: 8px; cursor: pointer;">
                Confirmar Cancelamento
            </button>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrf_token ?? '') ?>';

function showCancelModal() {
    document.getElementById('cancelModal').style.display = 'flex';
}

function hideCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

async function confirmCancel() {
    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('cancel', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Assinatura cancelada com sucesso.');
            window.location.reload();
        } else {
            alert(data.error || 'Erro ao cancelar');
        }
    } catch (error) {
        alert('Erro de conexão');
    }
    
    hideCancelModal();
}
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
