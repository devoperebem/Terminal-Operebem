# 📋 PLANO: Gerenciamento de Planos de Assinatura (Admin Panel)

**Data:** 2026-01-17  
**Status:** Aprovado - Em Implementação  
**Fase:** Extensão da Fase 1 (Stripe Integration)  
**Responsável:** IA + Usuário  
**Prioridade:** Alta

---

## 📋 Resumo Executivo

Criar painel administrativo completo (`/secure/adm/plans`) para gerenciar os planos de assinatura (PLUS/PRO) cadastrados na tabela `subscription_plans`. O sistema permitirá:

1. **Visualização**: Lista de planos com estatísticas detalhadas (assinantes ativos, receita, conversão)
2. **Edição de Preços**: Alterar valores com sincronização automática no Stripe (cria novo Price ID)
3. **Ativar/Desativar**: Controlar disponibilidade de planos para novos clientes (não afeta assinantes atuais)
4. **Sistema de Promoções**: Descontos temporários globais com prazo de validade
5. **Estatísticas**: Dashboard com métricas por plano e visão geral do negócio

---

## 🎯 Motivação e Contexto

### Por que isso é necessário?

Atualmente, os planos de assinatura estão cadastrados na tabela `subscription_plans` e são exibidos em `/subscription/plans` para os usuários. Porém, **não existe interface administrativa** para gerenciá-los. Qualquer alteração requer:
- Edição manual no banco de dados
- Criação manual de Price IDs no Stripe Dashboard
- Nenhuma visibilidade sobre estatísticas por plano

### Problemas atuais:

1. **Sem controle sobre preços**: Alterar preço requer SQL manual + Stripe Dashboard
2. **Sem gestão de disponibilidade**: Não é possível desativar temporariamente um plano
3. **Sem promoções/descontos**: Não há forma de criar ofertas temporárias globais
4. **Sem estatísticas**: Nenhuma visão sobre qual plano é mais popular, receita por tier, etc.
5. **Risco de inconsistência**: Stripe e banco podem ficar dessincronizados

### O que este plano resolve:

✅ Interface admin completa e intuitiva  
✅ Sincronização automática Stripe ↔ Banco  
✅ Promoções temporárias (incompatíveis com cupons)  
✅ Estatísticas em tempo real  
✅ Controle de disponibilidade de planos  
✅ Auditoria completa de alterações

---

## 🏗️ Arquitetura Técnica

### Diagrama de Fluxo

```
┌─────────────────────────────────────────────────────────────┐
│                    PAINEL ADMIN                             │
│                 /secure/adm/plans                           │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────────┐
        │  SubscriptionPlansAdminController       │
        │  • index() - Lista + Stats              │
        │  • edit() - Formulário                  │
        │  • update() - Salvar + Sync Stripe      │
        │  • toggleActive() - Ativar/Desativar    │
        │  • applyDiscount() - Promoção           │
        │  • removeDiscount() - Remover Promo     │
        └─────────────────────────────────────────┘
                          │
          ┌───────────────┴───────────────┐
          ▼                               ▼
┌────────────────────┐         ┌────────────────────┐
│SubscriptionPlan    │         │   StripeService    │
│Service             │         │  • createPrice()   │
│• getEffectivePrice │         │  • updateProduct() │
│• hasActiveDiscount │         └────────────────────┘
│• canApplyCoupon    │                  │
│• getStats          │                  ▼
└────────────────────┘         ┌────────────────────┐
          │                    │   STRIPE API       │
          ▼                    │ (Price Creation)   │
┌────────────────────┐         └────────────────────┘
│   DATABASE         │
│ subscription_plans │
│  + discount fields │
└────────────────────┘
```

---

## 🗄️ Banco de Dados

### Nova Migration: `025_add_discount_to_subscription_plans.sql`

```sql
-- Adicionar campos para sistema de promoções
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_percentage INTEGER DEFAULT 0 
CHECK (discount_percentage >= 0 AND discount_percentage <= 100);

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_start_date TIMESTAMP;

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_end_date TIMESTAMP;

ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS discount_label VARCHAR(100);

-- Índice para consultas de promoções ativas
CREATE INDEX IF NOT EXISTS idx_subscription_plans_discount_active 
ON subscription_plans(discount_percentage, discount_start_date, discount_end_date) 
WHERE discount_percentage > 0;

-- Comentários
COMMENT ON COLUMN subscription_plans.discount_percentage IS 'Percentual de desconto (0-100). Ex: 20 = 20% OFF';
COMMENT ON COLUMN subscription_plans.discount_start_date IS 'Início da promoção (NULL = imediato)';
COMMENT ON COLUMN subscription_plans.discount_end_date IS 'Fim da promoção (NULL = sem prazo)';
COMMENT ON COLUMN subscription_plans.discount_label IS 'Label da promoção (ex: "Black Friday", "Lançamento")';
```

---

## 📁 Estrutura de Arquivos

### Backend

```
src/
├── Controllers/
│   └── Admin/
│       └── SubscriptionPlansAdminController.php  [NOVO]
│
├── Services/
│   ├── SubscriptionPlanService.php               [NOVO]
│   └── StripeService.php                         [MODIFICAR]
│       └── + createPrice()                       [NOVO MÉTODO]
│
└── Views/
    └── admin_secure/
        └── subscription_plans/                    [NOVO DIRETÓRIO]
            ├── index.php                          [NOVO]
            └── edit.php                           [NOVO]
```

### Rotas

```php
// routes/web.php
$router->group('/secure/adm/plans', function($router) {
    $router->get('/', [SubscriptionPlansAdminController::class, 'index']);
    $router->get('/edit', [SubscriptionPlansAdminController::class, 'edit']);
    $router->post('/update', [SubscriptionPlansAdminController::class, 'update']);
    $router->post('/toggle', [SubscriptionPlansAdminController::class, 'toggleActive']);
    $router->post('/discount/apply', [SubscriptionPlansAdminController::class, 'applyDiscount']);
    $router->post('/discount/remove', [SubscriptionPlansAdminController::class, 'removeDiscount']);
});
```

---

## ⚠️ REGRAS DE NEGÓCIO CRÍTICAS

### 1. Alteração de Preço

**Comportamento**:
- Ao alterar preço no admin, **novo Stripe Price ID é criado automaticamente**
- Price ID antigo **permanece ativo** (assinantes atuais continuam com ele)
- Novas assinaturas usam o novo Price ID
- **Assinantes existentes NUNCA são afetados** (Stripe gerencia isso)

### 2. Planos Inativos

**Comportamento**:
- `is_active = false`: Plano **não aparece** em `/subscription/plans`
- Checkout direto: retorna erro "Plano temporariamente indisponível"
- **Assinantes atuais não são afetados**

### 3. Sistema de Promoções

**Regras**:
- Desconto aplicado globalmente (todos veem preço com desconto)
- **Cupons NÃO podem ser usados em planos com promoção ativa**
- Período de validade opcional
- Cálculo: `preço_efetivo = preço_base - (preço_base * percentual / 100)`

### 4. Sincronização Stripe

**Crítico**:
- Sempre validar resposta da API Stripe
- Em caso de erro, **não atualizar banco**
- Log detalhado de todas as operações

---

## ✅ Checklist de Implementação

### ETAPA 1: Banco de Dados (15 min)
- [ ] Criar migration `025_add_discount_to_subscription_plans.sql`
- [ ] Testar migration em desenvolvimento
- [ ] Executar migration em produção

### ETAPA 2: Service Layer (1-2h)
- [ ] Criar `SubscriptionPlanService.php`
  - [ ] `getEffectivePrice()`
  - [ ] `hasActiveDiscount()`
  - [ ] `canApplyCoupon()`
  - [ ] `getStatsForPlan()`
  - [ ] `getAllPlansWithStats()`
- [ ] Adicionar `createPrice()` em `StripeService.php`

### ETAPA 3: Controller (2-3h)
- [ ] Criar `SubscriptionPlansAdminController.php`
  - [ ] `index()` - Lista + estatísticas
  - [ ] `edit()` - Formulário
  - [ ] `update()` - Salvar + sync Stripe
  - [ ] `toggleActive()` - Ativar/desativar
  - [ ] `applyDiscount()` - Aplicar promoção
  - [ ] `removeDiscount()` - Remover promoção
- [ ] Adicionar rotas em `routes/web.php`

### ETAPA 4: Frontend Admin (2-3h)
- [ ] Criar `src/Views/admin_secure/subscription_plans/index.php`
- [ ] Criar `src/Views/admin_secure/subscription_plans/edit.php`
- [ ] Seguir padrão visual do restante do admin
- [ ] Adicionar link no menu admin

### ETAPA 5: Validações (1h)
- [ ] Modificar `SubscriptionService::createCheckout()`
  - [ ] Validar `is_active`
  - [ ] Bloquear cupom se tem promoção
  - [ ] Usar preço efetivo

### ETAPA 6: Testes (2-3h)
- [ ] Teste 1: Visualizar planos
- [ ] Teste 2: Editar preço (verificar novo Price ID no Stripe)
- [ ] Teste 3: Aplicar promoção
- [ ] Teste 4: Desativar plano
- [ ] Teste 5: Tentar cupom em promoção (deve bloquear)
- [ ] Teste 6: Validar estatísticas

### ETAPA 7: Deploy (1h)
- [ ] Code review
- [ ] Merge para main
- [ ] Deploy em produção
- [ ] Validação final

---

## 📊 Queries SQL de Estatísticas

```sql
-- Query completa para dashboard
SELECT 
    sp.id,
    sp.name,
    sp.slug,
    sp.tier,
    sp.price_cents,
    sp.discount_percentage,
    sp.is_active,
    sp.stripe_price_id,
    COUNT(DISTINCT CASE WHEN s.status IN ('active', 'trialing') THEN s.id END) as active_subscribers,
    COUNT(DISTINCT CASE WHEN s.created_at >= NOW() - INTERVAL '30 days' AND s.status IN ('active', 'trialing') THEN s.id END) as new_last_30_days,
    CASE 
        WHEN sp.interval_type = 'month' THEN sp.price_cents * COUNT(DISTINCT CASE WHEN s.status IN ('active', 'trialing') THEN s.id END)
        WHEN sp.interval_type = 'year' THEN (sp.price_cents / 12) * COUNT(DISTINCT CASE WHEN s.status IN ('active', 'trialing') THEN s.id END)
    END as estimated_monthly_revenue_cents
FROM subscription_plans sp
LEFT JOIN subscriptions s ON s.plan_slug = sp.slug
GROUP BY sp.id
ORDER BY sp.display_order ASC;
```

---

## 🧪 Cenários de Teste

### Teste 1: Alterar Preço
- [ ] Admin altera preço de R$ 29,90 para R$ 34,90
- [ ] Novo Price ID criado no Stripe
- [ ] Banco atualizado
- [ ] Assinantes atuais continuam pagando R$ 29,90
- [ ] Novos clientes veem R$ 34,90

### Teste 2: Aplicar Promoção
- [ ] Admin aplica 30% OFF (Black Friday)
- [ ] Preço efetivo calculado corretamente
- [ ] Badge de promoção aparece
- [ ] Campo de cupom desabilitado
- [ ] Após data de expiração, desconto remove automaticamente

### Teste 3: Desativar Plano
- [ ] Plano não aparece em `/subscription/plans`
- [ ] Assinantes atuais não afetados
- [ ] Checkout direto retorna erro

### Teste 4: Cupom em Promoção (Bloqueio)
- [ ] Tentativa de aplicar cupom em plano com promoção
- [ ] Backend retorna erro 400
- [ ] Mensagem clara para o usuário

---

## 📅 Cronograma

| Etapa | Tempo Estimado |
|-------|----------------|
| 1. Migration | 15 min |
| 2. Service Layer | 1-2h |
| 3. Controller | 2-3h |
| 4. Frontend Admin | 2-3h |
| 5. Validações | 1h |
| 6. Testes | 2-3h |
| 7. Deploy | 1h |
| **Total** | **9-13h** |

---

## 🔗 Integração com Plano Mestre

Este plano é uma **extensão da Fase 1.6 (Admin)** do `MASTER_IMPLEMENTATION_PLAN.md`.

Deve ser executado **após** a conclusão da Fase 1 (Stripe Integration).

---

## 📝 Notas Importantes

### Ambiente de Desenvolvimento

- **Test Mode Stripe**: Será configurado para testes
- **Production Mode**: Apenas após validação completa
- **Página Pública**: Modificações requerem aprovação do usuário

### Futuras Melhorias (Fora do escopo atual)

1. Histórico de alterações de preços
2. Previsualização de planos
3. Sistema de A/B testing
4. Notificações automáticas de promoções

---

*Documento criado em: 2026-01-17*  
*Versão: 1.0*  
*Status: Aprovado - Pronto para Implementação*
