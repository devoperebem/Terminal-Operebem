<?php
ob_start();

// Garantir que todas as variáveis existam para evitar erros
$subscription = $subscription ?? ['id' => 0, 'status' => 'unknown'];
$payments = $payments ?? [];
$trialExtensions = $trialExtensions ?? [];
?>
<style>
.info-label { font-weight: 600; color: #6c757d; font-size: 0.85rem; }
.info-value { font-size: 1rem; }
</style>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="/secure/adm/subscriptions" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
            <h1 class="h4 mb-0">
            <i class="fas fa-credit-card me-2"></i>
            Assinatura #<?= $subscription['id'] ?>
            <?php
            $statusBadges = [
                'active' => ['bg-success', 'Ativa'],
                'trialing' => ['bg-info', 'Trial'],
                'canceled' => ['bg-warning', 'Cancelada'],
                'past_due' => ['bg-danger', 'Atrasada'],
                'unpaid' => ['bg-danger', 'Não Paga'],
                'manual' => ['bg-primary', 'Manual'],
                'incomplete' => ['bg-secondary', 'Incompleta'],
            ];
            $statusInfo = $statusBadges[$subscription['status']] ?? ['bg-secondary', $subscription['status']];
            ?>
            <span class="badge <?= $statusInfo[0] ?>"><?= $statusInfo[1] ?></span>
            </h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/secure/adm/subscriptions/extend-trial?subscription_id=<?= $subscription['id'] ?>" class="btn btn-info btn-sm">
                <i class="fas fa-calendar-plus me-2"></i>Estender Trial
            </a>
            <a href="/secure/adm/users/view?id=<?= $subscription['user_id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-user me-2"></i>Ver Usuario
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informacoes da Assinatura</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <p class="info-label mb-1">Plano</p>
                            <p class="info-value"><?= htmlspecialchars($subscription['plan_name'] ?? $subscription['plan_slug']) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Tier</p>
                            <p class="info-value">
                                <?php
                                $tierBadges = ['FREE' => 'bg-secondary', 'PLUS' => 'bg-primary', 'PRO' => 'bg-warning text-dark'];
                                $tierClass = $tierBadges[$subscription['tier']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $tierClass ?>"><?= $subscription['tier'] ?></span>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Intervalo</p>
                            <p class="info-value"><?= $subscription['interval_type'] === 'month' ? 'Mensal' : 'Anual' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Valor</p>
                            <p class="info-value"><?= $subscription['price_cents'] ? ('R$ ' . number_format($subscription['price_cents'] / 100, 2, ',', '.')) : '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Origem</p>
                            <p class="info-value">
                                <?php if ($subscription['source'] === 'admin'): ?>
                                    <span class="badge bg-primary">Admin</span>
                                    <?php if ($subscription['admin_granted_name']): ?>
                                        <small class="text-muted d-block">por <?= htmlspecialchars($subscription['admin_granted_name']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Stripe</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Criado em</p>
                            <p class="info-value"><?= $subscription['created_at'] ? date('d/m/Y H:i', strtotime($subscription['created_at'])) : '-' ?></p>
                        </div>
                    </div>

                    <?php if ($subscription['admin_notes']): ?>
                        <hr>
                        <p class="info-label mb-1">Notas do Admin</p>
                        <p class="info-value"><?= nl2br(htmlspecialchars($subscription['admin_notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Usuario</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <p class="info-label mb-1">Nome</p>
                            <p class="info-value"><?= htmlspecialchars($subscription['user_name']) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Email</p>
                            <p class="info-value"><?= htmlspecialchars($subscription['user_email']) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">CPF</p>
                            <p class="info-value"><?= $subscription['user_cpf'] ?? '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Tier Atual</p>
                            <p class="info-value">
                                <?php
                                $tierBadges = ['FREE' => 'bg-secondary', 'PLUS' => 'bg-primary', 'PRO' => 'bg-warning text-dark'];
                                $tierClass = $tierBadges[$subscription['user_tier']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $tierClass ?>"><?= $subscription['user_tier'] ?></span>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Expira em</p>
                            <p class="info-value"><?= $subscription['subscription_expires_at'] ? date('d/m/Y H:i', strtotime($subscription['subscription_expires_at'])) : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Periodo e Trial</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <p class="info-label mb-1">Início do Período</p>
                            <p class="info-value"><?= $subscription['current_period_start'] ? date('d/m/Y H:i', strtotime($subscription['current_period_start'])) : '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Fim do Período</p>
                            <p class="info-value"><?= $subscription['current_period_end'] ? date('d/m/Y H:i', strtotime($subscription['current_period_end'])) : '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Trial Início</p>
                            <p class="info-value"><?= $subscription['trial_start'] ? date('d/m/Y H:i', strtotime($subscription['trial_start'])) : '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Trial Fim</p>
                            <p class="info-value"><?= $subscription['trial_end'] ? date('d/m/Y H:i', strtotime($subscription['trial_end'])) : '-' ?></p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Trial Usado</p>
                            <p class="info-value">
                                <?= $subscription['trial_used'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Nao</span>' ?>
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="info-label mb-1">Cancelar no Fim</p>
                            <p class="info-value">
                                <?= $subscription['cancel_at_period_end'] ? '<span class="badge bg-warning">Sim</span>' : '<span class="badge bg-secondary">Nao</span>' ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($subscription['canceled_at']): ?>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <strong>Cancelado em:</strong> <?= $subscription['canceled_at'] ? date('d/m/Y H:i', strtotime($subscription['canceled_at'])) : '-' ?>
                            <?php if ($subscription['ended_at']): ?>
                                <br><strong>Encerrado em:</strong> <?= $subscription['ended_at'] ? date('d/m/Y H:i', strtotime($subscription['ended_at'])) : '-' ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>IDs Stripe</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="info-label mb-1">Customer ID</p>
                        <p class="info-value"><code><?= $subscription['stripe_customer_id'] ?: '-' ?></code></p>
                    </div>
                    <div class="mb-3">
                        <p class="info-label mb-1">Subscription ID</p>
                        <p class="info-value"><code><?= $subscription['stripe_subscription_id'] ?: '-' ?></code></p>
                    </div>
                    <div>
                        <p class="info-label mb-1">Price ID</p>
                        <p class="info-value"><code><?= $subscription['stripe_price_id'] ?: '-' ?></code></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i>Historico de Pagamentos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Metodo</th>
                                    <th>Data</th>
                                    <th>Fatura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">
                                            Nenhum pagamento registrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><strong>R$ <?= number_format($payment['amount_cents'] / 100, 2, ',', '.') ?></strong></td>
                                    <td>
                                        <?php
                                        $paymentStatusBadges = [
                                            'succeeded' => ['bg-success', 'Pago'],
                                            'failed' => ['bg-danger', 'Falhou'],
                                            'pending' => ['bg-warning', 'Pendente'],
                                            'refunded' => ['bg-info', 'Reembolsado'],
                                        ];
                                        $paymentStatus = $paymentStatusBadges[$payment['status']] ?? ['bg-secondary', $payment['status']];
                                        ?>
                                        <span class="badge <?= $paymentStatus[0] ?>"><?= $paymentStatus[1] ?></span>
                                    </td>
                                            <td>
                                                <?php if ($payment['payment_method_type'] === 'card'): ?>
                                                    <i class="fas fa-credit-card"></i>
                                                    <?= strtoupper($payment['card_brand'] ?? '') ?>
                                                    <?= $payment['card_last4'] ? '****' . $payment['card_last4'] : '' ?>
                                                <?php elseif ($payment['payment_method_type'] === 'pix'): ?>
                                                    <i class="fas fa-qrcode"></i> PIX
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?= ($payment['paid_at'] ?? $payment['created_at']) ? date('d/m/Y H:i', strtotime($payment['paid_at'] ?? $payment['created_at'])) : '-' ?></td>
                                            <td>
                                                <?php if ($payment['hosted_invoice_url']): ?>
                                                    <a href="<?= htmlspecialchars($payment['hosted_invoice_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($trialExtensions)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Extensoes de Trial</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dias</th>
                                        <th>Novo Fim do Trial</th>
                                        <th>Concedido por</th>
                                        <th>Motivo</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trialExtensions as $ext): ?>
                                    <tr>
                                        <td><strong>+<?= $ext['days_extended'] ?> dias</strong></td>
                                        <td><?= $ext['new_trial_end'] ? date('d/m/Y H:i', strtotime($ext['new_trial_end'])) : '-' ?></td>
                                        <td><?= htmlspecialchars($ext['admin_name'] ?? 'Admin') ?></td>
                                        <td><?= htmlspecialchars($ext['reason'] ?? '-') ?></td>
                                        <td><?= $ext['created_at'] ? date('d/m/Y H:i', strtotime($ext['created_at'])) : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../../layouts/app.php';
?>
