-- Migration: 021_create_payment_history_table.sql
-- Tabela para histórico de pagamentos

CREATE TABLE IF NOT EXISTS payment_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES subscriptions(id) ON DELETE SET NULL,
    
    -- Stripe IDs
    stripe_payment_intent_id VARCHAR(255),
    stripe_invoice_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    
    -- Valores
    amount_cents INTEGER NOT NULL,
    currency VARCHAR(10) DEFAULT 'BRL',
    
    -- Status
    status VARCHAR(50) NOT NULL,
    -- Valores: pending, processing, succeeded, failed, refunded, disputed
    
    -- Método de pagamento
    payment_method_type VARCHAR(50), -- 'card', 'pix'
    card_last4 VARCHAR(4),
    card_brand VARCHAR(20),
    
    -- Detalhes
    description TEXT,
    failure_code VARCHAR(100),
    failure_message TEXT,
    
    -- URLs do Stripe
    hosted_invoice_url TEXT,
    invoice_pdf_url TEXT,
    receipt_url TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_payment_history_user_id ON payment_history(user_id);
CREATE INDEX IF NOT EXISTS idx_payment_history_subscription_id ON payment_history(subscription_id);
CREATE INDEX IF NOT EXISTS idx_payment_history_status ON payment_history(status);
CREATE INDEX IF NOT EXISTS idx_payment_history_stripe_invoice_id ON payment_history(stripe_invoice_id);
CREATE INDEX IF NOT EXISTS idx_payment_history_created_at ON payment_history(created_at);
