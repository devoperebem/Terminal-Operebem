<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;
use App\Services\PortalMaterialsConfigService;
use App\Services\PortalPricingConfigService;
use App\Services\PortalSyncAuditService;
use App\Services\PortalSyncService;

class AdminAlunoSyncAuditController extends BaseController
{
    private PortalSyncAuditService $auditService;
    private PortalSyncService $syncService;
    private PortalPricingConfigService $pricingService;
    private PortalMaterialsConfigService $materialsService;

    public function __construct()
    {
        parent::__construct();
        $this->auditService = new PortalSyncAuditService();
        $this->syncService = new PortalSyncService();
        $this->pricingService = new PortalPricingConfigService();
        $this->materialsService = new PortalMaterialsConfigService();
    }

    public function index(): void
    {
        $entries = $this->auditService->latest(150);
        $courses = $this->fetchCourses();

        $this->view('admin_secure/aluno_sync_audit', [
            'title' => 'Auditoria de Sync do Portal',
            'entries' => $entries,
            'courses' => $courses,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function retryPricing(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/sync-audit');
            return;
        }

        try {
            $plans = $this->pricingService->buildSyncPlans();
            $user = $this->authService->getCurrentUser();
            $email = (string)($user['email'] ?? 'admin@local');
            $result = $this->syncService->syncPricing($plans, [
                'synced_by' => $email,
                'source' => 'manual-retry',
                'synced_at' => date('c'),
            ]);

            if (!($result['success'] ?? false)) {
                $_SESSION['flash_error'] = 'Retry de pricing falhou (status ' . (int)($result['status'] ?? 0) . ').';
            } else {
                $_SESSION['flash_success'] = 'Retry de pricing executado com sucesso.';
            }
        } catch (\Throwable $e) {
            $this->logError('retryPricing', $e);
            $_SESSION['flash_error'] = 'Falha ao executar retry de pricing.';
        }

        $this->redirect('/secure/adm/aluno/sync-audit');
    }

    public function retryMaterialsCourse(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/sync-audit');
            return;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($courseId <= 0) {
            $_SESSION['flash_error'] = 'Selecione um curso valido para retry de materiais.';
            $this->redirect('/secure/adm/aluno/sync-audit');
            return;
        }

        try {
            $materials = $this->materialsService->buildSyncMaterialsForCourse($courseId);
            $user = $this->authService->getCurrentUser();
            $email = (string)($user['email'] ?? 'admin@local');
            $result = $this->syncService->syncMaterials($materials, [
                'synced_by' => $email,
                'source' => 'manual-retry',
                'course_id' => $courseId,
                'synced_at' => date('c'),
            ]);

            if (!($result['success'] ?? false)) {
                $_SESSION['flash_error'] = 'Retry de materiais falhou (status ' . (int)($result['status'] ?? 0) . ').';
            } else {
                $_SESSION['flash_success'] = 'Retry de materiais executado com sucesso para curso #' . $courseId . '.';
            }
        } catch (\Throwable $e) {
            $this->logError('retryMaterialsCourse', $e);
            $_SESSION['flash_error'] = 'Falha ao executar retry de materiais.';
        }

        $this->redirect('/secure/adm/aluno/sync-audit');
    }

    public function checkPortal(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/sync-audit');
            return;
        }

        try {
            $result = $this->syncService->checkPricingPublicEndpoint();
            if (!($result['success'] ?? false)) {
                $_SESSION['flash_error'] = 'Check no endpoint publico do Portal falhou (status ' . (int)($result['status'] ?? 0) . ').';
            } else {
                $_SESSION['flash_success'] = 'Check do endpoint publico do Portal executado com sucesso.';
            }
        } catch (\Throwable $e) {
            $this->logError('checkPortal', $e);
            $_SESSION['flash_error'] = 'Falha ao executar check do Portal.';
        }

        $this->redirect('/secure/adm/aluno/sync-audit');
    }

    private function fetchCourses(): array
    {
        try {
            return Database::fetchAll('SELECT id, title FROM courses ORDER BY COALESCE(position, id) ASC, id ASC', [], 'aluno');
        } catch (\Throwable $__) {
            return [];
        }
    }

    private function logError(string $context, \Throwable $e): void
    {
        try {
            Application::getInstance()->logger()->error('Aluno sync audit ' . $context . ' fail: ' . $e->getMessage());
        } catch (\Throwable $__) {
        }
    }
}
