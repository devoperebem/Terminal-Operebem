<?php
/**
 * Admin - Lista de Assinaturas
 */

$pageTitle = 'Assinaturas | Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';

// Função helper para formatar data
function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

// Função helper para status badge
function statusBadge($status) {
    $badges = [
        'active' => ['bg-success', 'Ativa'],
        'trialing' => ['bg-info', 'Trial'],
        'canceled' => ['bg-warning', 'Cancelada'],
        'past_due' => ['bg-danger', 'Atrasada'],
        'unpaid' => ['bg-danger', 'Não Paga'],
        'manual' => ['bg-primary', 'Manual'],
        'incomplete' => ['bg-secondary', 'Incompleta'],
    ];
    $b = $badges[$status] ?? ['bg-secondary', $status];
    return "<span class=\"badge {$b[0]}\">{$b[1]}</span>";
}

// Função helper para tier badge
function tierBadge($tier) {
    $badges = [
        'FREE' => ['bg-secondary', 'FREE'],
        'PLUS' => ['bg-primary', 'PLUS'],
        'PRO' => ['bg-warning text-dark', 'PRO'],
    ];
    $b = $badges[$tier] ?? ['bg-secondary', $tier];
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
        .stats-card.active { border-left-color: #198754; }
        .stats-card.trial { border-left-color: #0dcaf0; }
        .stats-card.canceled { border-left-color: #ffc107; }
        .stats-card.manual { border-left-color: #6f42c1; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/secure/adm/index">Admin Panel</a>
            <div class="d-flex">
                <a href="/secure/adm/index" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                $msgs = [
                    'tier_granted' => 'Tier concedido com sucesso!',
                    'trial_extended' => 'Trial estendido com sucesso!',
                ];
                echo $msgs[$_GET['success']] ?? 'Operação realizada com sucesso!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-credit-card-2-front"></i> Assinaturas</h2>
            <div>
                <a href="/secure/adm/subscriptions/grant" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Dar Tier Manual
                </a>
                <a href="/secure/adm/subscriptions/payments" class="btn btn-outline-primary">
                    <i class="bi bi-cash-stack"></i> Histórico de Pagamentos
                </a>
                <a href="/secure/adm/coupons" class="btn btn-outline-secondary">
                    <i class="bi bi-ticket-perforated"></i> Cupons
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stats-card active">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Ativas</h6>
                        <h3 class="mb-0"><?= number_format($stats['active']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card trial">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Em Trial</h6>
                        <h3 class="mb-0"><?= number_format($stats['trialing']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card canceled">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Canceladas</h6>
                        <h3 class="mb-0"><?= number_format($stats['canceled']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card manual">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Manuais</h6>
                        <h3 class="mb-0"><?= number_format($stats['manual']) ?></h3>
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
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativa</option>
                            <option value="trialing" <?= $status === 'trialing' ? 'selected' : '' ?>>Trial</option>
                            <option value="canceled" <?= $status === 'canceled' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="past_due" <?= $status === 'past_due' ? 'selected' : '' ?>>Atrasada</option>
                            <option value="manual" <?= $status === 'manual' ? 'selected' : '' ?>>Manual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tier</label>
                        <select name="tier" class="form-select">
                            <option value="">Todos</option>
                            <option value="PLUS" <?= $tier === 'PLUS' ? 'selected' : '' ?>>PLUS</option>
                            <option value="PRO" <?= $tier === 'PRO' ? 'selected' : '' ?>>PRO</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-control" placeholder="Nome, email ou CPF" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
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
                                <th>Plano</th>
                                <th>Tier</th>
                                <th>Status</th>
                                <th>Período</th>
                                <th>Origem</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        Nenhuma assinatura encontrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscriptions as $sub): ?>
                                    <tr>
                                        <td><?= $sub['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($sub['user_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($sub['user_email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($sub['plan_name'] ?? $sub['plan_slug']) ?></td>
                                        <td><?= tierBadge($sub['tier']) ?></td>
                                        <td><?= statusBadge($sub['status']) ?></td>
                                        <td>
                                            <?php if ($sub['current_period_end']): ?>
                                                <small>
                                                    <?= date('d/m/Y', strtotime($sub['current_period_start'])) ?>
                                                    - 
                                                    <?= date('d/m/Y', strtotime($sub['current_period_end'])) ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sub['source'] === 'admin'): ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Stripe</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($sub['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/secure/adm/subscriptions/view?id=<?= $sub['id'] ?>" class="btn btn-outline-primary" title="Ver detalhes">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/secure/adm/subscriptions/extend-trial?subscription_id=<?= $sub['id'] ?>" class="btn btn-outline-info" title="Estender Trial">
                                                    <i class="bi bi-calendar-plus"></i>
                                                </a>
                                                <a href="/secure/adm/users/view?id=<?= $sub['user_id'] ?>" class="btn btn-outline-secondary" title="Ver usuário">
                                                    <i class="bi bi-person"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&tier=<?= urlencode($tier) ?>&search=<?= urlencode($search) ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&tier=<?= urlencode($tier) ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&tier=<?= urlencode($tier) ?>&search=<?= urlencode($search) ?>">
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
