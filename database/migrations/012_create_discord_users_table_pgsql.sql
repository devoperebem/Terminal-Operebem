-- Tabela para rastrear usuários conectados ao Discord (PostgreSQL)
CREATE TABLE IF NOT EXISTS discord_users (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE,
    discord_id VARCHAR(255) NULL,
    discord_username VARCHAR(255) NULL,
    discord_avatar VARCHAR(500) NULL,
    verification_code VARCHAR(32) UNIQUE NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Nota: Não criamos índice único em discord_id porque ele pode ser NULL para múltiplos usuários
-- enquanto aguardam a verificação do Discord
CREATE INDEX IF NOT EXISTS idx_discord_id ON discord_users(discord_id);
CREATE INDEX IF NOT EXISTS idx_user_id ON discord_users(user_id);
CREATE INDEX IF NOT EXISTS idx_is_verified ON discord_users(is_verified);
CREATE INDEX IF NOT EXISTS idx_verification_code ON discord_users(verification_code);
