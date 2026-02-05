<?php
ob_start();
?>
<style>
.tier-option { cursor: pointer; transition: all 0.2s; }
.tier-option:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.tier-option.selected { border-color: #0d6efd !important; background: #f0f7ff; }
.tier-option input { display: none; }
</style>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Dar Tier Manualmente</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger alert-auto-dismiss">
                            <?php
                            $errorMessages = [
                                'csrf' => 'Token de segurança inválido. Tente novamente.',
                                'invalid' => 'Dados inválidos. Verifique os campos.',
                                'user_not_found' => 'Usuário não encontrado.',
                                'exception' => 'Ocorreu um erro inesperado. Tente novamente.',
                                'already_has_subscription' => 'Usuário já possui uma assinatura ativa.',
                            ];
                            echo $errorMessages[$error] ?? 'Erro desconhecido.';
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success) && $success): ?>
                        <div class="alert alert-success alert-auto-dismiss">
                            Tier concedido com sucesso!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/secure/adm/subscriptions/grant">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Usuário</label>
                            <?php if (isset($user) && $user): ?>
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

                        <div class="mb-4">
                            <label class="form-label fw-bold">Notas (opcional)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Motivo da concessao, referencia de venda, etc."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Conceder Tier
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
    document.querySelectorAll('.tier-option').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.tier-option').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    function setExpiration(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        document.getElementById('expires_at').value = date.toISOString().slice(0, 16);
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
        const tierClass = user.tier === 'PRO' ? 'warning' : (user.tier === 'PLUS' ? 'primary' : 'secondary');
        document.getElementById('selected_user_info').innerHTML = `
            <strong>${escapeHtml(user.name)}</strong><br>
            <small class="text-muted">${escapeHtml(user.email)}</small>
            <span class="badge bg-${tierClass} ms-2">${escapeHtml(user.tier || 'FREE')}</span>
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
