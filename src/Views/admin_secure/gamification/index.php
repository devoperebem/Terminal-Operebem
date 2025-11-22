<?php
$pageTitle = 'Gerenciamento de XP';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Sistema de Gamificação</h2>
            <p class="text-muted">Gerencie XP, Streaks e Níveis dos usuários</p>
        </div>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h3><?= number_format($stats['total_users'] ?? 0) ?></h3>
                    <small class="text-muted">Total de Usuários</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h3><?= number_format($stats['total_xp'] ?? 0) ?></h3>
                    <small class="text-muted">XP Total Distribuído</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                    <h3><?= number_format($stats['avg_xp'] ?? 0, 0) ?></h3>
                    <small class="text-muted">XP Médio por Usuário</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-fire fa-2x text-danger mb-2"></i>
                    <h3><?= number_format($stats['max_streak'] ?? 0) ?></h3>
                    <small class="text-muted">Maior Streak</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addXPModal">
                        <i class="fas fa-plus me-2"></i>Adicionar XP Manualmente
                    </button>
                    <button class="btn btn-warning" onclick="recalculateLevels()">
                        <i class="fas fa-sync me-2"></i>Recalcular Todos os Níveis
                    </button>
                    <a href="/secure/adm/gamification/settings" class="btn btn-secondary">
                        <i class="fas fa-cog me-2"></i>Configurações
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top 10 Usuários -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Usuários por XP</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>XP</th>
                                    <th>Nível</th>
                                    <th>Streak</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topUsers as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><span class="badge bg-warning"><?= number_format($user['xp'] ?? 0) ?></span></td>
                                    <td><span class="badge bg-primary">Nv <?= $user['level'] ?? 1 ?></span></td>
                                    <td><span class="badge bg-danger"><?= $user['streak'] ?? 0 ?> 🔥</span></td>
                                    <td>
                                        <a href="/secure/adm/gamification/user/<?= $user['id'] ?>" class="btn btn-sm btn-info" title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- XP por Fonte -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">XP por Fonte (Últimos 30 dias)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fonte</th>
                                    <th>Transações</th>
                                    <th>Total XP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($xpBySource)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Nenhuma transação de XP nos últimos 30 dias
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($xpBySource as $source): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $icons = [
                                            'daily_login' => 'fa-sign-in-alt',
                                            'lesson_complete' => 'fa-graduation-cap',
                                            'course_complete' => 'fa-certificate',
                                            'manual' => 'fa-user-shield',
                                            'streak_bonus' => 'fa-fire'
                                        ];
                                        $labels = [
                                            'daily_login' => 'Login Diário',
                                            'lesson_complete' => 'Aula Concluída',
                                            'course_complete' => 'Curso Concluído',
                                            'manual' => 'Manual (Admin)',
                                            'streak_bonus' => 'Bônus de Streak'
                                        ];
                                        $icon = $icons[$source['source']] ?? 'fa-star';
                                        $label = $labels[$source['source']] ?? $source['source'];
                                        ?>
                                        <i class="fas <?= $icon ?> me-2"></i><?= $label ?>
                                    </td>
                                    <td><?= number_format($source['count']) ?></td>
                                    <td><strong><?= number_format($source['total_xp']) ?> XP</strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico Recente -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Histórico Recente de XP</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Fonte</th>
                                    <th>XP</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentXP)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-history fa-2x mb-2 d-block"></i>
                                        Nenhum histórico de XP encontrado
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentXP as $xp): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($xp['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($xp['user_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($xp['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($xp['source'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $xp['amount'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $xp['amount'] > 0 ? '+' : '' ?><?= $xp['amount'] ?> XP
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($xp['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar XP -->
<div class="modal fade" id="addXPModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar XP Manualmente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addXPForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID do Usuário</label>
                        <input type="number" class="form-control" name="user_id" required>
                        <small class="text-muted">Ou busque pelo email abaixo</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantidade de XP</label>
                        <input type="number" class="form-control" name="amount" required>
                        <small class="text-muted">Use valores negativos para remover XP</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar XP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addXPForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('/secure/adm/gamification/add-xp', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao adicionar XP');
    }
});

async function recalculateLevels() {
    if (!confirm('Recalcular níveis de todos os usuários? Esta operação pode demorar.')) {
        return;
    }
    
    try {
        const response = await fetch('/secure/adm/gamification/recalculate-levels', {
            method: 'POST'
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao recalcular níveis');
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>
