-- Migration: 023_create_coupons_tables.sql
-- Tabelas para gerenciamento de cupons de desconto

-- Tabela de cupons
CREATE TABLE IF NOT EXISTS coupons (
    id SERIAL PRIMARY KEY,
    
    -- Código
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100),
    
    -- Stripe
    stripe_coupon_id VARCHAR(255) UNIQUE,
    stripe_promotion_code_id VARCHAR(255),
    
    -- Desconto
    discount_type VARCHAR(20) NOT NULL, -- 'percent', 'fixed'
    discount_value INTEGER NOT NULL, -- percentual (0-100) ou centavos
    
    -- Restrições
    max_redemptions INTEGER, -- NULL = ilimitado
    redemptions_count INTEGER DEFAULT 0,
    valid_from TIMESTAMP,
    valid_until TIMESTAMP,
    min_amount_cents INTEGER, -- valor mínimo do pedido
    first_time_only BOOLEAN DEFAULT FALSE, -- apenas primeira assinatura
    
    -- Planos aplicáveis (NULL = todos)
    applicable_plans TEXT[], -- array de slugs: ['plus_monthly', 'pro_yearly']
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Metadata
    created_by INTEGER REFERENCES admin_users(id),
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices de cupons
CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code);
CREATE INDEX IF NOT EXISTS idx_coupons_is_active ON coupons(is_active);
CREATE INDEX IF NOT EXISTS idx_coupons_valid_until ON coupons(valid_until);

-- Tabela de resgates de cupons
CREATE TABLE IF NOT EXISTS coupon_redemptions (
    id SERIAL PRIMARY KEY,
    coupon_id INTEGER NOT NULL REFERENCES coupons(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES subscriptions(id) ON DELETE SET NULL,
    
    -- Valores
    original_amount_cents INTEGER NOT NULL,
    discount_amount_cents INTEGER NOT NULL,
    final_amount_cents INTEGER NOT NULL,
    
    -- Timestamps
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices de resgates
CREATE INDEX IF NOT EXISTS idx_coupon_redemptions_coupon_id ON coupon_redemptions(coupon_id);
CREATE INDEX IF NOT EXISTS idx_coupon_redemptions_user_id ON coupon_redemptions(user_id);
