<?php
/**
 * Discord Integration Migrations
 * Este arquivo contém todas as migrações necessárias para suportar a integração com Discord
 *
 * Para executar as migrações manualmente, copie e execute cada SQL no seu cliente MySQL
 */

return [
    'migrations' => [
        // Migration 012: Criar tabela discord_users
        '012_create_discord_users_table' => "
            -- Tabela para rastrear usuários conectados ao Discord
            CREATE TABLE IF NOT EXISTS discord_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                discord_id VARCHAR(255) NULL,
                discord_username VARCHAR(255) NULL,
                discord_avatar VARCHAR(500) NULL,
                verification_code VARCHAR(32) UNIQUE NOT NULL,
                is_verified BOOLEAN DEFAULT FALSE,
                verified_at TIMESTAMP NULL,
                last_sync_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_discord_id (discord_id),
                INDEX idx_user_id (user_id),
                INDEX idx_is_verified (is_verified),
                INDEX idx_verification_code (verification_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",

        // Migration 013: Criar tabela discord_logs
        '013_create_discord_logs_table' => "
            -- Tabela para registrar logs de ações do Discord
            CREATE TABLE IF NOT EXISTS discord_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",

        // Migration 014: Criar trigger para auto-criar discord_users
        '014_create_discord_users_trigger' => "
            DELIMITER ;;
            DROP TRIGGER IF EXISTS create_discord_user_on_user_create;;
            CREATE TRIGGER create_discord_user_on_user_create
            AFTER INSERT ON users
            FOR EACH ROW
            BEGIN
                DECLARE @random_code VARCHAR(32);
                SET @random_code = CONCAT(
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1),
                    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(RAND()*31)+1, 1)
                );
                INSERT INTO discord_users (user_id, verification_code, discord_id)
                VALUES (NEW.id, @random_code, NULL)
                ON DUPLICATE KEY UPDATE updated_at = NOW();
            END;;
            DELIMITER ;
        ",
    ],
    'instructions' => [
        'Execute as migrações nesta ordem:',
        '1. Criar tabela discord_users',
        '2. Criar tabela discord_logs',
        '3. Criar trigger para auto-criação de registros',
        '',
        'Alternativamente, execute os scripts SQL individuais em: database/migrations/01X_*.sql'
    ]
];
?>
