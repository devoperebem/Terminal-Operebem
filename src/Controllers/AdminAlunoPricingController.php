<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\PortalPricingConfigService;
use App\Services\PortalSyncService;

class AdminAlunoPricingController extends BaseController
{
    private PortalPricingConfigService $pricingService;
    private PortalSyncService $syncService;

    public function __construct()
    {
        parent::__construct();
        $this->pricingService = new PortalPricingConfigService();
        $this->syncService = new PortalSyncService();
    }

    public function index(): void
    {
        $this->view('admin_secure/aluno_pricing_index', [
            'title' => 'Pricing do Portal do Aluno',
            'plans' => $this->pricingService->all(),
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function create(): void
    {
        $this->view('admin_secure/aluno_pricing_form', [
            'title' => 'Novo Plano do Portal',
            'plan' => null,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function edit(): void
    {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/pricing');
        }

        $plan = $this->pricingService->find($id);
        if ($plan === null) {
            $_SESSION['flash_error'] = 'Plano nao encontrado.';
            $this->redirect('/secure/adm/aluno/pricing');
        }

        $this->view('admin_secure/aluno_pricing_form', [
            'title' => 'Editar Plano do Portal',
            'plan' => $plan,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        $data = $this->collectPlanData();
        if ($data['name'] === '' || $data['price_display'] === '' || $data['cta_url'] === '') {
            $_SESSION['flash_error'] = 'Preencha nome, preco e URL do CTA.';
            $this->redirect('/secure/adm/aluno/pricing/create');
            return;
        }

        try {
            $this->pricingService->create($data);
            $this->runSync('Plano criado e sincronizado.');
        } catch (\Throwable $e) {
            $this->logError('pricing store', $e);
            $_SESSION['flash_error'] = 'Falha ao salvar plano. Verifique os dados e tente novamente.';
        }

        $this->redirect('/secure/adm/aluno/pricing');
    }

    public function update(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        $data = $this->collectPlanData();
        if ($data['name'] === '' || $data['price_display'] === '' || $data['cta_url'] === '') {
            $_SESSION['flash_error'] = 'Preencha nome, preco e URL do CTA.';
            $this->redirect('/secure/adm/aluno/pricing/edit?id=' . urlencode($id));
            return;
        }

        try {
            $updated = $this->pricingService->update($id, $data);
            if ($updated === null) {
                $_SESSION['flash_error'] = 'Plano nao encontrado para atualizacao.';
            } else {
                $this->runSync('Plano atualizado e sincronizado.');
            }
        } catch (\Throwable $e) {
            $this->logError('pricing update', $e);
            $_SESSION['flash_error'] = 'Falha ao atualizar plano. Tente novamente.';
        }

        $this->redirect('/secure/adm/aluno/pricing');
    }

    public function delete(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        try {
            $ok = $this->pricingService->delete($id);
            if (!$ok) {
                $_SESSION['flash_error'] = 'Plano nao encontrado para exclusao.';
            } else {
                $this->runSync('Plano removido e sincronizado.');
            }
        } catch (\Throwable $e) {
            $this->logError('pricing delete', $e);
            $_SESSION['flash_error'] = 'Falha ao excluir plano. Tente novamente.';
        }

        $this->redirect('/secure/adm/aluno/pricing');
    }

    public function sync(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/pricing');
            return;
        }

        $this->runSync('Sincronizacao executada com sucesso.');
        $this->redirect('/secure/adm/aluno/pricing');
    }

    private function collectPlanData(): array
    {
        return [
            'name' => trim((string)($_POST['name'] ?? '')),
            'slug' => trim((string)($_POST['slug'] ?? '')),
            'price_display' => trim((string)($_POST['price_display'] ?? '')),
            'price_subtitle' => trim((string)($_POST['price_subtitle'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'features' => (string)($_POST['features'] ?? ''),
            'cta_label' => trim((string)($_POST['cta_label'] ?? 'Assinar')),
            'cta_url' => trim((string)($_POST['cta_url'] ?? '')),
            'is_highlighted' => isset($_POST['is_highlighted']) && in_array((string)$_POST['is_highlighted'], ['1', 'on', 'true'], true),
            'badge_text' => trim((string)($_POST['badge_text'] ?? '')),
            'position' => (int)($_POST['position'] ?? 1),
        ];
    }

    private function runSync(string $successMessage): void
    {
        $plans = $this->pricingService->buildSyncPlans();
        $user = $this->authService->getCurrentUser();
        $email = (string)($user['email'] ?? 'admin@local');

        $result = $this->syncService->syncPricing($plans, [
            'synced_by' => $email,
            'source' => 'terminal-admin',
            'synced_at' => date('c'),
        ]);

        if (!($result['success'] ?? false)) {
            $_SESSION['flash_error'] = 'Plano salvo localmente, mas falhou no sync com Portal (status ' . (int)($result['status'] ?? 0) . ').';
            return;
        }

        $_SESSION['flash_success'] = $successMessage;
    }

    private function logError(string $context, \Throwable $e): void
    {
        try {
            Application::getInstance()->logger()->error('Aluno pricing ' . $context . ' fail: ' . $e->getMessage());
        } catch (\Throwable $__) {
        }
    }
}
