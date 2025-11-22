[1mdiff --git a/routes/web.php b/routes/web.php[m
[1mindex d48f82a..3137ad4 100644[m
[1m--- a/routes/web.php[m
[1m+++ b/routes/web.php[m
[36m@@ -63,6 +63,10 @@[m [m$router->get('/aluno/courses', function(){[m
 });[m
 // CSP report collection endpoint[m
 $router->post('/csp-report', [SecurityController::class, 'cspReport']);[m
[32m+[m[32m// Client logs from browser (Same-Origin only)[m
[32m+[m[32m$router->post('/api/client-log', [SecurityController::class, 'clientLog'], [SameOriginAjaxMiddleware::class]);[m
[32m+[m[32m// Gold dashboard data endpoint[m
[32m+[m[32m$router->post('/api/quotes/gold-boot', [QuotesController::class, 'goldBoot'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);[m
 [m
 // Rotas de autenticação (POST)[m
 $router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, SameOriginAjaxMiddleware::class, CsrfMiddleware::class]);[m
[36m@@ -82,6 +86,8 @@[m [m$router->post('/register/reenviar-codigo', [AuthController::class, 'reenviarCodi[m
 [m
 // Dashboard routes (protected)[m
 $router->get('/app/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);[m
[32m+[m[32m// Dashboard Gold (protected)[m
[32m+[m[32m$router->get('/app/dashboard/gold', [DashboardController::class, 'gold'], [AuthMiddleware::class]);[m
 // SSO start (public): se não autenticado, envia para modal de login; se autenticado, emite token e redireciona para o Portal do Aluno[m
 $router->get('/sso/start', [SsoController::class, 'start']);[m
 // Compat: redirecionar antigo /dashboard -> /app/dashboard (301)[m
[36m@@ -131,6 +137,9 @@[m [m$router->post('/actions/boot.php', [QuotesController::class, 'boot'], [AuthMiddl[m
 $router->post('/actions/quotes-public', [QuotesController::class, 'listarPublic'], [SameOriginAjaxMiddleware::class]);[m
 $router->get('/actions/quotes-public', [QuotesController::class, 'listarPublic'], [SameOriginAjaxMiddleware::class]);[m
 [m
[32m+[m[32m// Endpoint protegido específico do Dashboard Ouro (somente ativos necessários)[m
[32m+[m[32m$router->post('/actions/gold-boot.php', [QuotesController::class, 'goldBoot'], [AuthMiddleware::class, SameOriginAjaxMiddleware::class]);[m
[32m+[m
 $router->post('/app/profile/preferences', [ProfileController::class, 'updatePreferences'], [AuthMiddleware::class, CsrfMiddleware::class]);[m
 $router->post('/app/profile/change-password', [ProfileController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);[m
 // Upload avatar[m
[1mdiff --git a/src/Controllers/DashboardController.php b/src/Controllers/DashboardController.php[m
[1mindex 871d8d4..700adfe 100644[m
[1m--- a/src/Controllers/DashboardController.php[m
[1m+++ b/src/Controllers/DashboardController.php[m
[36m@@ -26,6 +26,20 @@[m [mclass DashboardController extends BaseController[m
         ]);[m
     }[m
 [m
[32m+[m[32m    public function gold(): void[m
[32m+[m[32m    {[m
[32m+[m[32m        $user = $this->authService->getCurrentUser();[m
[32m+[m[32m        $wsConfig = [[m
[32m+[m[32m            'url' => $_ENV['WS_PROXY_URL'] ?? 'ws://vps1.operebem.com:8765',[m
[32m+[m[32m            'key' => $_ENV['WS_PROXY_KEY'] ?? ''[m
[32m+[m[32m        ];[m
[32m+[m
[32m+[m[32m        $this->view('app/dashboard-gold', [[m
[32m+[m[32m            'user' => $user,[m
[32m+[m[32m            'ws_config' => $wsConfig[m
[32m+[m[32m        ]);[m
[32m+[m[32m    }[m
[32m+[m
     private function getQuotesData(): array[m
     {[m
         try {[m
[1mdiff --git a/src/Controllers/QuotesController.php b/src/Controllers/QuotesController.php[m
[1mindex dbd326b..65be135 100644[m
[1m--- a/src/Controllers/QuotesController.php[m
[1m+++ b/src/Controllers/QuotesController.php[m
[36m@@ -53,6 +53,110 @@[m [mclass QuotesController[m
         }[m
     }[m
 [m
[32m+[m[32m    /**[m
[32m+[m[32m     * Endpoint protegido específico do Dashboard Ouro.[m
[32m+[m[32m     * Retorna apenas os ativos necessários (gold, dxy, us10y, vix),[m
[32m+[m[32m     * com campos sanitizados e porcentagem unificada.[m
[32m+[m[32m     */[m
[32m+[m[32m    public function goldBoot(): void[m
[32m+[m[32m    {[m
[32m+[m[32m        header('Content-Type: application/json');[m
[32m+[m[32m        try {[m
[32m+[m[32m            $app = Application::getInstance();[m
[32m+[m[32m            // Preferências ampliadas incluindo id_api específicos informados e GC futures[m
[32m+[m[32m            $preferred = [[m
[32m+[m[32m                // Ouro principal[m
[32m+[m[32m                '8830','GOLD','XAUUSD','XAU/USD','GOLDUSD','GC1!','TVC:GOLD',[m
[32m+[m[32m                // DXY[m
[32m+[m[32m                '1224074','DXY','TVC:DXY','DX-Y.NYB','ICEUS:DXY',[m
[32m+[m[32m                // US10Y[m
[32m+[m[32m                'US10Y','^TNX','UST10Y',[m
[32m+[m[32m                // VIX (SPVIX informado)[m
[32m+[m[32m                '44336','SPVIX','VIX','^VIX',[m
[32m+[m[32m                // GVZ[m
[32m+[m[32m                'GVZ','^GVZ','GVOL','GOLD VOLATILITY',[m
[32m+[m[32m                // GC futures grid[m
[32m+[m[32m                '1178340','1178341','1178342','1193189','1193190','1213656','1213657','GC2!','GC3!','GC4!','GC5!','GC6!','GC7!'[m
[32m+[m[32m            ];[m
[32m+[m[32m            $subset = $this->quotesService->getByIdsOrCodes($preferred);[m
[32m+[m[32m            $subset = is_array($subset) ? $subset : [];[m
[32m+[m
[32m+[m[32m            $norm = function($v){ return strtoupper(trim((string)$v)); };[m
[32m+[m[32m            $match = function(array $list, array $spec) use ($norm) {[m
[32m+[m[32m                $codes = array_map($norm, $spec['codes'] ?? []);[m
[32m+[m[32m                $names = array_map($norm, $spec['names'] ?? []);[m
[32m+[m[32m                $keywords = array_map($norm, $spec['keywords'] ?? []);[m
[32m+[m[32m                $best = null;[m
[32m+[m[32m                foreach ($list as $it) {[m
[32m+[m[32m                    $code = $norm($it['code'] ?? '');[m
[32m+[m[32m                    $id   = $norm($it['id_api'] ?? '');[m
[32m+[m[32m                    $nome = $norm($it['nome'] ?? '');[m
[32m+[m[32m                    $ap   = $norm($it['apelido'] ?? '');[m
[32m+[m[32m                    if (in_array($code, $codes, true) || in_array($id, $codes, true)) return $it;[m
[32m+[m[32m                    if ($best === null && ($nome !== '' || $ap !== '')) {[m
[32m+[m[32m                        foreach ($names as $n) { if ($n !== '' && (str_contains($nome, $n) || str_contains($ap, $n))) { $best = $it; break; } }[m
[32m+[m[32m                        if ($best === null) {[m
[32m+[m[32m                            foreach ($keywords as $k) { if ($k !== '' && (str_contains($nome, $k) || str_contains($ap, $k))) { $best = $it; break; } }[m
[32m+[m[32m                        }[m
[32m+[m[32m                    }[m
[32m+[m[32m                }[m
[32m+[m[32m                if ($best !== null) return $best;[m
[32m+[m[32m                foreach ($list as $it) {[m
[32m+[m[32m                    $code = $norm($it['code'] ?? '');[m
[32m+[m[32m                    foreach ($codes as $c) { if ($c !== '' && str_contains($code, $c)) return $it; }[m
[32m+[m[32m                }[m
[32m+[m[32m                return null;[m
[32m+[m[32m            };[m
[32m+[m
[32m+[m[32m            $targets = [[m
[32m+[m[32m                'gold'  => ['codes' => ['8830','XAUUSD','XAU/USD','GOLD','GC1!','TVC:GOLD'], 'names' => ['OURO','GOLD'], 'keywords' => ['OURO','GOLD']],[m
[32m+[m[32m                'dxy'   => ['codes' => ['1224074','DXY','TVC:DXY','DX-Y.NYB','ICEUS:DXY'], 'names' => ['DXY','DOLLAR INDEX'], 'keywords' => ['DOLLAR','DÓLAR','INDEX']],[m
[32m+[m[32m                'us10y' => ['codes' => ['US10Y','^TNX','UST10Y'], 'names' => ['10Y','TREASURY'], 'keywords' => ['10Y','TREASURY']],[m
[32m+[m[32m                'vix'   => ['codes' => ['44336','SPVIX','VIX','^VIX'], 'names' => ['VIX'], 'keywords' => ['VOLATILITY','VIX']],[m
[32m+[m[32m                'gvz'   => ['codes' => ['GVZ','^GVZ','GVOL'], 'names' => ['GVZ','GOLD VOLATILITY'], 'keywords' => ['GOLD','VOLATILITY','GVZ']],[m
[32m+[m[32m            ];[m
[32m+[m
[32m+[m[32m            $pick = [];[m
[32m+[m[32m            foreach ($targets as $key => $spec) {[m
[32m+[m[32m                $item = $match($subset, $spec);[m
[32m+[m[32m                if ($item === null) {[m
[32m+[m[32m                    // fallback: buscar em toda a tabela se não veio na query filtrada[m
[32m+[m[32m                    try {[m
[32m+[m[32m                        $all = $this->quotesService->getAllQuotes();[m
[32m+[m[32m                        $item = $match(is_array($all) ? $all : [], $spec);[m
[32m+[m[32m                    } catch (\Throwable $t) { $item = null; }[m
[32m+[m[32m                }[m
[32m+[m[32m                $pick[$key] = $item ? $this->unifyQuote($this->sanitizeGoldRow($item)) : null;[m
[32m+[m[32m            }[m
[32m+[m
[32m+[m[32m            try { $app->logger()->info('[QUOTES] goldBoot', [ 'found' => array_map(fn($v)=> $v ? ($v['code'] ?? ($v['id_api'] ?? '')) : null, $pick) ]); } catch (\Throwable $t) {}[m
[32m+[m
[32m+[m[32m            // GC futures: GC1! ... GC7! por id_api[m
[32m+[m[32m            $futuresIds = ['1178340','1178341','1178342','1193189','1193190','1213656','1213657'];[m
[32m+[m[32m            $futures = [];[m
[32m+[m[32m            try {[m
[32m+[m[32m                $gc = $this->quotesService->getByIdsOrCodes($futuresIds);[m
[32m+[m[32m                $gc = is_array($gc) ? $gc : [];[m
[32m+[m[32m                foreach ($gc as $row) {[m
[32m+[m[32m                    $futures[] = $this->unifyQuote($this->sanitizeGoldRow($row));[m
[32m+[m[32m                }[m
[32m+[m[32m            } catch (\Throwable $t) { $futures = []; }[m
[32m+[m
[32m+[m[32m            // média de last_numeric quando disponível[m
[32m+[m[32m            $sum = 0.0; $cnt = 0;[m
[32m+[m[32m            foreach ($futures as $r) {[m
[32m+[m[32m                $ln = $r['last_numeric'] ?? null;[m
[32m+[m[32m                if (is_numeric($ln)) { $sum += (float)$ln; $cnt++; }[m
[32m+[m[32m                elseif (isset($r['last']) && is_numeric($r['last'])) { $sum += (float)$r['last']; $cnt++; }[m
[32m+[m[32m            }[m
[32m+[m[32m            $avg = $cnt > 0 ? round($sum / $cnt, 2) : null;[m
[32m+[m
[32m+[m[32m            echo json_encode(['data' => $pick, 'futures' => $futures, 'futures_avg' => $avg, 'error' => '']);[m
[32m+[m[32m        } catch (\Throwable $e) {[m
[32m+[m[32m            echo json_encode(['error' => $e->getMessage()]);[m
[32m+[m[32m        }[m
[32m+[m[32m    }[m
[32m+[m
     private function sanitizeRow(array $row): array[m
     {[m
         $allowed = [[m
[36m@@ -97,4 +201,38 @@[m [mclass QuotesController[m
             echo json_encode(['error' => $e->getMessage()]);[m
         }[m
     }[m
[32m+[m
[32m+[m[32m    private function unifyQuote(array $row): array[m
[32m+[m[32m    {[m
[32m+[m[32m        $row = is_array($row) ? $row : [];[m
[32m+[m[32m        $pc = $row['pc'] ?? null;[m
[32m+[m[32m        if ($pc === null || $pc === '' || !is_numeric($pc)) {[m
[32m+[m[32m            $last = $row['last'] ?? ($row['last_numeric'] ?? null);[m
[32m+[m[32m            $close = $row['last_close'] ?? null;[m
[32m+[m[32m            if (is_numeric($last) && is_numeric($close) && (float)$close != 0.0) {[m
[32m+[m[32m                $pc = ((float)$last - (float)$close) / (float)$close * 100.0;[m
[32m+[m[32m            }[m
[32m+[m[32m        }[m
[32m+[m[32m        if (is_numeric($pc)) {[m
[32m+[m[32m            $row['pc'] = round((float)$pc, 2);[m
[32m+[m[32m        }[m
[32m+[m[32m        return $row;[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    private function sanitizeGoldRow(array $row): array[m
[32m+[m[32m    {[m
[32m+[m[32m        // Campos permitidos — sem id_api, grupo, order_tabela[m
[32m+[m[32m        $allowed = [[m
[32m+[m[32m            'code','apelido','nome',[m
[32m+[m[32m            'last','pc','pcp','last_close','high','low','bid','ask','last_numeric','last_dir','pc_col',[m
[32m+[m[32m            'turnover','turnover_numeric',[m
[32m+[m[32m            'timestamp','time_utc','status_mercado','status_hr',[m
[32m+[m[32m            'icone_bandeira','bandeira','bolsa'[m
[32m+[m[32m        ];[m
[32m+[m[32m        $san = [];[m
[32m+[m[32m        foreach ($allowed as $k) {[m
[32m+[m[32m            if (array_key_exists($k, $row)) $san[$k] = $row[$k];[m
[32m+[m[32m        }[m
[32m+[m[32m        return $san;[m
[32m+[m[32m    }[m
 }[m
[1mdiff --git a/src/Controllers/SecurityController.php b/src/Controllers/SecurityController.php[m
[1mindex 71d8e49..9b13f4a 100644[m
[1m--- a/src/Controllers/SecurityController.php[m
[1m+++ b/src/Controllers/SecurityController.php[m
[36m@@ -2,6 +2,8 @@[m
 [m
 namespace App\Controllers;[m
 [m
[32m+[m[32muse App\Core\Application;[m
[32m+[m
 class SecurityController extends BaseController[m
 {[m
     // Retorna o token CSRF atual; se inexistente, gera um novo[m
[36m@@ -50,4 +52,46 @@[m [mclass SecurityController extends BaseController[m
         echo json_encode(['ok' => true]);[m
         exit;[m
     }[m
[32m+[m
[32m+[m[32m    // Recebe logs do cliente (JS) e envia ao Monolog[m
[32m+[m[32m    public function clientLog(): void[m
[32m+[m[32m    {[m
[32m+[m[32m        // Apenas JSON[m
[32m+[m[32m        $raw = file_get_contents('php://input') ?: '';[m
[32m+[m[32m        $payload = [];[m
[32m+[m[32m        try { $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $t) { $payload = []; }[m
[32m+[m[32m        $level = strtolower((string)($payload['level'] ?? 'error'));[m
[32m+[m[32m        $message = (string)($payload['message'] ?? '');[m
[32m+[m[32m        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];[m
[32m+[m
[32m+[m[32m        $levels = [[m
[32m+[m[32m            'debug' => \Monolog\Logger::DEBUG,[m
[32m+[m[32m            'info' => \Monolog\Logger::INFO,[m
[32m+[m[32m            'notice' => \Monolog\Logger::NOTICE,[m
[32m+[m[32m            'warning' => \Monolog\Logger::WARNING,[m
[32m+[m[32m            'error' => \Monolog\Logger::ERROR,[m
[32m+[m[32m            'critical' => \Monolog\Logger::CRITICAL,[m
[32m+[m[32m            'alert' => \Monolog\Logger::ALERT,[m
[32m+[m[32m            'emergency' => \Monolog\Logger::EMERGENCY,[m
[32m+[m[32m        ];[m
[32m+[m[32m        $levelConst = $levels[$level] ?? \Monolog\Logger::ERROR;[m
[32m+[m
[32m+[m[32m        $ctx = [[m
[32m+[m[32m            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',[m
[32m+[m[32m            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',[m
[32m+[m[32m            'path' => $_SERVER['REQUEST_URI'] ?? '',[m
[32m+[m[32m            'meta' => $meta,[m
[32m+[m[32m        ];[m
[32m+[m[32m        try {[m
[32m+[m[32m            $app = Application::getInstance();[m
[32m+[m[32m            $app->logger()->log($levelConst, '[client] ' . $message, $ctx);[m
[32m+[m[32m        } catch (\Throwable $t) {[m
[32m+[m[32m            // ignore[m
[32m+[m[32m        }[m
[32m+[m
[32m+[m[32m        http_response_code(204);[m
[32m+[m[32m        header('Content-Type: application/json');[m
[32m+[m[32m        echo json_encode(['ok' => true]);[m
[32m+[m[32m        exit;[m
[32m+[m[32m    }[m
 }[m
[1mdiff --git a/src/Middleware/SecurityHeadersMiddleware.php b/src/Middleware/SecurityHeadersMiddleware.php[m
[1mindex 5795790..d4dad2c 100644[m
[1m--- a/src/Middleware/SecurityHeadersMiddleware.php[m
[1m+++ b/src/Middleware/SecurityHeadersMiddleware.php[m
[36m@@ -102,7 +102,8 @@[m [mclass SecurityHeadersMiddleware[m
             // 'self' = mesmo domínio[m
             // 'nonce-xxx' = scripts inline com nonce específico[m
             // cdn.jsdelivr.net = Bootstrap, FontAwesome[m
[31m-            "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net https://cdn.jsdelivr.net",[m
[32m+[m[32m            // s3.tradingview.com = TradingView widgets[m
[32m+[m[32m            "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net https://cdn.jsdelivr.net s3.tradingview.com https://s3.tradingview.com",[m
             [m
             // style-src: fontes permitidas para CSS[m
             // 'unsafe-inline' = necessário para Bootstrap e estilos inline[m
[36m@@ -114,10 +115,15 @@[m [mclass SecurityHeadersMiddleware[m
             // img-src: fontes permitidas para imagens[m
             // data: = imagens base64[m
             // https: = qualquer imagem HTTPS (avatars, etc)[m
[31m-            "img-src 'self' data: https: http://localhost:* i.pravatar.cc",[m
[32m+[m[32m            // *.tradingview.com e *.tradingview-widget.com = logos/imagens dos widgets[m
[32m+[m[32m            "img-src 'self' data: https: http://localhost:* i.pravatar.cc *.tradingview.com *.tradingview-widget.com",[m
             [m
             // connect-src: fontes permitidas para AJAX, WebSocket, EventSource[m
[31m-            "connect-src 'self'",[m
[32m+[m[32m            // *.tradingview.com e *.tradingview-widget.com = data feeds e assets dos widgets[m
[32m+[m[32m            "connect-src 'self' https://*.tradingview.com wss://*.tradingview.com https://*.tradingview-widget.com wss://*.tradingview-widget.com",[m
[32m+[m
[32m+[m[32m            // frame-src: iframes permitidos (TradingView widgets)[m
[32m+[m[32m            "frame-src 'self' https://*.tradingview.com https://*.tradingview-widget.com",[m
             [m
             // frame-ancestors: quem pode embedar esta página em iframe[m
             // 'none' = ninguém (equivalente a X-Frame-Options: DENY)[m
[1mdiff --git a/src/Views/layouts/app.php b/src/Views/layouts/app.php[m
[1mindex 351aa42..770d5b6 100644[m
[1m--- a/src/Views/layouts/app.php[m
[1m+++ b/src/Views/layouts/app.php[m
[36m@@ -432,8 +432,14 @@[m
                         </button>[m
                     </li>[m
                     <?php endif; ?>[m
[31m-                    <li class="nav-item">[m
[31m-                        <a class="nav-link" href="/app/dashboard">Dashboard</a>[m
[32m+[m[32m                    <li class="nav-item dropdown">[m
[32m+[m[32m                        <a class="nav-link dropdown-toggle" href="/app/dashboard" id="navDashboard" role="button" data-bs-toggle="dropdown">[m
[32m+[m[32m                            Dashboard[m
[32m+[m[32m                        </a>[m
[32m+[m[32m                        <ul class="dropdown-menu" aria-labelledby="navDashboard">[m
[32m+[m[32m                            <li><a class="dropdown-item" href="/app/dashboard">Principal</a></li>[m
[32m+[m[32m                            <li><a class="dropdown-item" href="/app/dashboard/gold">Ouro</a></li>[m
[32m+[m[32m                        </ul>[m
                     </li>[m
                     <li class="nav-item dropdown">[m
                         <a class="nav-link dropdown-toggle" href="/app/indicators/feeling" id="navIndicators" role="button" data-bs-toggle="dropdown">[m
[36m@@ -525,6 +531,10 @@[m
                     <i class="fas fa-chart-line me-3" style="width: 20px;"></i>[m
                     <span>Dashboard</span>[m
                 </a>[m
[32m+[m[32m                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/dashboard/gold">[m
[32m+[m[32m                    <i class="fas fa-coins me-3" style="width: 20px;"></i>[m
[32m+[m[32m                    <span>Dashboard Ouro</span>[m
[32m+[m[32m                </a>[m
                 [m
                 <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/news">[m
                     <i class="fas fa-newspaper me-3" style="width: 20px;"></i>[m
[1mdiff --git a/storage/cache/fear_greed/2f73af9fa3e0d936f7bf22400c0cb25b6389c344.json b/storage/cache/fear_greed/2f73af9fa3e0d936f7bf22400c0cb25b6389c344.json[m
[1mindex 5dbd46f..c64876c 100644[m
[1m--- a/storage/cache/fear_greed/2f73af9fa3e0d936f7bf22400c0cb25b6389c344.json[m
[1m+++ b/storage/cache/fear_greed/2f73af9fa3e0d936f7bf22400c0cb25b6389c344.json[m
[36m@@ -1 +1 @@[m
[31m-{"success":true,"data":{"current":{"id":27,"date":"2025-11-19","timestamp":1763578659,"score":11.13,"rating":"extreme fear","created_at":"2025-11-19 06:00:02","updated_at":"2025-11-19 19:00:03","previous_close":10.67,"previous_1_week":32.63,"previous_1_month":28.81,"previous_1_year":49.51},"metadata":{"source":"local_database","version":"v2","cached":false,"cache_ttl":300,"last_updated":"2025-11-19 19:00:03"}}}[m
\ No newline at end of file[m
[32m+[m[32m{"success":true,"data":{"current":{"id":29,"date":"2025-11-21","timestamp":1763744120,"score":8.74,"rating":"extreme fear","created_at":"2025-11-21 06:00:02","updated_at":"2025-11-21 17:00:02","previous_close":6.44,"previous_1_week":21.47,"previous_1_month":28.69,"previous_1_year":56.6},"metadata":{"source":"local_database","version":"v2","cached":false,"cache_ttl":300,"last_updated":"2025-11-21 17:00:02"}}}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json b/storage/cache/quotes_public_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[1mindex 74ccad5..fb930bf 100644[m
[1m--- a/storage/cache/quotes_public_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[1m+++ b/storage/cache/quotes_public_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763571933,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763646521,"c":3}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json b/storage/cache/quotes_public_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[1mindex 6ae9c6b..9437f79 100644[m
[1m--- a/storage/cache/quotes_public_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[1m+++ b/storage/cache/quotes_public_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1762516307,"c":5}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763734742,"c":10}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json b/storage/cache/quotes_public_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[1mindex 8377852..95c56cd 100644[m
[1m--- a/storage/cache/quotes_public_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[1m+++ b/storage/cache/quotes_public_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1762527256,"c":11}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763734767,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json b/storage/cache/quotes_public_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[1mindex 88423d1..7c6fc5a 100644[m
[1m--- a/storage/cache/quotes_public_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[1m+++ b/storage/cache/quotes_public_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763596748,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763679901,"c":2}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json b/storage/cache/quotes_public_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[1mindex 74ccad5..7e16d21 100644[m
[1m--- a/storage/cache/quotes_public_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[1m+++ b/storage/cache/quotes_public_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763571933,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763646537,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json b/storage/cache/quotes_public_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[1mindex b4fea85..4f8f0b0 100644[m
[1m--- a/storage/cache/quotes_public_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[1m+++ b/storage/cache/quotes_public_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763580433,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763834195,"c":3}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json b/storage/cache/quotes_public_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[1mindex 54d788f..3f0748e 100644[m
[1m--- a/storage/cache/quotes_public_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[1m+++ b/storage/cache/quotes_public_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763580371,"c":10}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763736421,"c":2}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/84a9366f983c53f5c6fd2e012b3adb33e1fcbc26.json b/storage/cache/quotes_public_rl/84a9366f983c53f5c6fd2e012b3adb33e1fcbc26.json[m
[1mindex 33f33ed..56bd5d2 100644[m
[1m--- a/storage/cache/quotes_public_rl/84a9366f983c53f5c6fd2e012b3adb33e1fcbc26.json[m
[1m+++ b/storage/cache/quotes_public_rl/84a9366f983c53f5c6fd2e012b3adb33e1fcbc26.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1762651384,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763643915,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/97465fd7d779bc17156e44fa17dfeb2c541af2e2.json b/storage/cache/quotes_public_rl/97465fd7d779bc17156e44fa17dfeb2c541af2e2.json[m
[1mindex fd09c02..4f8f0b0 100644[m
[1m--- a/storage/cache/quotes_public_rl/97465fd7d779bc17156e44fa17dfeb2c541af2e2.json[m
[1m+++ b/storage/cache/quotes_public_rl/97465fd7d779bc17156e44fa17dfeb2c541af2e2.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763599910,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763834195,"c":3}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json b/storage/cache/quotes_public_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[1mindex 6d4b2dc..b0a21e7 100644[m
[1m--- a/storage/cache/quotes_public_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[1m+++ b/storage/cache/quotes_public_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763599906,"c":2}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763729296,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/bbc1460bda77f59d055389020791627942db03d8.json b/storage/cache/quotes_public_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[1mindex 399c540..ff546a7 100644[m
[1m--- a/storage/cache/quotes_public_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[1m+++ b/storage/cache/quotes_public_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763596517,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763834393,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/e25c32df874c379f0e026766a986f1b353b282a0.json b/storage/cache/quotes_public_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[1mindex d3d6f9f..6859bdd 100644[m
[1m--- a/storage/cache/quotes_public_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[1m+++ b/storage/cache/quotes_public_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763531116,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763682330,"c":3}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/quotes_public_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json b/storage/cache/quotes_public_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[1mindex 91476aa..5f9c8ab 100644[m
[1m--- a/storage/cache/quotes_public_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[1m+++ b/storage/cache/quotes_public_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763599906,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763837170,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/us_market_barometer/usmb_v1_current.json b/storage/cache/us_market_barometer/usmb_v1_current.json[m
[1mindex 72a0c77..d98c016 100644[m
[1m--- a/storage/cache/us_market_barometer/usmb_v1_current.json[m
[1m+++ b/storage/cache/us_market_barometer/usmb_v1_current.json[m
[36m@@ -1 +1 @@[m
[31m-{"success":true,"data":{"date":"2025-11-20","scraped_at":"2025-11-20 00:00:02","data":{"Large":{"Core":{"10y":13.37615,"1d":0.4999,"1m":-1.92649,"1y":6.45144,"3m":0.51446,"3y":16.0204,"5y":13.10441},"Growth":{"10y":14.74592,"1d":0.4641,"1m":-3.15027,"1y":19.43284,"3m":-0.53856,"3y":27.38516,"5y":10.78989},"Value":{"10y":10.97546,"1d":-0.1992,"1m":0.65873,"1y":9.66104,"3m":4.7666,"3y":13.21726,"5y":13.48121}},"Mid":{"Core":{"10y":9.89929,"1d":-0.1214,"1m":-2.56171,"1y":-0.00198,"3m":-0.83799,"3y":9.17766,"5y":9.2503},"Growth":{"10y":11.9478,"1d":0.2736,"1m":-4.50909,"1y":4.11575,"3m":-3.91783,"3y":15.51225,"5y":6.24058},"Value":{"10y":9.95892,"1d":-0.7485,"1m":0.83081,"1y":4.55383,"3m":2.31444,"3y":10.53904,"5y":12.52903}},"Small":{"Core":{"10y":8.75089,"1d":0.3069,"1m":-1.86259,"1y":-0.1149,"3m":0.5657,"3y":10.16856,"5y":9.25758},"Growth":{"10y":8.82029,"1d":0.5241,"1m":-3.88819,"1y":5.72122,"3m":0.27158,"3y":14.33453,"5y":3.12235},"Value":{"10y":8.22914,"1d":-0.1256,"1m":-0.45678,"1y":1.9964,"3m":1.76297,"3y":8.30008,"5y":12.27284}}},"raw":{"data":{"Large":{"Core":{"10y":13.37615,"1d":0.4999,"1m":-1.92649,"1y":6.45144,"3m":0.51446,"3y":16.0204,"5y":13.10441},"Growth":{"10y":14.74592,"1d":0.4641,"1m":-3.15027,"1y":19.43284,"3m":-0.53856,"3y":27.38516,"5y":10.78989},"Value":{"10y":10.97546,"1d":-0.1992,"1m":0.65873,"1y":9.66104,"3m":4.7666,"3y":13.21726,"5y":13.48121}},"Mid":{"Core":{"10y":9.89929,"1d":-0.1214,"1m":-2.56171,"1y":-0.00198,"3m":-0.83799,"3y":9.17766,"5y":9.2503},"Growth":{"10y":11.9478,"1d":0.2736,"1m":-4.50909,"1y":4.11575,"3m":-3.91783,"3y":15.51225,"5y":6.24058},"Value":{"10y":9.95892,"1d":-0.7485,"1m":0.83081,"1y":4.55383,"3m":2.31444,"3y":10.53904,"5y":12.52903}},"Small":{"Core":{"10y":8.75089,"1d":0.3069,"1m":-1.86259,"1y":-0.1149,"3m":0.5657,"3y":10.16856,"5y":9.25758},"Growth":{"10y":8.82029,"1d":0.5241,"1m":-3.88819,"1y":5.72122,"3m":0.27158,"3y":14.33453,"5y":3.12235},"Value":{"10y":8.22914,"1d":-0.1256,"1m":-0.45678,"1y":1.9964,"3m":1.76297,"3y":8.30008,"5y":12.27284}}},"scraped_at":"2025-11-20 00:00:02"},"metadata":{"source":"local_database","cached":false,"cache_ttl":300,"last_updated":"2025-11-20 00:00:05"}}}[m
\ No newline at end of file[m
[32m+[m[32m{"success":true,"data":{"date":"2025-11-22","scraped_at":"2025-11-22 18:00:04","data":{"Large":{"Core":{"10y":13.48569,"1d":1.6676,"1m":-2.2304,"1y":6.37068,"3m":2.34596,"3y":16.45365,"5y":13.41569},"Growth":{"10y":14.4414,"1d":0.2338,"1m":-6.10159,"1y":13.39938,"3m":-0.35703,"3y":27.1558,"5y":10.4215},"Value":{"10y":10.97086,"1d":1.2342,"1m":-0.21282,"1y":9.16463,"3m":3.97714,"3y":13.1227,"5y":13.53209}},"Mid":{"Core":{"10y":9.92821,"1d":2.014,"1m":-3.37103,"1y":-1.2157,"3m":-0.02121,"3y":9.44902,"5y":9.34515},"Growth":{"10y":11.83995,"1d":1.0303,"1m":-6.88953,"1y":0.64205,"3m":-3.8359,"3y":15.42334,"5y":5.64925},"Value":{"10y":9.97118,"1d":2.0335,"1m":-0.40169,"1y":3.4402,"3m":2.15324,"3y":10.61951,"5y":12.6591}},"Small":{"Core":{"10y":8.89202,"1d":2.4705,"1m":-1.66282,"1y":-0.08865,"3m":2.83703,"3y":10.92787,"5y":9.43227},"Growth":{"10y":8.85973,"1d":2.1654,"1m":-5.23706,"1y":2.47124,"3m":1.89033,"3y":14.93316,"5y":2.885},"Value":{"10y":8.27751,"1d":2.803,"1m":-1.00152,"1y":1.55077,"3m":2.85211,"3y":8.64257,"5y":12.38769}}},"raw":{"data":{"Large":{"Core":{"10y":13.48569,"1d":1.6676,"1m":-2.2304,"1y":6.37068,"3m":2.34596,"3y":16.45365,"5y":13.41569},"Growth":{"10y":14.4414,"1d":0.2338,"1m":-6.10159,"1y":13.39938,"3m":-0.35703,"3y":27.1558,"5y":10.4215},"Value":{"10y":10.97086,"1d":1.2342,"1m":-0.21282,"1y":9.16463,"3m":3.97714,"3y":13.1227,"5y":13.53209}},"Mid":{"Core":{"10y":9.92821,"1d":2.014,"1m":-3.37103,"1y":-1.2157,"3m":-0.02121,"3y":9.44902,"5y":9.34515},"Growth":{"10y":11.83995,"1d":1.0303,"1m":-6.88953,"1y":0.64205,"3m":-3.8359,"3y":15.42334,"5y":5.64925},"Value":{"10y":9.97118,"1d":2.0335,"1m":-0.40169,"1y":3.4402,"3m":2.15324,"3y":10.61951,"5y":12.6591}},"Small":{"Core":{"10y":8.89202,"1d":2.4705,"1m":-1.66282,"1y":-0.08865,"3m":2.83703,"3y":10.92787,"5y":9.43227},"Growth":{"10y":8.85973,"1d":2.1654,"1m":-5.23706,"1y":2.47124,"3m":1.89033,"3y":14.93316,"5y":2.885},"Value":{"10y":8.27751,"1d":2.803,"1m":-1.00152,"1y":1.55077,"3m":2.85211,"3y":8.64257,"5y":12.38769}}},"scraped_at":"2025-11-22 18:00:04"},"metadata":{"source":"local_database","cached":false,"cache_ttl":300,"last_updated":"2025-11-22 18:00:06"}}}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json b/storage/cache/usmb_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[1mindex 74ccad5..7e16d21 100644[m
[1m--- a/storage/cache/usmb_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[1m+++ b/storage/cache/usmb_rl/4101227ed9eca689a51fd3651a40a3e3d3abc09e.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763571933,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763646537,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json b/storage/cache/usmb_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[1mindex fcb5c26..95c56cd 100644[m
[1m--- a/storage/cache/usmb_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[1m+++ b/storage/cache/usmb_rl/48401d190ac42a9bdc7a3ea4bcc5773d4b95dd9f.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1762516321,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763734767,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json b/storage/cache/usmb_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[1mindex 8fa8387..95c56cd 100644[m
[1m--- a/storage/cache/usmb_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[1m+++ b/storage/cache/usmb_rl/4ca65c0c7e70b2e15c957c5ce08efbd5868f56a8.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1762527177,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763734767,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json b/storage/cache/usmb_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[1mindex 399c540..7c6fc5a 100644[m
[1m--- a/storage/cache/usmb_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[1m+++ b/storage/cache/usmb_rl/70b98ca83bfb8693f36c5a8120ea37ee51a6c2d0.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763596517,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763679901,"c":2}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json b/storage/cache/usmb_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[1mindex 74ccad5..7e16d21 100644[m
[1m--- a/storage/cache/usmb_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[1m+++ b/storage/cache/usmb_rl/717898478385e43c3deca8ac073d6e5c0fcb3b06.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763571933,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763646537,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json b/storage/cache/usmb_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[1mindex d0ced58..3f0748e 100644[m
[1m--- a/storage/cache/usmb_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[1m+++ b/storage/cache/usmb_rl/770977e55eb6353edd4dd5e2c00b1a7210091adf.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763580395,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763736421,"c":2}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json b/storage/cache/usmb_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[1mindex d0ced58..3f0748e 100644[m
[1m--- a/storage/cache/usmb_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[1m+++ b/storage/cache/usmb_rl/82d973ebaff9fa6b3e6b6e1ddffae69e71b9f42a.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763580395,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763736421,"c":2}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json b/storage/cache/usmb_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[1mindex 91476aa..b0a21e7 100644[m
[1m--- a/storage/cache/usmb_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[1m+++ b/storage/cache/usmb_rl/a32ad1e4877e8cd0f18cb9a9f63050aef9457743.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763599906,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763729296,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/bbc1460bda77f59d055389020791627942db03d8.json b/storage/cache/usmb_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[1mindex 399c540..f944911 100644[m
[1m--- a/storage/cache/usmb_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[1m+++ b/storage/cache/usmb_rl/bbc1460bda77f59d055389020791627942db03d8.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763596517,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763834124,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/e25c32df874c379f0e026766a986f1b353b282a0.json b/storage/cache/usmb_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[1mindex d3d6f9f..f2fc65f 100644[m
[1m--- a/storage/cache/usmb_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[1m+++ b/storage/cache/usmb_rl/e25c32df874c379f0e026766a986f1b353b282a0.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763531116,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763682338,"c":1}[m
\ No newline at end of file[m
[1mdiff --git a/storage/cache/usmb_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json b/storage/cache/usmb_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[1mindex 91476aa..5f9c8ab 100644[m
[1m--- a/storage/cache/usmb_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[1m+++ b/storage/cache/usmb_rl/fd451e956e9353ea0095332c05ec0841a00a6c51.json[m
[36m@@ -1 +1 @@[m
[31m-{"t":1763599906,"c":1}[m
\ No newline at end of file[m
[32m+[m[32m{"t":1763837170,"c":1}[m
\ No newline at end of file[m
