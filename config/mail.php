<?php

return [
    'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',
    
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.hostinger.com',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'timeout' => null,
        ]
    ],
    
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@operebem.com.br',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Terminal Operebem',
    ],
    
    'templates' => [
        'welcome' => [
            'subject' => 'Bem-vindo ao Terminal Operebem!',
            'template' => 'emails/welcome'
        ],
        'password_reset' => [
            'subject' => 'Redefinição de Senha - Terminal Operebem',
            'template' => 'emails/password_reset'
        ],
        'email_verification' => [
            'subject' => 'Verificação de Email - Terminal Operebem',
            'template' => 'emails/email_verification'
        ]
    ]
];
