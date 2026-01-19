<?php
ob_start();
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="fas fa-edit me-2"></i>Editar Plano</h1>
        <a href="/secure/adm/plans" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Informacoes do Plano</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome:</label>
                            <p class="mb-0"><?= htmlspecialchars($plan['name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Slug:</label>
                            <p class="mb-0"><code><?= htmlspecialchars($plan['slug']) ?></code></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tier:</label>
                            <p class="mb-0">
                                <span class="badge <?= $plan['tier'] === 'PRO' ? 'bg-purple' : 'bg-primary' ?>">
                                    <?= htmlspecialchars($plan['tier']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Intervalo:</label>
                            <p class="mb-0">
                                <?php
                                $intervals = ['month' => 'Mensal', 'year' => 'Anual'];
                                echo $intervals[$plan['interval_type']] ?? $plan['interval_type'];
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Stripe Product ID:</label>
                            <p class="mb-0"><code><?= htmlspecialchars($plan['stripe_product_id'] ?? 'N/A') ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Stripe Price ID:</label>
                            <p class="mb-0"><code><?= htmlspecialchars($plan['stripe_price_id'] ?? 'N/A') ?></code></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descricao:</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($plan['description'] ?? 'Sem descricao')) ?></p>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Alterar Preco</h5>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atencao:</strong> Ao alterar o preco, um novo Price ID sera criado no Stripe automaticamente. 
                        Assinantes atuais manterao o preco antigo.
                    </div>
                    
                    <form id="updatePriceForm">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="confirmed" value="0" id="confirmed">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preco Atual</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" class="form-control" 
                                           value="<?= number_format($plan['price_cents'] / 100, 2, ',', '.') ?>" 
                                           disabled>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Novo Preco</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="new-price" 
                                           name="price_cents" step="0.01" min="0.01" 
                                           placeholder="<?= number_format($plan['price_cents'] / 100, 2, ',', '.') ?>"
                                           required>
                                </div>
                                <small class="text-muted">Em reais (exemplo: 29.90)</small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Atualizar Preco
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Estatisticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assinantes Ativos:</label>
                        <p class="mb-0 h4 text-success">
                            <?= number_format($plan['active_subscribers'] ?? 0) ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Novos (30 dias):</label>
                        <p class="mb-0 h5 text-info">
                            +<?= number_format($plan['new_last_30_days'] ?? 0) ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Receita Mensal:</label>
                        <p class="mb-0 h5 text-primary">
                            R$ <?= number_format(($plan['monthly_revenue_cents'] ?? 0) / 100, 2, ',', '.') ?>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status:</label>
                        <p class="mb-0">
                            <?php if ($plan['is_active']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if ($plan['is_featured']): ?>
                        <div class="mb-3">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star me-1"></i>Plano em Destaque
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($plan['has_active_discount']): ?>
                <div class="card mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-percent me-2"></i>Desconto Ativo</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Desconto:</label>
                            <p class="mb-0 h4 text-danger">
                                <?= $plan['discount_percentage'] ?>% OFF
                            </p>
                        </div>
                        
                        <?php if ($plan['discount_label']): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Label:</label>
                                <p class="mb-0"><?= htmlspecialchars($plan['discount_label']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($plan['discount_start_date']): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Inicio:</label>
                                <p class="mb-0">
                                    <?= date('d/m/Y H:i', strtotime($plan['discount_start_date'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($plan['discount_end_date']): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fim:</label>
                                <p class="mb-0">
                                    <?= date('d/m/Y H:i', strtotime($plan['discount_end_date'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preco com Desconto:</label>
                            <p class="mb-0 h5 text-success">
                                R$ <?= number_format($plan['effective_price_cents'] / 100, 2, ',', '.') ?>
                            </p>
                            <small class="text-muted">
                                (Original: R$ <?= number_format($plan['price_cents'] / 100, 2, ',', '.') ?>)
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$scripts = <<<'SCRIPTS'
<script>
document.getElementById('updatePriceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPrice = parseFloat(document.getElementById('new-price').value);
    if (isNaN(newPrice) || newPrice <= 0) {
        alert('Preco invalido');
        return;
    }
    
    // Converter para centavos
    const newPriceCents = Math.round(newPrice * 100);
    
    // Confirmar alteração
    if (document.getElementById('confirmed').value !== '1') {
        if (!confirm(`Tem certeza que deseja alterar o preco para R$ ${newPrice.toFixed(2)}?\n\nUm novo Price ID sera criado no Stripe e os assinantes atuais manterao o preco antigo.`)) {
            return;
        }
        document.getElementById('confirmed').value = '1';
    }
    
    const formData = new FormData(this);
    formData.set('price_cents', newPriceCents);
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Atualizando...';
    
    fetch('/secure/adm/plans/update-price', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Preco atualizado com sucesso!\n\nNovo Stripe Price ID: ' + data.new_stripe_price_id);
            location.reload();
        } else {
            alert('Erro: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Atualizar Preco';
            document.getElementById('confirmed').value = '0';
        }
    })
    .catch(err => {
        alert('Erro ao atualizar preco');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Atualizar Preco';
        document.getElementById('confirmed').value = '0';
    });
});
</script>
SCRIPTS;

include __DIR__ . '/../../layouts/app.php';
