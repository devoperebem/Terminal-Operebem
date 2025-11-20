<?php

namespace App\Controllers;

use App\Services\AdminAuthService;
use App\Core\Application;

class AdminController extends BaseController
{
    private AdminAuthService $adminAuth;

    public function __construct()
    {
        parent::__construct();
        $this->adminAuth = new AdminAuthService();
    }

    public function loginForm(): void
    {
        // garante tabela e seed do admin padrão via ENV
        $this->adminAuth->ensureTableAndSeed();
        $this->view('admin/login', [
            'title' => 'Admin - Login',
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            try { Application::getInstance()->logger()->warning('Admin CSRF invalid'); } catch (\Throwable $t) {}
            $this->redirect('/admin/login?error=csrf');
        }
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        try { Application::getInstance()->logger()->info('Admin login attempt (legacy)', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']); } catch (\Throwable $t) {}
        $res = $this->adminAuth->login($username, $password);
        if ($res['success'] ?? false) {
            try { Application::getInstance()->logger()->info('Admin login success (legacy)'); } catch (\Throwable $t) {}
            $this->redirect('/admin/support');
        } else {
            try { Application::getInstance()->logger()->warning('Admin login failed (legacy)', ['username' => $username]); } catch (\Throwable $t) {}
            $this->redirect('/admin/login?error=auth');
        }
    }

    public function logout(): void
    {
        if (!$this->validateCsrf()) {
            try { Application::getInstance()->logger()->warning('Admin logout CSRF invalid'); } catch (\Throwable $t) {}
            $this->redirect('/admin/support');
        }
        $this->adminAuth->logout();
        try { Application::getInstance()->logger()->info('Admin logout'); } catch (\Throwable $t) {}
        $this->redirect('/admin/login');
    }

    public function users(): void
    {
        $this->view('admin/users', [
            'title' => 'Admin - Usuários',
            'admins' => $this->adminAuth->listAdmins(true),
            'ok' => $_GET['ok'] ?? null,
            'err' => $_GET['err'] ?? null,
        ]);
    }

    public function createUser(): void
    {
        if (!$this->validateCsrf()) {
            try { Application::getInstance()->logger()->warning('Admin createUser CSRF invalid'); } catch (\Throwable $t) {}
            $this->redirect('/admin/users?err=csrf');
        }
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $res = $this->adminAuth->createAdmin($username, $password);
        if ($res['success'] ?? false) {
            try { Application::getInstance()->logger()->info('Admin created', ['username' => $username]); } catch (\Throwable $t) {}
            $this->redirect('/admin/users?ok=1');
        } else {
            $msg = urlencode($res['message'] ?? 'erro');
            try { Application::getInstance()->logger()->warning('Admin create failed', ['username' => $username, 'reason' => $res['message'] ?? '']); } catch (\Throwable $t) {}
            $this->redirect('/admin/users?err=' . $msg);
        }
    }
}
