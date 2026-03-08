# Guia de Deploy e Testes — Admin Dashboard Refactoring

**Data:** 2026-03-06

---

## Pre-Deploy Checklist

### Terminal Operebem

1. **Variáveis de ambiente** — Verifique no `.env` de produção:
   ```
   ADMIN_PIN=<seu-pin-6-digitos>   # OBRIGATÓRIO — altere para valor seguro
   ```

2. **Migration do banco** — Execute a migration 027:
   ```
   Acesse: https://terminal.operebem.com.br/secure/adm/run-migrations
   ```
   Ou execute manualmente:
   ```sql
   -- Arquivo: database/migrations/027_create_plan_front_display_table.sql
   ```
   > Nota: O controller `saveFrontDisplay` também cria a tabela automaticamente via `CREATE TABLE IF NOT EXISTS` na primeira execução.

3. **Arquivos para deploy no Terminal:**
   - `src/Controllers/AdminSecureController.php`
   - `src/Controllers/Admin/SubscriptionPlansAdminController.php`
   - `src/Controllers/Api/PlanFrontDisplayApiController.php` (NOVO)
   - `src/Views/admin_secure/pin.php` (NOVO)
   - `src/Views/admin_secure/login.php`
   - `src/Views/admin_secure/index.php`
   - `src/Views/admin_secure/aluno_portal.php`
   - `src/Views/admin_secure/subscription_plans/edit.php`
   - `src/Views/layouts/app.php`
   - `public/assets/css/theme-dark.css`
   - `public/assets/css/theme-light.css`
   - `routes/web.php`
   - `database/migrations/027_create_plan_front_display_table.sql` (NOVO)
   - `.env` (apenas adicionar `ADMIN_PIN`)

### Portal do Aluno

1. **Variáveis de ambiente** — Verifique no `.env` de produção:
   ```
   TERMINAL_BASE_URL=https://terminal.operebem.com.br
   ```

2. **Arquivos para deploy no Portal:**
   - `src/Controllers/TerminalSyncController.php`
   - `.env` (apenas adicionar `TERMINAL_BASE_URL`)

---

## Testes Manuais

### Teste 1: PIN de Segurança
1. Acesse `https://terminal.operebem.com.br/secure/adm/login`
2. **Esperado:** Redirecionamento para `/secure/adm/pin`
3. Veja a tela com 6 inputs individuais para PIN
4. Digite um PIN errado → mensagem "PIN incorreto"
5. Digite o PIN correto (valor do `ADMIN_PIN`) → Redirecionamento para `/secure/adm/login`
6. Tente 5 PINs errados seguidos → mensagem "Muitas tentativas. Aguarde 15 minutos."
7. Após 30 minutos sem atividade, o PIN expira e pede novamente

### Teste 2: Login Admin (Ícones)
1. Na tela de login, verifique:
   - Ícone de usuário (fa-user) no campo de email
   - Ícone de cadeado (fa-lock) no campo de senha
   - Ambos visíveis e alinhados corretamente
2. Compare visualmente com o login de usuário regular

### Teste 3: Dashboard Redesenhado
1. Faça login admin completo (PIN → Login → 2FA)
2. Verifique o dashboard (`/secure/adm/index`):
   - Quick actions compactos com ícones
   - Status pills informativos
   - Métricas em grid responsivo
   - Gráficos Chart.js funcionando
   - Tabela de usuários recentes
3. Redimensione a janela para mobile → layout deve adaptar

### Teste 4: Legibilidade de Textos
1. Navegue por todas as páginas admin:
   - `/secure/adm/plans` — card headers legíveis
   - `/secure/adm/plans/edit?id=1` — headers legíveis
   - `/secure/adm/users/view?id=1` — modais com headers legíveis
   - `/secure/adm/subscriptions/grant` — header bg-success legível
   - `/secure/adm/subscriptions/extend-trial` — header bg-info legível
2. Se possível, teste em tema dark e light
3. Todos os textos em card-header devem ser legíveis

### Teste 5: Footer Admin
1. Em qualquer página admin autenticada, role até o footer
2. **Esperado:** Apenas "Secure Admin", links Tickets/Usuários/Administradores, copyright
3. **NÃO deve conter:** "Suporte" ou "Privacidade"

### Teste 6: Portal do Aluno — Pricing Removido
1. Acesse `/secure/adm/aluno` no Terminal
2. **Esperado:** Alert azul informando que pricing é gerenciado em "Planos & Preços"
3. **NÃO deve conter:** Card "Pricing" com botões "Gerenciar planos" / "Sincronizar pricing"

### Teste 7: Exibição no Front (Plan Edit)
1. Acesse `/secure/adm/plans`
2. Clique em "Editar" em qualquer plano
3. Role para baixo até "Exibição no Front"
4. Veja 3 seções colapsáveis: Terminal Operebem, Portal do Aluno, Diário de Trades
5. Clique em cada para expandir
6. Preencha campos (nome exibido, preço, features, CTA, etc.)
7. Clique "Salvar" → mensagem verde "Salvo!" deve aparecer
8. Recarregue a página → dados salvos devem persistir
9. Badge muda de "Não configurado" para "Configurado"

### Teste 8: API Pública front-display
1. Acesse no navegador:
   ```
   https://terminal.operebem.com.br/api/plans/front-display?system=portal_aluno
   ```
2. **Esperado:** JSON com `success: true` e array de `plans`
3. Teste com sistema inválido:
   ```
   https://terminal.operebem.com.br/api/plans/front-display?system=invalido
   ```
4. **Esperado:** JSON com `success: false` e status 422

### Teste 9: Integração Portal do Aluno
1. Após configurar "Exibição no Front" para `portal_aluno` no Terminal
2. Acesse `https://aluno.operebem.com.br`
3. Role até a seção "Planos e Preços"
4. **Esperado:** Planos exibidos conforme configurado no Terminal
5. Se a API falhar, deve usar fallback (dados locais ou estáticos)

### Teste 10: Rate Limiting
1. **PIN:** Tente 6 PINs errados → bloqueio após 5ª tentativa
2. **Login:** Tente 6 logins errados → bloqueio após 5ª tentativa
3. **2FA:** Tente 11 códigos errados → bloqueio após 10ª tentativa
4. Aguarde o tempo de cooldown e tente novamente → deve funcionar

---

## Ordem Recomendada de Deploy

1. **Terminal primeiro** — Deploy de todos os arquivos
2. Acesse `/secure/adm/run-migrations` para criar tabela `plan_front_display`
3. Teste PIN + Login + Dashboard
4. Configure "Exibição no Front" para cada plano
5. Teste a API: `GET /api/plans/front-display?system=portal_aluno`
6. **Portal do Aluno depois** — Deploy dos 2 arquivos
7. Teste a home do Portal → pricing cards devem refletir Terminal

---

## Rollback

Se algo der errado:
- **PIN gate:** Remova `ADMIN_PIN` do `.env` → bypass automático, login funciona normalmente
- **Front Display:** A tabela é criada com `IF NOT EXISTS`, sem impacto se não existir
- **Portal pricing:** Fallback estático garante que planos sempre aparecem na home
