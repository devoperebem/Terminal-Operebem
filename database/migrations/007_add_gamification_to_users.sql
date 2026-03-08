-- Migration: Adicionar sistema de gamificação
-- Data: 2025-01-13
-- Descrição: Adiciona colunas de XP, Streak e Level aos usuários

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS xp INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS streak INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN IF NOT EXISTS level INTEGER DEFAULT 1 NOT NULL,
ADD COLUMN IF NOT EXISTS last_xp_earned_at TIMESTAMP NULL;

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_users_xp ON users(xp DESC);
CREATE INDEX IF NOT EXISTS idx_users_level ON users(level DESC);
CREATE INDEX IF NOT EXISTS idx_users_streak ON users(streak DESC);

-- Comentários
COMMENT ON COLUMN users.xp IS 'Pontos de experiência totais do usuário';
COMMENT ON COLUMN users.streak IS 'Dias consecutivos de login';
COMMENT ON COLUMN users.level IS 'Nível calculado baseado em XP';
COMMENT ON COLUMN users.last_xp_earned_at IS 'Última vez que ganhou XP (qualquer fonte)';
