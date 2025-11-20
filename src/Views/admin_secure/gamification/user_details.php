<?php
$pageTitle = 'Detalhes do Usuário - XP';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="/secure/adm/gamification" class="btn btn-outline-secondary mb-3">
                Voltar ao Dashboard
            </a>
            <h2><?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <!-- Estatísticas do Usuário -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    
                    <h3>Nível <?= (int)($user['level'] ?? 1) ?></h3>
                    <small class="text-muted">Level Atual</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    
                    <h3><?= number_format((int)($user['xp'] ?? 0)) ?></h3>
                    <small class="text-muted">XP Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    
                    <h3><?= (int)($user['streak'] ?? 0) ?> 🔥</h3>
                    <small class="text-muted">Dias Seguidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    
                    <h3><?= number_format($nextLevelXP ?? 0) ?></h3>
                    <small class="text-muted">XP para Próx. Nível</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações Adicionais -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Informações</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?= (int)($user['id'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email Verificado:</strong></td>
                            <td>
                                <?php if (!empty($user['email_verified_at'])): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Não</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Último Login:</strong></td>
                            <td><?= (!empty($user['last_login_at']) && $user['last_login_at'] !== null) ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Nunca' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Último XP Ganho:</strong></td>
                            <td><?= (!empty($user['last_xp_earned_at']) && $user['last_xp_earned_at'] !== null) ? date('d/m/Y H:i', strtotime($user['last_xp_earned_at'])) : 'Nunca' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Membro desde:</strong></td>
                            <td><?= (!empty($user['created_at']) && $user['created_at'] !== null) ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">XP por Fonte</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($xpBySource)): ?>
                        <p class="text-muted text-center mb-0">Nenhuma transação de XP registrada</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fonte</th>
                                    <th class="text-center">Transações</th>
                                    <th class="text-end">XP Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($xpBySource as $item): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $sourceLabel = [
                                            'daily_login' => 'Login Diário',
                                            'lesson_complete' => 'Aulas Completas',
                                            'course_complete' => 'Cursos Completos',
                                            'manual' => 'Adicionado Manualmente'
                                        ];
                                        echo htmlspecialchars($sourceLabel[$item['source']] ?? $item['source'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td class="text-center"><?= number_format($item['count']) ?></td>
                                    <td class="text-end"><strong><?= number_format($item['total_xp']) ?> XP</strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de XP -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Histórico Completo de XP</h5>
                    <span class="badge bg-primary"><?= count($history) ?> transações</span>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <p class="text-muted text-center mb-0">Nenhuma transação de XP encontrada</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Fonte</th>
                                        <th>Descrição</th>
                                        <th class="text-end">XP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $item): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                        <td>
                                            <?php
                                            $sourceLabel = [
                                                'daily_login' => '<span class="badge bg-info">Login</span>',
                                                'lesson_complete' => '<span class="badge bg-primary">Aula</span>',
                                                'course_complete' => '<span class="badge bg-success">Curso</span>',
                                                'manual' => '<span class="badge bg-warning">Manual</span>'
                                            ];
                                            echo $sourceLabel[$item['source']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($item['source'], ENT_QUOTES, 'UTF-8') . '</span>';
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end">
                                            <strong class="<?= $item['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $item['amount'] > 0 ? '+' : '' ?><?= number_format($item['amount']) ?> XP
                                            </strong>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>
