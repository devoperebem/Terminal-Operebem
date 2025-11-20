<?php $title = "Criar Conta - Terminal Operebem"; ?>

<div class="card shadow-lg border-0">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h1 class="h3 text-primary fw-bold">
                <i class="fas fa-user-plus me-2"></i>Criar Nova Conta
            </h1>
            <p class="text-muted">Junte-se ao Terminal Operebem</p>
        </div>

        <div class="text-center">
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="fas fa-rocket me-2"></i>Começar Cadastro
            </button>
        </div>

        <hr class="my-4">

        <div class="text-center">
            <p class="mb-2">Já tem uma conta?</p>
            <a href="/login" class="btn btn-outline-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/register-modal.php'; ?>

<?php 
$content = ob_get_clean();
$scripts = '<script src="/assets/js/register-6steps.js"></script>';
include __DIR__ . '/../layouts/app.php';
?>
