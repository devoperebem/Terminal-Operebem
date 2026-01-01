<?php
/**
 * Página de Checkout Cancelado
 */

ob_start();
?>

<style>
.canceled-container {
    max-width: 500px;
    margin: 80px auto;
    padding: 0 20px;
    text-align: center;
}

.canceled-icon {
    width: 100px;
    height: 100px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
}

.canceled-icon i {
    font-size: 2.5rem;
    color: #64748b;
}

.canceled-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary, #1e293b);
    margin-bottom: 15px;
}

.canceled-message {
    font-size: 1.1rem;
    color: var(--text-secondary, #64748b);
    margin-bottom: 30px;
}

.canceled-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.canceled-actions a {
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-retry {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: #fff;
}

.btn-retry:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-home {
    background: transparent;
    border: 2px solid var(--border-color, #e2e8f0);
    color: var(--text-primary, #1e293b);
}

.btn-home:hover {
    background: var(--border-color, #e2e8f0);
}
</style>

<div class="canceled-container">
    <div class="canceled-icon">
        <i class="fas fa-times"></i>
    </div>
    
    <h1 class="canceled-title">Checkout Cancelado</h1>
    <p class="canceled-message">
        Você cancelou o processo de checkout. Não se preocupe, nenhuma cobrança foi feita.
    </p>
    
    <div class="canceled-actions">
        <a href="/subscription/plans" class="btn-retry">
            <i class="fas fa-redo me-2"></i>Tentar Novamente
        </a>
        <a href="/app/dashboard" class="btn-home">
            <i class="fas fa-home me-2"></i>Ir para Dashboard
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
