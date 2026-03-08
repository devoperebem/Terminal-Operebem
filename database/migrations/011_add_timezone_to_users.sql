-- Adiciona coluna timezone à tabela users
-- Padrão: America/Sao_Paulo (UTC-3)
ALTER TABLE users ADD COLUMN IF NOT EXISTS timezone VARCHAR(64) DEFAULT 'America/Sao_Paulo';

-- Índice para melhor performance
CREATE INDEX IF NOT EXISTS idx_users_timezone ON users(timezone);

-- Comentário explicativo
COMMENT ON COLUMN users.timezone IS 'Fuso horário do usuário para exibição de timestamps (formato IANA timezone database)';
