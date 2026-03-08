-- Migration: Tabela de histórico de XP
-- Data: 2025-01-13
-- Descrição: Rastreia todas as transações de XP para auditoria e gamificação

CREATE TABLE IF NOT EXISTS xp_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount INTEGER NOT NULL,
    source VARCHAR(50) NOT NULL, -- 'daily_login', 'lesson_completed', 'course_completed', 'streak_bonus', 'manual'
    source_id INTEGER NULL, -- ID da aula/curso se aplicável
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_xp_history_user ON xp_history(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_xp_history_source ON xp_history(source);
CREATE INDEX IF NOT EXISTS idx_xp_history_created ON xp_history(created_at DESC);

COMMENT ON TABLE xp_history IS 'Histórico de todas as transações de XP';
COMMENT ON COLUMN xp_history.source IS 'Origem do XP: daily_login, lesson_completed, course_completed, streak_bonus, manual';
COMMENT ON COLUMN xp_history.source_id IS 'ID da entidade relacionada (lesson_id ou course_id)';
