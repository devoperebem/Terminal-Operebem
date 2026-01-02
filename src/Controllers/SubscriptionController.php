<?php
/**
 * SubscriptionController - Páginas de assinatura para o usuário
 * 
 * Gerencia as páginas de planos, checkout, sucesso e gerenciamento de assinatura.
 */

namespace App\Controllers;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\StripeService;
use App\Services\SubscriptionService;

class SubscriptionController extends BaseController
{
    private AuthService $authService;
    private StripeService $stripeService;
    private SubscriptionService $subscriptionService;
    
    public function __construct()
    {
        try {
            parent::__construct();
            $this->authService = new AuthService();
            $this->stripeService = new StripeService();
            $this->subscriptionService = new SubscriptionService();
        } catch (\Throwable $e) {
            error_log('[SubscriptionController::__construct] Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
    
    /**
     * Página de planos
     * GET /subscription/plans
     */
    public function plans(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                $_SESSION['next_url'] = '/subscription/plans';
                $this->redirect('/?modal=login');
                return;
            }
            
            $plans = $this->subscriptionService->getActivePlans();
            $currentSubscription = $this->subscriptionService->getActiveSubscription($user['id']);
            $effectiveTier = $this->subscriptionService->getEffectiveTier($user);
            
            $this->view('subscription/plans', [
                'title' => 'Planos Operebem',
                'plans' => $plans,
                'currentSubscription' => $currentSubscription,
                'effectiveTier' => $effectiveTier,
                'stripePublicKey' => $this->stripeService->getPublicKey(),
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            // Log de erro para debug
            error_log('[SubscriptionController::plans] Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
    
    /**
     * Iniciar checkout
     * POST /subscription/checkout
     */
    public function checkout(): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->jsonResponse(['success' => false, 'error' => 'Não autenticado'], 401);
        }
        
        $planSlug = trim($_POST['plan'] ?? '');
        $couponCode = trim($_POST['coupon'] ?? '');
        
        if (empty($planSlug)) {
            $this->jsonResponse(['success' => false, 'error' => 'Plano não especificado'], 400);
        }
        
        $result = $this->subscriptionService->createCheckout(
            $user['id'],
            $planSlug,
            $couponCode ?: null
        );
        
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'checkout_url' => $result['checkout_url'],
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], 400);
        }
    }
    
    /**
     * Página de sucesso após checkout
     * GET /subscription/success
     */
    public function success(): void
    {
        $user = $this->authService->getCurrentUser();
        $sessionId = $_GET['session_id'] ?? '';
        
        $subscription = null;
        $plan = null;
        
        if ($user) {
            // Buscar assinatura atualizada
            $subscription = $this->subscriptionService->getActiveSubscription($user['id']);
            
            if ($subscription) {
                $plan = $this->subscriptionService->getPlanBySlug($subscription['plan_slug']);
            }
        }
        
        $this->view('subscription/success', [
            'title' => 'Assinatura Ativada!',
            'subscription' => $subscription,
            'plan' => $plan,
            'user' => $user,
        ]);
    }
    
    /**
     * Página de checkout cancelado
     * GET /subscription/canceled
     */
    public function canceled(): void
    {
        $user = $this->authService->getCurrentUser();
        
        $this->view('subscription/canceled', [
            'title' => 'Checkout Cancelado',
            'user' => $user,
        ]);
    }
    
    /**
     * Página de gerenciamento de assinatura
     * GET /subscription/manage
     */
    public function manage(): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $_SESSION['next_url'] = '/subscription/manage';
            $this->redirect('/?modal=login');
        }
        
        $subscription = $this->subscriptionService->getActiveSubscription($user['id']);
        $plan = null;
        $payments = [];
        
        if ($subscription) {
            $plan = $this->subscriptionService->getPlanBySlug($subscription['plan_slug']);
            $payments = $this->subscriptionService->getPaymentHistory($user['id'], 10);
        }
        
        $effectiveTier = $this->subscriptionService->getEffectiveTier($user);
        
        $this->view('subscription/manage', [
            'title' => 'Minha Assinatura',
            'subscription' => $subscription,
            'plan' => $plan,
            'payments' => $payments,
            'effectiveTier' => $effectiveTier,
            'user' => $user,
        ]);
    }
    
    /**
     * Cancelar assinatura
     * POST /subscription/cancel
     */
    public function cancel(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
        }
        
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->jsonResponse(['success' => false, 'error' => 'Não autenticado'], 401);
        }
        
        $immediate = (bool)($_POST['immediate'] ?? false);
        
        $result = $this->subscriptionService->cancelSubscription($user['id'], $immediate);
        
        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], 400);
        }
    }
    
    /**
     * Validar cupom (AJAX)
     * POST /subscription/validate-coupon
     */
    public function validateCoupon(): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->jsonResponse(['valid' => false, 'error' => 'Não autenticado'], 401);
        }
        
        $code = trim($_POST['code'] ?? '');
        $planSlug = trim($_POST['plan'] ?? '');
        
        if (empty($code) || empty($planSlug)) {
            $this->jsonResponse(['valid' => false, 'error' => 'Dados incompletos'], 400);
        }
        
        $result = $this->subscriptionService->validateCoupon($code, $planSlug, $user['id']);
        
        if ($result['valid']) {
            $plan = $this->subscriptionService->getPlanBySlug($planSlug);
            $originalPrice = $plan['price_cents'] ?? 0;
            
            $discountAmount = 0;
            if ($result['discount_type'] === 'percent') {
                $discountAmount = (int)($originalPrice * $result['discount_value'] / 100);
            } else {
                $discountAmount = $result['discount_value'];
            }
            
            $finalPrice = max(0, $originalPrice - $discountAmount);
            
            $this->jsonResponse([
                'valid' => true,
                'discount_type' => $result['discount_type'],
                'discount_value' => $result['discount_value'],
                'discount_amount_cents' => $discountAmount,
                'original_price_cents' => $originalPrice,
                'final_price_cents' => $finalPrice,
            ]);
        } else {
            $this->jsonResponse(['valid' => false, 'error' => $result['error']], 400);
        }
    }
    
    // =========================================================================
    // HELPERS
    // =========================================================================
    
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
