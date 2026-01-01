-- Migration: 022_create_subscription_plans_table.sql
-- Tabela para configuração dos planos de assinatura

CREATE TABLE IF NOT EXISTS subscription_plans (
    id SERIAL PRIMARY KEY,
    
    -- Identificação
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    
    -- Tier e intervalo
    tier VARCHAR(20) NOT NULL, -- 'PLUS', 'PRO'
    interval_type VARCHAR(20) NOT NULL, -- 'month', 'year'
    interval_count INTEGER DEFAULT 1,
    
    -- Stripe
    stripe_product_id VARCHAR(255),
    stripe_price_id VARCHAR(255),
    
    -- Preço (para exibição)
    price_cents INTEGER NOT NULL,
    currency VARCHAR(10) DEFAULT 'BRL',
    
    -- Opções
    is_installment BOOLEAN DEFAULT FALSE,
    installment_count INTEGER,
    supports_pix BOOLEAN DEFAULT FALSE,
    trial_days INTEGER DEFAULT 7,
    
    -- Features destacadas (JSON)
    features JSONB DEFAULT '[]',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INTEGER DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_subscription_plans_slug ON subscription_plans(slug);
CREATE INDEX IF NOT EXISTS idx_subscription_plans_tier ON subscription_plans(tier);
CREATE INDEX IF NOT EXISTS idx_subscription_plans_is_active ON subscription_plans(is_active);

-- Dados iniciais (serão atualizados com IDs reais do Stripe)
INSERT INTO subscription_plans (name, slug, tier, interval_type, price_cents, supports_pix, is_installment, trial_days, display_order, is_featured, features)
VALUES 
    ('PLUS Mensal', 'plus_monthly', 'PLUS', 'month', 2990, FALSE, FALSE, 7, 1, FALSE, 
     '["Dashboard Ouro", "Indicadores 1 min", "Snapshot Avançada", "Médias Cards"]'::jsonb),
    ('PRO Anual', 'pro_yearly', 'PRO', 'year', 69700, TRUE, FALSE, 7, 2, TRUE, 
     '["Tudo do PLUS", "Indicadores Tempo Real", "Dashboard NASDAQ", "Suporte Prioritário"]'::jsonb),
    ('PRO Anual (12x)', 'pro_yearly_installments', 'PRO', 'year', 83880, FALSE, TRUE, 7, 3, FALSE, 
     '["Tudo do PLUS", "Indicadores Tempo Real", "Dashboard NASDAQ", "Suporte Prioritário"]'::jsonb)
ON CONFLICT (slug) DO NOTHING;
