<?php
ob_start();
?>
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Estender Trial</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger alert-auto-dismiss">
                            <?php
                            $errorMessages = [
                                'csrf' => 'Token de segurança inválido. Tente novamente.',
                                'invalid' => 'Dados inválidos. Informe o usuário e quantidade de dias.',
                                'user_not_found' => 'Usuário não encontrado.',
                                'no_subscription' => 'Usuário não possui assinatura em trial.',
                                'exception' => 'Ocorreu um erro inesperado. Tente novamente.',
                            ];
                            echo $errorMessages[$error] ?? ('Erro: ' . htmlspecialchars($error));
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success) && $success): ?>
                        <div class="alert alert-success alert-auto-dismiss">
                            Trial estendido com sucesso!
                        </div>
                    <?php endif; ?>

                    <?php if (isset($subscription) && $subscription): ?>
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
                                    <strong><?= $subscription['trial_end'] ? date('d/m/Y H:i', strtotime($subscription['trial_end'])) : '-' ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/secure/adm/subscriptions/extend-trial">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Usuário</label>
                            <?php if (isset($subscription) && $subscription): ?>
                                <input type="hidden" name="user_id" value="<?= $subscription['user_id'] ?>">
                                <div class="form-control-plaintext">
                                    <?= htmlspecialchars($subscription['user_name']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($subscription['user_email']) ?>)</small>
                                </div>
                            <?php elseif (isset($user) && $user): ?>
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <div class="form-control-plaintext">
                                    <?= htmlspecialchars($user['name']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($user['email']) ?>)</small>
                                </div>
                            <?php else: ?>
                                <div class="input-group mb-2">
                                    <input type="text" id="user_search" class="form-control" placeholder="Buscar por nome, email ou CPF...">
                                    <button type="button" class="btn btn-outline-primary" onclick="searchUsers()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="user_id" id="user_id" required>
                                <div id="search_results" class="list-group"></div>
                                <div id="selected_user" class="card bg-light d-none mt-2">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div id="selected_user_info"></div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSelection()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Quantidade de Dias</label>
                            <div class="input-group">
                                <input type="number" name="additional_days" class="form-control" min="1" max="365" value="7" required id="days_input">
                                <span class="input-group-text">dias</span>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(7)">+7 dias</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(14)">+14 dias</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDays(30)">+30 dias</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Motivo (opcional)</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="Por que está estendendo o trial?"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info btn-lg">
                                <i class="fas fa-calendar-plus me-2"></i>Estender Trial
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script>
    function setDays(days) {
        document.getElementById('days_input').value = days;
    }

    async function searchUsers() {
        const query = document.getElementById('user_search').value.trim();
        if (query.length < 2) return;

        try {
            const response = await fetch('/api/admin/users/search?q=' + encodeURIComponent(query));
            const data = await response.json();

            const results = document.getElementById('search_results');
            results.innerHTML = '';

            if (data.data && data.data.length > 0) {
                data.data.slice(0, 10).forEach(user => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    const tierClass = user.tier === 'PRO' ? 'warning' : (user.tier === 'PLUS' ? 'primary' : 'secondary');
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${escapeHtml(user.name)}</strong>
                                <small class="text-muted d-block">${escapeHtml(user.email)}</small>
                            </div>
                            <span class="badge bg-${tierClass}">${escapeHtml(user.tier || 'FREE')}</span>
                        </div>
                    `;
                    item.onclick = () => selectUser(user);
                    results.appendChild(item);
                });
            } else {
                results.innerHTML = '<div class="list-group-item text-muted">Nenhum usuario encontrado.</div>';
            }
        } catch (e) {
            console.error('Erro ao buscar usuarios:', e);
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
SCRIPTS;
include __DIR__ . '/../../layouts/app.php';
?>
