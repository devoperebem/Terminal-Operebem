<?php
/**
 * Admin - Histórico de Pagamentos
 */

$pageTitle = 'Histórico de Pagamentos | Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

function formatMoney($cents) {
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
}

function statusBadge($status) {
    $badges = [
        'succeeded' => ['bg-success', 'Pago'],
        'pending' => ['bg-warning', 'Pendente'],
        'processing' => ['bg-info', 'Processando'],
        'failed' => ['bg-danger', 'Falhou'],
        'refunded' => ['bg-secondary', 'Reembolsado'],
        'disputed' => ['bg-danger', 'Disputado'],
    ];
    $b = $badges[$status] ?? ['bg-secondary', $status];
    return "<span class=\"badge {$b[0]}\">{$b[1]}</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .stats-card { border-left: 4px solid; }
        .stats-card.total { border-left-color: #6c757d; }
        .stats-card.success { border-left-color: #198754; }
        .stats-card.failed { border-left-color: #dc3545; }
        .stats-card.revenue { border-left-color: #0d6efd; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/secure/adm/index">Admin Panel</a>
            <div class="d-flex">
                <a href="/secure/adm/subscriptions" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-stack"></i> Histórico de Pagamentos</h2>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stats-card total">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total</h6>
                        <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card success">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Pagos</h6>
                        <h3 class="mb-0"><?= number_format($stats['succeeded']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card failed">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Falhados</h6>
                        <h3 class="mb-0"><?= number_format($stats['failed']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card revenue">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Receita Total</h6>
                        <h3 class="mb-0"><?= formatMoney($stats['total_amount']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="succeeded" <?= $status === 'succeeded' ? 'selected' : '' ?>>Pago</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendente</option>
                            <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Falhou</option>
                            <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>Reembolsado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-control" placeholder="Nome ou email do usuário" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Fatura</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Nenhum pagamento encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= $payment['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($payment['user_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($payment['user_email']) ?></small>
                                        </td>
                                        <td>
                                            <strong class="<?= $payment['status'] === 'succeeded' ? 'text-success' : '' ?>">
                                                <?= formatMoney($payment['amount_cents']) ?>
                                            </strong>
                                        </td>
                                        <td><?= statusBadge($payment['status']) ?></td>
                                        <td>
                                            <?php if ($payment['payment_method_type'] === 'card'): ?>
                                                <i class="bi bi-credit-card"></i>
                                                <?= strtoupper($payment['card_brand'] ?? '') ?>
                                                <?= $payment['card_last4'] ? '****' . $payment['card_last4'] : '' ?>
                                            <?php elseif ($payment['payment_method_type'] === 'pix'): ?>
                                                <i class="bi bi-qr-code"></i> PIX
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($payment['paid_at'] ?? $payment['created_at']) ?></td>
                                        <td>
                                            <?php if ($payment['hosted_invoice_url']): ?>
                                                <a href="<?= htmlspecialchars($payment['hosted_invoice_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver fatura">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($payment['receipt_url']): ?>
                                                <a href="<?= htmlspecialchars($payment['receipt_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver recibo">
                                                    <i class="bi bi-file-text"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!$payment['hosted_invoice_url'] && !$payment['receipt_url']): ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($payment['failure_message']): ?>
                                        <tr class="table-danger">
                                            <td colspan="7">
                                                <small>
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>Erro:</strong> <?= htmlspecialchars($payment['failure_message']) ?>
                                                    <?php if ($payment['failure_code']): ?>
                                                        <span class="badge bg-danger"><?= htmlspecialchars($payment['failure_code']) ?></span>
                                                    <?php endif; ?>
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
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                        Próxima
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <p class="text-center text-muted small mt-2 mb-0">
                        Mostrando página <?= $page ?> de <?= $totalPages ?> (<?= number_format($total) ?> registros)
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
