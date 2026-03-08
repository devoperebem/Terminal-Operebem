-- Migration: Create xp_settings table
-- Description: Tabela para armazenar configurações dinâmicas do sistema de XP
-- Created: 2025-11-14

CREATE TABLE IF NOT EXISTS xp_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Índice para busca rápida por chave
CREATE INDEX IF NOT EXISTS idx_xp_settings_key ON xp_settings(setting_key);

-- Comentários
COMMENT ON TABLE xp_settings IS 'Configurações dinâmicas do sistema de gamificação';
COMMENT ON COLUMN xp_settings.setting_key IS 'Chave única da configuração';
COMMENT ON COLUMN xp_settings.setting_value IS 'Valor numérico da configuração (XP)';
COMMENT ON COLUMN xp_settings.description IS 'Descrição da configuração';
COMMENT ON COLUMN xp_settings.updated_at IS 'Data da última atualização';
COMMENT ON COLUMN xp_settings.updated_by IS 'ID do admin que fez a última atualização';

-- Inserir valores padrão
INSERT INTO xp_settings (setting_key, setting_value, description) VALUES
    ('xp_daily_login', 5, 'XP concedido por login diário'),
    ('xp_lesson_base', 10, 'XP base por aula assistida'),
    ('xp_lesson_bonus_30min', 5, 'Bônus de XP para aulas com 30+ minutos'),
    ('xp_lesson_bonus_1h', 10, 'Bônus de XP para aulas com 1h+ de duração'),
    ('xp_course_complete', 50, 'XP concedido ao completar um curso')
ON CONFLICT (setting_key) DO NOTHING;
