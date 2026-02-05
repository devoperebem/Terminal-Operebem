<?php
ob_start();
?>
<style>
.stats-card { border-left: 4px solid; }
.stats-card.active { border-left-color: #198754; }
.stats-card.trial { border-left-color: #0dcaf0; }
.stats-card.canceled { border-left-color: #ffc107; }
.stats-card.manual { border-left-color: #6f42c1; }
</style>

<div class="container my-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible alert-auto-dismiss fade show">
            <?php
            $msgs = [
                'tier_granted' => 'Tier concedido com sucesso!',
                'trial_extended' => 'Trial estendido com sucesso!',
            ];
            echo $msgs[$_GET['success']] ?? 'Operacao realizada com sucesso!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="/secure/adm/index" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
            <h1 class="h4 mb-0"><i class="fas fa-credit-card me-2"></i>Assinaturas</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/secure/adm/subscriptions/grant" class="btn btn-success btn-sm">
                <i class="fas fa-plus-circle me-2"></i>Dar Tier Manual
            </a>
            <a href="/secure/adm/subscriptions/payments" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-cash-register me-2"></i>Historico de Pagamentos
            </a>
            <a href="/secure/adm/coupons" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-ticket me-2"></i>Cupons
            </a>
        </div>
    </div>

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
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                                    <td>
                                        <?php
                                        $tierBadges = [
                                            'FREE' => 'bg-secondary',
                                            'PLUS' => 'bg-primary',
                                            'PRO' => 'bg-warning text-dark',
                                        ];
                                        $tierClass = $tierBadges[$sub['tier']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $tierClass ?>"><?= $sub['tier'] ?></span>
                                    </td>
                                    <td>
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
                                        $statusInfo = $statusBadges[$sub['status']] ?? ['bg-secondary', $sub['status']];
                                        ?>
                                        <span class="badge <?= $statusInfo[0] ?>"><?= $statusInfo[1] ?></span>
                                    </td>
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
                                    <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/secure/adm/subscriptions/view?id=<?= $sub['id'] ?>" class="btn btn-outline-primary" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/secure/adm/subscriptions/extend-trial?subscription_id=<?= $sub['id'] ?>" class="btn btn-outline-info" title="Estender Trial">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <a href="/secure/adm/users/view?id=<?= $sub['user_id'] ?>" class="btn btn-outline-secondary" title="Ver usuário">
                                                <i class="fas fa-user"></i>
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
                                    Proxima
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

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../../layouts/app.php';
?>
