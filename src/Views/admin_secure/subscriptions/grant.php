<?php
/**
 * Admin - Dar Tier Manualmente
 */

$pageTitle = 'Dar Tier Manual | Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';

$errorMessages = [
    'csrf' => 'Token de segurança inválido. Tente novamente.',
    'invalid' => 'Dados inválidos. Verifique os campos.',
    'user_not_found' => 'Usuário não encontrado.',
    'exception' => 'Ocorreu um erro inesperado. Tente novamente.',
    'already_has_subscription' => 'Usuário já possui uma assinatura ativa.',
];
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
        .tier-option { cursor: pointer; transition: all 0.2s; }
        .tier-option:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .tier-option.selected { border-color: #0d6efd !important; background: #f0f7ff; }
        .tier-option input { display: none; }
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
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-gift"></i> Dar Tier Manualmente</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= $errorMessages[$error] ?? 'Erro desconhecido.' ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Tier concedido com sucesso!
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/secure/adm/subscriptions/grant">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <!-- Buscar Usuário -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Usuário</label>
                                <?php if ($user): ?>
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($user['name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                    <span class="badge bg-<?= $user['tier'] === 'PRO' ? 'warning' : ($user['tier'] === 'PLUS' ? 'primary' : 'secondary') ?> ms-2">
                                                        <?= $user['tier'] ?>
                                                    </span>
                                                </div>
                                                <a href="/secure/adm/subscriptions/grant" class="btn btn-sm btn-outline-secondary">
                                                    Trocar
                                                </a>
                                            </div>
                                        </div>
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

                            <!-- Escolher Tier -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tier</label>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="card tier-option h-100 text-center p-3" id="tier_plus_card">
                                            <input type="radio" name="tier" value="PLUS" id="tier_plus" required>
                                            <div>
                                                <span class="badge bg-primary fs-5 mb-2">PLUS</span>
                                                <p class="mb-0 small text-muted">Acesso a recursos Plus</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <label class="card tier-option h-100 text-center p-3" id="tier_pro_card">
                                            <input type="radio" name="tier" value="PRO" id="tier_pro">
                                            <div>
                                                <span class="badge bg-warning text-dark fs-5 mb-2">PRO</span>
                                                <p class="mb-0 small text-muted">Acesso completo</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Data de Expiração -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Data de Expiração</label>
                                <input type="datetime-local" name="expires_at" class="form-control" id="expires_at">
                                <div class="form-text">
                                    Deixe em branco para acesso vitalício (sem expiração).
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiration(7)">+7 dias</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiration(30)">+30 dias</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiration(90)">+90 dias</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiration(365)">+1 ano</button>
                                </div>
                            </div>

                            <!-- Notas -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Notas (opcional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Motivo da concessão, referência de venda, etc."></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Conceder Tier
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
        // Tier selection
        document.querySelectorAll('.tier-option').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.tier-option').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Set expiration date
        function setExpiration(days) {
            const date = new Date();
            date.setDate(date.getDate() + days);
            document.getElementById('expires_at').value = date.toISOString().slice(0, 16);
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
                <span class="badge bg-${user.tier === 'PRO' ? 'warning' : (user.tier === 'PLUS' ? 'primary' : 'secondary')} ms-2">${user.tier}</span>
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

        // Enter key search
        document.getElementById('user_search')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUsers();
            }
        });
    </script>
</body>
</html>
