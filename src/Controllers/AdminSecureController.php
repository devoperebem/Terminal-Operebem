<?php

namespace App\Controllers;

use App\Services\AdminAuthService;
use App\Services\JwtService;
use App\Core\Database;
use App\Core\Application;
use App\Services\EmailService;
use App\Services\SystemMaintenanceService;
use App\Services\RecaptchaService;

class AdminSecureController extends BaseController
{
    private AdminAuthService $adminAuth;

    public function __construct()
    {
        parent::__construct();
        $this->adminAuth = new AdminAuthService();
    }

    public function searchUsers(): void
    {
        // Admin-only; middleware enforced in routes
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string)($_GET['q'] ?? ''));
        $limit = 20;
        if ($q === '') { echo json_encode(['success'=>true,'data'=>[]]); return; }
        $like = '%' . $q . '%';
        try {
            $rows = Database::fetchAll('SELECT id, name, email FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ' . (int)$limit, [$like, $like]);
            echo json_encode(['success'=>true,'data'=>$rows]);
        } catch (\Throwable $t) {
            http_response_code(500);
            echo json_encode(['success'=>false,'message'=>'Erro ao buscar usuários']);
        }
    }

    private function audit(string $action, array $meta = []): void
    {
        try {
            $root = dirname(__DIR__, 2); // novo_public_html
            $logDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'admin';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            $file = $logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
            $entry = [
                'ts' => date('c'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'action' => $action,
                'meta' => $meta,
            ];
            @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $t) { /* ignore */ }
    }

    private function hasValidCaptcha(string $token): bool
    {
        if (!$token) return false;
        $ok = isset($_SESSION['captcha_ok_tokens'][$token]);
        if ($ok) unset($_SESSION['captcha_ok_tokens'][$token]);
        return $ok;
    }

    public function root(): void
    {
        $at = $_COOKIE['adm_at'] ?? '';
        if (!$at) { $this->redirect('/secure/adm/login'); }
        $jwt = new JwtService();
        try { $jwt->decode($at); $this->redirect('/secure/adm/index'); } catch (\Throwable $e) { $this->redirect('/secure/adm/login'); }
    }

    public function loginForm(): void
    {
        $this->adminAuth->ensureTableAndSeed();
        $this->view('admin_secure/login', [ 'title' => 'Secure Admin - Login', 'error' => $_GET['error'] ?? '', 'footerVariant' => 'admin-login' ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) { try { Application::getInstance()->logger()->warning('SecureAdmin CSRF invalid'); } catch (\Throwable $t) {} $this->redirect('/secure/adm/login?error=csrf'); }
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $captchaToken = trim($_POST['captcha_token'] ?? '');
        $rcToken = trim($_POST['rc_token'] ?? '');
        // Honeypot
        $hp = isset($_POST['website']) ? trim((string)$_POST['website']) : '';
        if ($hp !== '') { $this->redirect('/secure/adm/login?error=bot'); }
        try { Application::getInstance()->logger()->info('SecureAdmin login attempt', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'captcha' => (bool)$captchaToken]); } catch (\Throwable $t) {}
        if (!$this->hasValidCaptcha($captchaToken)) { try { Application::getInstance()->logger()->warning('SecureAdmin captcha invalid', ['username' => $username]); } catch (\Throwable $t) {} $this->redirect('/secure/adm/login?error=captcha'); }
        // Rate limit (15 min / 5 falhas)
        try {
            $since = date('Y-m-d H:i:s', time() - 15*60);
            $count = (int)(Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND attempted_at > ? AND success = 0', ['admin:'.$username, $since])['c'] ?? 0);
            if ($count >= 5) { $this->redirect('/secure/adm/login?error=ratelimit'); }
        } catch (\Throwable $t) { /* ignore */ }
        // reCAPTCHA v3 (opcional)
        try { $rc = new RecaptchaService(); if ($rc->isConfigured()) { $vr = $rc->verify($rcToken, $_SERVER['REMOTE_ADDR'] ?? '', 'admin_login'); if (!$vr['ok']) { $this->redirect('/secure/adm/login?error=rc'); } } } catch (\Throwable $t) { /* ignore */ }
        $res = $this->adminAuth->login($username, $password);
        // Log tentativa
        try { Database::insert('login_attempts', [ 'email' => 'admin:'.$username, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'success' => (int)($res['success'] ?? false), 'attempted_at' => date('Y-m-d H:i:s') ]); } catch (\Throwable $t) {}
        if (!($res['success'] ?? false)) { try { Application::getInstance()->logger()->warning('SecureAdmin auth failed', ['username' => $username]); } catch (\Throwable $t) {} $this->redirect('/secure/adm/login?error=auth'); }
        // Preparar 2FA: gerar código, armazenar na sessão com expiração de 5 minutos e enviar por email
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $adminUser = (string)($_SESSION['admin_username'] ?? $username);
        // Remover sessão de admin baseada no step 1 (defesa em profundidade; acesso real exige JWT)
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_login_time']);
        // Gerar código de 6 dígitos
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['adm_2fa'] = [
            'admin_id' => $adminId,
            'username' => strtolower(trim($adminUser)),
            'code' => $code,
            'exp' => time() + 300, // 5 minutos
        ];
        // Destinatário: se username for email válido, usar; senão, usar ADMIN_2FA_EMAIL do .env
        $toName = (string)$adminUser;
        $toEmail = filter_var($adminUser, FILTER_VALIDATE_EMAIL) ? strtolower(trim((string)$adminUser)) : (string)($_ENV['ADMIN_2FA_EMAIL'] ?? '');
        if ($toEmail === '') {
            $_SESSION['adm_2fa_email_sent'] = false;
            try { Application::getInstance()->logger()->error('SecureAdmin 2FA no recipient email (username not email and ADMIN_2FA_EMAIL missing)', ['username' => $adminUser]); } catch (\Throwable $__) {}
        } else {
            try {
                $sent = (new EmailService())->sendVerificationCode($toName, $code, $toEmail, 'admin_2fa');
                $_SESSION['adm_2fa_email_sent'] = (bool)$sent;
                if ($sent) {
                    Application::getInstance()->logger()->info('SecureAdmin 2FA code sent', ['id' => $adminId, 'username' => $adminUser, 'to' => $toEmail]);
                } else {
                    Application::getInstance()->logger()->warning('SecureAdmin 2FA send returned false', ['id' => $adminId, 'username' => $adminUser, 'to' => $toEmail]);
                }
            } catch (\Throwable $t) {
                $_SESSION['adm_2fa_email_sent'] = false;
                Application::getInstance()->logger()->error('SecureAdmin 2FA send failed: ' . $t->getMessage());
            }
        }
        $this->redirect('/secure/adm/2fa');
    }

    public function logout(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/index'); }
        // Revogar refresh token atual, se houver
        try {
            if (!empty($_SESSION['adm_rt_jti'])) {
                Database::update('admin_refresh_tokens', [ 'revoked_at' => date('Y-m-d H:i:s') ], [ 'jti' => (string)$_SESSION['adm_rt_jti'] ]);
            }
        } catch (\Throwable $t) { /* ignore */ }
        setcookie('adm_at', '', [ 'expires' => time()-3600, 'path' => '/' ]);
        setcookie('adm_rt', '', [ 'expires' => time()-3600, 'path' => '/' ]);
        unset($_SESSION['adm_rt_jti']);
        $this->redirect('/secure/adm/login');
    }

    // Forgot Password Flow (rate limit: 2 requests / 5 min)
    public function forgotForm(): void
    {
        $this->view('admin_secure/forgot', [ 'title' => 'Secure Admin - Esqueci a senha', 'error' => $_GET['error'] ?? '', 'ok' => $_GET['ok'] ?? '', 'footerVariant' => 'admin-login' ]);
    }

    public function forgotRequest(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/forgot?error=csrf'); }
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        if ($username === '') { $this->redirect('/secure/adm/forgot?error=invalid'); }
        // Rate limit 2 / 5min per username
        try {
            $since = date('Y-m-d H:i:s', time() - 5*60);
            $cnt = (int)(Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND attempted_at > ? AND success = 0', ['admin-forgot:'.$username, $since])['c'] ?? 0);
            if ($cnt >= 2) { $this->redirect('/secure/adm/forgot?error=ratelimit'); }
        } catch (\Throwable $t) { /* ignore */ }
        // Generate 6-digit code and email (if admin exists)
        $exists = Database::fetch('SELECT id, username FROM admin_users WHERE username = ?', [$username]);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['adm_fp'] = [ 'username' => $username, 'code' => $code, 'exp' => time() + 300 ];
        try {
            if ($exists) { (new EmailService())->sendVerificationCode($username, $code, $username, 'admin_2fa'); }
        } catch (\Throwable $t) { /* ignore */ }
        try { Database::insert('login_attempts', [ 'email' => 'admin-forgot:'.$username, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'success' => 0, 'attempted_at' => date('Y-m-d H:i:s') ]); } catch (\Throwable $t) {}
        $this->redirect('/secure/adm/forgot/verify?ok=sent');
    }

    public function forgotVerifyForm(): void
    {
        $this->view('admin_secure/forgot_verify', [ 'title' => 'Secure Admin - Verificar código', 'error' => $_GET['error'] ?? '', 'ok' => $_GET['ok'] ?? '', 'footerVariant' => 'admin-login' ]);
    }

    public function forgotVerify(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/forgot/verify?error=csrf'); }
        $pending = $_SESSION['adm_fp'] ?? null;
        if (!$pending || empty($pending['username'])) { $this->redirect('/secure/adm/forgot'); }
        $code = trim((string)($_POST['code'] ?? ''));
        // Rate limit verification: 10 attempts / 10min per username
        try {
            $since = date('Y-m-d H:i:s', time() - 10*60);
            $cnt = (int)(Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND attempted_at > ? AND success = 0', ['admin-fpv:'.$pending['username'], $since])['c'] ?? 0);
            if ($cnt >= 10) { $this->redirect('/secure/adm/forgot/verify?error=ratelimit'); }
        } catch (\Throwable $t) { /* ignore */ }
        if ($code === '' || time() > (int)$pending['exp'] || !hash_equals((string)$pending['code'], $code)) {
            try { Database::insert('login_attempts', [ 'email' => 'admin-fpv:'.$pending['username'], 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'success' => 0, 'attempted_at' => date('Y-m-d H:i:s') ]); } catch (\Throwable $t) {}
            $this->redirect('/secure/adm/forgot/verify?error=invalid');
        }
        // Success: generate new 32-char password, update and email
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()_-+=[]{}';
        $pw = '';
        for ($i=0; $i<32; $i++) { $pw .= $alphabet[random_int(0, strlen($alphabet)-1)]; }
        try {
            $hash = password_hash($pw, PASSWORD_ARGON2ID);
            Database::update('admin_users', [ 'password_hash' => $hash, 'updated_at' => date('Y-m-d H:i:s') ], [ 'username' => $pending['username'] ]);
            (new EmailService())->sendAdminNewPassword($pending['username'], $pw, $pending['username']);
            unset($_SESSION['adm_fp']);
        } catch (\Throwable $t) {
            $this->redirect('/secure/adm/forgot/verify?error=server');
        }
        $this->redirect('/secure/adm/login?ok=pwreset');
    }

    public function index(): void
    {
        // Ensure core indexes and admin token tables
        try { (new SystemMaintenanceService())->ensureCore(); } catch (\Throwable $t) { /* ignore */ }
        
        // Status metrics
        $status = [
            'db_ok' => false,
            'smtp_config' => !empty($_ENV['MAIL_HOST'] ?? ''),
            'recaptcha_site_key' => !empty($_ENV['RECAPTCHA_V3_SITE_KEY'] ?? ''),
            'jwt_secret' => false,
        ];
        try { $r = Database::fetch('SELECT 1 AS ok'); $status['db_ok'] = isset($r['ok']); } catch (\Throwable $t) { $status['db_ok'] = false; }
        try { $cfg = Application::getInstance()->config('app.jwt') ?? []; $status['jwt_secret'] = !empty($cfg['secret']); } catch (\Throwable $t) { $status['jwt_secret'] = false; }
        
        // Basic counts
        $counts = [ 'users_total' => 0, 'tickets_open' => 0, 'admins_total' => 0, 'logins_24h' => 0 ];
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL'); $counts['users_total'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'open'"); $counts['tickets_open'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM admin_users'); $counts['admins_total'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since = date('Y-m-d H:i:s', time()-24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE attempted_at >= ?', [$since]); $counts['logins_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        
        // User activity stats
        $userStats = [
            'active_7d' => 0,
            'active_24h' => 0,
            'new_7d' => 0,
            'new_24h' => 0,
            'verified' => 0,
        ];
        try { $since7d = date('Y-m-d H:i:s', time() - 7*24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE last_login_at >= ? AND deleted_at IS NULL', [$since7d]); $userStats['active_7d'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since24h = date('Y-m-d H:i:s', time() - 24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE last_login_at >= ? AND deleted_at IS NULL', [$since24h]); $userStats['active_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since7d = date('Y-m-d H:i:s', time() - 7*24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ? AND deleted_at IS NULL', [$since7d]); $userStats['new_7d'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since24h = date('Y-m-d H:i:s', time() - 24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ? AND deleted_at IS NULL', [$since24h]); $userStats['new_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE email_verified_at IS NOT NULL AND deleted_at IS NULL'); $userStats['verified'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        
        // XP & Gamification stats
        $xpStats = [
            'total_xp' => 0,
            'avg_xp' => 0,
            'total_transactions' => 0,
            'transactions_24h' => 0,
            'avg_level' => 0,
            'max_level' => 0,
            'avg_streak' => 0,
            'max_streak' => 0,
        ];
        try { $r = Database::fetch('SELECT COALESCE(SUM(xp), 0) AS total, COALESCE(AVG(xp), 0) AS avg FROM users WHERE deleted_at IS NULL'); $xpStats['total_xp'] = (int)($r['total'] ?? 0); $xpStats['avg_xp'] = round((float)($r['avg'] ?? 0), 1); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM xp_history'); $xpStats['total_transactions'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since24h = date('Y-m-d H:i:s', time() - 24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM xp_history WHERE created_at >= ?', [$since24h]); $xpStats['transactions_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COALESCE(AVG(level), 0) AS avg, COALESCE(MAX(level), 0) AS max FROM users WHERE deleted_at IS NULL'); $xpStats['avg_level'] = round((float)($r['avg'] ?? 0), 1); $xpStats['max_level'] = (int)($r['max'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COALESCE(AVG(streak), 0) AS avg, COALESCE(MAX(streak), 0) AS max FROM users WHERE deleted_at IS NULL'); $xpStats['avg_streak'] = round((float)($r['avg'] ?? 0), 1); $xpStats['max_streak'] = (int)($r['max'] ?? 0); } catch (\Throwable $t) {}
        
        // XP by source (last 7 days)
        $xpBySource = [];
        try { $since7d = date('Y-m-d H:i:s', time() - 7*24*3600); $xpBySource = Database::fetchAll('SELECT source, COUNT(*) AS count, SUM(amount) AS total_xp FROM xp_history WHERE created_at >= ? GROUP BY source ORDER BY total_xp DESC LIMIT 10', [$since7d]); } catch (\Throwable $t) {}
        
        // Discord stats
        $discordStats = [
            'verified_users' => 0,
            'pending_users' => 0,
            'syncs_24h' => 0,
        ];
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM discord_users WHERE is_verified = TRUE'); $discordStats['verified_users'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM discord_users WHERE is_verified = FALSE'); $discordStats['pending_users'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since24h = date('Y-m-d H:i:s', time() - 24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM discord_users WHERE last_sync_at >= ?', [$since24h]); $discordStats['syncs_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        
        // Top users by XP (last 10)
        $topUsers = [];
        try { $topUsers = Database::fetchAll('SELECT id, name, xp, level, streak FROM users WHERE deleted_at IS NULL ORDER BY xp DESC LIMIT 10'); } catch (\Throwable $t) {}
        
        // Recent XP transactions (last 20)
        $recentXP = [];
        try { $recentXP = Database::fetchAll('SELECT h.id, h.user_id, u.name AS user_name, h.amount, h.source, h.description, h.created_at FROM xp_history h JOIN users u ON u.id = h.user_id ORDER BY h.created_at DESC LIMIT 20'); } catch (\Throwable $t) {}
        
        $this->view('admin_secure/index', [
            'title' => 'Secure Admin - Dashboard',
            'footerVariant' => 'admin-auth',
            'status' => $status,
            'counts' => $counts,
            'userStats' => $userStats,
            'xpStats' => $xpStats,
            'xpBySource' => $xpBySource,
            'discordStats' => $discordStats,
            'topUsers' => $topUsers,
            'recentXP' => $recentXP,
        ]);
    }

    public function tickets(): void
    {
        // Carrega tickets e mensagens para visão administrativa
        $tickets = Database::fetchAll('SELECT * FROM support_tickets ORDER BY created_at DESC');
        $openTickets = array_values(array_filter($tickets, fn($t) => ($t['status'] ?? 'open') === 'open'));
        $closedTickets = array_values(array_filter($tickets, fn($t) => ($t['status'] ?? 'open') !== 'open'));
        $messagesByTicket = [];
        if ($tickets) {
            $ids = implode(',', array_map('intval', array_column($tickets, 'id')));
            if ($ids !== '') {
                $msgs = Database::fetchAll("SELECT * FROM support_messages WHERE ticket_id IN ($ids) ORDER BY created_at ASC");
                foreach ($msgs as $m) { $messagesByTicket[$m['ticket_id']][] = $m; }
            }
        }
        $this->view('admin_secure/tickets', [
            'title' => 'Secure Admin - Tickets',
            'tickets' => $tickets,
            'openTickets' => $openTickets,
            'closedTickets' => $closedTickets,
            'messages' => $messagesByTicket,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function users(): void
    {
        try { (new SystemMaintenanceService())->ensureCore(); } catch (\Throwable $t) { /* ignore */ }
        $limit = (int)($_GET['limit'] ?? 100);
        if ($limit < 1) $limit = 100; if ($limit > 1000) $limit = 1000;
        $q = trim((string)($_GET['q'] ?? ''));
        $df = trim((string)($_GET['from'] ?? ''));
        $dt = trim((string)($_GET['to'] ?? ''));
        $where = [];
        $params = [];
        if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($df !== '' && $dt !== '') { $where[] = 'created_at BETWEEN ? AND ?'; $params[] = $df . ' 00:00:00'; $params[] = $dt . ' 23:59:59'; }
        elseif ($df !== '') { $where[] = 'created_at >= ?'; $params[] = $df . ' 00:00:00'; }
        elseif ($dt !== '') { $where[] = 'created_at <= ?'; $params[] = $dt . ' 23:59:59'; }
        $sql = 'SELECT id, name, email, created_at FROM users' . ( $where ? (' WHERE ' . implode(' AND ', $where)) : '') . ' ORDER BY id DESC LIMIT ' . (int)$limit;
        $rows = Database::fetchAll($sql, $params);
        // Stats (DB-agnostic by using PHP timestamps)
        $stats = [ 'total' => 0, 'active_7d' => 0, 'active_48h' => 0, 'new_7d' => 0, 'new_48h' => 0 ];
        $since7d = date('Y-m-d H:i:s', time() - 7*24*3600);
        $since48h = date('Y-m-d H:i:s', time() - 48*3600);
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users'); $stats['total'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE last_login_at IS NOT NULL AND last_login_at >= ?', [$since7d]); $stats['active_7d'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE last_login_at IS NOT NULL AND last_login_at >= ?', [$since48h]); $stats['active_48h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ?', [$since7d]); $stats['new_7d'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ?', [$since48h]); $stats['new_48h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        $this->view('admin_secure/users', [ 'title' => 'Secure Admin - Usuários', 'users' => $rows, 'stats' => $stats, 'q' => $q, 'from' => $df, 'to' => $dt, 'limit' => $limit, 'footerVariant' => 'admin-auth' ]);
    }

    // Gestão de administradores (admin_users)
    public function admins(): void
    {
        $admins = (new AdminAuthService())->listAdmins(false);
        $this->view('admin_secure/admins', [ 'title' => 'Secure Admin - Administradores', 'admins' => $admins, 'ok' => $_GET['ok'] ?? null, 'err' => $_GET['err'] ?? null, 'footerVariant' => 'admin-auth' ]);
    }

    public function createAdmin(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/admins?err=csrf'); }
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $res = (new AdminAuthService())->createAdmin($username, $password);
        if ($res['success'] ?? false) {
            $this->audit('admin_create', ['username' => $username]);
            $this->redirect('/secure/adm/admins?ok=1');
        } else {
            $msg = urlencode($res['message'] ?? 'erro');
            $this->redirect('/secure/adm/admins?err=' . $msg);
        }
    }

    // Gestão de Reviews
    public function reviews(): void
    {
        $this->view('admin_secure/reviews', [
            'title' => 'Secure Admin - Gestão de Reviews',
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function twoFactorForm(): void
    {
        $pending = $_SESSION['adm_2fa'] ?? null;
        if (!$pending || empty($pending['admin_id'])) { $this->redirect('/secure/adm/login'); }
        $err = $_GET['error'] ?? null;
        if (!$err && array_key_exists('adm_2fa_email_sent', $_SESSION) && $_SESSION['adm_2fa_email_sent'] === false) { $err = 'email'; }
        $this->view('admin_secure/2fa', [ 'title' => 'Verificação em duas etapas', 'error' => $err, 'footerVariant' => 'admin-login' ]);
    }

    public function verifyTwoFactor(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/2fa?error=csrf'); }
        // Honeypot
        $hp = isset($_POST['website']) ? trim((string)$_POST['website']) : '';
        if ($hp !== '') { $this->redirect('/secure/adm/2fa?error=bot'); }
        $pending = $_SESSION['adm_2fa'] ?? null;
        if (!$pending || empty($pending['admin_id'])) { $this->redirect('/secure/adm/login'); }
        // Rate limit 2FA (10 min / 10 falhas)
        try {
            $since = date('Y-m-d H:i:s', time() - 10*60);
            $uname = (string)($pending['username'] ?? '');
            $cnt = (int)(Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND attempted_at > ? AND success = 0', ['admin-2fa:'.$uname, $since])['c'] ?? 0);
            if ($cnt >= 10) { $this->redirect('/secure/adm/2fa?error=ratelimit'); }
        } catch (\Throwable $t) { /* ignore */ }
        $code = trim((string)($_POST['code'] ?? ''));
        $now = time();
        if ($code === '' || $now > (int)$pending['exp'] || !hash_equals((string)$pending['code'], $code)) {
            try { Database::insert('login_attempts', [ 'email' => 'admin-2fa:'.((string)($pending['username'] ?? '')), 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'success' => 0, 'attempted_at' => date('Y-m-d H:i:s') ]); } catch (\Throwable $t) {}
            try { Application::getInstance()->logger()->warning('SecureAdmin 2FA invalid', ['id' => $pending['admin_id'] ?? 0]); } catch (\Throwable $t) {}
            $this->redirect('/secure/adm/2fa?error=invalid');
        }
        try { Database::insert('login_attempts', [ 'email' => 'admin-2fa:'.((string)($pending['username'] ?? '')), 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'success' => 1, 'attempted_at' => date('Y-m-d H:i:s') ]); } catch (\Throwable $t) {}
        // Sucesso: emitir JWT e limpar pendências
        $jwt = new JwtService();
        $sub = (int)$pending['admin_id'];
        $at = $jwt->issueAccessToken(['sub' => $sub, 'role' => 'admin']);
        $rt = $jwt->issueRefreshToken(['sub' => $sub, 'role' => 'admin']);
        $secure = Application::getInstance()->isHttps();
        $atTtl = (int)($_ENV['ADMIN_JWT_ACCESS_TTL'] ?? 600);
        if ($atTtl < 60) { $atTtl = 600; }
        setcookie('adm_at', $at, [ 'expires' => time()+$atTtl, 'path' => '/', 'domain' => '', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
        setcookie('adm_rt', $rt['token'], [ 'expires' => $rt['exp'], 'path' => '/', 'domain' => '', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
        $_SESSION['adm_rt_jti'] = $rt['jti'];
        // Persistir JTI para futura revogação/rotação
        try {
            (new SystemMaintenanceService())->ensureCore();
            Database::insert('admin_refresh_tokens', [
                'jti' => (string)$rt['jti'],
                'sub' => $sub,
                'exp' => (int)$rt['exp'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $t) { /* ignore */ }
        unset($_SESSION['adm_2fa']);
        try { Application::getInstance()->logger()->info('SecureAdmin auth success (2FA)', ['id' => $sub]); } catch (\Throwable $t) {}
        $this->audit('login_success', ['admin_id' => $sub]);
        $this->redirect('/secure/adm/index');
    }

    public function viewUser(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/users?err=invalid'); }
        $u = Database::fetch('SELECT id, name, email, cpf, created_at, last_login_at, xp, streak, tier FROM users WHERE id = ?', [$id]);
        if (!$u) { $this->redirect('/secure/adm/users?err=notfound'); }
        // Stats
        $stats = [ 'tickets_total' => 0, 'tickets_open' => 0, 'logins_total' => 0, 'logins_success_30d' => 0 ];
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM support_tickets WHERE user_id = ?', [$id]); $stats['tickets_total'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch("SELECT COUNT(*) AS c FROM support_tickets WHERE user_id = ? AND status = 'open'", [$id]); $stats['tickets_open'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ?', [(string)($u['email'] ?? '')]); $stats['logins_total'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        $since30d = date('Y-m-d H:i:s', time() - 30*24*3600);
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND success = 1 AND attempted_at >= ?', [(string)($u['email'] ?? ''), $since30d]); $stats['logins_success_30d'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        // Aluno: grants e progresso
        $courseGrants = [];
        $lessonGrants = [];
        $lessonProgress = [];
        try { $courseGrants = Database::fetchAll('SELECT ca.course_id, ca.expires_at, c.title FROM course_access ca JOIN courses c ON c.id = ca.course_id WHERE ca.user_id = :u ORDER BY c.title ASC', ['u'=>$id], 'aluno'); } catch (\Throwable $__) {}
        try { $lessonGrants = Database::fetchAll('SELECT la.lesson_id, la.expires_at, l.title, l.position, l.course_id, c.title AS course_title FROM lesson_access la JOIN lessons l ON l.id = la.lesson_id JOIN courses c ON c.id = l.course_id WHERE la.user_id = :u ORDER BY c.title ASC, l.position ASC', ['u'=>$id], 'aluno'); } catch (\Throwable $__) {}
        try { $lessonProgress = Database::fetchAll('SELECT p.lesson_id, p.last_second, p.completed, p.updated_at, l.title, l.position, l.duration_seconds, l.course_id, c.title AS course_title FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id JOIN courses c ON c.id = l.course_id WHERE p.user_id = :u ORDER BY p.updated_at DESC LIMIT 300', ['u'=>$id], 'aluno'); } catch (\Throwable $__) {}
        $this->view('admin_secure/user_view', [ 'title' => 'Usuário #' . (int)$u['id'], 'profile' => $u, 'stats' => $stats, 'courseGrants' => $courseGrants, 'lessonGrants' => $lessonGrants, 'progress' => $lessonProgress, 'footerVariant' => 'admin-auth' ]);
    }

    public function editUser(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/users?err=invalid'); }
        $u = Database::fetch('SELECT id, name, email, created_at, tier FROM users WHERE id = ?', [$id]);
        if (!$u) { $this->redirect('/secure/adm/users?err=notfound'); }
        $this->view('admin_secure/user_edit', [ 'title' => 'Editar Usuário #' . (int)$u['id'], 'profile' => $u, 'footerVariant' => 'admin-auth' ]);
    }

    public function updateUser(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/users?err=csrf'); }
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $tier = trim((string)($_POST['tier'] ?? ''));
        if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->redirect('/secure/adm/users?err=invalid'); }
        $allowedTiers = ['free','premium','pro'];
        $update = [ 'name' => $name, 'email' => $email ];
        if ($tier !== '' && in_array($tier, $allowedTiers, true)) { $update['tier'] = $tier; }
        Database::update('users', $update, [ 'id' => $id ]);
        try { Application::getInstance()->logger()->info('Admin updated user', ['id' => $id]); } catch (\Throwable $t) {}
        $this->audit('user_update', ['id' => $id]);
        $this->redirect('/secure/adm/users?ok=updated');
    }

    public function deleteUser(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/users?err=csrf'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/users?err=invalid'); }
        Database::delete('users', ['id' => $id]);
        try { Application::getInstance()->logger()->warning('Admin deleted user', ['id' => $id]); } catch (\Throwable $t) {}
        $this->audit('user_delete', ['id' => $id]);
        $this->redirect('/secure/adm/users?ok=deleted');
    }

    public function exportUsers(): void
    {
        // Optional filters
        $where = [];
        $params = [];
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        $df = trim((string)($_GET['from'] ?? ''));
        $dt = trim((string)($_GET['to'] ?? ''));
        if ($df !== '' && $dt !== '') { $where[] = 'created_at BETWEEN ? AND ?'; $params[] = $df . ' 00:00:00'; $params[] = $dt . ' 23:59:59'; }
        elseif ($df !== '') { $where[] = 'created_at >= ?'; $params[] = $df . ' 00:00:00'; }
        elseif ($dt !== '') { $where[] = 'created_at <= ?'; $params[] = $dt . ' 23:59:59'; }
        $sql = 'SELECT id, name, email, cpf, phone, last_login_at, created_at, xp, streak, tier FROM users' . ( $where ? (' WHERE ' . implode(' AND ', $where)) : '') . ' ORDER BY id ASC';
        $rows = Database::fetchAll($sql, $params);
        $filename = 'usuarios_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Nome','Email','CPF','Telefone','Último acesso','Criado em','XP','Streak','Tier']);
        foreach ($rows as $r) {
            fputcsv($out, [
                (int)$r['id'],
                (string)($r['name'] ?? ''),
                (string)($r['email'] ?? ''),
                (string)($r['cpf'] ?? ''),
                (string)($r['phone'] ?? ''),
                (string)($r['last_login_at'] ?? ''),
                (string)($r['created_at'] ?? ''),
                (int)($r['xp'] ?? 0),
                (int)($r['streak'] ?? 0),
                (string)($r['tier'] ?? '')
            ]);
        }
        fclose($out);
        exit;
    }

    public function crm(): void
    {
        // Ensure leads table exists with user_id reference
        try {
            Database::query("
                CREATE TABLE IF NOT EXISTS crm_leads (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                    name VARCHAR(255),
                    email VARCHAR(255) UNIQUE NOT NULL,
                    phone VARCHAR(50),
                    source VARCHAR(100) DEFAULT 'platform',
                    status VARCHAR(50) DEFAULT 'new',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_user_id ON crm_leads(user_id)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_email ON crm_leads(email)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_status ON crm_leads(status)");
            
            // Sync existing users as leads (converted status)
            Database::query("
                INSERT INTO crm_leads (user_id, name, email, source, status, created_at, updated_at)
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    'platform' as source,
                    'converted' as status,
                    u.created_at,
                    CURRENT_TIMESTAMP
                FROM users u
                WHERE u.email NOT IN (SELECT email FROM crm_leads)
                ON CONFLICT (email) DO NOTHING
            ");
        } catch (\Throwable $t) { /* ignore */ }
        
        $status = $_GET['status'] ?? '';
        $search = trim($_GET['search'] ?? '');
        
        $where = [];
        $params = [];
        
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        
        if ($search !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        $sql = 'SELECT * FROM crm_leads';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 500';
        
        $leads = [];
        try {
            $leads = Database::fetchAll($sql, $params);
        } catch (\Throwable $t) {
            $leads = [];
        }
        
        // Stats
        $stats = [
            'total' => 0,
            'new' => 0,
            'contacted' => 0,
            'qualified' => 0,
            'converted' => 0,
            'lost' => 0,
        ];
        
        try {
            $r = Database::fetch('SELECT COUNT(*) AS c FROM crm_leads');
            $stats['total'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM crm_leads WHERE status = 'new'");
            $stats['new'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM crm_leads WHERE status = 'contacted'");
            $stats['contacted'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM crm_leads WHERE status = 'qualified'");
            $stats['qualified'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM crm_leads WHERE status = 'converted'");
            $stats['converted'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM crm_leads WHERE status = 'lost'");
            $stats['lost'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        $this->view('admin_secure/crm', [
            'title' => 'Secure Admin - CRM',
            'footerVariant' => 'admin-auth',
            'leads' => $leads,
            'stats' => $stats,
            'currentStatus' => $status,
            'searchQuery' => $search,
        ]);
    }

    public function addLead(): void
    {
        // Validar dados
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $source = trim($_POST['source'] ?? 'manual');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($email)) {
            $_SESSION['flash_error'] = 'Email é obrigatório';
            header('Location: /secure/adm/crm');
            exit;
        }

        // Criar tabela se não existir
        try {
            Database::query("
                CREATE TABLE IF NOT EXISTS crm_leads (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255) UNIQUE NOT NULL,
                    phone VARCHAR(50),
                    source VARCHAR(100),
                    status VARCHAR(50) DEFAULT 'new',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_email ON crm_leads(email)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_status ON crm_leads(status)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_crm_leads_created ON crm_leads(created_at DESC)");
        } catch (\Throwable $t) { /* ignore */ }

        try {
            // Inserir lead
            Database::insert('crm_leads', [
                'name' => $name ?: null,
                'email' => $email,
                'phone' => $phone ?: null,
                'source' => $source,
                'status' => 'new',
                'notes' => $notes ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['flash_success'] = 'Lead adicionado com sucesso!';
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'unique') !== false) {
                $_SESSION['flash_error'] = 'Este email já está cadastrado como lead';
            } else {
                $_SESSION['flash_error'] = 'Erro ao adicionar lead: ' . $e->getMessage();
            }
        }

        header('Location: /secure/adm/crm');
        exit;
    }

    public function status(): void
    {
        $status = [
            'db_ok' => false,
            'smtp_config' => !empty($_ENV['MAIL_HOST'] ?? ''),
            'recaptcha_site_key' => !empty($_ENV['RECAPTCHA_V3_SITE_KEY'] ?? ''),
            'recaptcha_secret' => !empty($_ENV['RECAPTCHA_V3_SECRET'] ?? ''),
            'jwt_secret' => false,
        ];
        try { $r = Database::fetch('SELECT 1 AS ok'); $status['db_ok'] = isset($r['ok']); } catch (\Throwable $t) { $status['db_ok'] = false; }
        try { $jwtCfg = Application::getInstance()->config('app.jwt') ?? []; $status['jwt_secret'] = !empty($jwtCfg['secret']); } catch (\Throwable $t) { $status['jwt_secret'] = false; }

        // Simple metrics
        $metrics = [ 'users' => 0, 'tickets_open' => 0, 'admins' => 0, 'logins_24h' => 0 ];
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users'); $metrics['users'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'open'"); $metrics['tickets_open'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $r = Database::fetch('SELECT COUNT(*) AS c FROM admin_users'); $metrics['admins'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}
        try { $since = date('Y-m-d H:i:s', time()-24*3600); $r = Database::fetch('SELECT COUNT(*) AS c FROM login_attempts WHERE attempted_at >= ?', [$since]); $metrics['logins_24h'] = (int)($r['c'] ?? 0); } catch (\Throwable $t) {}

        $this->view('admin_secure/status', [ 'title' => 'Admin - Status', 'status' => $status, 'metrics' => $metrics, 'footerVariant' => 'admin-auth' ]);
    }

    public function refreshToken(): void
    {
        // Endpoint para renovar tokens usando refresh token em cookie (adm_rt)
        // Não usa SecureAdminMiddleware para permitir refresh mesmo com access token expirado.
        // Proteções: CSRF + SameOrigin aplicados na rota.
        try { (new SystemMaintenanceService())->ensureCore(); } catch (\Throwable $t) {}
        $rtCookie = $_COOKIE['adm_rt'] ?? '';
        if (!$rtCookie) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Refresh ausente']); exit; }
        $jwt = new JwtService();
        try {
            $payload = $jwt->decode($rtCookie);
            if (($payload['typ'] ?? '') !== 'refresh') { throw new \Exception('tipo invalido'); }
            $jti = (string)($payload['jti'] ?? '');
            $sub = (int)($payload['sub'] ?? 0);
            $exp = (int)($payload['exp'] ?? 0);
            if ($jti === '' || $sub <= 0 || $exp <= time()) { throw new \Exception('payload invalido'); }
            // Verificar revogação/validade em banco
            $row = Database::fetch('SELECT jti, sub, exp, revoked_at FROM admin_refresh_tokens WHERE jti = ?', [$jti]);
            if (!$row || (int)$row['sub'] !== $sub) { throw new \Exception('refresh desconhecido'); }
            if (!empty($row['revoked_at'])) { throw new \Exception('refresh revogado'); }
            if ((int)$row['exp'] < time()) { throw new \Exception('refresh expirado'); }
            // Rotacionar: revogar o atual e emitir novos tokens
            Database::update('admin_refresh_tokens', [ 'revoked_at' => date('Y-m-d H:i:s') ], [ 'jti' => $jti ]);
            $newAt = $jwt->issueAccessToken(['sub' => $sub, 'role' => 'admin']);
            $newRt = $jwt->issueRefreshToken(['sub' => $sub, 'role' => 'admin']);
            Database::insert('admin_refresh_tokens', [
                'jti' => (string)$newRt['jti'],
                'sub' => $sub,
                'exp' => (int)$newRt['exp'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['adm_rt_jti'] = (string)$newRt['jti'];
            $secure = Application::getInstance()->isHttps();
            $atTtl = (int)($_ENV['ADMIN_JWT_ACCESS_TTL'] ?? 600);
            if ($atTtl < 60) { $atTtl = 600; }
            setcookie('adm_at', $newAt, [ 'expires' => time()+$atTtl, 'path' => '/', 'domain' => '', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            setcookie('adm_rt', $newRt['token'], [ 'expires' => (int)$newRt['exp'], 'path' => '/', 'domain' => '', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            header('Content-Type: application/json');
            echo json_encode(['success'=>true]);
            exit;
        } catch (\Throwable $e) {
            // Em qualquer falha, limpar cookies e forçar novo login
            setcookie('adm_at', '', [ 'expires' => time()-3600, 'path' => '/secure/adm' ]);
            setcookie('adm_rt', '', [ 'expires' => time()-3600, 'path' => '/secure/adm' ]);
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Refresh inválido']);
            exit;
        }
    }

    public function runMigrations(): void
    {
        // Admin-only; enforced by SecureAdminMiddleware in routes
        header('Content-Type: text/html; charset=utf-8');
        echo "<pre>";
        try {
            // Ensure DB is initialized
            $app = Application::getInstance();
            Database::init($app->config('database'));
            $pdo = Database::connection();

            echo "📦 Iniciando execução das migrações...\n\n";

            // Create migrations table if not exists
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL);

            $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
            if (!is_dir($migrationsDir)) {
                echo "❌ Diretório de migrações não encontrado: {$migrationsDir}\n";
                echo "</pre>";
                return;
            }

            $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
            sort($files, SORT_NATURAL);

            // Optional: run only a specific migration (e.g., ?only=011 or ?only=011_add_timezone_to_users.sql)
            $only = isset($_GET['only']) ? trim((string)$_GET['only']) : '';
            if ($only !== '') {
                $onlyPattern = null;
                if (preg_match('/^\d+$/', $only)) {
                    $onlyPattern = sprintf('%s_%s', str_pad((string)((int)$only), 3, '0', STR_PAD_LEFT), '');
                }
                $files = array_values(array_filter($files, function($path) use ($only, $onlyPattern) {
                    $base = basename($path);
                    if ($onlyPattern !== null) {
                        return str_starts_with($base, $onlyPattern);
                    }
                    return strcasecmp($base, $only) === 0;
                }));
                echo "Filtro aplicado: somente '" . htmlspecialchars($only, ENT_QUOTES, 'UTF-8') . "'\n\n";
            }

            if (empty($files)) {
                echo "Nenhuma migração encontrada.\n";
                echo "</pre>";
                return;
            }

            $executed = 0;
            foreach ($files as $file) {
                $filename = basename($file);
                $stmt = $pdo->prepare('SELECT COUNT(1) FROM migrations WHERE filename = :filename');
                $stmt->execute(['filename' => $filename]);
                $alreadyRan = (int)$stmt->fetchColumn() > 0;

                if ($alreadyRan) {
                    echo "⏭️  Já executada: {$filename}\n";
                    continue;
                }

                $sql = file_get_contents($file);
                if ($sql === false || trim($sql) === '') {
                    echo "⚠️  Ignorando (arquivo vazio ou não lido): {$filename}\n";
                    continue;
                }

                echo "▶️  Executando: {$filename}\n";
                try {
                    $pdo->beginTransaction();
                    $pdo->exec($sql);
                    $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)');
                    $ins->execute(['filename' => $filename]);
                    $pdo->commit();
                    $executed++;
                    echo "✅ Concluída: {$filename}\n";
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    echo "❌ Erro em {$filename}: " . $e->getMessage() . "\n";
                    // Parar na primeira falha para segurança
                    echo "\nMigrações interrompidas.\n";
                    echo "</pre>";
                    http_response_code(500);
                    return;
                }
            }

            echo "\n🏁 Finalizado. Novas executadas: {$executed}\n";
            echo "</pre>";
        } catch (\Throwable $e) {
            http_response_code(500);
            echo '❌ ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            echo "</pre>";
        }
        exit;
    }
}
