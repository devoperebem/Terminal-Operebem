# QUICK START - Migração e Testes (Versão Rápida)

**Objetivo:** Executar migration 027 e validar funcionalidades básicas  
**Tempo Estimado:** 15-30 minutos  
**Pré-requisito:** Commits `a0b8acc` e `2910f0a` aplicados

---

## 🚀 EXECUÇÃO RÁPIDA (3 Passos)

### PASSO 1: Executar Migration (2 minutos)

**Opção A - Via Browser (RECOMENDADO):**
```
1. Acessar: https://operebem.com/secure/adm/migrations
2. Login como admin
3. Verificar se "027_create_admin_audit_logs.sql" aparece como executada
4. Se não, atualizar página
```

**Opção B - Via Terminal:**
```bash
cd "C:\Users\Administrator\Desktop\operebem\terminal operebem"

php -r "
require 'vendor/autoload.php';
\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();
\$sql = file_get_contents('database/migrations/027_create_admin_audit_logs.sql');
\$pdo->exec(\$sql);
\$pdo->exec(\"INSERT INTO migrations (filename) VALUES ('027_create_admin_audit_logs.sql')\");
echo \"✅ Migration OK\n\";
"
```

**Validar:**
```sql
-- Conectar ao PostgreSQL
psql -U usuario -d banco

-- Verificar tabela
\dt admin_audit_logs

-- Resultado esperado: tabela existe
```

---

### PASSO 2: Teste Básico (5 minutos)

#### 2.1. Testar Reset de Senha

```
1. Acessar: https://operebem.com/secure/adm/users
2. Clicar em qualquer usuário
3. Clicar botão "Resetar Senha"
4. Preencher:
   - Motivo: "Teste inicial"
   - Marcar "Deslogar de todos dispositivos"
5. Confirmar
6. Verificar mensagem de sucesso
```

**Validar no banco:**
```sql
SELECT * FROM admin_audit_logs 
WHERE action_type = 'password_reset_by_admin' 
ORDER BY created_at DESC 
LIMIT 1;

-- Deve retornar 1 linha
```

#### 2.2. Testar Log de Usuário

```
1. Fazer login como usuário comum
2. Ir para /profile
3. Alterar foto de perfil (upload qualquer imagem)
4. Voltar ao admin
5. Acessar perfil do usuário
6. Procurar seção "Histórico de Ações"
7. Deve aparecer log "Foto de perfil alterada"
```

---

### PASSO 3: Validação Final (3 minutos)

**Checklist Rápido:**

```sql
-- 1. Verificar estrutura da tabela
\d admin_audit_logs

-- 2. Verificar índices
SELECT indexname FROM pg_indexes WHERE tablename = 'admin_audit_logs';
-- Esperado: 4 índices (admin_id, user_id, action_type, created_at)

-- 3. Verificar campo trial_extended_days
\d subscriptions
-- Deve ter coluna: trial_extended_days | integer | default 0

-- 4. Verificar campo deleted_at
\d coupons  
-- Deve ter coluna: deleted_at | timestamp | NULL

-- 5. Contar logs
SELECT COUNT(*) FROM admin_audit_logs;
-- Deve ter pelo menos 2 (reset senha + avatar)
```

**Se todos comandos acima funcionarem: ✅ MIGRAÇÃO OK**

---

## 🧪 TESTES OPCIONAIS (Se Tiver Tempo)

### Teste 1: Deslogar Dispositivos (2 min)
```
1. Fazer login como usuário em 2 navegadores
2. Como admin: clicar "Deslogar de Todos Dispositivos"
3. Motivo: "Teste"
4. Confirmar
5. Atualizar navegadores do usuário
6. Deve redirecionar para login
```

### Teste 2: Cancelar Assinatura (2 min)
```
1. Selecionar usuário com tier PLUS/PRO
2. Clicar "Cancelar Assinatura"
3. Escolher "Ao final do período"
4. Motivo: "Teste"
5. Confirmar
6. Verificar flag cancel_at_period_end = true
```

### Teste 3: Estender Trial com Limite (3 min)
```
1. Usuário com assinatura trial
2. Estender 30 dias (deve funcionar)
3. Estender mais 30 dias (deve funcionar)
4. Tentar estender mais 1 dia (deve falhar - limite 60)
5. Mensagem: "Limite de extensão de trial atingido"
```

---

## 🐛 TROUBLESHOOTING RÁPIDO

### Erro: "Tabela admin_audit_logs não existe"
```sql
-- Executar manualmente:
psql -U usuario -d banco -f database/migrations/027_create_admin_audit_logs.sql
```

### Erro: "CSRF Token Inválido"
```
1. Limpar cookies do navegador
2. Fazer novo login
3. Tentar novamente
```

### Erro: "Email não enviado"
```bash
# Verificar .env
cat .env | grep MAIL_

# Deve ter:
# MAIL_HOST=smtp.hostinger.com
# MAIL_PORT=465
# MAIL_USERNAME=seu@email.com
# MAIL_PASSWORD=***
```

### Erro: "Método não encontrado"
```bash
# Verificar commit
git log -1 --oneline

# Deve ser: 2910f0a
# Se não, executar:
git pull origin main
```

---

## ✅ CHECKLIST DE CONCLUSÃO

Marcar como concluído se:

- [ ] Migration 027 executada
- [ ] Tabela `admin_audit_logs` existe no banco
- [ ] Reset de senha funciona
- [ ] Email de reset chega
- [ ] Log de avatar funciona
- [ ] Seção "Histórico de Ações" aparece na user_view
- [ ] Sem erros no console do navegador
- [ ] Sem erros no log do PHP

**Se todos marcados: ✅ FASE 1 CONCLUÍDA**

---

## 📋 RELATÓRIO MÍNIMO

```markdown
# RELATÓRIO RÁPIDO

**Data:** __/__/____
**Tempo:** ___ minutos

## Resultados
- [ ] ✅ Migration executada
- [ ] ✅ Reset senha OK
- [ ] ✅ Logs funcionando
- [ ] ❌ Problemas: _______________

## Bugs
1. _______________
2. _______________

## Status
- [ ] ✅ APROVADO
- [ ] ❌ REPROVADO
```

---

## 🎯 PRÓXIMO PASSO

Após conclusão, reportar resultado e seguir para:
- **Se APROVADO:** Deploy em produção
- **Se REPROVADO:** Consultar `FASE_1_MIGRACAO_E_TESTES.md` (guia completo)

---

**Boa execução! 🚀**
