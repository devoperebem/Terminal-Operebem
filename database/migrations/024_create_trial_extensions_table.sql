-- Migration: 024_create_trial_extensions_table.sql
-- Tabela para registrar extensões de trial feitas pelo admin

CREATE TABLE IF NOT EXISTS trial_extensions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES subscriptions(id) ON DELETE SET NULL,
    
    -- Extensão
    days_extended INTEGER NOT NULL,
    previous_trial_end TIMESTAMP,
    new_trial_end TIMESTAMP NOT NULL,
    
    -- Admin
    granted_by INTEGER NOT NULL REFERENCES admin_users(id),
    reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_trial_extensions_user_id ON trial_extensions(user_id);
CREATE INDEX IF NOT EXISTS idx_trial_extensions_subscription_id ON trial_extensions(subscription_id);
CREATE INDEX IF NOT EXISTS idx_trial_extensions_granted_by ON trial_extensions(granted_by);
