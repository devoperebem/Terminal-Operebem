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
    -- Nota: Não criamos índice único em discord_id porque ele pode ser NULL para múltiplos usuários
    INDEX idx_discord_id (discord_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_verified (is_verified),
    INDEX idx_verification_code (verification_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
