-- Migration: 030_populate_plans_and_front_display.sql
-- Atualiza preços dos planos e popula plan_front_display para os 3 sistemas

-- 1) Atualizar preço PLUS Mensal → R$9,90 = 990 cents
UPDATE subscription_plans SET
    price_cents = 990,
    features = '["Dashboard Ouro", "Cotações Tempo Real", "Indicadores Avançados", "Snapshot Avançada", "Acesso ao Ecossistema", "1 Conta no Diário"]'::jsonb,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'plus_monthly';

-- 2) Atualizar PRO Anual à vista → R$697 = 69700 cents
UPDATE subscription_plans SET
    price_cents = 69700,
    features = '["Tudo do PLUS", "Indicadores Tempo Real", "Dashboard NASDAQ", "Contas Ilimitadas no Diário", "Todos os Cursos", "Suporte Prioritário"]'::jsonb,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'pro_yearly';

-- 3) Atualizar PRO Anual 12x → 12x R$69,90 = 83880 cents total
UPDATE subscription_plans SET
    price_cents = 83880,
    installment_count = 12,
    features = '["Tudo do PLUS", "Indicadores Tempo Real", "Dashboard NASDAQ", "Contas Ilimitadas no Diário", "Todos os Cursos", "Suporte Prioritário"]'::jsonb,
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'pro_yearly_installments';

-- 4) Criar plano "Apenas o Diário" (R$49,70/mês = 4970 cents)
INSERT INTO subscription_plans (name, slug, tier, interval_type, interval_count, price_cents, supports_pix, is_installment, trial_days, display_order, is_featured, is_active, features)
VALUES (
    'Apenas o Diário', 'diario_only', 'DIARIO', 'month', 1, 4970, FALSE, FALSE, 0, 4, FALSE, TRUE,
    '["Diário de Trades Completo", "Métricas e Relatórios", "Sem outros benefícios do ecossistema"]'::jsonb
)
ON CONFLICT (slug) DO UPDATE SET
    name = 'Apenas o Diário',
    tier = 'DIARIO',
    interval_type = 'month',
    price_cents = 4970,
    display_order = 4,
    is_active = TRUE,
    features = '["Diário de Trades Completo", "Métricas e Relatórios", "Sem outros benefícios do ecossistema"]'::jsonb,
    updated_at = CURRENT_TIMESTAMP;

-- ==============================
-- PORTAL DO ALUNO — front display
-- ==============================
INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'free'),
    'portal_aluno',
    'FREE', 'Grátis', '',
    'Comece sua jornada sem compromisso',
    '["Todos os benefícios free nos outros serviços Operebem", "Curso Completo Introdução ao Trading", "Primeira aula de cada curso"]'::jsonb,
    'Começar Grátis', '/register',
    FALSE, NULL, TRUE, 0,
    '{"style":"outline"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'plus_monthly'),
    'portal_aluno',
    'PLUS', 'R$ 9,90', '/mês',
    'Acesso completo aos cursos essenciais',
    '["Todos os benefícios plus nos outros serviços Operebem", "Curso Completo Introdução ao Trading", "Curso Completo Terminal Operebem", "Curso Completo Diário Operebem", "Primeira aula de cada curso"]'::jsonb,
    'Assinar Plus', '/checkout?plan=plus_monthly',
    FALSE, NULL, TRUE, 1,
    '{"style":"default"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'pro_yearly_installments'),
    'portal_aluno',
    'PRO', '12x R$ 69,90', '',
    'Acesso total a todos os cursos',
    '["Todos os benefícios pro nos outros serviços Operebem", "Curso Completo Introdução ao Trading", "Curso Completo Terminal Operebem", "Curso Completo Diário Operebem", "Curso Completo Análise Técnica Avançada", "Curso Completo Gestão de Risco Avançada", "Curso Completo TTS - Theuska Trading System", "Curso Completo Como Operar B3", "Curso Completo Como Operar Criptoativos", "Curso Completo Como Operar CFDs"]'::jsonb,
    'Assinar Pro', '/checkout?plan=pro_yearly_installments',
    TRUE, 'ANUAL', TRUE, 2,
    '{"style":"highlighted"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

-- ==============================
-- DIÁRIO DE TRADES — front display
-- ==============================
INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'free'),
    'diario_trades',
    'FREE', 'Grátis', 'Apenas cadastro',
    'Apenas cadastro',
    '["Sem acesso ao Diário", "Sem contas de trading", "Sem métricas"]'::jsonb,
    'Acesso Limitado', '/register',
    FALSE, NULL, TRUE, 0,
    '{"style":"outline","feature_icons":["negative","negative","negative"]}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'plus_monthly'),
    'diario_trades',
    'PLUS', 'R$ 9,90', '/mês',
    'Para quem está começando',
    '["Acesso ao Ecossistema", "1 Conta Conectada", "Métricas Limitadas"]'::jsonb,
    'Assinar Plus', '/checkout?plan=plus_monthly',
    FALSE, NULL, TRUE, 1,
    '{"style":"default","feature_icons":["positive","positive","warning"]}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'pro_yearly_installments'),
    'diario_trades',
    'PRO ANUAL', '12x R$ 69,90', 'ou R$ 697 à vista',
    'A experiência completa sem limites',
    '["Acesso Total ao Ecossistema", "Contas Ilimitadas", "Todas as Métricas & Relatórios", "Diário de Trades Completo"]'::jsonb,
    'Assinar Pro', '/checkout?plan=pro_yearly_installments',
    TRUE, 'RECOMENDADO', TRUE, 2,
    '{"style":"highlighted"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'diario_only'),
    'diario_trades',
    'Apenas o Diário', 'R$ 49,70', '/mês',
    'Quer usar apenas o Diário sem os outros benefícios do ecossistema Operebem?',
    '["Diário de Trades Completo", "Métricas e Relatórios"]'::jsonb,
    'Assinar Individual', '/checkout?plan=diario_only',
    FALSE, NULL, TRUE, 3,
    '{"style":"standalone","layout":"horizontal"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

-- ==============================
-- TERMINAL OPEREBEM — front display
-- ==============================
INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'free'),
    'terminal',
    'FREE', 'Grátis', '',
    'Comece sua jornada sem compromisso',
    '["Dashboard Básico", "Cotações Delay 15min", "Watchlist (5 ativos)", "Comunidade Discord", "Acesso Limitado ao Diário", "Curso Introdução ao Trading", "Primeira aula de cada curso"]'::jsonb,
    'Começar Grátis', '/register',
    FALSE, NULL, TRUE, 0,
    '{"style":"outline"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'plus_monthly'),
    'terminal',
    'PLUS', 'R$ 9,90', '/mês',
    'Para quem está começando',
    '["Dashboard Ouro", "Cotações Tempo Real", "Indicadores Avançados", "Snapshot Avançada", "Acesso ao Ecossistema", "1 Conta no Diário", "Métricas Limitadas", "Cursos Essenciais no Portal"]'::jsonb,
    'Assinar Plus', '/checkout?plan=plus_monthly',
    FALSE, NULL, TRUE, 1,
    '{"style":"default"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_front_display (plan_id, system_key, display_name, price_display, price_subtitle, description, features, cta_label, cta_url, is_highlighted, badge_text, is_visible, display_order, metadata)
VALUES (
    (SELECT id FROM subscription_plans WHERE slug = 'pro_yearly_installments'),
    'terminal',
    'PRO', '12x R$ 69,90', 'ou R$ 697 à vista',
    'A experiência completa sem limites',
    '["Tudo do PLUS", "Dashboard NASDAQ", "Indicadores Tempo Real", "Contas Ilimitadas no Diário", "Todas as Métricas & Relatórios", "Diário de Trades Completo", "Todos os Cursos do Portal", "Suporte Prioritário"]'::jsonb,
    'Assinar Pro', '/checkout?plan=pro_yearly_installments',
    TRUE, 'RECOMENDADO', TRUE, 2,
    '{"style":"highlighted"}'::jsonb
)
ON CONFLICT (plan_id, system_key) DO UPDATE SET
    display_name = EXCLUDED.display_name, price_display = EXCLUDED.price_display, price_subtitle = EXCLUDED.price_subtitle,
    description = EXCLUDED.description, features = EXCLUDED.features, cta_label = EXCLUDED.cta_label, cta_url = EXCLUDED.cta_url,
    is_highlighted = EXCLUDED.is_highlighted, badge_text = EXCLUDED.badge_text, display_order = EXCLUDED.display_order,
    metadata = EXCLUDED.metadata, updated_at = CURRENT_TIMESTAMP;
