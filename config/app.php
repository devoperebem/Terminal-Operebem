<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Terminal Operebem',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'https://terminal.operebem.com.br',
    
    'timezone' => 'America/Sao_Paulo',
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'expiration' => (int) ($_ENV['JWT_EXPIRATION'] ?? 86400), // 24 horas
        'algorithm' => 'HS256'
    ],
    
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME_MINUTES'] ?? 720),
        'cookie_name' => 'terminal_operebem_session',
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax' // Mudado de Strict para Lax (compatibilidade iPhone/Safari)
    ],
    
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],
    
    'recaptcha' => [
        'site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
        'secret_key' => $_ENV['RECAPTCHA_SECRET_KEY'] ?? '',
        'enabled' => !empty($_ENV['RECAPTCHA_SITE_KEY'])
    ]
];
