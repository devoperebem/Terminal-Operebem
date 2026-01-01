# 🔐 Plano de Implementação: Integração Stripe + Sistema de Assinaturas

**Data:** 2026-01-01
**Status:** Planejamento

---

## 📋 Resumo Executivo

Integrar Stripe para gerenciamento de assinaturas do Terminal Operebem, com:
- Planos PLUS (mensal) e PRO (anual)
- Trial de 7 dias renovável pelo admin
- Cancelamento no fim do período
- Sistema de cupons
- Tier manual pelo admin

---

## 💰 Produtos e Preços

### Plano PLUS Mensal
| Campo | Valor |
|-------|-------|
| Nome | PLUS Mensal |
| Tier | PLUS |
| Preço | R$ 29,90/mês |
| Trial | 7 dias |
| Pagamento | Cartão |
| Parcelamento | Não |

### Plano PRO Anual
| Campo | Valor |
|-------|-------|
| Nome | PRO Anual |
| Tier | PRO |
| Preço à vista | R$ 697,00 |
| Preço parcelado | 12x R$ 69,90 (= R$ 838,80) |
| Trial | 7 dias |
| Pagamento à vista | Cartão ou PIX |
| Pagamento parcelado | Cartão |

---

## 🗄️ Banco de Dados

### Tabela: `subscriptions`

```sql
CREATE TABLE subscriptions (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices
    UNIQUE(user_id, status) -- Um usuário não pode ter 2 assinaturas ativas
);

CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_stripe_subscription_id ON subscriptions(stripe_subscription_id);
CREATE INDEX idx_subscriptions_stripe_customer_id ON subscriptions(stripe_customer_id);
```

### Tabela: `payment_history`

```sql
CREATE TABLE payment_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES subscriptions(id) ON DELETE SET NULL,
    
    -- Stripe IDs
    stripe_payment_intent_id VARCHAR(255),
    stripe_invoice_id VARCHAR(255) UNIQUE,
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

CREATE INDEX idx_payment_history_user_id ON payment_history(user_id);
CREATE INDEX idx_payment_history_subscription_id ON payment_history(subscription_id);
CREATE INDEX idx_payment_history_status ON payment_history(status);
CREATE INDEX idx_payment_history_stripe_invoice_id ON payment_history(stripe_invoice_id);
```

### Tabela: `subscription_plans`

```sql
CREATE TABLE subscription_plans (
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
    stripe_product_id VARCHAR(255) NOT NULL,
    stripe_price_id VARCHAR(255) NOT NULL,
    
    -- Preço (para exibição)
    price_cents INTEGER NOT NULL,
    currency VARCHAR(10) DEFAULT 'BRL',
    
    -- Opções
    is_installment BOOLEAN DEFAULT FALSE,
    installment_count INTEGER,
    supports_pix BOOLEAN DEFAULT FALSE,
    trial_days INTEGER DEFAULT 7,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    display_order INTEGER DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dados iniciais
INSERT INTO subscription_plans (name, slug, tier, interval_type, stripe_product_id, stripe_price_id, price_cents, supports_pix, is_installment, display_order) VALUES
('PLUS Mensal', 'plus_monthly', 'PLUS', 'month', 'prod_xxx', 'price_xxx', 2990, FALSE, FALSE, 1),
('PRO Anual', 'pro_yearly', 'PRO', 'year', 'prod_xxx', 'price_xxx', 69700, TRUE, FALSE, 2),
('PRO Anual Parcelado', 'pro_yearly_installments', 'PRO', 'year', 'prod_xxx', 'price_xxx', 83880, FALSE, TRUE, 3);
```

### Tabela: `coupons`

```sql
CREATE TABLE coupons (
    id SERIAL PRIMARY KEY,
    
    -- Código
    code VARCHAR(50) UNIQUE NOT NULL,
    
    -- Stripe
    stripe_coupon_id VARCHAR(255) UNIQUE,
    stripe_promotion_code_id VARCHAR(255),
    
    -- Desconto
    discount_type VARCHAR(20) NOT NULL, -- 'percent', 'fixed'
    discount_value INTEGER NOT NULL, -- percentual ou centavos
    
    -- Restrições
    max_redemptions INTEGER, -- NULL = ilimitado
    redemptions_count INTEGER DEFAULT 0,
    valid_until TIMESTAMP,
    min_amount_cents INTEGER, -- valor mínimo do pedido
    
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

CREATE INDEX idx_coupons_code ON coupons(code);
CREATE INDEX idx_coupons_is_active ON coupons(is_active);
```

### Tabela: `coupon_redemptions`

```sql
CREATE TABLE coupon_redemptions (
    id SERIAL PRIMARY KEY,
    coupon_id INTEGER NOT NULL REFERENCES coupons(id),
    user_id INTEGER NOT NULL REFERENCES users(id),
    subscription_id INTEGER REFERENCES subscriptions(id),
    
    -- Valores
    original_amount_cents INTEGER NOT NULL,
    discount_amount_cents INTEGER NOT NULL,
    final_amount_cents INTEGER NOT NULL,
    
    -- Timestamps
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(coupon_id, user_id, subscription_id)
);
```

### Tabela: `trial_extensions` (para renovar trials)

```sql
CREATE TABLE trial_extensions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    subscription_id INTEGER REFERENCES subscriptions(id),
    
    -- Extensão
    days_extended INTEGER NOT NULL,
    new_trial_end TIMESTAMP NOT NULL,
    
    -- Admin
    granted_by INTEGER NOT NULL REFERENCES admin_users(id),
    reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Alterações na tabela `users`

```sql
-- Já existem:
-- tier VARCHAR DEFAULT 'FREE'
-- subscription_expires_at TIMESTAMP

-- Adicionar:
ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) UNIQUE;
CREATE INDEX idx_users_stripe_customer_id ON users(stripe_customer_id);
```

---

## 📁 Estrutura de Arquivos

```
src/
├── Controllers/
│   ├── SubscriptionController.php       # Páginas de assinatura
│   ├── StripeWebhookController.php      # Webhooks
│   └── Admin/
│       └── SubscriptionAdminController.php
│
├── Services/
│   ├── StripeService.php                # API Stripe
│   ├── SubscriptionService.php          # Lógica de negócio
│   └── CouponService.php                # Cupons
│
├── Views/
│   ├── subscription/
│   │   ├── plans.php                    # Escolher plano
│   │   ├── checkout.php                 # Pre-checkout
│   │   ├── success.php                  # Sucesso
│   │   ├── canceled.php                 # Checkout cancelado
│   │   └── manage.php                   # Gerenciar assinatura
│   │
│   └── admin_secure/
│       └── subscriptions/
│           ├── index.php                # Lista assinaturas
│           ├── view.php                 # Detalhes
│           ├── grant.php                # Dar tier manual
│           ├── payments.php             # Histórico pagamentos
│           ├── coupons/
│           │   ├── index.php            # Lista cupons
│           │   └── create.php           # Criar cupom
│           └── extend_trial.php         # Estender trial

config/
└── stripe.php

database/
└── migrations/
    ├── 020_create_subscriptions_table.sql
    ├── 021_create_payment_history_table.sql
    ├── 022_create_subscription_plans_table.sql
    ├── 023_create_coupons_tables.sql
    ├── 024_create_trial_extensions_table.sql
    └── 025_add_stripe_customer_id_to_users.sql
```

---

## 🔗 Rotas

### Públicas (usuário logado)
```
GET  /subscription/plans              # Ver planos
POST /subscription/checkout           # Iniciar checkout Stripe
GET  /subscription/success            # Callback sucesso
GET  /subscription/canceled           # Callback cancelamento
GET  /subscription/manage             # Gerenciar assinatura
POST /subscription/cancel             # Cancelar assinatura
POST /subscription/validate-coupon    # Validar cupom (AJAX)
```

### Webhook (Stripe)
```
POST /api/stripe/webhook              # Receber eventos Stripe
```

### Admin
```
GET  /secure/adm/subscriptions                  # Lista
GET  /secure/adm/subscriptions/view?id=X        # Detalhes
GET  /secure/adm/subscriptions/grant            # Formulário dar tier
POST /secure/adm/subscriptions/grant            # Processar
GET  /secure/adm/subscriptions/payments         # Histórico pagamentos
POST /secure/adm/subscriptions/extend-trial     # Estender trial

GET  /secure/adm/coupons                        # Lista cupons
GET  /secure/adm/coupons/create                 # Criar
POST /secure/adm/coupons/create                 # Processar
POST /secure/adm/coupons/toggle                 # Ativar/desativar
GET  /secure/adm/coupons/report?id=X            # Relatório de uso
```

---

## 🔔 Webhooks Stripe

| Evento | Ação |
|--------|------|
| `checkout.session.completed` | Criar assinatura, iniciar trial ou ativar |
| `customer.subscription.created` | Backup - criar registro se não existe |
| `customer.subscription.updated` | Atualizar status, período, cancelamento |
| `customer.subscription.deleted` | Finalizar assinatura, tier → FREE |
| `customer.subscription.trial_will_end` | Notificar usuário (3 dias antes) |
| `invoice.created` | Registrar invoice pendente |
| `invoice.paid` | Registrar pagamento, atualizar período |
| `invoice.payment_failed` | Registrar falha, notificar usuário |
| `invoice.payment_action_required` | PIX pendente, notificar |
| `charge.refunded` | Registrar reembolso |
| `charge.dispute.created` | Alerta para admin |

---

## 🔐 Variáveis de Ambiente

```env
# Stripe API
STRIPE_PUBLIC_KEY=pk_live_xxx
STRIPE_SECRET_KEY=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Stripe Price IDs (criar no dashboard)
STRIPE_PRICE_PLUS_MONTHLY=price_xxx
STRIPE_PRICE_PRO_YEARLY=price_xxx
STRIPE_PRICE_PRO_YEARLY_INSTALLMENTS=price_xxx

# Trial
SUBSCRIPTION_TRIAL_DAYS=7

# URLs
STRIPE_SUCCESS_URL=https://terminal.operebem.com.br/subscription/success?session_id={CHECKOUT_SESSION_ID}
STRIPE_CANCEL_URL=https://terminal.operebem.com.br/subscription/canceled
```

---

## 📅 Cronograma de Implementação

### Fase 1: Setup e Infraestrutura (2-3 horas)
- [ ] Criar migrations
- [ ] Executar migrations em dev/prod
- [ ] Configurar variáveis .env
- [ ] Criar produtos/preços no Stripe Dashboard
- [ ] Configurar webhook no Stripe Dashboard

### Fase 2: Services (3-4 horas)
- [ ] `config/stripe.php`
- [ ] `StripeService.php` - wrapper da API
- [ ] `SubscriptionService.php` - lógica de negócio
- [ ] `CouponService.php` - gerenciamento de cupons

### Fase 3: Webhooks (2-3 horas)
- [ ] `StripeWebhookController.php`
- [ ] Validação de assinatura de webhook
- [ ] Handlers para cada evento
- [ ] Logs detalhados

### Fase 4: Checkout do Usuário (3-4 horas)
- [ ] `SubscriptionController.php`
- [ ] View: plans.php (escolher plano)
- [ ] View: success.php
- [ ] View: canceled.php
- [ ] View: manage.php
- [ ] Integração com cupons

### Fase 5: Admin Panel (3-4 horas)
- [ ] Lista de assinaturas
- [ ] Dar tier manualmente
- [ ] Estender trial
- [ ] Histórico de pagamentos
- [ ] CRUD de cupons

### Fase 6: Testes e Deploy (2 horas)
- [ ] Testar fluxo completo em sandbox
- [ ] Testar webhooks com Stripe CLI
- [ ] Deploy em produção
- [ ] Testar em produção com valor mínimo

---

## ✅ Checklist Pré-Implementação

- [ ] Criar conta Stripe (ou verificar existente)
- [ ] Criar produtos no Stripe Dashboard
- [ ] Obter chaves de API
- [ ] Configurar webhook URL no Stripe

---

## 📝 Notas Importantes

1. **Trial renovável:** Admin pode estender trial via `trial_extensions`
2. **Tier manual:** Assinaturas com `source='admin'` não passam pelo Stripe
3. **PIX:** Stripe gera QR code, confirmação automática via webhook
4. **Parcelamento:** Usar Stripe Installments (beta) ou criar price fixo

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
