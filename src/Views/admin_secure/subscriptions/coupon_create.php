<?php
/**
 * Admin - Criar Cupom
 */

$pageTitle = 'Criar Cupom | Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';

$errorMessages = [
    'csrf' => 'Token de segurança inválido. Tente novamente.',
    'invalid_code' => 'Código inválido. Deve ter pelo menos 3 caracteres.',
    'invalid_value' => 'Valor de desconto inválido.',
    'percent_over_100' => 'Percentual não pode ser maior que 100%.',
    'code_exists' => 'Este código já existe.',
    'exception' => 'Ocorreu um erro inesperado. Tente novamente.',
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
        .discount-type-card { cursor: pointer; transition: all 0.2s; }
        .discount-type-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .discount-type-card.selected { border-color: #0d6efd !important; background: #f0f7ff; }
        .discount-type-card input { display: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/secure/adm/index">Admin Panel</a>
            <div class="d-flex">
                <a href="/secure/adm/coupons" class="btn btn-outline-light btn-sm">
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
                        <h5 class="mb-0"><i class="bi bi-ticket-perforated"></i> Criar Novo Cupom</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= $errorMessages[$error] ?? 'Erro desconhecido.' ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/secure/adm/coupons/create">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <!-- Código -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Código do Cupom</label>
                                <input type="text" name="code" class="form-control text-uppercase" 
                                       placeholder="Ex: NATAL2026" required minlength="3" maxlength="30"
                                       pattern="[A-Za-z0-9_-]+" title="Somente letras, números, _ e -">
                                <div class="form-text">Somente letras, números, _ e - (será convertido para maiúsculas)</div>
                            </div>

                            <!-- Tipo de Desconto -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tipo de Desconto</label>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="card discount-type-card h-100 text-center p-3 selected" id="type_percent_card">
                                            <input type="radio" name="discount_type" value="percent" id="type_percent" checked>
                                            <div>
                                                <i class="bi bi-percent fs-1 text-primary"></i>
                                                <p class="mb-0 mt-2">Percentual</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <label class="card discount-type-card h-100 text-center p-3" id="type_fixed_card">
                                            <input type="radio" name="discount_type" value="fixed" id="type_fixed">
                                            <div>
                                                <i class="bi bi-currency-dollar fs-1 text-success"></i>
                                                <p class="mb-0 mt-2">Valor Fixo</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Valor do Desconto -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Valor do Desconto</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discount_prefix">%</span>
                                    <input type="number" name="discount_value" class="form-control" min="1" max="100" required id="discount_value">
                                </div>
                                <div class="form-text" id="discount_help">
                                    Para desconto percentual, informe o percentual (ex: 10 para 10%)
                                </div>
                            </div>

                            <!-- Limite de Usos -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Limite de Usos (opcional)</label>
                                <input type="number" name="max_redemptions" class="form-control" min="1" placeholder="Sem limite">
                                <div class="form-text">Deixe em branco para uso ilimitado</div>
                            </div>

                            <!-- Validade -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Válido até (opcional)</label>
                                <input type="datetime-local" name="valid_until" class="form-control">
                                <div class="form-text">Deixe em branco para sem data de expiração</div>
                            </div>

                            <!-- Notas -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Notas (opcional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Observações internas sobre o cupom"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Criar Cupom
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
        const prefix = document.getElementById('discount_prefix');
        const valueInput = document.getElementById('discount_value');
        const helpText = document.getElementById('discount_help');

        // Discount type selection
        document.querySelectorAll('.discount-type-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.discount-type-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                const isPercent = this.querySelector('input').value === 'percent';
                if (isPercent) {
                    prefix.textContent = '%';
                    valueInput.max = 100;
                    valueInput.placeholder = 'Ex: 10';
                    helpText.textContent = 'Para desconto percentual, informe o percentual (ex: 10 para 10%)';
                } else {
                    prefix.textContent = 'R$';
                    valueInput.max = 99999;
                    valueInput.placeholder = 'Ex: 5000 (para R$ 50,00)';
                    helpText.textContent = 'Para desconto fixo, informe o valor em centavos (ex: 5000 para R$ 50,00)';
                }
            });
        });

        // Auto uppercase code
        document.querySelector('input[name="code"]').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
