<?php
/**
 * Admin - Lista de Cupons
 */

$title = 'Secure Admin - Cupons';
$pageTitle = 'Cupons';
$csrf_token = $_SESSION['csrf_token'] ?? '';
$footerVariant = 'admin-auth';

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

$successMessages = [
    'created' => 'Cupom criado com sucesso!',
    'toggled' => 'Status do cupom alterado!',
];

ob_start();
?>
<style>
    .coupon-code {
        font-family: monospace;
        font-size: 1.1rem;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 4px;
    }
</style>

<div class="container my-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $successMessages[$success] ?? 'Operacao realizada com sucesso!' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            Erro: <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="fas fa-ticket me-2"></i><?= $pageTitle ?></h1>
        <div class="d-flex gap-2">
            <a href="/secure/adm/subscriptions" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
            <a href="/secure/adm/coupons/create" class="btn btn-success btn-sm">
                <i class="fas fa-plus-circle me-2"></i>Novo Cupom
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Codigo</th>
                            <th>Desconto</th>
                            <th>Uso</th>
                            <th>Validade</th>
                            <th>Criado por</th>
                            <th>Status</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Nenhum cupom cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr class="<?= !$coupon['is_active'] ? 'table-secondary' : '' ?>">
                                    <td>
                                        <span class="coupon-code"><?= htmlspecialchars($coupon['code']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($coupon['discount_type'] === 'percent'): ?>
                                            <strong class="text-success"><?= $coupon['discount_value'] ?>%</strong>
                                        <?php else: ?>
                                            <strong class="text-success">R$ <?= number_format($coupon['discount_value'] / 100, 2, ',', '.') ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= $coupon['usage_count'] ?>
                                            <?php if ($coupon['max_redemptions']): ?>
                                                / <?= $coupon['max_redemptions'] ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($coupon['valid_until']): ?>
                                            <?php
                                            $validUntil = strtotime($coupon['valid_until']);
                                            $expired = $validUntil < time();
                                            ?>
                                            <span class="<?= $expired ? 'text-danger' : '' ?>">
                                                <?= formatDate($coupon['valid_until']) ?>
                                                <?php if ($expired): ?>
                                                    <span class="badge bg-danger">Expirado</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sem limite</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($coupon['created_by_name'] ?? 'Admin') ?></td>
                                    <td>
                                        <?php if ($coupon['is_active']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="/secure/adm/coupons/toggle" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $coupon['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $coupon['is_active'] ? 'Desativar' : 'Ativar' ?>">
                                                <i class="fas <?= $coupon['is_active'] ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php if ($coupon['notes']): ?>
                                    <tr class="<?= !$coupon['is_active'] ? 'table-secondary' : '' ?>">
                                        <td colspan="7">
                                            <small class="text-muted">
                                                <i class="fas fa-sticky-note me-2"></i><?= htmlspecialchars($coupon['notes']) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../../layouts/app.php';
?>
