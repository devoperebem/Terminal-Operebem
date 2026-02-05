<?php
/**
 * SubscriptionPlansAdminController - Gerenciamento de planos de assinatura no admin
 * 
 * Permite visualizar, editar preços, ativar/desativar planos e gerenciar promoções.
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Services\SubscriptionPlanService;
use App\Services\AdminAuthService;

class SubscriptionPlansAdminController extends BaseController
{
    private ?SubscriptionPlanService $planService = null;
    private AdminAuthService $adminAuthService;
    
    public function __construct()
    {
        parent::__construct();
        $this->adminAuthService = new AdminAuthService();
    }
    
    /**
     * Renderiza view admin com footerVariant correto
     */
    private function adminView(string $viewName, array $data = []): void
    {
        $data['footerVariant'] = 'admin-auth';
        $this->view($viewName, $data);
    }
    
    /**
     * Retorna o SubscriptionPlanService (lazy loading)
     */
    private function getPlanService(): SubscriptionPlanService
    {
        if ($this->planService === null) {
            $this->planService = new SubscriptionPlanService();
        }
        return $this->planService;
    }
    
    /**
     * Lista todos os planos com estatísticas
     * GET /secure/adm/plans
     */
    public function index(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Buscar todos os planos com estatísticas
        $plans = $this->getPlanService()->getAllPlansWithStats();
        
        // Estatísticas gerais
        $stats = $this->getPlanService()->getGeneralStats();
        
        $this->adminView('admin_secure/subscription_plans/index', compact('admin', 'plans', 'stats'));
    }
    
    /**
     * Formulário de edição de um plano
     * GET /secure/adm/plans/edit/{id}
     */
    public function edit(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        $planId = (int)($_GET['id'] ?? 0);
        if (!$planId) {
            header('Location: /secure/adm/plans?error=ID+invalido');
            exit;
        }
        
        $plan = $this->getPlanService()->getPlanById($planId);
        if (!$plan) {
            header('Location: /secure/adm/plans?error=Plano+nao+encontrado');
            exit;
        }
        
        $this->adminView('admin_secure/subscription_plans/edit', compact('admin', 'plan'));
    }
    
    /**
     * Atualiza o preço de um plano
     * POST /secure/adm/plans/update-price
     */
    public function updatePrice(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
        
        $planId = (int)($_POST['plan_id'] ?? 0);
        $newPriceCents = (int)($_POST['price_cents'] ?? 0);
        
        if (!$planId || $newPriceCents <= 0) {
            echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
            exit;
        }
        
        // Confirmar ação (deve ser confirmada pelo front)
        if (empty($_POST['confirmed'])) {
            echo json_encode([
                'success' => false, 
                'error' => 'Alteração de preço requer confirmação',
                'requires_confirmation' => true
            ]);
            exit;
        }
        
        $result = $this->getPlanService()->updatePrice($planId, $newPriceCents);
        
        // Log da ação
        if ($result['success']) {
            error_log("[ADMIN] Plano #{$planId} - Preço alterado de {$result['old_price_cents']} para {$result['new_price_cents']} centavos por {$admin['email']}");
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Aplica desconto temporário a um plano
     * POST /secure/adm/plans/apply-discount
     */
    public function applyDiscount(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
        
        $planId = (int)($_POST['plan_id'] ?? 0);
        $discountPercentage = (int)($_POST['discount_percentage'] ?? 0);
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $label = $_POST['label'] ?? null;
        
        if (!$planId) {
            echo json_encode(['success' => false, 'error' => 'ID do plano inválido']);
            exit;
        }
        
        $result = $this->getPlanService()->applyDiscount(
            $planId,
            $discountPercentage,
            $startDate,
            $endDate,
            $label
        );
        
        // Log da ação
        if ($result['success']) {
            error_log("[ADMIN] Plano #{$planId} - Desconto de {$discountPercentage}% aplicado por {$admin['email']}");
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Remove desconto de um plano
     * POST /secure/adm/plans/remove-discount
     */
    public function removeDiscount(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
        
        $planId = (int)($_POST['plan_id'] ?? 0);
        
        if (!$planId) {
            echo json_encode(['success' => false, 'error' => 'ID do plano inválido']);
            exit;
        }
        
        $result = $this->getPlanService()->removeDiscount($planId);
        
        // Log da ação
        if ($result['success']) {
            error_log("[ADMIN] Plano #{$planId} - Desconto removido por {$admin['email']}");
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Ativa ou desativa um plano
     * POST /secure/adm/plans/toggle-active
     */
    public function toggleActive(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
        
        $planId = (int)($_POST['plan_id'] ?? 0);
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'true';
        
        if (!$planId) {
            echo json_encode(['success' => false, 'error' => 'ID do plano inválido']);
            exit;
        }
        
        $result = $this->getPlanService()->toggleActive($planId, $isActive);
        
        // Log da ação
        if ($result['success']) {
            $status = $isActive ? 'ativado' : 'desativado';
            error_log("[ADMIN] Plano #{$planId} - Status alterado para {$status} por {$admin['email']}");
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
