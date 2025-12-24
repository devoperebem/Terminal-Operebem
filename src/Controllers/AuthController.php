<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Application;
use Carbon\Carbon;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use App\Services\UserJwtService;
use App\Services\RecaptchaService;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        // Redirect to home with login modal
        header('Location: /?modal=login', true, 302);
        exit;
    }

    public function showRegister(): void
    {
        // Redirect to home with register modal
        header('Location: /?modal=register', true, 302);
        exit;
    }

    public function login(): void
    {
        $logger = Application::getInstance()->logger();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!$this->validateCsrf()) {
            try { $logger->warning('auth.login.csrf_invalid', ['ip'=>$ip,'ua'=>$ua]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        // Require Captcha verification (set by vendor/operebem/captcha/public/verify.php)
        $captchaOk = isset($_SESSION['captcha_passed']) && ($_SESSION['captcha_passed'] === true);
        $captchaTime = (int)($_SESSION['captcha_passed_time'] ?? 0);
        if (!($captchaOk && (time() - $captchaTime) <= 300)) { // 5 minutos de janela
            try { $logger->warning('auth.login.captcha_required', ['ip'=>$ip,'ua'=>$ua]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Valide o captcha antes de continuar.']);
        }

        $email = trim($_POST['email'] ?? '');
        $rcToken = trim((string)($_POST['rc_token'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validações básicas
        if (empty($email) || empty($password)) {
            try { $logger->warning('auth.login.missing_fields', ['ip'=>$ip,'email'=>$email]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Email e senha são obrigatórios']);
        }

        // Verificar rate limiting
        if ($this->isRateLimited($email)) {
            try { $logger->warning('auth.login.rate_limited', ['ip'=>$ip,'email'=>$email]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.']);
        }

        // reCAPTCHA v3 (opcional): se slider captcha já passou, não bloquear
        try {
            $rc = new RecaptchaService();
            if ($rc->isConfigured()) {
                $vr = $rc->verify($rcToken, $_SERVER['REMOTE_ADDR'] ?? '', 'user_login');
                if (!$vr['ok'] && !$captchaOk) {
                    $this->json(['success'=>false,'message'=>'Verificação anti-bot obrigatória.']);
                }
                try { $logger->info('auth.login.recaptcha', ['ok'=>$vr['ok'] ?? null, 'ip'=>$ip, 'email'=>$email]); } catch (\Throwable $t) {}
            }
        } catch (\Throwable $t) { /* ignore */ }

        // Tentar fazer login
        $result = $this->authService->login($email, $password, $remember);
        try { $logger->info('auth.login.attempt', ['email'=>$email,'ip'=>$ip,'success'=>$result['success'] ?? null]); } catch (\Throwable $t) {}

        // Registrar tentativa de login
        $this->logLoginAttempt($email, $result['success']);

        if ($result['success']) {
            // Limpar sinalizador de captcha após sucesso
            unset($_SESSION['captcha_passed'], $_SESSION['captcha_passed_time']);
            // Emitir access/refresh tokens para usuários (cookies httpOnly)
            try {
                (new \App\Services\SystemMaintenanceService())->ensureCore();
                $jwt = new UserJwtService();
                // Access claim
                $user = Database::fetch('SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
                if ($user && !empty($user['id'])) {
                    $at = $jwt->issueAccessToken(['sub' => (int)$user['id'], 'role' => 'user']);
                    $rt = $jwt->issueRefreshToken(['sub' => (int)$user['id'], 'role' => 'user']);
                    $secure = Application::getInstance()->isHttps();
                    setcookie('__Host-access_token', $at, [ 'expires' => time()+600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
                    setcookie('__Host-refresh_token', $rt['token'], [ 'expires' => (int)$rt['exp'], 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
                    $_SESSION['usr_rt_jti'] = (string)$rt['jti'];
                    try {
                        Database::insert('user_refresh_tokens', [
                            'jti' => (string)$rt['jti'],
                            'user_id' => (int)$user['id'],
                            'exp' => (int)$rt['exp'],
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (\Throwable $t) { /* ignore */ }
                    try { $logger->info('auth.login.success', ['user_id'=>(int)$user['id'], 'email'=>$email, 'ip'=>$ip]); } catch (\Throwable $t) {}
                    
                    // Processar gamificação (XP + Streak)
                    try {
                        error_log('[AuthController] Iniciando processamento de gamificação para user ' . $user['id']);
                        $gamification = new \App\Services\GamificationService();
                        $result = $gamification->processLogin((int)$user['id']);
                        error_log('[AuthController] Gamificação processada: ' . json_encode($result));
                    } catch (\Throwable $t) {
                        error_log('[AuthController] Gamification error: ' . $t->getMessage() . ' | Trace: ' . $t->getTraceAsString());
                    }
                }
            } catch (\Throwable $t) { /* ignore */ }
            $redir = (string)($_SESSION['next_url'] ?? '/app/dashboard');
            unset($_SESSION['next_url']);
            $this->json([
                'success' => true,
                'message' => $result['message'] ?? 'Login realizado com sucesso',
                'redirect' => $redir
            ]);
        } else {
            // Limpar sessão de captcha para exigir nova validação no próximo envio
            unset($_SESSION['captcha_passed'], $_SESSION['captcha_passed_time']);
            $result['require_captcha_reset'] = true;
            try { $logger->warning('auth.login.failure', ['email'=>$email,'ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json($result, 401);
        }
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? ''
        ];

        $result = $this->authService->register($data);

        if ($result['success']) {
            $this->json([
                'success' => true,
                'message' => $result['message'],
                'redirect' => '/login'
            ]);
        } else {
            $this->json($result, 400);
        }
    }

    /**
     * Passo 1: Consultar CPF
     */
    public function consultarCpf(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        // Novo fluxo: requer que o passo atual seja 2 (após email)
        if (!isset($_SESSION['registro_temp']) || (int)($_SESSION['registro_temp']['step'] ?? 0) !== 2) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > ($_SESSION['registro_temp']['expires_at'] ?? 0)) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $cpf = trim($_POST['cpf'] ?? '');
        $cpfDigits = preg_replace('/[^0-9]/', '', $cpf);

        if (empty($cpfDigits)) {
            $this->json(['success' => false, 'message' => 'CPF é obrigatório']);
        }

        // Bloquear imediatamente se CPF já estiver cadastrado
        try {
            $existsCpf = \App\Core\Database::fetch("SELECT id FROM users WHERE cpf = ? AND (deleted_at IS NULL)", [$cpfDigits]);
            if ($existsCpf) {
                $this->json([
                    'success' => false,
                    'message' => 'No momento não é possível criar uma conta com este CPF. Entre em contato com o suporte.',
                    'redirect' => '/support'
                ], 409);
            }
        } catch (\Exception $e) {
            // Em caso de erro de conexão, seguir para a consulta externa (fallback)
            \App\Core\Application::getInstance()->logger()->warning('Erro ao checar CPF existente no passo 1: ' . $e->getMessage());
        }

        // Primeiro consultar a API externa
        $cpfService = new \App\Services\CpfService();
        $result = $cpfService->consultarCpf($cpfDigits);

        // Nota: A verificação de CPF existente foi removida deste método
        // para evitar problemas de conexão com banco de dados.
        // A verificação será feita no momento do registro final (método register)

        if ($result['success']) {
            try {
                $birth = $result['data']['data_nascimento'] ?? null;
                if ($birth) {
                    $age = Carbon::parse($birth)->age;
                    if ($age < 18) {
                        $this->json([
                            'success' => false,
                            'message' => 'O Terminal Operebem é destinado apenas a maiores de 18 anos. Temos projetos para menores com autorização dos pais. Entre em contato com o suporte.',
                            'redirect' => '/support'
                        ], 403);
                    }
                }
            } catch (\Throwable $t) {
                // ignorar erro de parse e seguir
            }
            // Armazenar dados na sessão temporariamente
            // Garantir que o CPF na sessão está normalizado (apenas dígitos)
            $cpfFromApi = preg_replace('/[^0-9]/', '', $result['data']['cpf']);
            if (!isset($_SESSION['registro_temp']) || !is_array($_SESSION['registro_temp'])) {
                $_SESSION['registro_temp'] = [];
            }
            $_SESSION['registro_temp']['cpf'] = $cpfFromApi;
            $_SESSION['registro_temp']['nome'] = $result['data']['nome'];
            $_SESSION['registro_temp']['genero'] = $result['data']['genero'];
            $_SESSION['registro_temp']['data_nascimento'] = $result['data']['data_nascimento'];
            $_SESSION['registro_temp']['step'] = 3;
            $_SESSION['registro_temp']['expires_at'] = time() + 1800; // 30 minutos

            $this->json([
                'success' => true,
                'data' => [
                    'nome' => $result['data']['nome'],
                    'genero' => \App\Services\CpfService::formatarGenero($result['data']['genero']),
                    'data_nascimento' => \App\Services\CpfService::formatarDataNascimento($result['data']['data_nascimento'])
                ]
            ]);
        } else {
            $this->json($result, 400);
        }
    }

    /**
     * Passo 2: Confirmar dados do CPF
     */
    public function confirmarDados(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        if (!isset($_SESSION['registro_temp']) || $_SESSION['registro_temp']['step'] !== 3) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > $_SESSION['registro_temp']['expires_at']) {
            try { $logger->warning('auth.register.code.session_expired', ['ip'=>$ip]); } catch (\Throwable $t) {}
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        // Exigir aceite das políticas de segurança e confidencialidade
        $aceite = $_POST['aceite_politicas'] ?? '';
        $aceitou = ($aceite === '1' || $aceite === 'on' || $aceite === 'true');
        if (!$aceitou) {
            $this->json(['success' => false, 'message' => 'É necessário aceitar as políticas de segurança e confidencialidade.']);
        }

        // Atualizar sessão (permanecemos no passo 3 validado; próximo é criar senha)
        $_SESSION['registro_temp']['step'] = 3;

        $this->json([
            'success' => true,
            'message' => 'Dados confirmados com sucesso'
        ]);
    }

    /**
     * Passo 2 (alternativo): Usuário informa manualmente os dados ("Não sou eu!")
     */
    public function overrideDados(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        if (!isset($_SESSION['registro_temp']) || !in_array($_SESSION['registro_temp']['step'], [2, 3], true)) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > $_SESSION['registro_temp']['expires_at']) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $nome = trim($_POST['nome'] ?? '');
        $genero = trim($_POST['genero'] ?? '');
        $dataNascimento = trim($_POST['data_nascimento'] ?? '');
        $aceitouTermos = isset($_POST['aceite_termos']) && ($_POST['aceite_termos'] === '1' || $_POST['aceite_termos'] === 'on');

        if (strlen($nome) < 2) {
            $this->json(['success' => false, 'message' => 'Informe um nome válido']);
        }

        if (!in_array($genero, ['Masculino', 'Feminino', 'Outro'], true)) {
            $this->json(['success' => false, 'message' => 'Informe um gênero válido']);
        }

        // Validação simples de data (YYYY-MM-DD) ou (DD/MM/YYYY)
        $dateValid = false;
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dataNascimento)) {
            $dateValid = true;
        } elseif (preg_match('/^\\d{2}\\/\\d{2}\\/\\d{4}$/', $dataNascimento)) {
            $parts = explode('/', $dataNascimento);
            $dataNascimento = sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
            $dateValid = true;
        }
        if (!$dateValid) {
            $this->json(['success' => false, 'message' => 'Informe uma data de nascimento válida']);
        }

        if (!$aceitouTermos) {
            $this->json(['success' => false, 'message' => 'É necessário aceitar os Termos de Uso, Política de Privacidade e Aviso de Risco']);
        }

        // Persistir dados na sessão de registro e avançar
        $_SESSION['registro_temp']['nome'] = $nome;
        $_SESSION['registro_temp']['genero'] = $genero;
        $_SESSION['registro_temp']['data_nascimento'] = $dataNascimento;
        $_SESSION['registro_temp']['step'] = 3; // mantém coerência e permite seguir para criar senha

        $this->json([
            'success' => true,
            'message' => 'Dados informados com sucesso'
        ]);
    }

    /**
     * Passo 3: Criar senha
     */
    public function criarSenha(): void
    {
        $logger = Application::getInstance()->logger();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->validateCsrf()) {
            try { $logger->warning('auth.register.password.csrf_invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        if (!isset($_SESSION['registro_temp']) || $_SESSION['registro_temp']['step'] !== 3) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > $_SESSION['registro_temp']['expires_at']) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        // Symfony Validator para senha "decente"
        $validator = Validation::createValidator();
        $passwordViolations = $validator->validate($password, new Assert\Sequentially([
            new Assert\NotBlank(message: 'Senha é obrigatória'),
            new Assert\Length(min: 8, minMessage: 'A senha deve ter pelo menos {{ limit }} caracteres'),
            new Assert\Regex(
                pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                message: 'A senha deve conter pelo menos 1 letra minúscula, 1 maiúscula e 1 número'
            )
        ]));
        if (count($passwordViolations) > 0) {
            try { $logger->warning('auth.register.password.weak', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => $passwordViolations[0]->getMessage()]);
        }

        if ($password !== $passwordConfirmation) {
            try { $logger->warning('auth.register.password.mismatch', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'As senhas não coincidem']);
        }

        // Armazenar senha na sessão
        $_SESSION['registro_temp']['password'] = password_hash($password, PASSWORD_ARGON2ID);
        $_SESSION['registro_temp']['step'] = 4;

        try { $logger->info('auth.register.password.ok', ['ip'=>$ip]); } catch (\Throwable $t) {}
        $this->json([
            'success' => true,
            'message' => 'Senha criada com sucesso'
        ]);
    }

    /**
     * Passo 4: Adicionar telefone
     */
    public function adicionarTelefone(): void
    {
        $logger = Application::getInstance()->logger();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->validateCsrf()) {
            try { $logger->warning('auth.register.phone.csrf_invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        // Novo fluxo: telefone é o primeiro passo. Se a sessão não existir, inicializa.
        if (!isset($_SESSION['registro_temp'])) {
            $_SESSION['registro_temp'] = [ 'step' => 0, 'expires_at' => time() + 1800 ];
        }

        if (time() > ($_SESSION['registro_temp']['expires_at'] ?? 0)) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $telefone = trim($_POST['telefone'] ?? '');
        $countryCodeInput = trim($_POST['country_code'] ?? '');
        $countryIso = strtoupper(trim($_POST['country_iso'] ?? ''));
        $validator = Validation::createValidator();
        $telViolations = $validator->validate($telefone, new Assert\NotBlank(message: 'Telefone é obrigatório'));
        if (count($telViolations) > 0) {
            try { $logger->warning('auth.register.phone.invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => $telViolations[0]->getMessage()]);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneProto = $phoneUtil->parse($telefone, $countryIso ?: null);
        } catch (NumberParseException $exception) {
            try { $logger->warning('auth.register.phone.parse_error', ['ip'=>$ip, 'error'=>$exception->getMessage()]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Telefone inválido para o país selecionado']);
        }

        // Validação robusta usando PhoneValidationService
        $phoneValidationService = new \App\Services\PhoneValidationService();
        $validationResult = $phoneValidationService->validatePhoneNumber($phoneProto, $phoneUtil);

        if (!$validationResult['valid']) {
            try { $logger->warning('auth.register.phone.invalid_pattern', ['ip'=>$ip, 'reason'=>$validationResult['message'] ?? 'unknown']); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => $validationResult['message'] ?? 'Telefone inválido para o país selecionado']);
        }

        $countryCodeParsed = '+' . $phoneProto->getCountryCode();
        if ($countryCodeInput && $countryCodeInput !== $countryCodeParsed) {
            try { $logger->warning('auth.register.phone.country_mismatch', ['ip'=>$ip, 'input'=>$countryCodeInput, 'parsed'=>$countryCodeParsed]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'O código do país não corresponde ao telefone informado']);
        }

        $e164 = $phoneUtil->format($phoneProto, PhoneNumberFormat::E164);
        $numericPhone = preg_replace('/[^0-9]/', '', $e164);
        if (!$numericPhone || strlen($numericPhone) < 6 || strlen($numericPhone) > 15) {
            $this->json(['success' => false, 'message' => 'Telefone inválido. Verifique o número informado.']);
        }

        // Unicidade do telefone
        $existsPhone = Database::fetch("SELECT id FROM users WHERE phone = ? AND deleted_at IS NULL", [$numericPhone]);
        if ($existsPhone) {
            try { $logger->warning('auth.register.phone.duplicate', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json([
                'success' => false,
                'message' => 'Esse número nao pode ser registrado no momento, tente novamente com outro número ou entre em contato com o suporte.',
                'redirect' => '/support'
            ], 409);
        }

        // Armazenar telefone na sessão
        $regionCode = $phoneUtil->getRegionCodeForNumber($phoneProto) ?: ($countryIso ?: null);
        $_SESSION['registro_temp']['telefone'] = $numericPhone;
        $_SESSION['registro_temp']['telefone_iso'] = $regionCode;
        $_SESSION['registro_temp']['telefone_codigo_pais'] = $countryCodeParsed;
        $_SESSION['registro_temp']['step'] = 1;
        $_SESSION['registro_temp']['expires_at'] = time() + 1800; // 30 minutos
        try { $logger->info('auth.register.phone.ok', ['ip'=>$ip]); } catch (\Throwable $t) {}
        $this->json([
            'success' => true,
            'message' => 'Telefone adicionado com sucesso'
        ]);
    }

    /**
     * Passo 5: Adicionar email
     */
    public function adicionarEmail(): void
    {
        $logger = Application::getInstance()->logger();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->validateCsrf()) {
            try { $logger->warning('auth.register.email.csrf_invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        // Novo fluxo: email é o passo 2 (após telefone)
        if (!isset($_SESSION['registro_temp']) || $_SESSION['registro_temp']['step'] !== 1) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > ($_SESSION['registro_temp']['expires_at'] ?? 0)) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $email = trim($_POST['email'] ?? '');
        $validator = Validation::createValidator();
        $emailViolations = $validator->validate($email, new Assert\Sequentially([
            new Assert\NotBlank(message: 'Email é obrigatório'),
            new Assert\Email(message: 'Email inválido')
        ]));
        if (count($emailViolations) > 0) {
            try { $logger->warning('auth.register.email.invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => $emailViolations[0]->getMessage()], 400);
        }

        // Verificar se usuário já está cadastrado com este email (tolerar falha de DB no fluxo)
        try {
            $exists = \App\Core\Database::fetch("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [$email]);
            if ($exists) {
                try { $logger->warning('auth.register.email.duplicate', ['ip'=>$ip,'email'=>$email]); } catch (\Throwable $t) {}
                $this->json(['success' => false, 'message' => 'Este email já está cadastrado. Faça login ou recupere a senha.'], 409);
            }
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->warning('Falha ao verificar email existente: ' . $e->getMessage()); } catch (\Throwable $t) {}
            // seguir fluxo e validar duplicidade mais adiante em verificarCodigo()
        }

        // Nota: A verificação de email existente foi removida deste método
        // para evitar problemas de conexão com banco de dados.
        // A verificação será feita no momento do registro final (método verificarCodigo)

        // Atualizar sessão: apenas armazenar email e avançar o fluxo; código será gerado apenas no passo 5
        $_SESSION['registro_temp']['email'] = $email;
        $_SESSION['registro_temp']['step'] = 2;
        $_SESSION['registro_temp']['expires_at'] = time() + 1800; // refresh da sessão de registro

        try { $logger->info('auth.register.email.ok', ['ip'=>$ip,'email'=>$email]); } catch (\Throwable $t) {}
        $this->json(['success' => true, 'message' => 'Email adicionado com sucesso']);
    }

    /**
     * Passo 4: Verificar código e finalizar registro
     */
    public function verificarCodigo(): void
    {
        $logger = Application::getInstance()->logger();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->validateCsrf()) {
            try { $logger->warning('auth.register.code.csrf_invalid', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        if (!isset($_SESSION['registro_temp']) || $_SESSION['registro_temp']['step'] !== 4) {
            try { $logger->warning('auth.register.code.invalid_state', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        if (time() > $_SESSION['registro_temp']['expires_at']) {
            unset($_SESSION['registro_temp']);
            $this->json(['success' => false, 'message' => 'Sessão de registro expirada'], 400);
        }

        $codigo = trim($_POST['codigo'] ?? '');

        if (empty($codigo)) {
            try { $logger->warning('auth.register.code.missing', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Código de verificação é obrigatório']);
        }

        // Verificar se código não expirou
        if (time() > $_SESSION['registro_temp']['code_expires_at']) {
            try { $logger->warning('auth.register.code.expired', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Código de verificação expirado']);
        }

        // Verificar código
        if ($codigo !== $_SESSION['registro_temp']['verification_code']) {
            try { $logger->warning('auth.register.code.mismatch', ['ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Código de verificação inválido']);
        }

        // Criar usuário
        try {
            $tempData = $_SESSION['registro_temp'];

            $gen = strtoupper(trim((string)($tempData['genero'] ?? '')));
            if ($gen !== 'M' && $gen !== 'F' && $gen !== 'I') {
                $first = substr($gen, 0, 1);
                if ($first === 'M') { $gen = 'M'; }
                elseif ($first === 'F') { $gen = 'F'; }
                else { $gen = 'I'; }
            }
            $birth = (string)($tempData['data_nascimento'] ?? '');
            $birthNorm = null;
            if ($birth !== '') {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birth)) {
                    $parts = explode('/', $birth);
                    $birthNorm = sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
                    $birthNorm = $birth;
                }
            }
            
            // Verificar se CPF já existe antes de tentar inserir
            try {
                $existingCpf = Database::fetch("SELECT id FROM users WHERE cpf = ?", [$tempData['cpf']]);
                if ($existingCpf) {
                    $this->json(['success' => false, 'message' => 'Não foi possível criar uma conta com este CPF. Entre em contato com o suporte.', 'redirect' => '/support']);
                }
            } catch (\Exception $e) {
                // Se houver erro de banco, continuar (será detectado na inserção)
                Application::getInstance()->logger()->warning('Erro ao verificar CPF existente: ' . $e->getMessage());
            }
            
            // Verificar se email já existe antes de tentar inserir
            try {
                $existingEmail = Database::fetch("SELECT id FROM users WHERE email = ?", [$tempData['email']]);
                if ($existingEmail) {
                    $this->json(['success' => false, 'message' => 'Email já cadastrado no sistema']);
                }
            } catch (\Exception $e) {
                // Se houver erro de banco, continuar (será detectado na inserção)
                Application::getInstance()->logger()->warning('Erro ao verificar email existente: ' . $e->getMessage());
            }
            
            $userId = Database::insert('users', [
                'name' => $tempData['nome'],
                'email' => $tempData['email'],
                'cpf' => $tempData['cpf'],
                'phone' => $tempData['telefone'],
                'gender' => $gen,
                'birth_date' => $birthNorm,
                'password' => $tempData['password'], // Senha definida pelo usuário
                'theme' => 'light',
                'media_card' => true,
                'email_verified_at' => Carbon::now()->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);

            // Buscar usuário criado
            $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);

            // Fazer login automático
            $this->authService->createSessionFromUser($user);

            // Limpar dados temporários
            unset($_SESSION['registro_temp']);

            try { $logger->info('auth.register.success', ['user_id'=>$userId ?? null, 'ip'=>$ip]); } catch (\Throwable $t) {}
            $this->json([
                'success' => true,
                'message' => 'Conta criada com sucesso!',
                'redirect' => '/app/dashboard'
            ]);

        } catch (\Exception $e) {
            try { $logger->error('auth.register.exception', ['ip'=>$ip, 'error'=>$e->getMessage()]); } catch (\Throwable $t) {}
            Application::getInstance()->logger()->error('Erro ao criar usuário: ' . $e->getMessage());
            
            // Verificar se é erro de duplicata
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cpf') !== false) {
                    $this->json(['success' => false, 'message' => 'Não foi possível criar uma conta com este CPF. Entre em contato com o suporte.', 'redirect' => '/support']);
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $this->json(['success' => false, 'message' => 'Email já cadastrado no sistema']);
                }
            }
            
            // Verificar se é erro de conexão com banco
            if (strpos($e->getMessage(), 'Erro ao conectar com o banco de dados') !== false) {
                $this->json([
                    'success' => false, 
                    'message' => 'Sistema temporariamente indisponível. Tente novamente em alguns minutos.'
                ], 503);
            }
            
            // Para outros erros, permitir nova tentativa
            $this->json(['success' => false, 'message' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    /**
     * Reenviar código de verificação
     */
    public function reenviarCodigo(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        if (!isset($_SESSION['registro_temp']) || (int)($_SESSION['registro_temp']['step'] ?? 0) < 2) {
            $this->json(['success' => false, 'message' => 'Sessão de registro inválida'], 400);
        }

        // Gerar novo código
        $verificationCode = sprintf('%06d', mt_rand(100000, 999999));
        
        $_SESSION['registro_temp']['verification_code'] = $verificationCode;
        $_SESSION['registro_temp']['code_expires_at'] = time() + 1800; // 30 minutos

        // Enviar email (usar fallback de nome se ainda não definido)
        $reg = $_SESSION['registro_temp'];
        $em = (string)($reg['email'] ?? '');
        $safeName = isset($reg['nome']) && is_string($reg['nome']) && $reg['nome'] !== ''
            ? $reg['nome']
            : (function($em){
                $local = explode('@', $em)[0] ?? 'Usuario';
                $local = preg_replace('/[._-]+/', ' ', $local);
                $local = trim($local);
                if ($local === '') { $local = 'Usuario'; }
                return ucwords($local);
              })($em);
        $_SESSION['registro_temp']['nome'] = $safeName;
        $emailService = new \App\Services\EmailService();
        $emailSent = $emailService->sendVerificationCode($safeName, $verificationCode, $em, 'user_register');

        if ($emailSent) {
            $this->json(['success' => true, 'message' => 'Novo código enviado']);
        } else {
            $debug = (($_ENV['APP_DEBUG'] ?? 'false') === 'true') || (($_ENV['MAIL_SOFT_FAIL'] ?? 'false') === 'true');
            if ($debug) {
                $this->json([
                    'success' => true,
                    'message' => 'Novo código gerado (modo teste/soft-fail habilitado)',
                    'dev_preview_code' => $verificationCode
                ]);
            }
            $this->json(['success' => false, 'message' => 'Erro ao enviar email'], 500);
        }
    }

    public function logout(): void
    {
        // Revogar refresh token do usuário, se houver
        try {
            if (!empty($_SESSION['usr_rt_jti'])) {
                Database::update('user_refresh_tokens', [ 'revoked_at' => date('Y-m-d H:i:s') ], [ 'jti' => (string)$_SESSION['usr_rt_jti'] ]);
            }
        } catch (\Throwable $t) { /* ignore */ }
        // Limpar cookies de tokens
        $secure = Application::getInstance()->isHttps();
        setcookie('__Host-access_token', '', [ 'expires' => time()-3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
        setcookie('__Host-refresh_token', '', [ 'expires' => time()-3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
        unset($_SESSION['usr_rt_jti']);
        $this->authService->logout();

        // Helper para gerar token de logout JWT
        $generateLogoutToken = function(string $secret, string $iss, string $aud): string {
            $now = time();
            $ttl = 30;
            $header = ['alg' => 'HS256', 'typ' => 'JWT'];
            $payload = [ 'iss' => $iss, 'aud' => $aud, 'iat' => $now, 'exp' => $now + $ttl, 'jti' => bin2hex(random_bytes(16)), 'typ' => 'logout' ];
            $h64 = rtrim(strtr(base64_encode(json_encode($header, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
            $p64 = rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
            $sig = hash_hmac('sha256', $h64 . '.' . $p64, $secret, true);
            $s64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
            return $h64 . '.' . $p64 . '.' . $s64;
        };

        $iss = (string)(Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $retTerminalHome = $iss;

        // Notificar o Diário Operebem em background (não bloqueia)
        try {
            $diarioSecret = trim((string)($_ENV['SSO_DIARIO_SECRET'] ?? $_ENV['SSO_SHARED_SECRET'] ?? ''));
            $diarioAud = (string)($_ENV['SSO_DIARIO_AUDIENCE'] ?? 'https://diario.operebem.com.br');
            if ($diarioSecret !== '' && $diarioAud !== '') {
                $diarioJwt = $generateLogoutToken($diarioSecret, $iss, $diarioAud);
                $diarioUrl = rtrim($diarioAud, '/') . '/sso/logout?token=' . urlencode($diarioJwt) . '&return=' . urlencode($retTerminalHome);
                // Requisição assíncrona em background (fire-and-forget)
                $ch = curl_init($diarioUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // 500ms timeout para não bloquear
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (\Throwable $t) { /* ignore */ }

        // Propagar logout para o Portal do Aluno via token assinado (curta duração) - redirect principal
        try {
            $aud = (string)($_ENV['SSO_AUDIENCE'] ?? 'https://aluno.operebem.com.br');
            $secret = trim((string)($_ENV['SSO_SHARED_SECRET'] ?? ''));
            if ($secret !== '' && $aud !== '') {
                $jwt = $generateLogoutToken($secret, $iss, $aud);
                // Após logout no Portal do Aluno, retornar para a home pública do Terminal
                $cb = rtrim($aud, '/') . '/sso/logout?token=' . urlencode($jwt) . '&return=' . urlencode($retTerminalHome);
                header('Location: ' . $cb, true, 302);
                exit;
            }
        } catch (\Throwable $t) { /* ignore */ }
        $this->redirect('/');
    }

    public function refreshToken(): void
    {
        // Renovar tokens do usuário usando cookie __Host-refresh_token
        try { (new \App\Services\SystemMaintenanceService())->ensureCore(); } catch (\Throwable $t) {}
        $rtCookie = $_COOKIE['__Host-refresh_token'] ?? '';
        if (!$rtCookie) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Refresh ausente']); exit; }
        $jwt = new UserJwtService();
        try {
            $payload = $jwt->decode($rtCookie);
            if (($payload['typ'] ?? '') !== 'refresh') { throw new \Exception('tipo invalido'); }
            $jti = (string)($payload['jti'] ?? '');
            $sub = (int)($payload['sub'] ?? 0);
            $exp = (int)($payload['exp'] ?? 0);
            if ($jti === '' || $sub <= 0 || $exp <= time()) { throw new \Exception('payload invalido'); }
            $row = Database::fetch('SELECT jti, user_id, exp, revoked_at FROM user_refresh_tokens WHERE jti = ?', [$jti]);
            if (!$row || (int)$row['user_id'] !== $sub) { throw new \Exception('refresh desconhecido'); }
            if (!empty($row['revoked_at'])) { throw new \Exception('refresh revogado'); }
            if ((int)$row['exp'] < time()) { throw new \Exception('refresh expirado'); }
            // Rotacionar e emitir novos
            Database::update('user_refresh_tokens', [ 'revoked_at' => date('Y-m-d H:i:s') ], [ 'jti' => $jti ]);
            $newAt = $jwt->issueAccessToken(['sub' => $sub, 'role' => 'user']);
            $newRt = $jwt->issueRefreshToken(['sub' => $sub, 'role' => 'user']);
            Database::insert('user_refresh_tokens', [
                'jti' => (string)$newRt['jti'],
                'user_id' => $sub,
                'exp' => (int)$newRt['exp'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['usr_rt_jti'] = (string)$newRt['jti'];
            $secure = Application::getInstance()->isHttps();
            setcookie('__Host-access_token', $newAt, [ 'expires' => time()+600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            setcookie('__Host-refresh_token', $newRt['token'], [ 'expires' => (int)$newRt['exp'], 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            header('Content-Type: application/json');
            echo json_encode(['success'=>true]);
            exit;
        } catch (\Throwable $e) {
            // Limpar cookies em falha
            $secure = Application::getInstance()->isHttps();
            setcookie('__Host-access_token', '', [ 'expires' => time()-3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            setcookie('__Host-refresh_token', '', [ 'expires' => time()-3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict' ]);
            header('Content-Type: application/json'); http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Refresh inválido']);
            exit;
        }
    }

    public function showForgotPassword(): void
    {
        header('Location: /?modal=forgot', true, 302);
        exit;
    }

    public function forgotPassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }
        $this->checkHoneypot();

        // Exigir verificação de Captcha (sinalizada por vendor/operebem/captcha/public/verify.php)
        $captchaOk = isset($_SESSION['captcha_passed']) && ($_SESSION['captcha_passed'] === true);
        $captchaTime = (int)($_SESSION['captcha_passed_time'] ?? 0);
        if (!($captchaOk && (time() - $captchaTime) <= 300)) { // 5 minutos de janela
            $this->json(['success' => false, 'message' => 'Valide o captcha antes de continuar.']);
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email inválido']);
        }

        $genericResponse = [
            'success' => true,
            'message' => 'Se o email existir em nossa base, você receberá instruções para redefinir sua senha.'
        ];

        // reCAPTCHA v3 (opcional)
        try {
            $rc = new RecaptchaService();
            $rcToken = trim((string)($_POST['rc_token'] ?? ''));
            if ($rc->isConfigured() && $rcToken !== '') {
                $vr = $rc->verify($rcToken, $_SERVER['REMOTE_ADDR'] ?? '', 'forgot_password');
                if (!$vr['ok'] && !$captchaOk) {
                    $this->json(['success'=>false,'message'=>'Verificação anti-bot obrigatória.']);
                }
            }
        } catch (\Throwable $t) { /* ignore */ }

        // Cooldown configurável via .env (padrão 180s)
        $cooldownSec = (int)(($_ENV['FORGOT_COOLDOWN_SECONDS'] ?? 180));
        try {
            $last = Database::fetch("SELECT created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1", [$email]);
            if ($last && !empty($last['created_at'])) {
                $lastTs = strtotime((string)$last['created_at']);
                if ($lastTs && (time() - $lastTs) < $cooldownSec) {
                    $remaining = $cooldownSec - (time() - $lastTs);
                    $resp = $genericResponse; $resp['cooldown'] = $remaining;
                    $this->json($resp);
                }
            }
        } catch (\Throwable $t) { /* ignore */ }

        // Rate limit configurável via .env (padrão: 5 requisições por hora)
        $rlMax = (int)(($_ENV['FORGOT_RATE_LIMIT_MAX'] ?? 5));
        $rlWindow = (int)(($_ENV['FORGOT_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600));
        if ($rlMax > 0 && $rlWindow > 0) {
            try {
                $since = Carbon::now()->subSeconds($rlWindow)->toDateTimeString();
                $row = Database::fetch("SELECT COUNT(*) AS c FROM password_resets WHERE email = ? AND created_at >= ?", [$email, $since]);
                if ($row && (int)($row['c'] ?? 0) >= $rlMax) {
                    $resp = $genericResponse; $resp['cooldown'] = $cooldownSec; $resp['rate_limited'] = true;
                    $this->json($resp);
                }
            } catch (\Throwable $t) { /* ignore */ }
        }

        try {
            $user = Database::fetch("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL", [$email]);
            if ($user) {
                $tokenRaw = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $tokenRaw);
                Database::delete('password_resets', ['email' => $email]);
                Database::insert('password_resets', [
                    'email' => $email,
                    'token' => $tokenHash,
                    'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
                    'created_at' => Carbon::now()->toDateTimeString()
                ]);

                $baseUrl = Application::getInstance()->config('app.url');
                $resetUrl = rtrim($baseUrl, '/') . '/reset-password?token=' . urlencode($tokenRaw);
                try {
                    $emailService = new \App\Services\EmailService();
                    $emailService->sendPasswordReset($email, (string)($user['name'] ?? 'Usuário'), $resetUrl);
                } catch (\Throwable $t) {
                    Application::getInstance()->logger()->warning('Falha ao enviar email de redefinição: ' . $t->getMessage());
                }
                Application::getInstance()->logger()->info('Password reset solicitado', ['email_mask' => substr($email,0,2) . '***' . strstr($email,'@')]);
            }
        } catch (\Throwable $e) {
            Application::getInstance()->logger()->error('Erro em forgotPassword: ' . $e->getMessage());
        }

        // Retornar também o cooldown sugerido para UI
        $genericResponse['cooldown'] = $cooldownSec;
        $this->json($genericResponse);
    }

    public function showResetPassword(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $this->regenerateCsrf();
        }

        $token = $_GET['token'] ?? '';
        $valid = false;
        if (is_string($token) && $token !== '') {
            try {
                $hash = hash('sha256', $token);
                $pr = Database::fetch("SELECT email FROM password_resets WHERE token = ? AND (expires_at > NOW()) AND used_at IS NULL", [$hash]);
                $valid = (bool) $pr;
            } catch (\Throwable $t) {}
        }

        $this->view('auth/reset-password', ['reset_token' => $token, 'token_valid' => $valid]);
    }

    public function resetPassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $token = trim($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

        if ($token === '') {
            $this->json(['success' => false, 'message' => 'Token inválido']);
        }

        $validator = Validation::createValidator();
        $viol = $validator->validate($password, new Assert\Sequentially([
            new Assert\NotBlank(message: 'Senha é obrigatória'),
            new Assert\Length(min: 8, minMessage: 'A senha deve ter pelo menos {{ limit }} caracteres'),
            new Assert\Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', message: 'A senha deve conter pelo menos 1 letra minúscula, 1 maiúscula e 1 número')
        ]));
        if (count($viol) > 0) {
            $this->json(['success' => false, 'message' => $viol[0]->getMessage()]);
        }
        if ($password !== $passwordConfirmation) {
            $this->json(['success' => false, 'message' => 'As senhas não coincidem']);
        }

        try {
            $hash = hash('sha256', $token);
            $pr = Database::fetch("SELECT email FROM password_resets WHERE token = ? AND (expires_at > NOW()) AND used_at IS NULL", [$hash]);
            if (!$pr || empty($pr['email'])) {
                $this->json(['success' => false, 'message' => 'Link inválido ou expirado']);
            }
            $email = $pr['email'];
            $user = Database::fetch("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [$email]);
            if (!$user) {
                $this->json(['success' => true, 'message' => 'Senha redefinida com sucesso']);
            }
            Database::update('users', [
                'password' => password_hash($password, PASSWORD_ARGON2ID),
                'updated_at' => Carbon::now()->toDateTimeString()
            ], ['id' => $user['id']]);

            Database::update('password_resets', [ 'used_at' => Carbon::now()->toDateTimeString() ], ['token' => $hash]);
            Database::delete('password_resets', ['email' => $email]);

            Application::getInstance()->logger()->info('Senha redefinida', ['email_mask' => substr($email,0,2) . '***' . strstr($email,'@')]);
            $this->json(['success' => true, 'message' => 'Senha redefinida com sucesso', 'redirect' => '/']);
        } catch (\Throwable $e) {
            Application::getInstance()->logger()->error('Erro em resetPassword: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    private function isRateLimited(string $email): bool
    {
        try {
            $attempts = Database::fetchAll(
                "SELECT * FROM login_attempts 
                 WHERE email = ? AND attempted_at > ? AND success = FALSE",
                [$email, Carbon::now()->subMinutes(15)->toDateTimeString()]
            );

            return count($attempts) >= 5;
        } catch (\Exception $e) {
            // Se houver erro de banco, não aplicar rate limiting
            Application::getInstance()->logger()->warning('Erro ao verificar rate limiting: ' . $e->getMessage());
            return false;
        }
    }

    private function checkHoneypot(): void
    {
        $hp = isset($_POST['website']) ? trim((string)$_POST['website']) : '';
        if ($hp !== '') {
            $this->json(['success' => false, 'message' => 'Requisição inválida'], 400);
        }
    }

    private function logLoginAttempt(string $email, bool $success): void
    {
        try {
            Database::insert('login_attempts', [
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'success' => (bool)$success,  // Garantir que é boolean
                'attempted_at' => Carbon::now()->toDateTimeString()
            ]);

            // Limpar tentativas antigas (mais de 24 horas)
            Database::query(
                "DELETE FROM login_attempts WHERE attempted_at < ?",
                [Carbon::now()->subDay()->toDateTimeString()]
            );
        } catch (\Exception $e) {
            // Se houver erro de banco, apenas logar o erro
            Application::getInstance()->logger()->warning('Erro ao registrar tentativa de login: ' . $e->getMessage());
        }
    }
}

