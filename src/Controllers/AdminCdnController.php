<?php

namespace App\Controllers;

use App\Services\CdnService;

class AdminCdnController extends BaseController
{
    private CdnService $cdnService;

    public function __construct()
    {
        parent::__construct();
        $this->cdnService = new CdnService();
    }

    public function index(): void
    {
        $status = $this->cdnService->getStatus();
        $filesResult = $this->cdnService->listFiles();
        
        $categories = [];
        if (!empty($filesResult['success']) && isset($filesResult['data']['categories'])) {
            $categories = $filesResult['data']['categories'];
        }

        $this->view('admin_secure/cdn_index', [
            'title' => 'CDN Operebem - Gestão de Arquivos',
            'footerVariant' => 'admin-auth',
            'cdnConfigured' => $this->cdnService->isConfigured(),
            'cdnStatus' => $status,
            'categories' => $categories,
            'cdnBaseUrl' => $this->cdnService->getBaseUrl(),
        ]);
    }

    public function upload(): void
    {
        if (!$this->validateCsrf()) {
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        $category = trim($_POST['category'] ?? '');
        if (empty($category)) {
            $_SESSION['flash_error'] = 'Categoria é obrigatória.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Erro no upload do arquivo.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        $file = $_FILES['file'];
        $result = $this->cdnService->upload($file['tmp_name'], $category, $file['name']);

        if ($result['success']) {
            $url = $result['data']['url'] ?? $result['data']['token_url'] ?? '';
            $_SESSION['flash_success'] = 'Arquivo enviado com sucesso! URL: ' . $url;
        } else {
            $_SESSION['flash_error'] = 'Falha no upload: ' . ($result['error'] ?? 'Erro desconhecido');
        }

        $this->redirect('/secure/adm/cdn');
    }

    public function delete(): void
    {
        if (!$this->validateCsrf()) {
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        $category = trim($_POST['category'] ?? '');
        $filename = trim($_POST['filename'] ?? '');

        if (empty($category) || empty($filename)) {
            $_SESSION['flash_error'] = 'Categoria e nome do arquivo são obrigatórios.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        $result = $this->cdnService->delete($category, $filename);

        if ($result['success']) {
            $_SESSION['flash_success'] = 'Arquivo deletado com sucesso.';
        } else {
            $_SESSION['flash_error'] = 'Falha ao deletar: ' . ($result['error'] ?? 'Erro desconhecido');
        }

        $this->redirect('/secure/adm/cdn');
    }

    public function cleanup(): void
    {
        if (!$this->validateCsrf()) {
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $this->redirect('/secure/adm/cdn');
            return;
        }

        $result = $this->cdnService->cleanup();

        if ($result['success']) {
            $tokensDeleted = $result['data']['tokens_deleted'] ?? 0;
            $rateLimitsDeleted = $result['data']['rate_limits_deleted'] ?? 0;
            $_SESSION['flash_success'] = "Limpeza concluída. Tokens: {$tokensDeleted}, Rate limits: {$rateLimitsDeleted}";
        } else {
            $_SESSION['flash_error'] = 'Falha na limpeza: ' . ($result['error'] ?? 'Erro desconhecido');
        }

        $this->redirect('/secure/adm/cdn');
    }

    public function generateToken(): void
    {
        header('Content-Type: application/json');

        if (!$this->validateCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $filePath = trim($_POST['file_path'] ?? '');
        $expiryMinutes = (int)($_POST['expiry_minutes'] ?? 30);
        $reusable = isset($_POST['reusable']) && $_POST['reusable'] === '1';
        $maxUses = (int)($_POST['max_uses'] ?? 1);

        if (empty($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Caminho do arquivo é obrigatório']);
            return;
        }

        $result = $this->cdnService->generateToken($filePath, $expiryMinutes, $reusable, $maxUses);
        echo json_encode($result);
    }
}
