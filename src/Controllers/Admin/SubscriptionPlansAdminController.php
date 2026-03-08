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
        
        // Load front display configs for all systems
        $frontDisplay = [];
        try {
            $rows = Database::fetchAll(
                'SELECT * FROM plan_front_display WHERE plan_id = ?',
                [$planId]
            );
            foreach ($rows as $row) {
                $row['features'] = json_decode($row['features'] ?? '[]', true) ?: [];
                $frontDisplay[$row['system_key']] = $row;
            }
        } catch (\Throwable $t) { /* table may not exist yet */ }
        
        $this->adminView('admin_secure/subscription_plans/edit', compact('admin', 'plan', 'frontDisplay'));
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
    
    /**
     * Salva configuração de exibição no front para um plano+sistema
     * POST /secure/adm/plans/front-display/save
     */
    public function saveFrontDisplay(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
        
        $planId    = (int)($_POST['plan_id'] ?? 0);
        $systemKey = trim((string)($_POST['system_key'] ?? ''));
        
        $validSystems = ['terminal', 'portal_aluno', 'diario_trades'];
        if (!$planId || !in_array($systemKey, $validSystems, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Dados inválidos (plan_id ou system_key)']);
            exit;
        }
        
        // Ensure table exists
        try {
            Database::execute("CREATE TABLE IF NOT EXISTS plan_front_display (
                id SERIAL PRIMARY KEY,
                plan_id INTEGER NOT NULL REFERENCES subscription_plans(id) ON DELETE CASCADE,
                system_key VARCHAR(50) NOT NULL,
                display_name VARCHAR(150),
                price_display VARCHAR(100),
                price_subtitle VARCHAR(100),
                description TEXT,
                features JSONB DEFAULT '[]',
                cta_label VARCHAR(100),
                cta_url VARCHAR(500),
                is_highlighted BOOLEAN DEFAULT FALSE,
                badge_text VARCHAR(50),
                is_visible BOOLEAN DEFAULT TRUE,
                display_order INTEGER DEFAULT 0,
                metadata JSONB DEFAULT '{}',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(plan_id, system_key)
            )");
        } catch (\Throwable $t) { /* table already exists */ }
        
        $displayName   = trim((string)($_POST['display_name'] ?? ''));
        $priceDisplay  = trim((string)($_POST['price_display'] ?? ''));
        $priceSubtitle = trim((string)($_POST['price_subtitle'] ?? ''));
        $description   = trim((string)($_POST['description'] ?? ''));
        $ctaLabel      = trim((string)($_POST['cta_label'] ?? ''));
        $ctaUrl        = trim((string)($_POST['cta_url'] ?? ''));
        $isHighlighted = !empty($_POST['is_highlighted']);
        $badgeText     = trim((string)($_POST['badge_text'] ?? ''));
        $isVisible     = !isset($_POST['is_visible']) || !empty($_POST['is_visible']);
        $displayOrder  = (int)($_POST['display_order'] ?? 0);
        
        // Parse features (one per line)
        $featuresRaw = trim((string)($_POST['features'] ?? ''));
        $features = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))));
        $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE);
        
        try {
            // Upsert (INSERT ... ON CONFLICT UPDATE)
            Database::execute(
                "INSERT INTO plan_front_display 
                    (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?::jsonb, ?, ?, ?, ?, ?, ?, NOW())
                 ON CONFLICT (plan_id, system_key) DO UPDATE SET
                    display_name = EXCLUDED.display_name,
                    price_display = EXCLUDED.price_display,
                    price_subtitle = EXCLUDED.price_subtitle,
                    description = EXCLUDED.description,
                    features = EXCLUDED.features,
                    cta_label = EXCLUDED.cta_label,
                    cta_url = EXCLUDED.cta_url,
                    is_highlighted = EXCLUDED.is_highlighted,
                    badge_text = EXCLUDED.badge_text,
                    is_visible = EXCLUDED.is_visible,
                    display_order = EXCLUDED.display_order,
                    updated_at = NOW()",
                [$planId, $systemKey, $displayName, $priceDisplay, $priceSubtitle, $description, $featuresJson, $ctaLabel, $ctaUrl, $isHighlighted ? 'true' : 'false', $badgeText, $isVisible ? 'true' : 'false', $displayOrder]
            );
            
            error_log("[ADMIN] Plan #{$planId} front-display for '{$systemKey}' saved by {$admin['email']}");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
        } catch (\Throwable $e) {
            error_log("[ADMIN] Error saving front-display: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
    }
}
