-- Migration: 031_update_plan_front_display_cta_urls.sql
-- Standardize CTA URLs for all systems

-- PRO plans → direct to checkout
UPDATE plan_front_display 
SET cta_url = '/subscription/checkout?plan=pro_yearly_installments',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'pro_yearly_installments')
  AND system_key = 'terminal';

UPDATE plan_front_display 
SET cta_url = 'https://terminal.operebem.com.br/subscription/checkout?plan=pro_yearly_installments',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'pro_yearly_installments')
  AND system_key IN ('portal_aluno', 'diario_trades');

-- PLUS plans → plans page
UPDATE plan_front_display 
SET cta_url = '/subscription/plans',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'plus_monthly')
  AND system_key = 'terminal';

UPDATE plan_front_display 
SET cta_url = 'https://terminal.operebem.com.br/subscription/plans',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'plus_monthly')
  AND system_key IN ('portal_aluno', 'diario_trades');

-- FREE plans → register
UPDATE plan_front_display 
SET cta_url = '/?modal=register',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'free')
  AND system_key = 'terminal';

UPDATE plan_front_display 
SET cta_url = 'https://terminal.operebem.com.br/?modal=register',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'free')
  AND system_key IN ('portal_aluno', 'diario_trades');

-- Apenas Diário → checkout
UPDATE plan_front_display 
SET cta_url = 'https://terminal.operebem.com.br/subscription/checkout?plan=diario_only',
    updated_at = CURRENT_TIMESTAMP
WHERE plan_id = (SELECT id FROM subscription_plans WHERE slug = 'diario_only')
  AND system_key = 'diario_trades';
