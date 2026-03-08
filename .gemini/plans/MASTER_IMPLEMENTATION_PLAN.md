# 🎯 PLANO MESTRE: Implementação Completa do Sistema de Assinaturas

**Data:** 2026-01-01
**Status:** Aguardando Aprovação
**Responsável:** IA + Usuário

---

## 📋 Ordem de Implementação

```
┌─────────────────────────────────────────────────────────────────────┐
│                     FASE 1: STRIPE (14-16h)                         │
│                                                                     │
│  ├── 1.1 Setup e Configuração                                       │
│  ├── 1.2 Tabelas e Migrations                                       │
│  ├── 1.3 Services (Stripe + Subscription)                           │
│  ├── 1.4 Webhooks                                                   │
│  ├── 1.5 Frontend Usuário (Planos, Checkout, Gerenciar)             │
│  ├── 1.6 Admin (Assinaturas, Cupons, Histórico)                     │
│  └── 1.7 TESTE FASE 1                                               │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   FASE 2: RESTRIÇÕES (9-11h)                        │
│                                                                     │
│  ├── 2.1 TierService                                                │
│  ├── 2.2 Middleware de Tier                                         │
│  ├── 2.3 Expor Tier no Frontend                                     │
│  ├── 2.4 Bloquear Features por Tier                                 │
│  ├── 2.5 Intervalos Variáveis de Polling                            │
│  └── 2.6 TESTE FASE 2                                               │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    FASE 3: EMAILS (15-17h)                          │
│                                                                     │
│  ├── 3.1 Tabelas e Migrations                                       │
│  ├── 3.2 Services (Email, Template, Queue, Trigger)                 │
│  ├── 3.3 Admin Interface                                            │
│  ├── 3.4 Templates Padrão                                           │
│  ├── 3.5 Integrar com Eventos                                       │
│  └── 3.6 TESTE FASE 3                                               │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ⚠️ IMPORTANTE: Credenciais Stripe

As credenciais fornecidas são de **PRODUÇÃO** (pk_live_, sk_live_).

**Recomendação:** Para desenvolvimento seguro, usar credenciais de **TESTE** primeiro:
- Criar no Stripe Dashboard > Developers > API Keys > Test mode
- Testar todo o fluxo com cartões de teste
- Só depois trocar para produção

**Se quiser ir direto para produção:**
- Cuidado com cobranças reais
- Usar valores baixos para teste (R$ 1,00)
- Reembolsar imediatamente após testar

---

## 📦 FASE 1: Integração Stripe

### 1.1 Setup e Configuração (30 min)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.1.1 | `.env` (produção) | Adicionar variáveis STRIPE_* |
| 1.1.2 | `config/stripe.php` | Criar arquivo de configuração |
| 1.1.3 | Stripe Dashboard | Criar produtos e preços |
| 1.1.4 | Stripe Dashboard | Configurar webhook endpoint |

**Variáveis .env:**
```env
STRIPE_PUBLIC_KEY=pk_live_51Qia9YDhuEkxOnkW...
STRIPE_SECRET_KEY=sk_live_51Qia9YDhuEkxOnkW...
STRIPE_WEBHOOK_SECRET=whsec_XXX (obter após criar webhook)

# Preços (criar no dashboard e pegar IDs)
STRIPE_PRICE_PLUS_MONTHLY=price_XXX
STRIPE_PRICE_PRO_YEARLY=price_XXX
STRIPE_PRICE_PRO_YEARLY_INSTALLMENTS=price_XXX

# Trial
SUBSCRIPTION_TRIAL_DAYS=7
```

### 1.2 Tabelas e Migrations (1h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.2.1 | `020_create_subscriptions_table.sql` | Tabela de assinaturas |
| 1.2.2 | `021_create_payment_history_table.sql` | Histórico de pagamentos |
| 1.2.3 | `022_create_subscription_plans_table.sql` | Configuração dos planos |
| 1.2.4 | `023_create_coupons_tables.sql` | Cupons + redemptions |
| 1.2.5 | `024_create_trial_extensions_table.sql` | Extensões de trial |
| 1.2.6 | `025_add_stripe_customer_id_to_users.sql` | Adicionar campo em users |
| 1.2.7 | Executar migrations | Via /secure/adm/run-migrations |

### 1.3 Services (4h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.3.1 | `StripeService.php` | Wrapper da API Stripe |
| 1.3.2 | `SubscriptionService.php` | Lógica de negócio |
| 1.3.3 | `CouponService.php` | Gerenciamento de cupons |
| 1.3.4 | Atualizar `TierService.php` | Integrar com subscription |

### 1.4 Webhooks (3h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.4.1 | `StripeWebhookController.php` | Receber eventos |
| 1.4.2 | Rota webhook | `POST /api/stripe/webhook` |
| 1.4.3 | Handler: checkout.session.completed | Criar assinatura |
| 1.4.4 | Handler: invoice.paid | Registrar pagamento |
| 1.4.5 | Handler: invoice.payment_failed | Registrar falha |
| 1.4.6 | Handler: customer.subscription.* | Atualizar status |

### 1.5 Frontend Usuário (3h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.5.1 | `SubscriptionController.php` | Controller |
| 1.5.2 | `subscription/plans.php` | Página de planos |
| 1.5.3 | `subscription/success.php` | Sucesso |
| 1.5.4 | `subscription/canceled.php` | Cancelamento |
| 1.5.5 | `subscription/manage.php` | Gerenciar assinatura |
| 1.5.6 | Rotas | Adicionar em web.php |

### 1.6 Admin (3h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 1.6.1 | `SubscriptionAdminController.php` | Controller admin |
| 1.6.2 | `admin_secure/subscriptions/index.php` | Lista assinaturas |
| 1.6.3 | `admin_secure/subscriptions/grant.php` | Dar tier manual |
| 1.6.4 | `admin_secure/subscriptions/extend_trial.php` | Estender trial |
| 1.6.5 | `admin_secure/subscriptions/payments.php` | Histórico |
| 1.6.6 | `admin_secure/coupons/index.php` | CRUD cupons |
| 1.6.7 | Rotas admin | Adicionar em web.php |

### 1.7 Gerenciamento de Planos - Admin (9-13h)

**Objetivo**: Painel admin completo para gerenciar planos de assinatura (PLUS/PRO) com edição de preços, promoções, estatísticas e sincronização automática com Stripe.

**Documento Detalhado**: `SUBSCRIPTION_PLANS_ADMIN.md`

| Etapa | Tempo | Descrição |
|-------|-------|-----------|
| 1.7.1 | 15 min | Migration: adicionar campos de desconto |
| 1.7.2 | 1-2h | Service Layer (SubscriptionPlanService + StripeService) |
| 1.7.3 | 2-3h | Controller (SubscriptionPlansAdminController) |
| 1.7.4 | 2-3h | Frontend Admin (index.php + edit.php) |
| 1.7.5 | 1h | Validações de negócio |
| 1.7.6 | 2-3h | Testes completos |
| 1.7.7 | 1h | Deploy produção |

**Funcionalidades**:
- ✅ Visualização de planos com estatísticas (assinantes, receita)
- ✅ Edição de preços com sincronização Stripe automática
- ✅ Ativar/Desativar planos (não afeta assinantes atuais)
- ✅ Sistema de promoções temporárias (incompatível com cupons)
- ✅ Dashboard com métricas globais

**Regras Críticas**:
- Alteração de preço cria novo Stripe Price ID automaticamente
- Assinantes atuais nunca são afetados por mudanças de preço
- Cupons bloqueados em planos com promoção ativa
- Planos inativos não aparecem para novos clientes

### 1.8 TESTE FASE 1 (ver plano de testes abaixo)

---

## 🔒 FASE 2: Restrições por Tier

### 2.1 TierService (1h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 2.1.1 | `TierService.php` | Criar/atualizar service |
| 2.1.2 | Configurar features | Matriz de features por tier |
| 2.1.3 | `getEffectiveTier()` | Mover de SsoController |

### 2.2 Middleware (1h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 2.2.1 | `TierMiddleware.php` | Middleware de verificação |
| 2.2.2 | Integrar em rotas | Aplicar em endpoints bloqueados |

### 2.3 Frontend Global (1h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 2.3.1 | `app.php` | Expor tier no JS |
| 2.3.2 | `tier-utils.js` | Utilitário JS |
| 2.3.3 | CSS | Estilos para feature-locked |
| 2.3.4 | `upgrade-required.php` | Partial de upgrade |

### 2.4 Bloquear Features (3h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 2.4.1 | `dashboard-gold.php` | Bloquear para FREE |
| 2.4.2 | `gold-dashboard.js` | Verificação JS |
| 2.4.3 | `QuotesController.php` | Bloquear API para FREE |
| 2.4.4 | Snapshot Avançada | Identificar e bloquear |
| 2.4.5 | Médias Cards | Identificar e bloquear |

### 2.5 Intervalos Variáveis (2h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 2.5.1 | `FearGreedController.php` | Retornar intervalo |
| 2.5.2 | `UsMarketBarometerController.php` | Retornar intervalo |
| 2.5.3 | `OBIndicesController.php` | Retornar intervalo |
| 2.5.4 | `tier-polling.js` | Cliente JS inteligente |
| 2.5.5 | Atualizar views | Usar novo polling |

### 2.6 TESTE FASE 2 (ver plano de testes abaixo)

---

## 📧 FASE 3: Sistema de Emails

### 3.1 Tabelas (1h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 3.1.1 | `026_create_email_templates_table.sql` | Templates |
| 3.1.2 | `027_create_email_triggers_table.sql` | Triggers |
| 3.1.3 | `028_create_email_queue_table.sql` | Fila |
| 3.1.4 | `029_create_email_log_table.sql` | Log |

### 3.2 Services (4h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 3.2.1 | `EmailService.php` | Serviço principal |
| 3.2.2 | `EmailTemplateService.php` | Gerenciar templates |
| 3.2.3 | `EmailQueueService.php` | Fila de emails |
| 3.2.4 | `EmailTriggerService.php` | Gerenciar triggers |
| 3.2.5 | `EmailEvents.php` | Constantes de eventos |

### 3.3 Admin Interface (5h)

| Tarefa | Arquivo | Descrição |
|--------|---------|-----------|
| 3.3.1 | `EmailController.php` | Controller admin |
| 3.3.2 | `emails/templates/index.php` | Lista templates |
| 3.3.3 | `emails/templates/edit.php` | Editor HTML |
| 3.3.4 | `emails/templates/preview.php` | Preview |
| 3.3.5 | `emails/triggers/index.php` | Lista triggers |
| 3.3.6 | `emails/triggers/edit.php` | Configurar |
| 3.3.7 | `emails/queue/index.php` | Fila pendente |
| 3.3.8 | `emails/log/index.php` | Histórico |
| 3.3.9 | Rotas admin | Adicionar em web.php |

### 3.4 Templates Padrão (2h)

| Tarefa | Template | Descrição |
|--------|----------|-----------|
| 3.4.1 | `welcome` | Boas-vindas |
| 3.4.2 | `payment_success` | Pagamento confirmado |
| 3.4.3 | `payment_failed` | Pagamento falhou |
| 3.4.4 | `trial_started` | Trial iniciado |
| 3.4.5 | `trial_ending` | Trial expirando |
| 3.4.6 | `subscription_canceled` | Assinatura cancelada |
| 3.4.7 | `password_reset` | Recuperação de senha |

### 3.5 Integrar com Eventos (2h)

| Tarefa | Local | Descrição |
|--------|-------|-----------|
| 3.5.1 | `AuthController::register` | Trigger user.created |
| 3.5.2 | `StripeWebhookController` | Triggers de pagamento |
| 3.5.3 | `SubscriptionService` | Triggers de subscription |
| 3.5.4 | Cron job | Processar fila |

### 3.6 TESTE FASE 3 (ver plano de testes abaixo)

---

## 📅 Cronograma Estimado

| Fase | Dias | Horas |
|------|------|-------|
| Fase 1: Stripe | 2-3 dias | 14-16h |
| Fase 2: Restrições | 1-2 dias | 9-11h |
| Fase 3: Emails | 2-3 dias | 15-17h |
| **Total** | **5-8 dias** | **38-44h** |

---

## ✅ Checklist Pré-Implementação

- [x] Plano de Stripe criado
- [x] Plano de Restrições criado
- [x] Plano de Emails criado
- [x] Mapeamento de Endpoints criado
- [x] Credenciais Stripe recebidas
- [ ] **AGUARDANDO APROVAÇÃO DO USUÁRIO**
- [ ] Criar produtos no Stripe Dashboard
- [ ] Configurar webhook no Stripe Dashboard

---

## 🚀 Próximos Passos Após Aprovação

1. Configurar variáveis .env no servidor
2. Criar produtos/preços no Stripe Dashboard
3. Iniciar Fase 1.1 (Setup)
4. Seguir plano sequencialmente
5. Testar cada fase antes de prosseguir

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
