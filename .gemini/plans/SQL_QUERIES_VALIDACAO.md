# SQL Queries de Validação - Sistema de Auditoria

**Uso:** Copiar e colar no PostgreSQL para validar migration e testar funcionalidades

---

## 📊 VALIDAÇÃO DA MIGRATION

### 1. Verificar se migration foi executada
```sql
SELECT * FROM migrations 
WHERE filename = '027_create_admin_audit_logs.sql';

-- Resultado esperado: 1 linha com executed_at preenchido
```

### 2. Verificar se tabela admin_audit_logs existe
```sql
SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_name = 'admin_audit_logs'
);

-- Resultado esperado: t (true)
```

### 3. Ver estrutura completa da tabela
```sql
\d admin_audit_logs

-- OU (sem psql):
SELECT 
    column_name, 
    data_type, 
    character_maximum_length,
    is_nullable,
    column_default
FROM information_schema.columns 
WHERE table_name = 'admin_audit_logs'
ORDER BY ordinal_position;
```

### 4. Verificar índices criados
```sql
SELECT 
    indexname, 
    indexdef 
FROM pg_indexes 
WHERE tablename = 'admin_audit_logs'
ORDER BY indexname;

-- Resultado esperado: 5 índices
-- 1. admin_audit_logs_pkey (PRIMARY KEY)
-- 2. idx_audit_action_type
-- 3. idx_audit_admin_id
-- 4. idx_audit_created_at
-- 5. idx_audit_user_id
```

### 5. Verificar campo trial_extended_days em subscriptions
```sql
SELECT 
    column_name, 
    data_type, 
    column_default,
    is_nullable
FROM information_schema.columns 
WHERE table_name = 'subscriptions' 
AND column_name = 'trial_extended_days';

-- Resultado esperado:
-- column_name         | data_type | column_default | is_nullable
-- --------------------+-----------+----------------+------------
-- trial_extended_days | integer   | 0              | YES
```

### 6. Verificar campo deleted_at em coupons
```sql
SELECT 
    column_name, 
    data_type, 
    is_nullable
FROM information_schema.columns 
WHERE table_name = 'coupons' 
AND column_name = 'deleted_at';

-- Resultado esperado:
-- column_name | data_type                   | is_nullable
-- ------------+-----------------------------+------------
-- deleted_at  | timestamp without time zone | YES
```

---

## 🔍 CONSULTAS DE LOGS DE AUDITORIA

### 7. Ver últimos 20 logs (todos)
```sql
SELECT 
    id,
    actor_type,
    COALESCE(admin_email, user_email) as email,
    action_type,
    description,
    created_at
FROM admin_audit_logs
ORDER BY created_at DESC
LIMIT 20;
```

### 8. Ver logs de ações de ADMIN
```sql
SELECT 
    id,
    admin_email,
    action_type,
    description,
    created_at
FROM admin_audit_logs
WHERE actor_type = 'admin'
ORDER BY created_at DESC
LIMIT 10;
```

### 9. Ver logs de ações de USUÁRIO
```sql
SELECT 
    id,
    user_email,
    action_type,
    description,
    created_at
FROM admin_audit_logs
WHERE actor_type = 'user'
ORDER BY created_at DESC
LIMIT 10;
```

### 10. Ver logs de um usuário específico (trocar ID)
```sql
SELECT 
    id,
    actor_type,
    action_type,
    description,
    changes,
    created_at
FROM admin_audit_logs
WHERE user_id = 123  -- TROCAR PELO ID DO USUÁRIO
ORDER BY created_at DESC;
```

### 11. Ver logs de um admin específico (trocar ID)
```sql
SELECT 
    id,
    admin_email,
    user_id,
    action_type,
    description,
    created_at
FROM admin_audit_logs
WHERE admin_id = 1  -- TROCAR PELO ID DO ADMIN
ORDER BY created_at DESC;
```

### 12. Ver logs por tipo de ação
```sql
-- Reset de senha
SELECT * FROM admin_audit_logs 
WHERE action_type = 'password_reset_by_admin'
ORDER BY created_at DESC;

-- Logout de dispositivos
SELECT * FROM admin_audit_logs 
WHERE action_type = 'logout_all_devices'
ORDER BY created_at DESC;

-- Cancelamento de assinatura
SELECT * FROM admin_audit_logs 
WHERE action_type = 'subscription_canceled'
ORDER BY created_at DESC;

-- Extensão de trial
SELECT * FROM admin_audit_logs 
WHERE action_type = 'trial_extended'
ORDER BY created_at DESC;

-- Alteração de avatar
SELECT * FROM admin_audit_logs 
WHERE action_type = 'avatar_changed'
ORDER BY created_at DESC;

-- Alteração de preferências
SELECT * FROM admin_audit_logs 
WHERE action_type = 'profile_updated'
ORDER BY created_at DESC;

-- Alteração de senha pelo usuário
SELECT * FROM admin_audit_logs 
WHERE action_type = 'password_changed'
ORDER BY created_at DESC;
```

### 13. Ver logs das últimas 24 horas
```sql
SELECT 
    actor_type,
    action_type,
    COALESCE(admin_email, user_email) as email,
    description,
    created_at
FROM admin_audit_logs
WHERE created_at > NOW() - INTERVAL '24 hours'
ORDER BY created_at DESC;
```

### 14. Contar logs por tipo de ação
```sql
SELECT 
    action_type,
    COUNT(*) as total,
    COUNT(CASE WHEN actor_type = 'admin' THEN 1 END) as admin_actions,
    COUNT(CASE WHEN actor_type = 'user' THEN 1 END) as user_actions
FROM admin_audit_logs
GROUP BY action_type
ORDER BY total DESC;
```

### 15. Ver detalhes completos de um log (com JSONB formatado)
```sql
SELECT 
    id,
    actor_type,
    admin_id,
    admin_email,
    user_id,
    user_email,
    action_type,
    entity_type,
    entity_id,
    description,
    jsonb_pretty(changes) as changes_formatted,
    ip_address,
    user_agent,
    created_at
FROM admin_audit_logs
WHERE id = 1  -- TROCAR PELO ID DO LOG
ORDER BY created_at DESC;
```

---

## 🧪 TESTES DE VALIDAÇÃO

### 16. Testar inserção manual de log (simulação)
```sql
-- Inserir log de teste
INSERT INTO admin_audit_logs (
    actor_type,
    admin_id,
    admin_email,
    user_id,
    action_type,
    entity_type,
    entity_id,
    description,
    changes,
    ip_address,
    created_at
) VALUES (
    'admin',
    1,
    'admin@operebem.com',
    123,
    'test_action',
    'user',
    123,
    'Log de teste manual',
    '{"test": true, "reason": "validação"}',
    '127.0.0.1',
    NOW()
);

-- Verificar se foi inserido
SELECT * FROM admin_audit_logs WHERE action_type = 'test_action' ORDER BY created_at DESC LIMIT 1;

-- Deletar log de teste
DELETE FROM admin_audit_logs WHERE action_type = 'test_action';
```

### 17. Verificar performance de índices
```sql
-- Explicar query (deve usar índice)
EXPLAIN ANALYZE 
SELECT * FROM admin_audit_logs 
WHERE user_id = 123 
ORDER BY created_at DESC 
LIMIT 20;

-- Resultado esperado: "Index Scan using idx_audit_user_id"
```

### 18. Verificar constraint de actor_type
```sql
-- Tentar inserir actor_type inválido (deve falhar)
INSERT INTO admin_audit_logs (
    actor_type,
    action_type,
    description
) VALUES (
    'invalid_type',  -- Deve falhar
    'test',
    'Teste de constraint'
);

-- Erro esperado: "violates check constraint"
```

---

## 🔧 QUERIES DE MANUTENÇÃO

### 19. Ver tamanho da tabela
```sql
SELECT 
    pg_size_pretty(pg_total_relation_size('admin_audit_logs')) as tamanho_total,
    pg_size_pretty(pg_relation_size('admin_audit_logs')) as tamanho_dados,
    pg_size_pretty(pg_indexes_size('admin_audit_logs')) as tamanho_indices;
```

### 20. Ver estatísticas de uso
```sql
SELECT 
    COUNT(*) as total_logs,
    COUNT(DISTINCT user_id) as usuarios_unicos,
    COUNT(DISTINCT admin_id) as admins_unicos,
    MIN(created_at) as primeiro_log,
    MAX(created_at) as ultimo_log,
    COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '24 hours') as logs_24h,
    COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '7 days') as logs_7d
FROM admin_audit_logs;
```

### 21. Logs por hora (últimas 24h)
```sql
SELECT 
    DATE_TRUNC('hour', created_at) as hora,
    COUNT(*) as total,
    COUNT(CASE WHEN actor_type = 'admin' THEN 1 END) as admin,
    COUNT(CASE WHEN actor_type = 'user' THEN 1 END) as user
FROM admin_audit_logs
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY hora
ORDER BY hora DESC;
```

### 22. Limpar logs antigos (simulação - NÃO EXECUTAR EM PRODUÇÃO)
```sql
-- ATENÇÃO: Apenas para testes! 
-- Em produção, usar variável AUDIT_LOG_RETENTION_DAYS do .env

-- Ver quantos logs seriam deletados
SELECT COUNT(*) 
FROM admin_audit_logs 
WHERE created_at < NOW() - INTERVAL '90 days';

-- Deletar logs com mais de 90 dias (DESCOMENTAR PARA EXECUTAR)
-- DELETE FROM admin_audit_logs 
-- WHERE created_at < NOW() - INTERVAL '90 days';
```

---

## 📊 QUERIES PARA DASHBOARD ADMIN

### 23. Resumo de atividades do dia
```sql
SELECT 
    action_type,
    COUNT(*) as total,
    COUNT(DISTINCT user_id) as usuarios_afetados
FROM admin_audit_logs
WHERE created_at >= CURRENT_DATE
GROUP BY action_type
ORDER BY total DESC;
```

### 24. Top 10 usuários mais ativos (logs)
```sql
SELECT 
    user_id,
    user_email,
    COUNT(*) as total_acoes,
    MAX(created_at) as ultima_acao
FROM admin_audit_logs
WHERE actor_type = 'user'
AND user_id IS NOT NULL
GROUP BY user_id, user_email
ORDER BY total_acoes DESC
LIMIT 10;
```

### 25. Top 10 admins mais ativos
```sql
SELECT 
    admin_id,
    admin_email,
    COUNT(*) as total_acoes,
    COUNT(DISTINCT user_id) as usuarios_afetados,
    MAX(created_at) as ultima_acao
FROM admin_audit_logs
WHERE actor_type = 'admin'
AND admin_id IS NOT NULL
GROUP BY admin_id, admin_email
ORDER BY total_acoes DESC
LIMIT 10;
```

---

## 🚨 QUERIES DE SEGURANÇA/AUDITORIA

### 26. Detectar múltiplos resets de senha em curto período
```sql
-- Usuários que tiveram senha resetada mais de 2x em 7 dias
SELECT 
    user_id,
    user_email,
    COUNT(*) as resets,
    MIN(created_at) as primeiro_reset,
    MAX(created_at) as ultimo_reset
FROM admin_audit_logs
WHERE action_type = 'password_reset_by_admin'
AND created_at > NOW() - INTERVAL '7 days'
GROUP BY user_id, user_email
HAVING COUNT(*) > 2
ORDER BY resets DESC;
```

### 27. Ver todos logouts forçados
```sql
SELECT 
    user_id,
    user_email,
    admin_email,
    description,
    changes,
    created_at
FROM admin_audit_logs
WHERE action_type = 'logout_all_devices'
ORDER BY created_at DESC;
```

### 28. Ver todas extensões de trial (monitorar abusos)
```sql
SELECT 
    user_id,
    admin_email,
    description,
    changes->>'additional_days' as dias_estendidos,
    changes->>'total_extended_days' as total_acumulado,
    created_at
FROM admin_audit_logs
WHERE action_type = 'trial_extended'
ORDER BY created_at DESC;
```

### 29. Verificar usuários que atingiram limite de extensão
```sql
SELECT 
    user_id,
    user_email,
    changes->>'total_extended_days' as dias_estendidos
FROM admin_audit_logs
WHERE action_type = 'trial_extended'
AND (changes->>'total_extended_days')::int >= 60
ORDER BY created_at DESC;
```

### 30. Ver ações de um IP específico (trocar IP)
```sql
SELECT 
    actor_type,
    action_type,
    COALESCE(admin_email, user_email) as email,
    description,
    created_at
FROM admin_audit_logs
WHERE ip_address = '192.168.1.100'  -- TROCAR PELO IP
ORDER BY created_at DESC;
```

---

## 🔬 QUERIES AVANÇADAS (JSONB)

### 31. Buscar logs com change específico
```sql
-- Ver todos logs onde timezone foi alterado
SELECT 
    user_id,
    user_email,
    changes->>'timezone' as novo_timezone,
    created_at
FROM admin_audit_logs
WHERE action_type = 'profile_updated'
AND changes ? 'timezone'
ORDER BY created_at DESC;
```

### 32. Ver logs com logout_all = true
```sql
SELECT 
    user_id,
    admin_email,
    description,
    (changes->>'logout_all')::boolean as logout_all,
    created_at
FROM admin_audit_logs
WHERE action_type = 'password_reset_by_admin'
AND changes->>'logout_all' = 'true'
ORDER BY created_at DESC;
```

### 33. Ver motivos de cancelamentos
```sql
SELECT 
    user_id,
    admin_email,
    changes->>'reason' as motivo,
    changes->>'cancel_type' as tipo,
    created_at
FROM admin_audit_logs
WHERE action_type = 'subscription_canceled'
ORDER BY created_at DESC;
```

### 34. Estatísticas de alterações de preferências
```sql
SELECT 
    COUNT(*) as total_alteracoes,
    COUNT(*) FILTER (WHERE changes ? 'theme') as alterou_tema,
    COUNT(*) FILTER (WHERE changes ? 'timezone') as alterou_timezone,
    COUNT(*) FILTER (WHERE changes ? 'media_card') as alterou_media_card,
    COUNT(*) FILTER (WHERE changes ? 'advanced_snapshot') as alterou_snapshot
FROM admin_audit_logs
WHERE action_type = 'profile_updated';
```

---

## 🧹 LIMPEZA E RESET (APENAS PARA TESTES)

### 35. Deletar TODOS os logs (⚠️ CUIDADO!)
```sql
-- ATENÇÃO: Isso apaga TODOS os logs permanentemente!
-- Apenas usar em ambiente de testes!

-- Verificar quantos logs existem
SELECT COUNT(*) FROM admin_audit_logs;

-- Deletar todos (DESCOMENTAR PARA EXECUTAR)
-- TRUNCATE TABLE admin_audit_logs RESTART IDENTITY CASCADE;
```

### 36. Deletar logs de teste
```sql
-- Deletar apenas logs com action_type = 'test_action'
DELETE FROM admin_audit_logs 
WHERE action_type LIKE 'test%' 
OR description LIKE '%teste%' 
OR description LIKE '%test%';
```

---

## 📝 TEMPLATES DE QUERIES CUSTOMIZADAS

### 37. Template: Buscar logs entre datas
```sql
SELECT * FROM admin_audit_logs
WHERE created_at BETWEEN '2026-01-01 00:00:00' AND '2026-01-31 23:59:59'
ORDER BY created_at DESC;
```

### 38. Template: Buscar logs com múltiplos filtros
```sql
SELECT * FROM admin_audit_logs
WHERE 1=1
  AND actor_type = 'admin'                    -- OU 'user', 'system'
  AND action_type = 'password_reset_by_admin' -- Trocar pelo tipo desejado
  AND user_id = 123                           -- Trocar pelo ID
  AND created_at > NOW() - INTERVAL '7 days'
ORDER BY created_at DESC;
```

### 39. Template: Exportar logs para CSV (via psql)
```bash
# Executar no terminal (não no psql):
psql -U usuario -d banco -c "
COPY (
    SELECT 
        id,
        actor_type,
        admin_email,
        user_email,
        action_type,
        description,
        created_at
    FROM admin_audit_logs
    WHERE created_at > NOW() - INTERVAL '30 days'
    ORDER BY created_at DESC
) TO STDOUT WITH CSV HEADER
" > logs_export.csv
```

---

## ✅ CHECKLIST DE VALIDAÇÃO SQL

Executar queries na ordem e marcar:

- [ ] Query 1: Migration registrada
- [ ] Query 2: Tabela existe
- [ ] Query 3: Estrutura correta (14 colunas)
- [ ] Query 4: 5 índices criados
- [ ] Query 5: Campo trial_extended_days existe
- [ ] Query 6: Campo deleted_at existe
- [ ] Query 7: Logs visíveis
- [ ] Query 17: Índices sendo usados
- [ ] Query 18: Constraint funcionando
- [ ] Query 20: Estatísticas OK

**Se todas marcadas: ✅ VALIDAÇÃO SQL COMPLETA**

---

**Use essas queries para validar, monitorar e debugar o sistema de auditoria! 📊**
