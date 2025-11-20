<?php

return [
    // Usar DB_CONNECTION do .env (pgsql ou mysql)
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    
    'connections' => [
        // Banco MySQL/MariaDB (compatibilidade e backup)
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? '',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ],
        
        // Banco PostgreSQL (sistema e usuários - PRINCIPAL)
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_DATABASE'] ?? '',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ],
        
        // Banco PostgreSQL para cotações (separado)
        'quotes' => [
            'driver' => 'pgsql',
            'host' => $_ENV['QUOTES_DB_HOST'] ?? 'localhost',
            'port' => $_ENV['QUOTES_DB_PORT'] ?? '5432',
            'database' => $_ENV['QUOTES_DB_DATABASE'] ?? '',
            'username' => $_ENV['QUOTES_DB_USERNAME'] ?? '',
            'password' => $_ENV['QUOTES_DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ],
        // Aluno DB (PostgreSQL) - usado pelo Secure Admin para gestão integrada
        'aluno' => [
            'driver' => 'pgsql',
            'host' => $_ENV['ALUNO_DB_HOST'] ?? '',
            'port' => $_ENV['ALUNO_DB_PORT'] ?? '5432',
            'database' => $_ENV['ALUNO_DB_DATABASE'] ?? '',
            'username' => $_ENV['ALUNO_DB_USERNAME'] ?? '',
            'password' => $_ENV['ALUNO_DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ]
    ]
];
