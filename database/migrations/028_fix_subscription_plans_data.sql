-- Migration: 028_fix_subscription_plans_data.sql
-- Corrige os dados dos planos de assinatura conforme estrutura correta:
-- FREE: Plano gratuito (sem Stripe)
-- PLUS: Assinatura mensal
-- PRO: Assinatura anual (R$879 à vista ou 12x R$87)

-- 1) Inserir plano FREE se não existir
INSERT INTO subscription_plans (name, slug, tier, interval_type, price_cents, supports_pix, is_installment, trial_days, display_order, is_featured, is_active, features)
VALUES (
    'Free', 'free', 'FREE', 'lifetime', 0, FALSE, FALSE, 0, 0, FALSE, TRUE,
    '["Dashboard Básico", "Cotações Delay 15min", "Watchlist (5 ativos)", "Comunidade Discord"]'::jsonb
)
ON CONFLICT (slug) DO UPDATE SET
    name = 'Free',
    tier = 'FREE',
    interval_type = 'lifetime',
    price_cents = 0,
    supports_pix = FALSE,
    is_installment = FALSE,
    trial_days = 0,
    display_order = 0,
    is_active = TRUE,
    features = '["Dashboard Básico", "Cotações Delay 15min", "Watchlist (5 ativos)", "Comunidade Discord"]'::jsonb,
    updated_at = CURRENT_TIMESTAMP;

-- 2) Corrigir plano PLUS Mensal
UPDATE subscription_plans SET
    name = 'PLUS Mensal',
    tier = 'PLUS',
    interval_type = 'month',
    interval_count = 1,
    is_installment = FALSE,
    display_order = 1,
    is_featured = FALSE,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'plus_monthly';

-- 3) Corrigir plano PRO Anual (à vista = R$879,00 = 87900 cents)
UPDATE subscription_plans SET
    name = 'PRO Anual',
    tier = 'PRO',
    interval_type = 'year',
    interval_count = 1,
    price_cents = 87900,
    is_installment = FALSE,
    supports_pix = TRUE,
    display_order = 2,
    is_featured = TRUE,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'pro_yearly';

-- 4) Corrigir plano PRO Anual Parcelado (12x R$87,00 = 104400 cents total)
UPDATE subscription_plans SET
    name = 'PRO Anual (12x)',
    tier = 'PRO',
    interval_type = 'year',
    interval_count = 1,
    price_cents = 104400,
    is_installment = TRUE,
    installment_count = 12,
    supports_pix = FALSE,
    display_order = 3,
    is_featured = FALSE,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'pro_yearly_installments';
