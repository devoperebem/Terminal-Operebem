<?php

namespace App\Core;

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NativeMailerHandler;

class Application
{
    private static ?Application $instance = null;
    private array $config = [];
    private ?Logger $logger = null;
    private array $services = [];
    private string $basePath;

    private function __construct()
    {
        $this->basePath = dirname(__DIR__, 2);
        
        // Set default timezone for all date/time functions
        date_default_timezone_set('America/Sao_Paulo');
        
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->setupLogger();
        $this->checkEnvironmentHealth();
        $this->setupErrorHandling();
        $this->sendSecurityHeaders();
        $this->startSession();
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        $base = dirname(__DIR__, 2);
        $dotenv = Dotenv::createMutable($base);
        $dotenv->safeLoad();
        // Permitir overrides em .env.local, se existir
        $local = $base . '/.env.local';
        if (is_file($local)) {
            $serverName = (string)($_SERVER['SERVER_NAME'] ?? '');
            $httpHost = (string)($_SERVER['HTTP_HOST'] ?? '');
            $appEnv = (string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '');
            $isWindows = stripos(PHP_OS_FAMILY, 'Windows') !== false;
            $isLocalHost = in_array($serverName, ['localhost', '127.0.0.1'], true) || in_array($httpHost, ['localhost', '127.0.0.1'], true);
            $shouldLoadLocal = $isWindows || $isLocalHost || strcasecmp($appEnv, 'local') === 0;
            if ($shouldLoadLocal) {
                try {
                    $dotenvLocal = Dotenv::createMutable($base, '.env.local');
                    $dotenvLocal->load();
                } catch (\Throwable $t) { /* ignore */ }
            }
        }
    }

    private function loadConfiguration(): void
    {
        $configPath = dirname(__DIR__, 2) . '/config';
        
        $this->config = [
            'app' => require $configPath . '/app.php',
            'database' => require $configPath . '/database.php',
        ];
        // Carregar configuração de e-mail se existir
        $mailCfg = $configPath . '/mail.php';
        if (file_exists($mailCfg)) {
            $this->config['mail'] = require $mailCfg;
        } else {
            $this->config['mail'] = [];
        }
        // Carregar configuração de admin se existir
        $adminCfg = $configPath . '/admin.php';
        if (file_exists($adminCfg)) {
            $this->config['admin'] = require $adminCfg;
        }
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('terminal-operebem');
        try {
            $this->logger->setTimezone(new \DateTimeZone('America/Sao_Paulo'));
        } catch (\Throwable $t) {
            // keep default timezone on failure
        }
        
        try {
            // Garantir que o diretório de logs existe
            $logDir = $this->basePath . '/storage/logs';
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                    // Se não conseguir criar, usar diretório temporário do sistema
                    $logDir = sys_get_temp_dir() . '/terminal_operebem_logs';
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0755, true);
                    }
                }
            }
            
            // Selecionar nível via env
            $envLevel = strtolower((string)($_ENV['LOG_LEVEL'] ?? 'info'));
            $levelMap = [
                'debug' => Logger::DEBUG,
                'info' => Logger::INFO,
                'notice' => Logger::NOTICE,
                'warning' => Logger::WARNING,
                'error' => Logger::ERROR,
                'critical' => Logger::CRITICAL,
                'alert' => Logger::ALERT,
                'emergency' => Logger::EMERGENCY,
            ];
            $level = $levelMap[$envLevel] ?? Logger::INFO;

            // Handler para arquivo de log geral (rotativo quando LOG_CHANNEL=daily)
            $channel = strtolower((string)($_ENV['LOG_CHANNEL'] ?? 'single'));
            if ($channel === 'daily') {
                $fileHandler = new RotatingFileHandler($logDir . '/app.log', 14, $level); // 14 dias
            } else {
                $fileHandler = new StreamHandler($logDir . '/app.log', $level);
            }
            $fileHandler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));
            
            $this->logger->pushHandler($fileHandler);
            
            // Handler para erros críticos
            $errorHandler = new StreamHandler($logDir . '/error.log', Logger::ERROR);
            $errorHandler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));
            $this->logger->pushHandler($errorHandler);

            // Handler para eventos de segurança (avisos para cima)
            $securityHandler = new StreamHandler($logDir . '/security.log', Logger::WARNING);
            $securityHandler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));
            $this->logger->pushHandler($securityHandler);
            
            // Handler para debug (apenas em desenvolvimento)
            if ($this->config('app.debug', false)) {
                $debugHandler = new StreamHandler($logDir . '/debug.log', Logger::DEBUG);
                $this->logger->pushHandler($debugHandler);
            }

            // Alerta por email em erros (opcional via env ALERT_EMAIL) — habilitar somente quando MAIL_MAILER for 'mail'/'native'
            if (PHP_SAPI !== 'cli') {
                $driver = strtolower((string)($_ENV['MAIL_MAILER'] ?? 'smtp'));
                if (in_array($driver, ['mail', 'native'], true)) {
                    $alertTo = $_ENV['ALERT_EMAIL'] ?? '';
                    $from = $_ENV['MAIL_FROM_ADDRESS'] ?? 'alerts@terminal.local';
                    if (!empty($alertTo)) {
                        try {
                            $mailer = new NativeMailerHandler($alertTo, 'Terminal Operebem ALERT', $from, Logger::ERROR);
                            $this->logger->pushHandler($mailer);
                        } catch (\Throwable $t) {
                            // Ignorar falhas no handler de email
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Se falhar completamente, criar logger básico sem arquivo
            $this->logger = new Logger('terminal-operebem');
            // Em produção, logs vão para syslog ou são ignorados
        }
    }

    private function setupErrorHandling(): void
    {
        $debug = $this->config('app.debug', false);
        
        if ($debug) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $sessionConfig = $this->config('app.session', [
                'lifetime' => 120,
                'cookie_path' => '/',
                'cookie_domain' => null,
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_name' => 'terminal_operebem_session'
            ]);
            
            try {
                $isHttps = $this->isHttps();
                @ini_set('session.use_strict_mode', '1');
                @ini_set('session.use_only_cookies', '1');
                @ini_set('session.sid_length', '48');
                @ini_set('session.sid_bits_per_character', '6');
                @ini_set('session.gc_maxlifetime', (string)($sessionConfig['lifetime'] * 60));
                session_set_cookie_params([
                    'lifetime' => $sessionConfig['lifetime'] * 60,
                    'path' => $sessionConfig['cookie_path'],
                    // Enforce secure cookie de forma condicional (HTTPS)
                    'secure' => $isHttps,
                    'httponly' => $sessionConfig['cookie_httponly'],
                    'samesite' => $sessionConfig['cookie_samesite']
                ]);
                
                session_name($sessionConfig['cookie_name']);
                session_start();
            } catch (\Exception $e) {
                // Se falhar, tentar sessão básica
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
            }
        } elseif (session_status() === PHP_SESSION_NONE) {
            // Headers já enviados, tentar sessão básica
            @session_start();
        }
    }

    public function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        $xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (is_string($xfp) && strtolower($xfp) === 'https') {
            return true;
        }
        $xfs = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '';
        if (is_string($xfs) && strtolower($xfs) === 'on') {
            return true;
        }
        return false;
    }

    private function sendSecurityHeaders(): void
    {
        try {
            if (PHP_SAPI === 'cli') return;
            if (headers_sent()) return;
            $env = strtolower((string)($_ENV['APP_ENV'] ?? 'production'));
            if ($env !== 'production') return;
            if (!$this->isHttps()) return;
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        } catch (\Throwable $t) { /* ignore */ }
    }

    private function checkEnvironmentHealth(): void
    {
        // Verificações mínimas para alertar inconsistências de ambiente
        try {
            $issues = [];
            $req = [
                'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
                'QUOTES_DB_HOST', 'QUOTES_DB_DATABASE', 'QUOTES_DB_USERNAME', 'QUOTES_DB_PASSWORD',
                'JWT_SECRET', 'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'
            ];
            foreach ($req as $k) {
                if (empty($_ENV[$k])) { $issues[] = "ENV ausente: {$k}"; }
            }
            if (!empty($_ENV['JWT_SECRET']) && strlen((string)$_ENV['JWT_SECRET']) < 32) {
                $issues[] = 'JWT_SECRET muito curto';
            }
            $stripeEnabled = strtolower((string)($_ENV['STRIPE_ENABLED'] ?? 'false')) === 'true';
            if ($stripeEnabled && !empty($_ENV['STRIPE_SECRET_KEY']) && str_starts_with((string)$_ENV['STRIPE_SECRET_KEY'], 'sk_test_')) {
                $issues[] = 'Stripe em modo teste em produção';
            }
            if ($issues) {
                foreach ($issues as $m) {
                    $this->logger->warning('[ENV] ' . $m);
                }
            }
        } catch (\Throwable $t) {
            // Ignorar falhas nesta checagem
        }
    }

    public function handleError($severity, $message, $file, $line): void
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        try {
            $this->logger->error("PHP Error: $message", [
                'file' => $file,
                'line' => $line,
                'severity' => $severity
            ]);
        } catch (\Exception $e) {
            // Se logger falhar, continuar sem logging
        }
    }

    public function handleException($exception): void
    {
        try {
            $this->logger->error("Uncaught Exception: " . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        } catch (\Exception $e) {
            // Se logger falhar, continuar sem logging
        }
        
        if ($this->config('app.debug', false)) {
            echo "<pre>" . $exception . "</pre>";
        } else {
            http_response_code(500);
            $errorFile = $this->basePath . '/src/Views/errors/500.php';
            if (file_exists($errorFile)) {
                include $errorFile;
            } else {
                echo "<h1>Erro interno do servidor</h1>";
            }
        }
    }

    public function config(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    public function register(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    public function get(string $name)
    {
        return $this->services[$name] ?? null;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
