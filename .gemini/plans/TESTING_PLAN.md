# 🧪 PLANO DE TESTES: Sistema de Assinaturas

**Data:** 2026-01-01
**Status:** Aguardando Implementação

---

## 🎯 Objetivo

Validar cada fase da implementação antes de prosseguir para a próxima.
Testes devem ser executados com participação do usuário quando necessário.

---

## 📋 FASE 1: Testes Stripe

### 1.1 Teste de Configuração

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.1.1 | Variáveis .env | Verificar no servidor | Variáveis presentes | ⬜ |
| 1.1.2 | Conexão Stripe | Endpoint de ping | Resposta OK | ⬜ |
| 1.1.3 | Listar produtos | API interna | Produtos aparecem | ⬜ |

### 1.2 Teste de Migrations

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.2.1 | Tabela subscriptions | SELECT * | Tabela existe | ⬜ |
| 1.2.2 | Tabela payment_history | SELECT * | Tabela existe | ⬜ |
| 1.2.3 | Tabela subscription_plans | SELECT * | Dados iniciais | ⬜ |
| 1.2.4 | Tabela coupons | SELECT * | Tabela existe | ⬜ |
| 1.2.5 | users.stripe_customer_id | \d users | Coluna existe | ⬜ |

### 1.3 Teste de Checkout (COM USUÁRIO)

⚠️ **ATENÇÃO:** Estes testes fazem cobranças reais se usando credenciais live!

| # | Teste | Passos | Esperado | Status |
|---|-------|--------|----------|--------|
| 1.3.1 | Acessar página de planos | /subscription/plans | Página carrega, planos visíveis | ⬜ |
| 1.3.2 | Iniciar checkout PLUS | Clicar "Assinar PLUS" | Redireciona para Stripe Checkout | ⬜ |
| 1.3.3 | Cancelar checkout | Clicar "Voltar" no Stripe | Retorna para /subscription/canceled | ⬜ |
| 1.3.4 | Completar checkout PLUS | Pagar com cartão real | Redireciona para success, tier = PLUS | ⬜ |
| 1.3.5 | Iniciar checkout PRO | Clicar "Assinar PRO" | Redireciona para Stripe Checkout | ⬜ |
| 1.3.6 | Pagar PRO com PIX | Escolher PIX | QR Code aparece | ⬜ |
| 1.3.7 | Confirmar PIX | Pagar PIX | Webhook processa, tier = PRO | ⬜ |

**Cartões de teste Stripe (se modo teste):**
- Sucesso: `4242 4242 4242 4242`
- Falha: `4000 0000 0000 0002`
- Requer autenticação: `4000 0025 0000 3155`

### 1.4 Teste de Webhooks

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.4.1 | Receber webhook | Stripe CLI ou Dashboard | Log de recebimento | ⬜ |
| 1.4.2 | Validar assinatura | Verificar header | Aceita apenas válidos | ⬜ |
| 1.4.3 | checkout.session.completed | Completar checkout | Subscription criada no DB | ⬜ |
| 1.4.4 | invoice.paid | Renovação | Payment_history criado | ⬜ |
| 1.4.5 | customer.subscription.deleted | Cancelar no Stripe | Status = canceled | ⬜ |

### 1.5 Teste de Admin

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.5.1 | Lista de assinaturas | /secure/adm/subscriptions | Ver assinaturas | ⬜ |
| 1.5.2 | Dar tier manual | Formulário grant | Usuário recebe tier | ⬜ |
| 1.5.3 | Estender trial | Formulário extend | Trial estendido | ⬜ |
| 1.5.4 | Histórico pagamentos | Página payments | Ver pagamentos | ⬜ |
| 1.5.5 | Criar cupom | Formulário cupom | Cupom criado | ⬜ |
| 1.5.6 | Usar cupom | Checkout com cupom | Desconto aplicado | ⬜ |

### 1.6 Teste de Trial

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.6.1 | Trial de 7 dias | Nova assinatura | trial_end correto | ⬜ |
| 1.6.2 | Acesso durante trial | Usar features PLUS | Funciona | ⬜ |
| 1.6.3 | Trial expirado | Simular data | Tier volta FREE ou cobra | ⬜ |
| 1.6.4 | Estender trial (admin) | Usar ferramenta | Trial estendido | ⬜ |

### 1.7 Teste de Cancelamento

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 1.7.1 | Página gerenciar | /subscription/manage | Ver assinatura atual | ⬜ |
| 1.7.2 | Solicitar cancelamento | Botão cancelar | Confirma ação | ⬜ |
| 1.7.3 | Cancelar ao fim período | Confirmar | cancel_at_period_end = true | ⬜ |
| 1.7.4 | Manter acesso | Verificar tier | Ainda tem acesso | ⬜ |
| 1.7.5 | Após expirar | Simular data | Tier volta FREE | ⬜ |

---

## 🔒 FASE 2: Testes de Restrições

### 2.1 Testes de Tier Efetivo

| # | Teste | Como Testar | Esperado | Status |
|---|-------|-------------|----------|--------|
| 2.1.1 | Tier FREE | Usuário sem assinatura | tier = FREE | ⬜ |
| 2.1.2 | Tier PLUS ativo | Assinatura PLUS ativa | tier = PLUS | ⬜ |
| 2.1.3 | Tier PRO ativo | Assinatura PRO ativa | tier = PRO | ⬜ |
| 2.1.4 | Tier expirado | subscription_expires_at passado | tier = FREE | ⬜ |
| 2.1.5 | Tier vitalício | subscription_expires_at NULL | tier mantido | ⬜ |
| 2.1.6 | Tier manual admin | source = admin | tier conforme admin | ⬜ |

### 2.2 Testes de Bloqueio (COM USUÁRIO)

| # | Teste | Como | Esperado | Status |
|---|-------|------|----------|--------|
| 2.2.1 | Dashboard Ouro FREE | Acessar como FREE | Bloqueado, modal upgrade | ⬜ |
| 2.2.2 | Dashboard Ouro PLUS | Acessar como PLUS | Acesso total | ⬜ |
| 2.2.3 | Dashboard Ouro PRO | Acessar como PRO | Acesso total | ⬜ |
| 2.2.4 | API gold-boot FREE | Chamar API como FREE | 403 + tier_required | ⬜ |
| 2.2.5 | API gold-boot PLUS | Chamar API como PLUS | Dados retornados | ⬜ |
| 2.2.6 | Snapshot Avançada FREE | Ver dashboard | Bloqueado | ⬜ |
| 2.2.7 | Médias Cards FREE | Ver dashboard | Bloqueado | ⬜ |

### 2.3 Testes de Intervalo Variável

| # | Teste | Como | Esperado | Status |
|---|-------|------|----------|--------|
| 2.3.1 | Intervalo FREE | Observar polling | 5 minutos | ⬜ |
| 2.3.2 | Intervalo PLUS | Observar polling | 1 minuto | ⬜ |
| 2.3.3 | Intervalo PRO | Observar polling | 5 segundos | ⬜ |
| 2.3.4 | Mudança dinâmica | Upgrade de tier | Intervalo muda | ⬜ |
| 2.3.5 | Resposta API | Verificar JSON | _tier presente | ⬜ |

### 2.4 Testes de Frontend

| # | Teste | Como | Esperado | Status |
|---|-------|------|----------|--------|
| 2.4.1 | window.OPEREBEM.user.tier | Console JS | Tier correto | ⬜ |
| 2.4.2 | TierUtils.hasAccess() | Console JS | Boolean correto | ⬜ |
| 2.4.3 | Modal upgrade | Tentar acessar bloqueado | Modal aparece, link funciona | ⬜ |
| 2.4.4 | Badge de tier | Ver perfil | Badge correto | ⬜ |

---

## 📧 FASE 3: Testes de Emails (COM USUÁRIO)

### 3.1 Testes de Admin

| # | Teste | Como | Esperado | Status |
|---|-------|------|----------|--------|
| 3.1.1 | Lista templates | /secure/adm/emails/templates | Templates visíveis | ⬜ |
| 3.1.2 | Criar template | Preencher formulário | Template criado | ⬜ |
| 3.1.3 | Editar template | Modificar HTML | Salva corretamente | ⬜ |
| 3.1.4 | Preview template | Botão preview | Renderiza com variáveis | ⬜ |
| 3.1.5 | Testar template | Enviar para meu email | Email recebido | ⬜ |
| 3.1.6 | Lista triggers | /secure/adm/emails/triggers | Triggers visíveis | ⬜ |
| 3.1.7 | Ativar trigger | Toggle | is_enabled = true | ⬜ |
| 3.1.8 | Fila de emails | /secure/adm/emails/queue | Emails pendentes | ⬜ |
| 3.1.9 | Log de emails | /secure/adm/emails/log | Histórico visível | ⬜ |

### 3.2 Testes de Triggers

| # | Teste | Evento | Esperado | Status |
|---|-------|--------|----------|--------|
| 3.2.1 | Criar conta | user.created | Email de boas-vindas | ⬜ |
| 3.2.2 | Pagamento OK | payment.succeeded | Email de confirmação | ⬜ |
| 3.2.3 | Pagamento falhou | payment.failed | Email de alerta | ⬜ |
| 3.2.4 | Trial iniciado | subscription.trial_started | Email informativo | ⬜ |
| 3.2.5 | Trial expirando | subscription.trial_ending | Email de lembrete | ⬜ |
| 3.2.6 | Assinatura cancelada | subscription.canceled | Email de confirmação | ⬜ |

### 3.3 Testes de Variáveis

| # | Teste | Variável | Esperado | Status |
|---|-------|----------|----------|--------|
| 3.3.1 | Nome usuário | {{user_name}} | Nome completo | ⬜ |
| 3.3.2 | Primeiro nome | {{user_first_name}} | Primeiro nome | ⬜ |
| 3.3.3 | Email | {{user_email}} | Email correto | ⬜ |
| 3.3.4 | Tier | {{user_tier}} | Tier atual | ⬜ |
| 3.3.5 | Data | {{date}} | Data formatada | ⬜ |
| 3.3.6 | Valor | {{amount}} | Valor formatado | ⬜ |

### 3.4 Testes de Fila

| # | Teste | Como | Esperado | Status |
|---|-------|------|----------|--------|
| 3.4.1 | Email com delay | Trigger com delay | Fica em pending | ⬜ |
| 3.4.2 | Processar fila | Executar cron | Email enviado | ⬜ |
| 3.4.3 | Cancelar email | Botão cancelar | Status = cancelled | ⬜ |
| 3.4.4 | Enviar agora | Botão send now | Email enviado | ⬜ |

---

## 📊 Matriz de Aprovação

| Fase | Critério de Aprovação | Aprovador |
|------|----------------------|-----------|
| Fase 1 | Todos os testes 1.x passando | Usuário |
| Fase 2 | Todos os testes 2.x passando | Usuário |
| Fase 3 | Todos os testes 3.x passando | Usuário |

---

## 🔄 Procedimento de Teste

### Para cada fase:

1. **Implementar** - IA implementa código
2. **Deploy** - Push para produção
3. **Executar testes** - IA executa testes automatizáveis
4. **Testes com usuário** - Usuário executa testes marcados "COM USUÁRIO"
5. **Reportar resultados** - Preencher status
6. **Corrigir** - Se falhas, corrigir e re-testar
7. **Aprovar** - Usuário aprova para próxima fase

---

## 📝 Template de Relatório de Teste

```markdown
## Relatório de Teste - Fase X

**Data:** YYYY-MM-DD
**Testador:** [Nome]

### Resultados

| Teste | Status | Observações |
|-------|--------|-------------|
| X.X.X | ✅/❌ | Notas |

### Problemas Encontrados

1. [Descrição do problema]
   - Gravidade: Alta/Média/Baixa
   - Solução: [Proposta]

### Aprovação

- [ ] Aprovado para próxima fase
- [ ] Requer correções
```

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
