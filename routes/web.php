<?php

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;
use App\Controllers\QuotesController;
use App\Controllers\SupportController;
use App\Controllers\CommunityController;
use App\Controllers\SelfTestController;
use App\Controllers\FearGreedController;
use App\Controllers\IndicatorsController;
use App\Controllers\FedController;
use App\Controllers\SecurityController;
use App\Controllers\AdminController;
use App\Controllers\AdminSecureController;
use App\Controllers\TestController;
use App\Controllers\DiagnosticsController;
use App\Controllers\UsMarketBarometerController;
use App\Controllers\OrmController;
use App\Controllers\CronQuotesController;
use App\Controllers\TvController;
use App\Controllers\NewsController;
use App\Controllers\CaptchaController;
use App\Controllers\SsoController;
use App\Controllers\ReviewsController;
use App\Controllers\AdminReviewsController;
use App\Controllers\MarketClockController;
use App\Controllers\AdminAlunoController;
use App\Controllers\AdminAlunoCoursesController;
use App\Controllers\AdminAlunoAccessController;
use App\Controllers\AdminAlunoBunnyController;
use App\Controllers\AdminAlunoLessonsController;
use App\Controllers\AdminAlunoEnrollmentsController;
use App\Controllers\OBIndicesController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\SecureAdminMiddleware;
use App\Middleware\DebugOnlyMiddleware;
use App\Middleware\SameOriginAjaxMiddleware;

$router = new Router();

// Rotas públicas (apenas para usuários não logados)
$router->get('/', [HomeController::class, 'index'], [GuestMiddleware::class]);
$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->get('/reset-password', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);

// CSRF utility
$router->get('/csrf/token', [SecurityController::class, 'token']);
// Public self-tests page (developer use)
$router->get('/selftest', [SelfTestController::class, 'index']);
// Public status page (no sensitive data)
$router->get('/status', [SelfTestController::class, 'publicStatus']);
// Public shortcut to Aluno portal courses via SSO
$router->get('/aluno/courses', function(){
    header('Location: /sso/start?return=%2Fcourses', true, 302);
    exit;
});
// CSP report collection endpoint
$router->post('/csp-report', [SecurityController::class, 'cspReport']);
// Client logs from browser (Same-Origin only)
$router->post('/api/client-log', [SecurityController::class, 'clientLog'], [SameOriginAjaxMiddleware::class]);
// Gold dashboard data endpoint
$router->post('/api/quotes/gold-boot', [QuotesController::class, 'goldBoot'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);

// Rotas de autenticação (POST)
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);

// Rotas do novo sistema de registro multi-step (6 passos)
$router->post('/register/consultar-cpf', [AuthController::class, 'consultarCpf'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/confirmar-dados', [AuthController::class, 'confirmarDados'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/override-dados', [AuthController::class, 'overrideDados'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/criar-senha', [AuthController::class, 'criarSenha'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/adicionar-telefone', [AuthController::class, 'adicionarTelefone'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/adicionar-email', [AuthController::class, 'adicionarEmail'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/verificar-codigo', [AuthController::class, 'verificarCodigo'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
$router->post('/register/reenviar-codigo', [AuthController::class, 'reenviarCodigo'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);

// Dashboard routes (protected)
$router->get('/app/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
// Dashboard Gold (protected)
$router->get('/app/dashboard/gold', [DashboardController::class, 'gold'], [AuthMiddleware::class]);
// SSO start (public): se não autenticado, envia para modal de login; se autenticado, emite token e redireciona para o Portal do Aluno
$router->get('/sso/start', [SsoController::class, 'start']);
// SSO start para Diário Operebem (public): mesma lógica, mas redireciona para o Diário
$router->get('/sso/diario/start', [SsoController::class, 'diarioStart']);
// Compat: redirecionar antigo /dashboard -> /app/dashboard (301)
$router->get('/dashboard', function(){
    header('Location: /app/dashboard', true, 301);
    exit;
});

// Indicators routes (protected)
$router->get('/app/indicators/feeling', [IndicatorsController::class, 'feeling'], [AuthMiddleware::class]);
// Federal Reserve (US)
$router->get('/app/indicators/fed', [IndicatorsController::class, 'fed'], [AuthMiddleware::class]);
// Market Clock Fullscreen
$router->get('/app/market-clock', [IndicatorsController::class, 'marketClock'], [AuthMiddleware::class]);

// OB Indices (protected)
$router->get('/app/indicators/ob-indices', [OBIndicesController::class, 'index'], [AuthMiddleware::class]);
// OB Indices API
$router->get('/api/indices', [OBIndicesController::class, 'getIndices'], [AuthMiddleware::class]);
$router->get('/api/indices/{name}', [OBIndicesController::class, 'getIndex'], [AuthMiddleware::class]);
// Página de auto-teste
$router->get('/tools/indices-selftest', [OBIndicesController::class, 'selfTest'], [AuthMiddleware::class]);

// News (protected)
$router->get('/app/news', [NewsController::class, 'index'], [AuthMiddleware::class]);
// TV (protected)
$router->get('/app/tv', [TvController::class, 'index'], [AuthMiddleware::class]);
$router->get('/app/tv/test', [TvController::class, 'test'], [AuthMiddleware::class]);
// News API proxy (protected)
$router->get('/api/news', [NewsController::class, 'noticias'], [AuthMiddleware::class]);
$router->get('/api/news/noticias', [NewsController::class, 'noticias'], [AuthMiddleware::class]);

// Profile (protected)
$router->get('/app/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);
// Profile Dev (protected)
$router->get('/dev/profile', [ProfileController::class, 'indexDev'], [AuthMiddleware::class]);

// Community Discord (protected)
$router->get('/app/community', [\App\Controllers\CommunityController::class, 'index'], [AuthMiddleware::class]);
$router->get('/app/community/status', [\App\Controllers\CommunityController::class, 'status'], [AuthMiddleware::class]);
$router->post('/app/community/disconnect', [\App\Controllers\CommunityController::class, 'disconnect'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/app/community/regenerate-code', [\App\Controllers\CommunityController::class, 'regenerateCode'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Quotes API routes (protected)
// Endpoint protegido para uso interno do app (dashboard)
$router->post('/actions/boot.php', [QuotesController::class, 'boot'], [AuthMiddleware::class]);

// Endpoint público somente para listar (home) com proteção same-origin + rate-limit
$router->post('/actions/quotes-public', [QuotesController::class, 'listarPublic'], [SameOriginAjaxMiddleware::class]);
$router->get('/actions/quotes-public', [QuotesController::class, 'listarPublic'], [SameOriginAjaxMiddleware::class]);

// Endpoint protegido específico do Dashboard Ouro (somente ativos necessários)
$router->post('/actions/gold-boot.php', [QuotesController::class, 'goldBoot'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);

$router->post('/app/profile/preferences', [ProfileController::class, 'updatePreferences'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/app/profile/change-password', [ProfileController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
// Upload avatar
$router->post('/app/profile/avatar', [ProfileController::class, 'uploadAvatar'], [AuthMiddleware::class, CsrfMiddleware::class]);
// Delete avatar
$router->post('/app/profile/avatar/delete', [ProfileController::class, 'deleteAvatar'], [AuthMiddleware::class, CsrfMiddleware::class]);
// Gamification stats
$router->get('/api/profile/gamification', [ProfileController::class, 'getGamificationStats'], [AuthMiddleware::class]);

// Logout
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
// User token refresh (no AuthMiddleware): CSRF + SameOrigin only
$router->post('/app/token/refresh', [AuthController::class, 'refreshToken'], [SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);

// Suporte (público - disponível para logados e não logados)
$router->get('/support', [SupportController::class, 'index']);
$router->post('/support', [SupportController::class, 'submitTicket'], [CsrfMiddleware::class]);

// Suporte do usuário (logado)
$router->get('/app/support', [SupportController::class, 'myTickets'], [AuthMiddleware::class]);
$router->post('/app/support/reply', [SupportController::class, 'userReply'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Feedback do usuário (logado)
$router->post('/api/feedback/submit', [\App\Controllers\FeedbackController::class, 'submit'], [AuthMiddleware::class, CsrfMiddleware::class]);

// (legacy /admin routes removidos para consolidar em /secure/adm)

// Secure Admin (JWT + Captcha)
$router->get('/secure/adm', [AdminSecureController::class, 'root']);
$router->get('/secure/adm/login', [AdminSecureController::class, 'loginForm']);
$router->post('/secure/adm/login', [AdminSecureController::class, 'login'], [CsrfMiddleware::class]);
$router->post('/secure/adm/logout', [AdminSecureController::class, 'logout'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/secure/adm/index', [AdminSecureController::class, 'index'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/tickets', [AdminSecureController::class, 'tickets'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/users', [AdminSecureController::class, 'users'], [SecureAdminMiddleware::class]);
// Users management
$router->get('/secure/adm/users/view', [AdminSecureController::class, 'viewUser'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/users/edit', [AdminSecureController::class, 'editUser'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/users/update', [AdminSecureController::class, 'updateUser'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/users/delete', [AdminSecureController::class, 'deleteUser'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/secure/adm/users/export', [AdminSecureController::class, 'exportUsers'], [SecureAdminMiddleware::class]);
// Users search API (admin)
$router->get('/api/admin/users/search', [AdminSecureController::class, 'searchUsers'], [SecureAdminMiddleware::class, SameOriginAjaxMiddleware::class]);
// 2FA
$router->get('/secure/adm/2fa', [AdminSecureController::class, 'twoFactorForm']);
$router->post('/secure/adm/2fa', [AdminSecureController::class, 'verifyTwoFactor'], [CsrfMiddleware::class]);
// Token refresh (no middleware; uses CSRF + SameOrigin)
$router->post('/secure/adm/token/refresh', [AdminSecureController::class, 'refreshToken'], [SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);
// CRM
$router->get('/secure/adm/crm', [AdminSecureController::class, 'crm'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/crm/add', [AdminSecureController::class, 'addLead'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
// Feedback API (usado na página de reviews)
$router->get('/api/admin/feedbacks', [\App\Controllers\AdminFeedbackController::class, 'api'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/feedback/promote', [\App\Controllers\AdminFeedbackController::class, 'promote'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
// Admin Status (extended, sensitive)
$router->get('/secure/adm/status', [AdminSecureController::class, 'status'], [SecureAdminMiddleware::class]);

// Secure Admin - Gestão de administradores (admin_users)
$router->get('/secure/adm/admins', [AdminSecureController::class, 'admins'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/admins/create', [AdminSecureController::class, 'createAdmin'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Tickets de suporte (ações)
$router->post('/secure/adm/support/create', [SupportController::class, 'adminCreate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/support/reply', [SupportController::class, 'adminReply'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/support/close', [SupportController::class, 'adminClose'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/support/reopen', [SupportController::class, 'adminReopen'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Gestão de Reviews
$router->get('/secure/adm/reviews', [AdminSecureController::class, 'reviews'], [SecureAdminMiddleware::class]);

// Secure Admin - Aluno: Cursos (CRUD básico)
$router->get('/secure/adm/aluno', [AdminAlunoController::class, 'portal'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/aluno/courses', [AdminAlunoCoursesController::class, 'index'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/aluno/courses/create', [AdminAlunoCoursesController::class, 'create'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/aluno/courses/edit', [AdminAlunoCoursesController::class, 'edit'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/aluno/courses/store', [AdminAlunoCoursesController::class, 'store'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/courses/update', [AdminAlunoCoursesController::class, 'update'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/courses/move', [AdminAlunoCoursesController::class, 'move'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/courses/delete', [AdminAlunoCoursesController::class, 'delete'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
// Secure Admin - Aluno: Acessos (grants por curso/aula)
$router->get('/secure/adm/aluno/access', [AdminAlunoAccessController::class, 'index'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/aluno/access/grant-course', [AdminAlunoAccessController::class, 'grantCourse'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/access/grant-lesson', [AdminAlunoAccessController::class, 'grantLesson'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
// Access management: revoke/extend
$router->post('/secure/adm/aluno/access/revoke-course', [AdminAlunoAccessController::class, 'revokeCourse'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/access/revoke-lesson', [AdminAlunoAccessController::class, 'revokeLesson'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/access/extend-course', [AdminAlunoAccessController::class, 'extendCourse'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/access/extend-lesson', [AdminAlunoAccessController::class, 'extendLesson'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Aluno: Bunny Tools (import/sync, refresh thumbnails)
$router->get('/secure/adm/aluno/bunny', [AdminAlunoBunnyController::class, 'tools'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/aluno/bunny/import-collection', [AdminAlunoBunnyController::class, 'importCollection'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/bunny/import-default', [AdminAlunoBunnyController::class, 'importDefault'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/bunny/refresh-thumbnails', [AdminAlunoBunnyController::class, 'refreshThumbnails'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Aluno: Lessons Management (CRUD + reorder)
$router->get('/secure/adm/aluno/lessons', [AdminAlunoLessonsController::class, 'index'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/aluno/lessons/create', [AdminAlunoLessonsController::class, 'create'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/aluno/lessons/edit', [AdminAlunoLessonsController::class, 'edit'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/store', [AdminAlunoLessonsController::class, 'store'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/update', [AdminAlunoLessonsController::class, 'update'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/delete', [AdminAlunoLessonsController::class, 'delete'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/reorder', [AdminAlunoLessonsController::class, 'reorder'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/toggle', [AdminAlunoLessonsController::class, 'toggleEnabled'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/lessons/set-preview', [AdminAlunoLessonsController::class, 'setPreview'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Aluno: Enrollments panel
$router->get('/secure/adm/aluno/enrollments', [AdminAlunoEnrollmentsController::class, 'index'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/aluno/enrollments/extend', [AdminAlunoEnrollmentsController::class, 'extend'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/enrollments/cancel', [AdminAlunoEnrollmentsController::class, 'cancel'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/aluno/enrollments/reactivate', [AdminAlunoEnrollmentsController::class, 'reactivate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Secure Admin - Gamification (XP System)
$router->get('/secure/adm/gamification', [\App\Controllers\Admin\GamificationController::class, 'index'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/gamification/add-xp', [\App\Controllers\Admin\GamificationController::class, 'addXP'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/gamification/reset-streak', [\App\Controllers\Admin\GamificationController::class, 'resetStreak'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/gamification/recalculate-levels', [\App\Controllers\Admin\GamificationController::class, 'recalculateLevels'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/secure/adm/gamification/user/{id}', [\App\Controllers\Admin\GamificationController::class, 'userDetails'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/gamification/settings', [\App\Controllers\Admin\GamificationController::class, 'settings'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/gamification/settings', [\App\Controllers\Admin\GamificationController::class, 'saveSettings'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Executar todas as migrations do sistema via navegador (somente admin)
$router->get('/secure/adm/run-migrations', [AdminSecureController::class, 'runMigrations'], [SecureAdminMiddleware::class]);

// Secure Admin - Forgot Password flow
$router->get('/secure/adm/forgot', [AdminSecureController::class, 'forgotForm']);
$router->post('/secure/adm/forgot', [AdminSecureController::class, 'forgotRequest'], [CsrfMiddleware::class]);
$router->get('/secure/adm/forgot/verify', [AdminSecureController::class, 'forgotVerifyForm']);
$router->post('/secure/adm/forgot/verify', [AdminSecureController::class, 'forgotVerify'], [CsrfMiddleware::class]);


// Legacy /admin routes removed

// Test/Diagnostics endpoints (locked behind DebugOnlyMiddleware)
$router->get('/test/ping', [TestController::class, 'ping'], [DebugOnlyMiddleware::class]);
$router->get('/test/db', [TestController::class, 'db'], [DebugOnlyMiddleware::class]);
$router->get('/test/session', [TestController::class, 'session'], [DebugOnlyMiddleware::class]);
$router->post('/test/csrf-check', [TestController::class, 'csrfCheck'], [DebugOnlyMiddleware::class, CsrfMiddleware::class]);
$router->get('/test/mail', [DiagnosticsController::class, 'testMail'], [DebugOnlyMiddleware::class]);


// Fear & Greed API (protected - app internal)
$router->get('/api/fg/current', [FearGreedController::class, 'current'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/summary', [FearGreedController::class, 'summary'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/summary/{date}', [FearGreedController::class, 'summary'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/date/{date}', [FearGreedController::class, 'byDate'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/historical', [FearGreedController::class, 'historical'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/indicator/{indicator}', [FearGreedController::class, 'indicator'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/indicator/{indicator}/{date}', [FearGreedController::class, 'indicator'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
// V2 endpoints
$router->get('/api/fg/indicators', [FearGreedController::class, 'indicators'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/sync-status', [FearGreedController::class, 'syncStatus'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/uptime', [FearGreedController::class, 'uptime'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->get('/api/fg/indicators/historical', [FearGreedController::class, 'indicatorsHistorical'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
$router->delete('/api/fg/cache/{key}', [FearGreedController::class, 'cache'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);

// US Market Barometer internal API (secure proxy)
$router->get('/api/usmb/data', [UsMarketBarometerController::class, 'data'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
// FedWatch API (secure proxy)
$router->get('/api/fed/probabilities', [FedController::class, 'probabilities'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);
// Brasil Trends API removed (using Google Trends embeds)
// ORM internal API (secure, normalized 8 assets)
$router->get('/api/orm', [OrmController::class, 'data'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);

// Legal redirects (public)
$router->get('/terms', function(){ header('Location: https://group.operebem.com.br/terminal/terms-of-use', true, 302); exit; });
$router->get('/privacy', function(){ header('Location: https://group.operebem.com.br/terminal/privacy-policy', true, 302); exit; });
$router->get('/risk', function(){ header('Location: https://group.operebem.com.br/terminal/risk-advice', true, 302); exit; });

// OpereBem Captcha SDK & API (served from vendor/operebem/captcha)
$router->get('/sdk/captcha-sdk.js', function(){
    $base = __DIR__ . '/../vendor/operebem/captcha';
    $file = $base . '/src/Assets/js/captcha-sdk.js';
    if (is_file($file)) {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }
    http_response_code(404); echo 'Not found'; exit;
});

$router->get('/embed.html', [CaptchaController::class, 'embed']);

$router->get('/api/generate', [CaptchaController::class, 'generate']);

$router->post('/api/verify', [CaptchaController::class, 'verify']);

$router->get('/api/config', [CaptchaController::class, 'config']);

$router->get('/captcha/cache/{img}', function($params){
    $img = basename((string)($params['img'] ?? ''));
    $base = __DIR__ . '/../vendor/operebem/captcha';
    $file = $base . '/public/cache/' . $img;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $ct = 'image/png';
        if ($ext === 'jpg' || $ext === 'jpeg') { $ct = 'image/jpeg'; }
        elseif ($ext === 'gif') { $ct = 'image/gif'; }
        elseif ($ext === 'webp') { $ct = 'image/webp'; }
        header('Content-Type: ' . $ct);
        header('Cache-Control: public, max-age=300');
        readfile($file);
        exit;
    }
    http_response_code(404); echo 'Not found'; exit;
});

$router->get('/images/{theme}/{img}', function($params){
    $theme = preg_replace('/[^a-z]/', '', (string)($params['theme'] ?? ''));
    $img = basename((string)($params['img'] ?? ''));
    $base = __DIR__ . '/../vendor/operebem/captcha';
    $file = $base . '/src/Assets/images/' . $theme . '/' . $img;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $ct = 'image/png';
        if ($ext === 'jpg' || $ext === 'jpeg') { $ct = 'image/jpeg'; }
        elseif ($ext === 'gif') { $ct = 'image/gif'; }
        elseif ($ext === 'webp') { $ct = 'image/webp'; }
        header('Content-Type: ' . $ct);
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }
    http_response_code(404); echo 'Not found'; exit;
});

// ============================================================================
// DISCORD BOT API
// ============================================================================

// Discord Bot API (protegido por API Key no header X-API-KEY)
$router->post('/api/discord/verify', [\App\Controllers\Api\DiscordApiController::class, 'verify']);
$router->post('/api/discord/sync-xp', [\App\Controllers\Api\DiscordApiController::class, 'syncXP']);
$router->post('/api/discord/award-xp', [\App\Controllers\Api\DiscordApiController::class, 'awardXP']);
$router->get('/api/discord/user/{discord_id}', [\App\Controllers\Api\DiscordApiController::class, 'getUser']);
$router->get('/api/discord/verified-users', [\App\Controllers\Api\DiscordApiController::class, 'getVerifiedUsers']);
$router->get('/api/discord/stats', [\App\Controllers\Api\DiscordApiController::class, 'getStats']);
$router->post('/api/discord/log', [\App\Controllers\Api\DiscordApiController::class, 'log']);
$router->get('/api/discord/ping', [\App\Controllers\Api\DiscordApiController::class, 'ping']);
$router->get('/api/discord/message-xp-config', [\App\Controllers\Api\DiscordApiController::class, 'getMessageXPConfig']);

// ============================================================================
// XP API (Aulas e Cursos)
// ============================================================================
// Protegido por X-API-KEY (ALUNO_API_KEY)
$router->post('/api/xp/lesson-completed', [\App\Controllers\Api\XPApiController::class, 'lessonCompleted']);
$router->post('/api/xp/course-completed', [\App\Controllers\Api\XPApiController::class, 'courseCompleted']);

// ============================================================================
// REVIEWS API
// ============================================================================

// Public Reviews API (lista reviews ativos)
$router->get('/api/reviews', [ReviewsController::class, 'index']);
$router->get('/api/reviews/{id}', [ReviewsController::class, 'show']);

// Admin Reviews API (CRUD completo - requer autenticação admin)
$router->get('/api/admin/reviews', [AdminReviewsController::class, 'index'], [SecureAdminMiddleware::class]);
$router->get('/api/admin/reviews/{id}', [AdminReviewsController::class, 'show'], [SecureAdminMiddleware::class]);
$router->post('/api/admin/reviews', [AdminReviewsController::class, 'create'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->put('/api/admin/reviews/{id}', [AdminReviewsController::class, 'update'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/admin/reviews/{id}', [AdminReviewsController::class, 'delete'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/admin/reviews/{id}/toggle', [AdminReviewsController::class, 'toggle'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/admin/reviews/reorder', [AdminReviewsController::class, 'reorder'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/reviews/upload-avatar', [AdminReviewsController::class, 'uploadAvatar'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// ============================================================================
// MARKET CLOCK API
// ============================================================================

// Public Market Clock API (status das bolsas)
$router->get('/api/market-clock', [MarketClockController::class, 'index']);
$router->get('/api/market-clock/all', [MarketClockController::class, 'all']);
$router->get('/api/market-clock/{code}', [MarketClockController::class, 'show']);
// Admin-only: atualizar status e gravar em tabela clock (pode ser acionado por botão no admin ou cron interno)
$router->post('/api/market-clock/update-statuses', [MarketClockController::class, 'updateStatuses'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

return $router;
