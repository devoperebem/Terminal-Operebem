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
/* Status badges - cores que funcionam em qualquer tema */
.tier-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
}

.tier-badge.free { background: #64748b; color: #fff; }
.tier-badge.plus { background: #3b82f6; color: #fff; }
.tier-badge.pro { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); color: #fff; }

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-badge.active { background: #22c55e; color: #fff; }
.status-badge.trialing { background: #f59e0b; color: #000; }
.status-badge.canceled { background: #dc2626; color: #fff; }
.status-badge.past_due { background: #ea580c; color: #fff; }

/* Cancel modal styling */
.cancel-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.cancel-modal-content {
    background: var(--card-bg, #1a1a1a);
    border: 1px solid var(--border-color, #333);
    border-radius: 16px;
    padding: 30px;
    max-width: 450px;
    margin: 20px;
    color: var(--text-primary, #fff);
}

.cancel-alert {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.4);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.cancel-alert h4 {
    color: #f87171;
    margin-bottom: 10px;
}

.cancel-alert p {
    color: #fca5a5;
    margin: 0;
}

/* Warning banner */
.warning-banner-styled {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.4);
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.warning-banner-styled i {
    font-size: 1.5rem;
    color: #fbbf24;
}

.warning-banner-styled p {
    margin: 0;
    color: var(--text-primary);
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-credit-card me-2"></i>Minha Assinatura
                </h1>
                <div class="d-flex gap-2">
                    <a href="/subscription/plans" class="btn btn-primary">
                        <i class="fas fa-rocket me-2"></i>Ver Planos
                    </a>
                    <a href="/app/dashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($subscription): ?>
        <?php if ($subscription['cancel_at_period_end']): ?>
        <div class="warning-banner-styled">
            <i class="fas fa-exclamation-triangle"></i>
            <p>
                Sua assinatura foi cancelada e expira em 
                <strong><?= date('d/m/Y', strtotime($subscription['current_period_end'])) ?></strong>.
                Você ainda tem acesso até essa data.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-star me-2 text-warning"></i>Status da Assinatura
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small">Plano</p>
                        <p class="fw-bold mb-0"><?= htmlspecialchars($plan['name'] ?? $subscription['plan_slug']) ?></p>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small">Tier Atual</p>
                        <span class="tier-badge <?= strtolower($effectiveTier) ?>"><?= $effectiveTier ?></span>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small">Status</p>
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
                    
                    <?php if ($subscription['status'] === 'trialing' && $subscription['trial_end']): ?>
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small">Trial até</p>
                        <p class="fw-bold mb-0"><?= date('d/m/Y H:i', strtotime($subscription['trial_end'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($subscription['current_period_end']): ?>
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small"><?= $subscription['cancel_at_period_end'] ? 'Expira em' : 'Próxima Cobrança' ?></p>
                        <p class="fw-bold mb-0"><?= date('d/m/Y', strtotime($subscription['current_period_end'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-sm-6 col-md-4">
                        <p class="text-muted mb-1 small">Origem</p>
                        <p class="fw-bold mb-0"><?= $subscription['source'] === 'admin' ? 'Concedido pelo Admin' : 'Stripe' ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!$subscription['cancel_at_period_end'] && $subscription['status'] !== 'canceled'): ?>
        <div class="card">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="card-title mb-0 text-danger">
                    <i class="fas fa-times-circle me-2"></i>Cancelar Assinatura
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Ao cancelar, você continuará tendo acesso até o fim do período atual.
                </p>
                <button class="btn btn-outline-danger" onclick="showCancelModal()">
                    <i class="fas fa-times me-2"></i>Cancelar Assinatura
                </button>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>Você não tem uma assinatura ativa</h5>
                <p class="text-muted">Assine um plano para desbloquear funcionalidades premium!</p>
                <a href="/subscription/plans" class="btn btn-primary">
                    <i class="fas fa-rocket me-2"></i>Ver Planos
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($payments)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>Histórico de Pagamentos
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
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
                                <span class="badge bg-<?= $payment['status'] === 'succeeded' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : 'warning') ?>">
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
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmação de cancelamento -->
<div id="cancelModal" class="cancel-modal">
    <div class="cancel-modal-content">
        <div class="cancel-alert">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Tem certeza?</h4>
            <p>
                Ao cancelar, você perderá acesso às funcionalidades premium quando o período atual expirar.
            </p>
        </div>
        
        <p class="mb-4">
            Você continuará tendo acesso até 
            <strong><?= $subscription ? date('d/m/Y', strtotime($subscription['current_period_end'])) : '--' ?></strong>.
        </p>
        
        <div class="d-flex gap-3 justify-content-end">
            <button onclick="hideCancelModal()" class="btn btn-outline-secondary">
                Voltar
            </button>
            <button onclick="confirmCancel()" class="btn btn-danger">
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
