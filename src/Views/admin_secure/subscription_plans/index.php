<?php
ob_start();
?>
<style>
.stats-card { border-left: 4px solid; }
.stats-card.subscribers { border-left-color: #198754; }
.stats-card.new { border-left-color: #0dcaf0; }
.stats-card.revenue { border-left-color: #6f42c1; }
.stats-card.plans { border-left-color: #0d6efd; }
.bg-purple { background-color: #6f42c1 !important; color: #fff; }
.discount-badge { 
    font-size: 0.75rem; 
    padding: 0.25rem 0.5rem; 
}
.plan-row.inactive {
    opacity: 0.6;
    background-color: #f8f9fa;
}
</style>

<div class="container my-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible alert-auto-dismiss fade show">
            Operacao realizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible alert-auto-dismiss fade show">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="fas fa-box-open me-2"></i>Gerenciamento de Planos</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="/secure/adm/subscriptions" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-credit-card me-2"></i>Assinaturas
            </a>
            <a href="/secure/adm/coupons" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-ticket me-2"></i>Cupons
            </a>
        </div>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card subscribers">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Assinantes Ativos</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_active']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card new">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Novos (30d)</h6>
                    <h3 class="mb-0"><?= number_format($stats['new_30_days']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card revenue">
                <div class="card-body">
                    <h6 class="text-muted mb-1">MRR Total</h6>
                    <h3 class="mb-0">R$ <?= number_format($stats['mrr_cents'] / 100, 2, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card plans">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Planos Ativos</h6>
                    <h3 class="mb-0"><?= count(array_filter($plans, fn($p) => $p['is_active'])) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Planos -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Planos de Assinatura</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plano</th>
                            <th>Tier</th>
                            <th>Intervalo</th>
                            <th>Preco</th>
                            <th>Assinantes</th>
                            <th>Novos (30d)</th>
                            <th>Receita Mensal</th>
                            <th>Status</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Nenhum plano encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plans as $plan): ?>
                                <tr class="plan-row <?= !$plan['is_active'] ? 'inactive' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($plan['name']) ?></strong>
                                        <?php if ($plan['is_featured']): ?>
                                            <span class="badge bg-warning text-dark ms-1">Destaque</span>
                                        <?php endif; ?>
                                        <?php if ($plan['has_active_discount']): ?>
                                            <br>
                                            <span class="badge bg-danger discount-badge">
                                                <?= $plan['discount_percentage'] ?>% OFF
                                                <?php if ($plan['discount_label']): ?>
                                                    - <?= htmlspecialchars($plan['discount_label']) ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $plan['tier'] === 'PRO' ? 'bg-purple' : 'bg-primary' ?>">
                                            <?= htmlspecialchars($plan['tier']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $intervals = ['month' => 'Mensal', 'year' => 'Anual'];
                                        echo $intervals[$plan['interval_type']] ?? $plan['interval_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if ($plan['has_active_discount']): ?>
                                                <span class="text-decoration-line-through text-muted small">
                                                    R$ <?= number_format($plan['price_cents'] / 100, 2, ',', '.') ?>
                                                </span>
                                                <br>
                                                <strong class="text-success">
                                                    R$ <?= number_format($plan['effective_price_cents'] / 100, 2, ',', '.') ?>
                                                </strong>
                                            <?php else: ?>
                                                <strong>
                                                    R$ <?= number_format($plan['price_cents'] / 100, 2, ',', '.') ?>
                                                </strong>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?= number_format($plan['active_subscribers']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($plan['new_last_30_days'] > 0): ?>
                                            <span class="badge bg-info text-dark">
                                                +<?= number_format($plan['new_last_30_days']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>R$ <?= number_format($plan['monthly_revenue_cents'] / 100, 2, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input 
                                                class="form-check-input toggle-plan-status" 
                                                type="checkbox" 
                                                data-plan-id="<?= $plan['id'] ?>"
                                                <?= $plan['is_active'] ? 'checked' : '' ?>
                                            >
                                            <label class="form-check-label small">
                                                <?= $plan['is_active'] ? 'Ativo' : 'Inativo' ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/secure/adm/plans/edit?id=<?= $plan['id'] ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-discount" 
                                                    data-plan-id="<?= $plan['id'] ?>"
                                                    data-plan-name="<?= htmlspecialchars($plan['name']) ?>"
                                                    data-has-discount="<?= !empty($plan['has_active_discount']) ? '1' : '0' ?>"
                                                    title="Aplicar Desconto">
                                                <i class="fas fa-percent"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Aplicar Desconto -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aplicar Desconto Promocional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="discountForm">
                    <input type="hidden" id="discount-plan-id" name="plan_id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Plano:</label>
                        <p id="discount-plan-name" class="text-muted"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount-percentage" class="form-label">Desconto (%)</label>
                        <input type="number" class="form-control" id="discount-percentage" 
                               name="discount_percentage" min="0" max="100" required>
                        <small class="text-muted">Entre 0 e 100</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount-label" class="form-label">Label Promocional (opcional)</label>
                        <input type="text" class="form-control" id="discount-label" 
                               name="label" placeholder="Ex: BLACK FRIDAY 30% OFF">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="discount-start" class="form-label">Data Inicio (opcional)</label>
                            <input type="datetime-local" class="form-control" id="discount-start" name="start_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="discount-end" class="form-label">Data Fim (opcional)</label>
                            <input type="datetime-local" class="form-control" id="discount-end" name="end_date">
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atencao:</strong> Planos com promocao ativa NAO aceitam cupons.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnRemoveDiscount" style="display: none;">
                    <i class="fas fa-times me-2"></i>Remover Desconto
                </button>
                <button type="button" class="btn btn-primary" id="btnApplyDiscount">
                    <i class="fas fa-check me-2"></i>Aplicar Desconto
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const discountModal = new bootstrap.Modal(document.getElementById('discountModal'));
    
    // Toggle status do plano
    document.querySelectorAll('.toggle-plan-status').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const planId = this.dataset.planId;
            const isActive = this.checked;
            
            if (!confirm(`Deseja realmente ${isActive ? 'ativar' : 'desativar'} este plano?`)) {
                this.checked = !isActive;
                return;
            }
            
            fetch('/secure/adm/plans/toggle-active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    plan_id: planId,
                    is_active: isActive,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.error);
                    toggle.checked = !isActive;
                }
            })
            .catch(err => {
                alert('Erro ao atualizar status');
                toggle.checked = !isActive;
            });
        });
    });
    
    // Aplicar desconto
    document.querySelectorAll('.btn-discount').forEach(btn => {
        btn.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const planName = this.dataset.planName;
            const hasDiscount = this.dataset.hasDiscount === '1';
            
            document.getElementById('discountForm').reset();
            document.getElementById('discount-plan-id').value = planId;
            document.getElementById('discount-plan-name').textContent = planName;
            
            // Mostrar botao de remover desconto se plano ja tem desconto ativo
            document.getElementById('btnRemoveDiscount').style.display = hasDiscount ? 'inline-block' : 'none';
            
            discountModal.show();
        });
    });
    
    // Aplicar desconto
    document.getElementById('btnApplyDiscount').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('discountForm'));
        
        fetch('/secure/adm/plans/apply-discount', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Desconto aplicado com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(err => {
            alert('Erro ao aplicar desconto');
        });
    });
    
    // Remover desconto
    document.getElementById('btnRemoveDiscount').addEventListener('click', function() {
        const planId = document.getElementById('discount-plan-id').value;
        
        if (!confirm('Deseja remover o desconto deste plano?')) return;
        
        fetch('/secure/adm/plans/remove-discount', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                plan_id: planId,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Desconto removido com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(err => {
            alert('Erro ao remover desconto');
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();

include __DIR__ . '/../../layouts/app.php';
?>
