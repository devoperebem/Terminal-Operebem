<?php
ob_start();
$frontDisplay = $frontDisplay ?? [];
$systems = [
    'terminal'      => ['label' => 'Terminal Operebem',  'icon' => 'fas fa-chart-line',    'color' => '#3b82f6'],
    'portal_aluno'  => ['label' => 'Portal do Aluno',    'icon' => 'fas fa-graduation-cap','color' => '#ec4899'],
    'diario_trades' => ['label' => 'Diario de Trades',   'icon' => 'fas fa-book',          'color' => '#f59e0b'],
];
?>
<style>
.bg-purple { background-color: #6f42c1 !important; color: #fff; }
.fd-section { border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; margin-bottom: 1rem; }
.fd-toggle { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; cursor: pointer; background: var(--card-bg); border: none; width: 100%; text-align: left; font-weight: 600; font-size: 0.9rem; color: var(--text-primary); transition: background 0.15s; }
.fd-toggle:hover { background: var(--bg-secondary); }
.fd-toggle .fd-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.85rem; flex-shrink: 0; }
.fd-toggle .fd-chevron { margin-left: auto; transition: transform 0.2s; color: var(--text-secondary); }
.fd-toggle[aria-expanded="true"] .fd-chevron { transform: rotate(180deg); }
.fd-toggle .fd-status { font-size: 0.7rem; font-weight: 500; padding: 0.15rem 0.5rem; border-radius: 4px; }
.fd-body { padding: 1rem 1.25rem; border-top: 1px solid var(--border-color); background: var(--card-bg); }
.fd-save-result { display: inline-block; margin-left: 0.75rem; font-size: 0.8rem; font-weight: 500; opacity: 0; transition: opacity 0.3s; }
.fd-save-result.show { opacity: 1; }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="fas fa-edit me-2"></i>Editar Plano</h1>
        <a href="/secure/adm/plans" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
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
                <div class="card-header">
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

    <!-- ================================================================ -->
    <!-- EXIBICAO NO FRONT — Configuração de exibição por sistema         -->
    <!-- ================================================================ -->
    <div class="card mt-2 mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-desktop me-1"></i>
            <h5 class="mb-0">Exibicao no Front</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3" style="font-size:0.85rem;">
                Configure como este plano aparece em cada sistema. Os sistemas satelites consultam esta configuracao via API
                (<code>GET /api/plans/front-display?system=portal_aluno</code>).
            </p>

            <?php foreach ($systems as $sysKey => $sys):
                $fd = $frontDisplay[$sysKey] ?? [];
                $hasData = !empty($fd);
                $collapseId = 'fd_' . $sysKey;
            ?>
            <div class="fd-section">
                <button class="fd-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                    <span class="fd-icon" style="background:<?= $sys['color'] ?>;">
                        <i class="<?= $sys['icon'] ?>"></i>
                    </span>
                    <span><?= $sys['label'] ?></span>
                    <?php if ($hasData): ?>
                        <span class="fd-status badge bg-success">Configurado</span>
                    <?php else: ?>
                        <span class="fd-status badge bg-secondary">Nao configurado</span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down fd-chevron"></i>
                </button>
                <div class="collapse" id="<?= $collapseId ?>">
                    <div class="fd-body">
                        <form class="fd-form" data-system="<?= $sysKey ?>">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <input type="hidden" name="system_key" value="<?= $sysKey ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nome exibido</label>
                                    <input type="text" class="form-control" name="display_name" value="<?= htmlspecialchars($fd['display_name'] ?? $plan['name']) ?>" placeholder="<?= htmlspecialchars($plan['name']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Preco exibido</label>
                                    <input type="text" class="form-control" name="price_display" value="<?= htmlspecialchars($fd['price_display'] ?? '') ?>" placeholder="R$ 9,90">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Subtitulo preco</label>
                                    <input type="text" class="form-control" name="price_subtitle" value="<?= htmlspecialchars($fd['price_subtitle'] ?? '') ?>" placeholder="/mes">
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descricao</label>
                                    <input type="text" class="form-control" name="description" value="<?= htmlspecialchars($fd['description'] ?? '') ?>" placeholder="Descricao curta do plano para este sistema">
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Features (uma por linha)</label>
                                    <textarea class="form-control" name="features" rows="4" placeholder="Acesso completo aos cursos&#10;Suporte prioritario&#10;..."><?= htmlspecialchars(is_array($fd['features'] ?? null) ? implode("\n", $fd['features']) : '') ?></textarea>
                                    <small class="text-muted">Cada linha sera um item com check no card de pricing.</small>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">CTA Label</label>
                                    <input type="text" class="form-control" name="cta_label" value="<?= htmlspecialchars($fd['cta_label'] ?? '') ?>" placeholder="Assinar">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">CTA URL</label>
                                    <input type="url" class="form-control" name="cta_url" value="<?= htmlspecialchars($fd['cta_url'] ?? '') ?>" placeholder="https://terminal.operebem.com.br/subscribe/plus">
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Badge</label>
                                    <input type="text" class="form-control" name="badge_text" value="<?= htmlspecialchars($fd['badge_text'] ?? '') ?>" placeholder="POPULAR">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Ordem</label>
                                    <input type="number" class="form-control" name="display_order" value="<?= (int)($fd['display_order'] ?? 0) ?>" min="0">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_highlighted" value="1" id="hl_<?= $sysKey ?>" <?= !empty($fd['is_highlighted']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="hl_<?= $sysKey ?>">Destaque</label>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="vis_<?= $sysKey ?>" <?= (!$hasData || !empty($fd['is_visible'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vis_<?= $sysKey ?>">Visivel</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex align-items-center">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save me-1"></i>Salvar <?= $sys['label'] ?>
                                </button>
                                <span class="fd-save-result" id="result_<?= $sysKey ?>"></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
document.getElementById('updatePriceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPrice = parseFloat(document.getElementById('new-price').value);
    if (isNaN(newPrice) || newPrice <= 0) {
        alert('Preco invalido');
        return;
    }
    
    const newPriceCents = Math.round(newPrice * 100);
    
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

// Front Display forms — async save per system
document.querySelectorAll('.fd-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const sys = this.dataset.system;
        const btn = this.querySelector('button[type="submit"]');
        const resultEl = document.getElementById('result_' + sys);
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
        resultEl.classList.remove('show');
        
        const formData = new FormData(this);
        // Ensure unchecked checkboxes send '0'
        if (!formData.has('is_highlighted')) formData.set('is_highlighted', '0');
        if (!formData.has('is_visible')) formData.set('is_visible', '0');
        
        fetch('/secure/adm/plans/front-display/save', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
            resultEl.classList.add('show');
            if (data.success) {
                resultEl.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Salvo!';
                // Update the badge status
                const section = this.closest('.fd-section');
                const badge = section.querySelector('.fd-status');
                if (badge) { badge.className = 'fd-status badge bg-success'; badge.textContent = 'Configurado'; }
            } else {
                resultEl.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>' + (data.error || 'Erro');
            }
            setTimeout(() => resultEl.classList.remove('show'), 4000);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
            resultEl.classList.add('show');
            resultEl.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Erro de rede';
            setTimeout(() => resultEl.classList.remove('show'), 4000);
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();

include __DIR__ . '/../../layouts/app.php';
