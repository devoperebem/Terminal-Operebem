<?php
$pageTitle = 'Configurações de XP';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="/secure/adm/gamification" class="btn btn-outline-secondary mb-3">
                Voltar ao Dashboard
            </a>
            <h2>Configurações do Sistema de XP</h2>
            <p class="text-muted">Configure os valores de XP concedidos por cada ação</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Valores de XP</h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- Login Diário -->
                        <div class="mb-4">
                            <h6 class="text-primary">Login Diário</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="xp_daily_login" class="form-label">XP por Login Diário</label>
                                    <input type="number" class="form-control" id="xp_daily_login" name="xp_daily_login" 
                                           value="<?= $xp_daily_login ?? 5 ?>" min="0" max="100" required>
                                    <small class="text-muted">XP concedido quando usuário faz login pela primeira vez no dia</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Aulas -->
                        <div class="mb-4">
                            <h6 class="text-primary">Aulas Assistidas</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="xp_lesson_base" class="form-label">XP Base por Aula</label>
                                    <input type="number" class="form-control" id="xp_lesson_base" name="xp_lesson_base" 
                                           value="<?= $xp_lesson_base ?? 10 ?>" min="0" max="100" required>
                                    <small class="text-muted">XP base para qualquer aula assistida</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="xp_lesson_bonus_30min" class="form-label">Bônus para Aulas +30min</label>
                                    <input type="number" class="form-control" id="xp_lesson_bonus_30min" name="xp_lesson_bonus_30min" 
                                           value="<?= $xp_lesson_bonus_30min ?? 5 ?>" min="0" max="50" required>
                                    <small class="text-muted">XP adicional para aulas com 30+ minutos</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="xp_lesson_bonus_1h" class="form-label">Bônus para Aulas +1h</label>
                                    <input type="number" class="form-control" id="xp_lesson_bonus_1h" name="xp_lesson_bonus_1h" 
                                           value="<?= $xp_lesson_bonus_1h ?? 10 ?>" min="0" max="50" required>
                                    <small class="text-muted">XP adicional para aulas com 1h+ de duração</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Cursos -->
                        <div class="mb-4">
                            <h6 class="text-primary">Cursos Completos</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="xp_course_complete" class="form-label">XP por Curso Completo</label>
                                    <input type="number" class="form-control" id="xp_course_complete" name="xp_course_complete" 
                                           value="<?= $xp_course_complete ?? 50 ?>" min="0" max="200" required>
                                    <small class="text-muted">XP concedido ao completar 100% de um curso</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Discord: Mensagens -->
                        <div class="mb-4">
                            <h6 class="text-primary">Participação no Discord</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="xp_discord_msg_amount" class="form-label">XP por Mensagem</label>
                                    <input type="number" class="form-control" id="xp_discord_msg_amount" name="xp_discord_msg_amount" 
                                           value="<?= $xp_discord_msg_amount ?? 1 ?>" min="0" max="25" required>
                                    <small class="text-muted">XP concedido por mensagem (0 desativa)</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="xp_discord_msg_cooldown_minutes" class="form-label">Cooldown (minutos)</label>
                                    <input type="number" class="form-control" id="xp_discord_msg_cooldown_minutes" name="xp_discord_msg_cooldown_minutes" 
                                           value="<?= $xp_discord_msg_cooldown_minutes ?? 10 ?>" min="0" max="180" required>
                                    <small class="text-muted">Intervalo mínimo entre premiações</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="xp_discord_msg_daily_cap" class="form-label">Limite Diário de XP</label>
                                    <input type="number" class="form-control" id="xp_discord_msg_daily_cap" name="xp_discord_msg_daily_cap" 
                                           value="<?= $xp_discord_msg_daily_cap ?? 25 ?>" min="0" max="500" required>
                                    <small class="text-muted">Máximo de XP/dia vindo de mensagens (0 desativa)</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            
                            <strong>Informação:</strong> As configurações são salvas no banco de dados e aplicadas imediatamente em todo o sistema.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/secure/adm/gamification" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Fórmula de Nível -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Fórmula de Nível</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">O nível é calculado com base no XP total:</p>
                    <div class="bg-light p-3 rounded text-center">
                        <code class="fs-5">Level = floor(√(XP / 10))</code>
                    </div>
                    <hr>
                    <p class="mb-2"><strong>Exemplos:</strong></p>
                    <ul class="small">
                        <li>100 XP = Nível 3</li>
                        <li>400 XP = Nível 6</li>
                        <li>1000 XP = Nível 10</li>
                        <li>2500 XP = Nível 15</li>
                    </ul>
                </div>
            </div>

            <!-- Sistema de Streak -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Sistema de Streak</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        O streak (dias consecutivos) aumenta quando o usuário faz login todos os dias.
                    </p>
                    <ul class="small">
                        <li><strong>Aumenta:</strong> Se usuário logou ontem ou nas últimas 48h</li>
                        <li><strong>Quebra:</strong> Se usuário não logou há mais de 48h</li>
                        <li><strong>Reset:</strong> Volta para 1 quando quebra</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Salvando...';
    
    try {
        const formData = new FormData(this);
        const response = await fetch('/secure/adm/gamification/settings', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mostrar mensagem de sucesso
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                
                ${result.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            this.insertBefore(alert, this.firstChild);
            
            // Remover alerta após 3 segundos
            setTimeout(() => alert.remove(), 3000);
        } else {
            throw new Error(result.message || 'Erro ao salvar');
        }
    } catch (error) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            
            ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        this.insertBefore(alert, this.firstChild);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
