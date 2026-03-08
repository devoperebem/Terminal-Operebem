-- Migration: 026_add_discount_to_subscription_plans.sql
-- Adiciona campos para sistema de promoções/descontos temporários

-- Campos para descontos promocionais
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_percentage INTEGER DEFAULT 0 
CHECK (discount_percentage >= 0 AND discount_percentage <= 100);

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_start_date TIMESTAMP NULL;

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_end_date TIMESTAMP NULL;

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_label VARCHAR(100) NULL;

-- Comentários para documentação
COMMENT ON COLUMN subscription_plans.discount_percentage IS 'Percentual de desconto (0-100) aplicado temporariamente';
COMMENT ON COLUMN subscription_plans.discount_start_date IS 'Data de início da promoção';
COMMENT ON COLUMN subscription_plans.discount_end_date IS 'Data de fim da promoção';
COMMENT ON COLUMN subscription_plans.discount_label IS 'Label promocional (ex: "BLACK FRIDAY 30% OFF")';

-- Índice para consultas de planos com promoção ativa
CREATE INDEX IF NOT EXISTS idx_subscription_plans_discount_dates 
ON subscription_plans(discount_start_date, discount_end_date) 
WHERE discount_percentage > 0;
