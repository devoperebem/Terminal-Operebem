<?php
/**
 * Admin - Estender Trial
 */

$pageTitle = 'Estender Trial | Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';

$errorMessages = [
    'csrf' => 'Token de segurança inválido. Tente novamente.',
    'invalid' => 'Dados inválidos. Informe o usuário e quantidade de dias.',
    'user_not_found' => 'Usuário não encontrado.',
    'no_subscription' => 'Usuário não possui assinatura em trial.',
    'exception' => 'Ocorreu um erro inesperado. Tente novamente.',
];

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
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

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Estender Trial</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= $errorMessages[$error] ?? 'Erro: ' . htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Trial estendido com sucesso!
                            </div>
                        <?php endif; ?>

                        <?php if ($subscription): ?>
                            <!-- Informações da assinatura -->
                            <div class="alert alert-light border mb-4">
                                <h6 class="alert-heading">Assinatura Atual</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Usuário</small><br>
                                        <strong><?= htmlspecialchars($subscription['user_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($subscription['user_email']) ?></small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Trial termina em</small><br>
                                        <strong><?= formatDate($subscription['trial_end']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/secure/adm/subscriptions/extend-trial">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <!-- Usuário -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Usuário</label>
                                <?php if ($subscription): ?>
                                    <input type="hidden" name="user_id" value="<?= $subscription['user_id'] ?>">
                                    <div class="form-control-plaintext">
                                        <?= htmlspecialchars($subscription['user_name']) ?> 
                                        <small class="text-muted">(<?= htmlspecialchars($subscription['user_email']) ?>)</small>
                                    </div>
                                <?php elseif ($user): ?>
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <div class="form-control-plaintext">
                                        <?= htmlspecialchars($user['name']) ?> 
                                        <small class="text-muted">(<?= htmlspecialchars($user['email']) ?>)</small>
                                    </div>
                                <?php else: ?>
                                    <div class="input-group mb-2">
                                        <input type="text" id="user_search" class="form-control" placeholder="Buscar por nome, email ou CPF...">
                                        <button type="button" class="btn btn-outline-primary" onclick="searchUsers()">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="user_id" id="user_id" required>
                                    <div id="search_results" class="list-group"></div>
                                    <div id="selected_user" class="card bg-light d-none mt-2">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div id="selected_user_info"></div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSelection()">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Quantidade de Dias -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Quantidade de Dias</label>
                                <div class="input-group">
                                    <input type="number" name="days" class="form-control" min="1" max="365" value="7" required id="days_input">
                                    <span class="input-group-text">dias</span>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(7)">+7 dias</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(14)">+14 dias</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(30)">+30 dias</button>
                                </div>
                            </div>

                            <!-- Motivo -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Motivo (opcional)</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Por que está estendendo o trial?"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-info btn-lg">
                                    <i class="bi bi-calendar-plus"></i> Estender Trial
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setDays(days) {
            document.getElementById('days_input').value = days;
        }

        // User search
        async function searchUsers() {
            const query = document.getElementById('user_search').value.trim();
            if (query.length < 2) return;

            try {
                const response = await fetch('/api/admin/users/search?q=' + encodeURIComponent(query));
                const data = await response.json();
                
                const results = document.getElementById('search_results');
                results.innerHTML = '';

                if (data.users && data.users.length > 0) {
                    data.users.slice(0, 10).forEach(user => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${escapeHtml(user.name)}</strong>
                                    <small class="text-muted d-block">${escapeHtml(user.email)}</small>
                                </div>
                                <span class="badge bg-${user.tier === 'PRO' ? 'warning' : (user.tier === 'PLUS' ? 'primary' : 'secondary')}">${user.tier}</span>
                            </div>
                        `;
                        item.onclick = () => selectUser(user);
                        results.appendChild(item);
                    });
                } else {
                    results.innerHTML = '<div class="list-group-item text-muted">Nenhum usuário encontrado.</div>';
                }
            } catch (e) {
                console.error('Erro ao buscar usuários:', e);
            }
        }

        function selectUser(user) {
            document.getElementById('user_id').value = user.id;
            document.getElementById('search_results').innerHTML = '';
            document.getElementById('user_search').classList.add('d-none');
            document.getElementById('selected_user').classList.remove('d-none');
            document.getElementById('selected_user_info').innerHTML = `
                <strong>${escapeHtml(user.name)}</strong><br>
                <small class="text-muted">${escapeHtml(user.email)}</small>
            `;
        }

        function clearSelection() {
            document.getElementById('user_id').value = '';
            document.getElementById('user_search').classList.remove('d-none');
            document.getElementById('user_search').value = '';
            document.getElementById('selected_user').classList.add('d-none');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById('user_search')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUsers();
            }
        });
    </script>
</body>
</html>
