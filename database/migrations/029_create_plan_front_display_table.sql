-- Migration: 027_create_plan_front_display_table.sql
-- Tabela centralizada de configuração de exibição dos planos nos sistemas satélites.
-- O Terminal é o source of truth; Portal do Aluno, Diário de Trades e o próprio Terminal
-- consultam esta tabela (via API) para renderizar pricing cards nas suas interfaces.

CREATE TABLE IF NOT EXISTS plan_front_display (
    id SERIAL PRIMARY KEY,

    -- Vínculo com o plano de assinatura
    plan_id INTEGER NOT NULL REFERENCES subscription_plans(id) ON DELETE CASCADE,

    -- Sistema alvo: 'terminal', 'portal_aluno', 'diario_trades'
    system_key VARCHAR(50) NOT NULL,

    -- Dados de exibição no front
    display_name VARCHAR(150),          -- Nome exibido no card (pode diferir do nome interno)
    price_display VARCHAR(100),         -- Ex: "Grátis", "R$ 9,90", "12x R$ 69,90"
    price_subtitle VARCHAR(100),        -- Ex: "/mês", "por ano"
    description TEXT,                   -- Descrição curta do plano para este sistema
    features JSONB DEFAULT '[]',        -- Lista de features exibidas no card ["Feature 1", "Feature 2"]
    cta_label VARCHAR(100),             -- Texto do botão CTA
    cta_url VARCHAR(500),               -- URL do botão CTA
    is_highlighted BOOLEAN DEFAULT FALSE, -- Plano em destaque visual
    badge_text VARCHAR(50),             -- Ex: "POPULAR", "ANUAL", "MELHOR CUSTO"
    is_visible BOOLEAN DEFAULT TRUE,    -- Controla visibilidade neste sistema
    display_order INTEGER DEFAULT 0,    -- Ordem de exibição

    -- Metadados
    metadata JSONB DEFAULT '{}',        -- Dados extras por sistema (ex: ícone, cor customizada)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraint: um plano só tem uma config por sistema
    UNIQUE(plan_id, system_key)
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_plan_front_display_system ON plan_front_display(system_key);
CREATE INDEX IF NOT EXISTS idx_plan_front_display_plan ON plan_front_display(plan_id);
CREATE INDEX IF NOT EXISTS idx_plan_front_display_visible ON plan_front_display(system_key, is_visible) WHERE is_visible = TRUE;

-- Comentários
COMMENT ON TABLE plan_front_display IS 'Configuração centralizada de exibição de planos por sistema (Terminal, Portal do Aluno, Diário de Trades)';
COMMENT ON COLUMN plan_front_display.system_key IS 'Identificador do sistema: terminal, portal_aluno, diario_trades';
COMMENT ON COLUMN plan_front_display.features IS 'Array JSON de strings com features exibidas no pricing card';
COMMENT ON COLUMN plan_front_display.metadata IS 'Dados extras específicos do sistema (ícone, cor, etc)';
