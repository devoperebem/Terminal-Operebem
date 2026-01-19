-- Migration: 027_create_admin_audit_logs.sql
-- Cria tabela para logs de auditoria de ações administrativas e do usuário

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id SERIAL PRIMARY KEY,
    
    -- Entidade afetada (usuário que sofreu a ação)
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    -- Quem executou a ação
    actor_type VARCHAR(20) NOT NULL, -- 'admin' ou 'user'
    actor_id INTEGER,                -- ID do admin ou user que fez a ação
    actor_email VARCHAR(255),
    
    -- Informações da ação
    action_type VARCHAR(100) NOT NULL,  -- ex: "subscription_canceled", "password_reset_by_admin"
    entity_type VARCHAR(50) NOT NULL,   -- ex: "subscription", "user", "coupon"
    entity_id INTEGER,                  -- ID do registro afetado
    
    -- Detalhes da mudança
    description TEXT,                   -- Descrição legível da ação
    changes JSONB,                      -- JSON com valores antigos e novos: {old: {...}, new: {...}}
    
    -- Contexto da requisição
    ip_address VARCHAR(45),             -- IPv4 ou IPv6
    user_agent TEXT,
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON admin_audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_actor ON admin_audit_logs(actor_type, actor_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action_type ON admin_audit_logs(action_type);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON admin_audit_logs(created_at DESC);

-- Comentários para documentação
COMMENT ON TABLE admin_audit_logs IS 'Log de auditoria de ações administrativas e do usuário';
COMMENT ON COLUMN admin_audit_logs.user_id IS 'ID do usuário afetado pela ação';
COMMENT ON COLUMN admin_audit_logs.actor_type IS 'Tipo de ator: admin ou user';
COMMENT ON COLUMN admin_audit_logs.actor_id IS 'ID do admin ou user que executou a ação';
COMMENT ON COLUMN admin_audit_logs.action_type IS 'Tipo de ação executada';
COMMENT ON COLUMN admin_audit_logs.entity_type IS 'Tipo de entidade afetada';
COMMENT ON COLUMN admin_audit_logs.entity_id IS 'ID do registro afetado';
COMMENT ON COLUMN admin_audit_logs.description IS 'Descrição legível da ação para exibição';
COMMENT ON COLUMN admin_audit_logs.changes IS 'JSON com valores antigos e novos';

-- Adicionar coluna para rastrear extensões de trial
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS trial_extended_days INTEGER DEFAULT 0;

COMMENT ON COLUMN subscriptions.trial_extended_days IS 'Total de dias de trial adicionados por extensões';

-- Adicionar soft delete para cupons
ALTER TABLE coupons 
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;

CREATE INDEX IF NOT EXISTS idx_coupons_deleted_at ON coupons(deleted_at);

-- Variável de ambiente para retenção de logs (documentação)
-- Adicionar ao .env: AUDIT_LOG_RETENTION_DAYS=90
