-- Migration: 020_create_subscriptions_table.sql
-- Tabela para gerenciar assinaturas de usuários

CREATE TABLE IF NOT EXISTS subscriptions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Stripe IDs
    stripe_customer_id VARCHAR(255),
    stripe_subscription_id VARCHAR(255) UNIQUE,
    stripe_price_id VARCHAR(255),
    
    -- Plano
    plan_slug VARCHAR(50) NOT NULL, -- 'plus_monthly', 'pro_yearly', 'pro_yearly_installments'
    tier VARCHAR(20) NOT NULL, -- 'PLUS', 'PRO'
    interval_type VARCHAR(20) NOT NULL, -- 'month', 'year'
    
    -- Status
    status VARCHAR(50) NOT NULL DEFAULT 'incomplete',
    -- Valores: incomplete, trialing, active, past_due, canceled, unpaid, manual
    
    -- Trial
    trial_start TIMESTAMP,
    trial_end TIMESTAMP,
    trial_used BOOLEAN DEFAULT FALSE,
    
    -- Período atual
    current_period_start TIMESTAMP,
    current_period_end TIMESTAMP,
    
    -- Cancelamento
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    canceled_at TIMESTAMP,
    ended_at TIMESTAMP,
    
    -- Origem (stripe ou admin)
    source VARCHAR(20) DEFAULT 'stripe', -- 'stripe', 'admin'
    admin_granted_by INTEGER REFERENCES admin_users(id),
    admin_notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_subscription_id ON subscriptions(stripe_subscription_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_customer_id ON subscriptions(stripe_customer_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_tier ON subscriptions(tier);
