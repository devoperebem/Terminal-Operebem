# FASE 1 - MIGRAÇÃO E TESTES - Sistema de Logs de Auditoria

**Data de Criação:** 19/01/2026  
**Status:** PENDENTE EXECUÇÃO  
**Responsável:** IA de Execução  
**Dependências:** FASE 1 Parte 1 e 2 já concluídas (commits: `a0b8acc` e `2910f0a`)

---

## 📋 ÍNDICE

1. [Contexto do Projeto](#contexto-do-projeto)
2. [Pré-requisitos](#pré-requisitos)
3. [ETAPA 1: Executar Migration 027](#etapa-1-executar-migration-027)
4. [ETAPA 2: Verificar Estrutura do Banco](#etapa-2-verificar-estrutura-do-banco)
5. [ETAPA 3: Testes Funcionais](#etapa-3-testes-funcionais)
6. [ETAPA 4: Testes de Auditoria](#etapa-4-testes-de-auditoria)
7. [ETAPA 5: Testes de Email](#etapa-5-testes-de-email)
8. [ETAPA 6: Validação Final](#etapa-6-validação-final)
9. [Troubleshooting](#troubleshooting)
10. [Rollback (Se Necessário)](#rollback-se-necessário)

---

## 🎯 CONTEXTO DO PROJETO

### O Que Foi Implementado

Sistema completo de logs de auditoria para rastrear ações de administradores e usuários, incluindo:

- **Tabela `admin_audit_logs`**: Armazena histórico de todas as ações
- **Service `AuditLogService`**: Gerencia criação e consulta de logs
- **Interface Admin**: Visualização de logs na `user_view.php`
- **Controllers**: Métodos para reset de senha, logout de dispositivos, cancelamento de assinatura
- **Logs Automáticos**: Alterações de avatar, preferências e senha do usuário

### Arquivos Modificados/Criados

```
database/migrations/027_create_admin_audit_logs.sql  ✅ Criado (não executado)
src/Services/AuditLogService.php                     ✅ Criado
src/Views/admin_secure/user_view.php                 ✅ Modificado
src/Controllers/Admin/SubscriptionAdminController.php ✅ Modificado
src/Controllers/AdminSecureController.php            ✅ Modificado
src/Controllers/ProfileController.php                ✅ Modificado
routes/web.php                                       ✅ Modificado
.env.example                                         ✅ Modificado
```

---

## ✅ PRÉ-REQUISITOS

### 1. Verificar Conexão com Banco de Dados

```bash
# Navegar para o diretório do projeto
cd "C:\Users\Administrator\Desktop\operebem\terminal operebem"

# Testar conexão PostgreSQL
php -r "
\$host = getenv('DB_HOST') ?: 'localhost';
\$db = getenv('DB_NAME') ?: 'operebem';
\$user = getenv('DB_USER') ?: 'postgres';
\$pass = getenv('DB_PASS') ?: '';

try {
    \$pdo = new PDO(\"pgsql:host=\$host;dbname=\$db\", \$user, \$pass);
    echo \"✅ Conexão com banco OK\n\";
} catch (Exception \$e) {
    echo \"❌ Erro: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
```

**Resultado Esperado:** `✅ Conexão com banco OK`

### 2. Verificar Configuração de Email

```bash
# Verificar se variáveis de email estão configuradas no .env
php -r "
\$required = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
\$missing = [];

foreach (\$required as \$var) {
    if (empty(getenv(\$var))) {
        \$missing[] = \$var;
    }
}

if (empty(\$missing)) {
    echo \"✅ Configuração de email OK\n\";
} else {
    echo \"⚠️  Variáveis faltando: \" . implode(', ', \$missing) . \"\n\";
}
"
```

**Resultado Esperado:** `✅ Configuração de email OK`

### 3. Verificar Último Commit

```bash
git log -1 --oneline
```

**Resultado Esperado:** `2910f0a IA - FEAT: Implementa controllers e rotas para logs de auditoria (FASE 1 - Parte 2)`

---

## 🔧 ETAPA 1: Executar Migration 027

### Opção A: Via Painel Admin (RECOMENDADO)

1. **Acessar painel de migrações:**
   ```
   URL: https://operebem.com/secure/adm/migrations
   ```

2. **Fazer login como admin**

3. **Executar migration 027:**
   - Procurar na lista: `027_create_admin_audit_logs.sql`
   - Verificar se está marcada como "Já executada" ou "Pendente"
   - Se pendente, clicar em "Executar" ou atualizar a página

4. **Verificar resultado:**
   - Deve aparecer: `✅ Executada: 027_create_admin_audit_logs.sql`

### Opção B: Via Terminal (Se tiver acesso SSH)

```bash
# Ler o arquivo de migration
cat database/migrations/027_create_admin_audit_logs.sql

# Executar via PHP
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

\$sql = file_get_contents('database/migrations/027_create_admin_audit_logs.sql');

try {
    \$pdo->beginTransaction();
    \$pdo->exec(\$sql);
    \$pdo->exec(\"INSERT INTO migrations (filename) VALUES ('027_create_admin_audit_logs.sql')\");
    \$pdo->commit();
    echo \"✅ Migration 027 executada com sucesso!\n\";
} catch (Exception \$e) {
    \$pdo->rollBack();
    echo \"❌ Erro: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
```

### Opção C: Executar SQL Direto (PostgreSQL CLI)

```bash
# Conectar ao banco (ajustar credenciais conforme .env)
psql -U usuario_postgres -d nome_banco

# Dentro do psql, executar:
\i database/migrations/027_create_admin_audit_logs.sql

# Registrar migration manualmente
INSERT INTO migrations (filename, executed_at) 
VALUES ('027_create_admin_audit_logs.sql', NOW());

# Sair
\q
```

---

## 🔍 ETAPA 2: Verificar Estrutura do Banco

### 2.1. Verificar Tabela `admin_audit_logs`

```sql
-- Conectar ao PostgreSQL
psql -U usuario -d banco

-- Verificar se tabela existe
\dt admin_audit_logs

-- Ver estrutura da tabela
\d admin_audit_logs

-- Resultado esperado:
-- Coluna           | Tipo                        | Nullable
-- -----------------+-----------------------------+---------
-- id               | bigint                      | NOT NULL
-- actor_type       | character varying(20)       | NOT NULL
-- admin_id         | integer                     |
-- admin_email      | character varying(255)      |
-- user_id          | integer                     |
-- user_email       | character varying(255)      |
-- action_type      | character varying(100)      | NOT NULL
-- entity_type      | character varying(50)       |
-- entity_id        | integer                     |
-- description      | text                        | NOT NULL
-- changes          | jsonb                       |
-- ip_address       | character varying(45)       |
-- user_agent       | text                        |
-- created_at       | timestamp without time zone | default NOW()
```

**Checklist:**
- [ ] Tabela `admin_audit_logs` existe
- [ ] Coluna `changes` é do tipo `jsonb`
- [ ] Índices foram criados (`idx_audit_admin_id`, `idx_audit_user_id`, `idx_audit_created_at`)

### 2.2. Verificar Campo `trial_extended_days` em `subscriptions`

```sql
-- Ver estrutura da tabela subscriptions
\d subscriptions

-- Verificar se campo existe
SELECT column_name, data_type, column_default 
FROM information_schema.columns 
WHERE table_name = 'subscriptions' 
AND column_name = 'trial_extended_days';

-- Resultado esperado:
-- column_name         | data_type | column_default
-- --------------------+-----------+---------------
-- trial_extended_days | integer   | 0
```

**Checklist:**
- [ ] Campo `trial_extended_days` existe
- [ ] Tipo é `INTEGER`
- [ ] Default é `0`

### 2.3. Verificar Campo `deleted_at` em `coupons`

```sql
-- Ver estrutura da tabela coupons
\d coupons

-- Verificar se campo existe
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'coupons' 
AND column_name = 'deleted_at';

-- Resultado esperado:
-- column_name | data_type                   | is_nullable
-- ------------+-----------------------------+------------
-- deleted_at  | timestamp without time zone | YES
```

**Checklist:**
- [ ] Campo `deleted_at` existe
- [ ] Permite NULL
- [ ] Tipo é `TIMESTAMP`

---

## 🧪 ETAPA 3: TESTES FUNCIONAIS

### 3.1. Teste: Reset de Senha por Admin

**Objetivo:** Verificar se admin consegue resetar senha de usuário e se log é registrado

#### Passo a Passo:

1. **Acessar painel admin**
   ```
   URL: https://operebem.com/secure/adm/users
   ```

2. **Selecionar um usuário de teste**
   - Clicar em um usuário qualquer
   - Anotar ID do usuário (exemplo: ID 123)

3. **Na página `/secure/adm/users/view?id=123`:**
   - Procurar seção "Ações Rápidas" ou botões de ação
   - Clicar no botão **"Resetar Senha"**

4. **Preencher modal:**
   - **Motivo:** "Teste de reset de senha via admin"
   - **Deslogar de todos dispositivos:** ☑️ (marcar)
   - Clicar em **"Confirmar Reset"**

5. **Verificar resultado:**
   - Deve aparecer mensagem de sucesso
   - Usuário deve receber email com nova senha

6. **Verificar log no banco:**
   ```sql
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'password_reset_by_admin' 
   ORDER BY created_at DESC 
   LIMIT 5;
   ```

**Resultado Esperado:**
```
✅ Mensagem: "Senha resetada com sucesso. Nova senha enviada por email."
✅ Email recebido pelo usuário
✅ Registro na tabela admin_audit_logs com:
   - actor_type = 'admin'
   - action_type = 'password_reset_by_admin'
   - changes->>'reason' = 'Teste de reset de senha via admin'
   - changes->>'logout_all' = true
```

**Checklist:**
- [ ] Modal abre corretamente
- [ ] Validação de CSRF funciona
- [ ] Email é enviado
- [ ] Usuário consegue fazer login com nova senha
- [ ] Log aparece na tabela `admin_audit_logs`
- [ ] Log aparece na seção "Histórico de Ações" da user_view.php

---

### 3.2. Teste: Deslogar de Todos Dispositivos

**Objetivo:** Verificar se admin consegue deslogar usuário de todos dispositivos

#### Passo a Passo:

1. **Preparar ambiente:**
   - Fazer login como usuário de teste em 2 navegadores diferentes (Chrome + Firefox)
   - Marcar opção "Lembrar-me" em ambos

2. **Como admin:**
   - Acessar `/secure/adm/users/view?id=123`
   - Clicar em **"Deslogar de Todos Dispositivos"**

3. **Preencher modal:**
   - **Motivo:** "Teste de segurança - logout forçado"
   - Clicar em **"Confirmar Logout"**

4. **Verificar resultado:**
   - Voltar aos navegadores onde usuário está logado
   - Atualizar página (F5)
   - Usuário deve ser redirecionado para login

5. **Verificar no banco:**
   ```sql
   -- Ver se remember_tokens foram deletados
   SELECT * FROM remember_tokens WHERE user_id = 123;
   -- Resultado esperado: 0 linhas
   
   -- Ver log de auditoria
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'logout_all_devices' 
   AND user_id = 123
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

**Resultado Esperado:**
```
✅ Mensagem: "Usuário deslogado de todos dispositivos com sucesso"
✅ Usuário deslogado automaticamente em todos navegadores
✅ Tabela remember_tokens não tem registros do user_id
✅ Log registrado em admin_audit_logs
```

**Checklist:**
- [ ] Modal abre corretamente
- [ ] Validação de motivo obrigatório funciona
- [ ] Todos remember_tokens foram deletados
- [ ] Usuário foi deslogado em todos dispositivos
- [ ] Log aparece na interface admin

---

### 3.3. Teste: Cancelar Assinatura

**Objetivo:** Verificar se cancelamento de assinatura funciona e registra log

#### Passo a Passo:

1. **Selecionar usuário com assinatura ativa:**
   - Acessar `/secure/adm/users`
   - Filtrar por tier = PLUS ou PRO
   - Escolher usuário com `subscription_expires_at` futuro

2. **Na página do usuário:**
   - Procurar seção **"Gerenciar Assinatura"**
   - Clicar em **"Cancelar Assinatura"**

3. **Preencher modal:**
   - **Tipo de cancelamento:** "Ao final do período" (ou "Imediatamente")
   - **Motivo:** "Teste de cancelamento via admin"
   - Clicar em **"Confirmar Cancelamento"**

4. **Verificar resultado:**
   - Status da assinatura deve mudar
   - Se "Imediato": tier do usuário vira FREE
   - Se "Ao final": flag `cancel_at_period_end` = true

5. **Verificar no banco:**
   ```sql
   -- Ver assinatura
   SELECT id, user_id, status, tier, cancel_at_period_end 
   FROM subscriptions 
   WHERE user_id = 123;
   
   -- Ver log
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'subscription_canceled' 
   AND user_id = 123
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

**Resultado Esperado:**
```
✅ Mensagem: "Assinatura cancelada com sucesso"
✅ Status da subscription atualizado
✅ Log registrado com motivo do cancelamento
✅ Se Stripe configurado, cancelamento refletido lá também
```

**Checklist:**
- [ ] Modal abre
- [ ] Cancelamento imediato funciona
- [ ] Cancelamento ao final do período funciona
- [ ] Tier do usuário atualizado corretamente
- [ ] Log completo com tipo e motivo

---

### 3.4. Teste: Estender Trial (Validação de Limite)

**Objetivo:** Verificar se limite de 60 dias é respeitado

#### Passo a Passo:

1. **Criar assinatura trial:**
   - Selecionar usuário sem assinatura ativa
   - Dar tier PLUS manualmente com trial de 7 dias

2. **Tentar estender 30 dias:**
   - Clicar em "Estender Trial"
   - Escolher: **30 dias**
   - Motivo: "Teste 1 - extensão dentro do limite"
   - Confirmar

3. **Verificar:**
   ```sql
   SELECT trial_end, trial_extended_days 
   FROM subscriptions 
   WHERE user_id = 123;
   -- Esperado: trial_extended_days = 30
   ```

4. **Tentar estender mais 35 dias (excede limite):**
   - Clicar em "Estender Trial" novamente
   - Escolher: **35 dias**
   - Deve aparecer erro: "Só é possível estender mais 30 dias"

5. **Tentar estender 30 dias:**
   - Escolher: **30 dias**
   - Deve funcionar

6. **Tentar estender 1 dia (deve falhar):**
   - Total seria 61 dias
   - Deve aparecer: "Limite de extensão de trial atingido (máximo: 60 dias)"

**Resultado Esperado:**
```
✅ Extensão dentro do limite funciona
✅ Extensão que excede limite é bloqueada
✅ Mensagem clara indica quantos dias restam
✅ Campo trial_extended_days é incrementado corretamente
```

**Checklist:**
- [ ] Validação de limite funciona
- [ ] Mensagens de erro são claras
- [ ] Campo `trial_extended_days` atualiza corretamente
- [ ] Logs registram todas tentativas

---

## 📝 ETAPA 4: TESTES DE AUDITORIA (Logs de Usuário)

### 4.1. Teste: Log de Alteração de Avatar

#### Passo a Passo:

1. **Fazer login como usuário comum**
   ```
   URL: https://operebem.com/profile
   ```

2. **Alterar foto de perfil:**
   - Clicar em "Alterar Foto"
   - Upload de uma imagem (PNG, JPG ou WEBP)
   - Confirmar

3. **Verificar log no banco:**
   ```sql
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'avatar_changed' 
   AND user_id = 123
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

**Resultado Esperado:**
```
✅ Avatar atualizado
✅ Log registrado com:
   - actor_type = 'user'
   - action_type = 'avatar_changed'
   - user_email = email do usuário
   - description = 'Foto de perfil alterada'
```

**Checklist:**
- [ ] Upload funciona
- [ ] Log é criado automaticamente
- [ ] Log aparece na user_view.php quando admin acessa perfil do usuário

---

### 4.2. Teste: Log de Alteração de Preferências

#### Passo a Passo:

1. **Ainda logado como usuário:**
   - Ir para `/profile`
   - Alterar **Timezone** para "America/New_York"
   - Alterar **Tema** para "dark"
   - Marcar/desmarcar opções de media_card e advanced_snapshot
   - Clicar em "Salvar Preferências"

2. **Verificar log:**
   ```sql
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'profile_updated' 
   AND user_id = 123
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

**Resultado Esperado:**
```json
{
  "actor_type": "user",
  "action_type": "profile_updated",
  "description": "Preferências do perfil atualizadas",
  "changes": {
    "theme": "dark",
    "timezone": "America/New_York",
    "media_card": true,
    "advanced_snapshot": false
  }
}
```

**Checklist:**
- [ ] Preferências salvas corretamente
- [ ] Log registrado com changes em JSONB
- [ ] Changes contém todos os campos alterados

---

### 4.3. Teste: Log de Alteração de Senha

#### Passo a Passo:

1. **Ainda logado como usuário:**
   - Ir para `/profile`
   - Procurar seção "Alterar Senha"
   - **Senha Atual:** [senha atual]
   - **Nova Senha:** NovaSenh@123
   - **Confirmar:** NovaSenh@123
   - Clicar em "Alterar Senha"

2. **Verificar log:**
   ```sql
   SELECT * FROM admin_audit_logs 
   WHERE action_type = 'password_changed' 
   AND user_id = 123
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

3. **Testar nova senha:**
   - Fazer logout
   - Tentar login com senha antiga (deve falhar)
   - Fazer login com nova senha (deve funcionar)

**Resultado Esperado:**
```
✅ Senha alterada com sucesso
✅ Login com senha antiga falha
✅ Login com nova senha funciona
✅ Log registrado com action_type = 'password_changed'
```

**Checklist:**
- [ ] Validação de senha atual funciona
- [ ] Nova senha é hasheada corretamente
- [ ] Log é criado
- [ ] Sem informações sensíveis no log (senha não aparece)

---

## 📧 ETAPA 5: TESTES DE EMAIL

### 5.1. Verificar Configuração SMTP

```bash
# Criar script de teste de email
cat > test_email.php << 'EOF'
<?php
require 'vendor/autoload.php';

$email = new \App\Services\EmailService();

try {
    $result = $email->sendAdminNewPassword(
        'Usuário Teste',
        'SenhaTeste123',
        'seuemail@teste.com'  // TROCAR POR EMAIL REAL
    );
    
    if ($result) {
        echo "✅ Email enviado com sucesso!\n";
    } else {
        echo "❌ Falha ao enviar email\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
EOF

# Executar teste
php test_email.php
```

**Resultado Esperado:**
```
✅ Email enviado com sucesso!
```

**Se falhar, verificar:**
- [ ] `.env` tem todas variáveis de MAIL_*
- [ ] Credenciais do SMTP estão corretas
- [ ] Porta está correta (465 para SSL, 587 para TLS)
- [ ] Firewall não está bloqueando

### 5.2. Testar Email de Reset de Senha

1. **Executar reset de senha via admin** (teste 3.1)
2. **Verificar inbox do usuário:**
   - Assunto: "Sua senha foi resetada - Terminal Operebem"
   - Corpo deve conter:
     - Nome do usuário
     - Nova senha temporária
     - Instruções para alterar senha

**Checklist:**
- [ ] Email chega em até 1 minuto
- [ ] Formatação está correta
- [ ] Senha no email funciona
- [ ] Links (se houver) funcionam

---

## ✅ ETAPA 6: VALIDAÇÃO FINAL

### 6.1. Verificar Interface Admin

1. **Acessar `/secure/adm/users/view?id=123`**
2. **Verificar seções criadas:**

   #### Seção "Gerenciar Assinatura"
   - [ ] Card aparece se usuário tem assinatura
   - [ ] Botões: "Estender Trial", "Cancelar", "Resetar Trial"
   - [ ] Status da assinatura visível

   #### Seção "Histórico de Ações"
   - [ ] Timeline com últimos 20 logs
   - [ ] Logs formatados corretamente
   - [ ] Ícones diferentes por tipo de ação
   - [ ] Badges coloridas (admin/user)
   - [ ] Botão "Ver Detalhes" abre modal

   #### Seção "Ações Rápidas"
   - [ ] Botão "Resetar Senha"
   - [ ] Botão "Deslogar de Todos Dispositivos"

3. **Testar modais:**
   - [ ] Modal "Cancelar Assinatura" abre
   - [ ] Modal "Estender Trial" valida limite
   - [ ] Modal "Resetar Senha" tem checkbox de logout
   - [ ] Modal "Deslogar Dispositivos" pede motivo
   - [ ] Modal "Detalhes do Log" mostra JSON formatado

### 6.2. Verificar Performance

```sql
-- Ver quantidade de logs
SELECT COUNT(*) FROM admin_audit_logs;

-- Ver logs por tipo
SELECT action_type, COUNT(*) 
FROM admin_audit_logs 
GROUP BY action_type 
ORDER BY COUNT(*) DESC;

-- Verificar índices
SELECT 
    tablename, 
    indexname, 
    indexdef 
FROM pg_indexes 
WHERE tablename = 'admin_audit_logs';
```

**Resultado Esperado:**
```
✅ Índices criados:
   - idx_audit_admin_id
   - idx_audit_user_id  
   - idx_audit_action_type
   - idx_audit_created_at
✅ Queries rápidas (< 100ms para 1000 registros)
```

### 6.3. Teste de Carga (Opcional)

```php
<?php
// Criar script para inserir 1000 logs de teste
require 'vendor/autoload.php';

$service = new \App\Services\AuditLogService();

for ($i = 0; $i < 1000; $i++) {
    $service->logUserAction([
        'user_id' => rand(1, 100),
        'user_email' => "user{$i}@test.com",
        'action_type' => 'test_action',
        'description' => "Log de teste #{$i}"
    ]);
}

echo "✅ 1000 logs inseridos\n";
```

**Verificar:**
- [ ] Inserção rápida (< 5 segundos)
- [ ] Interface admin carrega rápido
- [ ] Paginação funciona (se implementada)

---

## 🔧 TROUBLESHOOTING

### Problema 1: Migration 027 não executa

**Sintomas:**
- Erro ao acessar `/secure/adm/migrations`
- SQL syntax error

**Solução:**
```bash
# Verificar se migration já foi executada
psql -U usuario -d banco -c "SELECT * FROM migrations WHERE filename = '027_create_admin_audit_logs.sql';"

# Se retornar linha, migration já foi executada
# Se não, verificar erros no SQL:

# Executar linha por linha:
psql -U usuario -d banco

-- Criar tabela
CREATE TABLE admin_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    actor_type VARCHAR(20) NOT NULL CHECK (actor_type IN ('admin', 'user', 'system')),
    -- ... restante do SQL
);

-- Se der erro, anotar mensagem e investigar
```

### Problema 2: Logs não aparecem na interface

**Sintomas:**
- Seção "Histórico de Ações" vazia
- Ações são executadas mas logs não salvam

**Diagnóstico:**
```sql
-- Verificar se tabela existe
SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_name = 'admin_audit_logs'
);

-- Se FALSE, migration não foi executada

-- Verificar se AuditLogService está sendo chamado
-- Adicionar temporariamente em AuditLogService.php linha 50:
error_log("AUDIT LOG: " . json_encode($data));

-- Checar logs em storage/logs/php-error.log
```

**Solução:**
1. Executar migration 027
2. Limpar cache (se houver)
3. Verificar se `AuditLogService` está no namespace correto

### Problema 3: Email não envia

**Sintomas:**
- Reset de senha funciona mas email não chega
- Erro 500 ao resetar senha

**Diagnóstico:**
```bash
# Verificar logs de erro
tail -f storage/logs/php-error.log

# Testar SMTP manualmente
php -r "
\$smtp = fsockopen(getenv('MAIL_HOST'), getenv('MAIL_PORT'), \$errno, \$errstr, 5);
if (\$smtp) {
    echo '✅ Conexão SMTP OK\n';
    fclose(\$smtp);
} else {
    echo '❌ Erro: ' . \$errstr . '\n';
}
"
```

**Solução:**
1. Verificar `.env`:
   ```env
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=seu@email.com
   MAIL_PASSWORD=suasenha
   MAIL_ENCRYPTION=ssl
   ```
2. Testar credenciais em cliente de email
3. Verificar se IP do servidor não está bloqueado

### Problema 4: CSRF Token Inválido

**Sintomas:**
- Ao clicar em qualquer botão de ação, retorna erro CSRF

**Diagnóstico:**
```php
// Adicionar em AdminSecureController.php linha 914:
error_log("CSRF Session: " . ($_SESSION['csrf_token'] ?? 'VAZIO'));
error_log("CSRF POST: " . ($_POST['csrf_token'] ?? 'VAZIO'));
```

**Solução:**
1. Verificar se sessão está iniciada
2. Limpar cookies do navegador
3. Verificar se `CsrfMiddleware` está nas rotas
4. Gerar novo token:
   ```php
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   ```

### Problema 5: Método não encontrado (404)

**Sintomas:**
- Ao clicar em "Resetar Senha": 404 Not Found
- Rota `/secure/adm/users/reset-password` não existe

**Diagnóstico:**
```bash
# Verificar se rotas foram adicionadas
grep "reset-password" routes/web.php

# Deve aparecer:
# $router->post('/secure/adm/users/reset-password', ...
```

**Solução:**
1. Verificar se commit `2910f0a` foi aplicado
2. Executar `git pull` se necessário
3. Limpar cache de rotas (se houver)

---

## 🔄 ROLLBACK (Se Necessário)

### Se algo der muito errado:

#### Rollback da Migration

```sql
-- Remover tabela
DROP TABLE IF EXISTS admin_audit_logs CASCADE;

-- Remover campo trial_extended_days
ALTER TABLE subscriptions DROP COLUMN IF EXISTS trial_extended_days;

-- Remover campo deleted_at de coupons
ALTER TABLE coupons DROP COLUMN IF EXISTS deleted_at;

-- Remover registro da migration
DELETE FROM migrations WHERE filename = '027_create_admin_audit_logs.sql';
```

#### Rollback do Código

```bash
# Voltar para commit anterior
git log --oneline -5

# Identificar commit antes do 2910f0a (exemplo: a0b8acc)
git reset --hard a0b8acc

# Forçar push (CUIDADO!)
git push origin main --force
```

**⚠️ ATENÇÃO:** Só fazer rollback se absolutamente necessário. Logs já criados serão perdidos.

---

## 📊 CHECKLIST FINAL DE VALIDAÇÃO

Antes de marcar como concluído, verificar:

### Banco de Dados
- [ ] Tabela `admin_audit_logs` criada
- [ ] Campo `trial_extended_days` em `subscriptions`
- [ ] Campo `deleted_at` em `coupons`
- [ ] Todos índices criados
- [ ] Migration registrada em `migrations`

### Funcionalidades
- [ ] Reset de senha via admin funciona
- [ ] Email de reset chega
- [ ] Logout de todos dispositivos funciona
- [ ] Cancelamento de assinatura funciona
- [ ] Extensão de trial respeita limite de 60 dias

### Logs de Auditoria
- [ ] Logs de admin são registrados
- [ ] Logs de usuário são registrados
- [ ] Logs aparecem na interface admin
- [ ] Modal de detalhes mostra JSON formatado
- [ ] Timeline está ordenada (mais recente primeiro)

### Interface Admin
- [ ] Seção "Gerenciar Assinatura" aparece
- [ ] Seção "Histórico de Ações" aparece
- [ ] Botões de ação funcionam
- [ ] Modais abrem e fecham
- [ ] Validações de formulário funcionam

### Performance
- [ ] Queries rápidas (< 100ms)
- [ ] Interface responsiva
- [ ] Sem erros no console do navegador
- [ ] Sem warnings no log do PHP

### Segurança
- [ ] Validação CSRF em todas rotas POST
- [ ] Senhas nunca aparecem em logs
- [ ] Apenas admins acessam rotas /secure/adm/*
- [ ] Logs não podem ser editados/deletados

---

## 📝 RELATÓRIO DE EXECUÇÃO

Após completar todos os testes, preencher:

```markdown
# RELATÓRIO - FASE 1: MIGRAÇÃO E TESTES

**Data de Execução:** __/__/____
**Executado por:** [Nome da IA/Pessoa]
**Duração Total:** ___ minutos

## Resultados

### Migração
- [ ] ✅ Migration 027 executada com sucesso
- [ ] ❌ Problemas encontrados: _______________

### Testes Funcionais
- [ ] ✅ Reset de senha: OK
- [ ] ✅ Logout dispositivos: OK  
- [ ] ✅ Cancelar assinatura: OK
- [ ] ✅ Estender trial: OK

### Testes de Auditoria
- [ ] ✅ Log de avatar: OK
- [ ] ✅ Log de preferências: OK
- [ ] ✅ Log de senha: OK

### Testes de Email
- [ ] ✅ Email de reset: OK
- [ ] ❌ Problemas: _______________

### Bugs Encontrados
1. _______________
2. _______________

### Observações
_______________________________________________
_______________________________________________

## Status Final
- [ ] ✅ APROVADO - Sistema pronto para produção
- [ ] ⚠️  APROVADO COM RESSALVAS - Ver observações
- [ ] ❌ REPROVADO - Necessita correções
```

---

## 🎯 PRÓXIMOS PASSOS (Após Aprovação)

1. **Fazer backup do banco de dados**
   ```bash
   pg_dump -U usuario -d banco > backup_pre_audit_logs.sql
   ```

2. **Deploy em produção** (se em staging)

3. **Monitorar logs por 24h:**
   ```sql
   -- Ver quantidade de logs por hora
   SELECT 
       DATE_TRUNC('hour', created_at) as hora,
       COUNT(*) as total
   FROM admin_audit_logs
   WHERE created_at > NOW() - INTERVAL '24 hours'
   GROUP BY hora
   ORDER BY hora DESC;
   ```

4. **Configurar retenção de logs** (opcional):
   ```sql
   -- Criar job para deletar logs com mais de 90 dias
   DELETE FROM admin_audit_logs 
   WHERE created_at < NOW() - INTERVAL '90 days';
   ```

5. **Documentar para equipe:**
   - Como visualizar logs de um usuário
   - Como interpretar changes JSONB
   - Políticas de retenção de dados

---

## 📞 SUPORTE

**Em caso de dúvidas ou problemas:**

1. Verificar seção [Troubleshooting](#troubleshooting)
2. Consultar documentação:
   - `.gemini/plans/ADMIN_SUBSCRIPTION_SCREENS_GUIDE.md`
3. Verificar logs:
   - `storage/logs/php-error.log`
   - `storage/logs/admin/YYYY-MM-DD.log`

---

**Boa sorte com a migração e testes! 🚀**
