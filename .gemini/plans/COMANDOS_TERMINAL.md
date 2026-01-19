# Comandos de Terminal - Migração e Validação

**Copiar e colar no terminal para executar testes**

---

## 🚀 SETUP INICIAL

### Navegar para diretório do projeto
```bash
cd "C:\Users\Administrator\Desktop\operebem\terminal operebem"
```

### Verificar branch e último commit
```bash
git status
git log -1 --oneline

# Resultado esperado: 2910f0a IA - FEAT: Implementa controllers e rotas...
```

---

## 🔧 EXECUÇÃO DA MIGRATION

### Método 1: Via PHP (Recomendado)
```bash
php -r "
require 'vendor/autoload.php';

echo \"📦 Iniciando migration 027...\n\n\";

try {
    \$app = \App\Core\Application::getInstance();
    \App\Core\Database::init(\$app->config('database'));
    \$pdo = \App\Core\Database::connection();
    
    echo \"✅ Conexão com banco OK\n\";
    
    // Verificar se migration já foi executada
    \$stmt = \$pdo->prepare('SELECT COUNT(*) FROM migrations WHERE filename = ?');
    \$stmt->execute(['027_create_admin_audit_logs.sql']);
    \$exists = \$stmt->fetchColumn();
    
    if (\$exists > 0) {
        echo \"⚠️  Migration já foi executada anteriormente\n\";
        exit(0);
    }
    
    echo \"📄 Lendo arquivo SQL...\n\";
    \$sql = file_get_contents('database/migrations/027_create_admin_audit_logs.sql');
    
    if (!\$sql) {
        echo \"❌ Erro ao ler arquivo de migration\n\";
        exit(1);
    }
    
    echo \"▶️  Executando migration...\n\";
    \$pdo->beginTransaction();
    \$pdo->exec(\$sql);
    \$pdo->exec(\"INSERT INTO migrations (filename) VALUES ('027_create_admin_audit_logs.sql')\");
    \$pdo->commit();
    
    echo \"✅ Migration 027 executada com sucesso!\n\n\";
    echo \"📊 Validando estrutura...\n\";
    
    // Validar tabela criada
    \$stmt = \$pdo->query(\"SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'admin_audit_logs'\");
    \$tableExists = \$stmt->fetchColumn();
    
    if (\$tableExists) {
        echo \"✅ Tabela admin_audit_logs criada\n\";
    } else {
        echo \"❌ Tabela não foi criada\n\";
        exit(1);
    }
    
    // Contar índices
    \$stmt = \$pdo->query(\"SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'admin_audit_logs'\");
    \$indexCount = \$stmt->fetchColumn();
    echo \"✅ {$indexCount} índices criados\n\n\";
    
    echo \"🎉 Migration concluída com sucesso!\n\";
    
} catch (Exception \$e) {
    if (isset(\$pdo) && \$pdo->inTransaction()) {
        \$pdo->rollBack();
    }
    echo \"❌ Erro: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
```

### Método 2: Via psql (Alternativo)
```bash
# Windows (ajustar caminho do psql se necessário)
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d operebem -f "database/migrations/027_create_admin_audit_logs.sql"

# Linux/Mac
psql -U usuario -d banco -f database/migrations/027_create_admin_audit_logs.sql

# Registrar migration manualmente
psql -U usuario -d banco -c "INSERT INTO migrations (filename) VALUES ('027_create_admin_audit_logs.sql');"
```

---

## 🧪 VALIDAÇÃO PÓS-MIGRATION

### Teste 1: Verificar conexão com banco
```bash
php -r "
\$host = getenv('DB_HOST') ?: 'localhost';
\$db = getenv('DB_NAME') ?: 'operebem';
\$user = getenv('DB_USER') ?: 'postgres';
\$pass = getenv('DB_PASS') ?: '';

try {
    \$pdo = new PDO(\"pgsql:host=\$host;dbname=\$db\", \$user, \$pass);
    echo \"✅ Conexão com banco de dados OK\n\";
    echo \"   Host: \$host\n\";
    echo \"   Database: \$db\n\";
    echo \"   User: \$user\n\";
} catch (Exception \$e) {
    echo \"❌ Erro de conexão: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
```

### Teste 2: Verificar estrutura da tabela
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"📊 Estrutura da tabela admin_audit_logs:\n\n\";

\$stmt = \$pdo->query(\"
    SELECT 
        column_name, 
        data_type, 
        is_nullable,
        column_default
    FROM information_schema.columns 
    WHERE table_name = 'admin_audit_logs'
    ORDER BY ordinal_position
\");

\$columns = \$stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty(\$columns)) {
    echo \"❌ Tabela não encontrada!\n\";
    exit(1);
}

foreach (\$columns as \$col) {
    printf(\"%-20s %-30s %s\n\", 
        \$col['column_name'], 
        \$col['data_type'],
        \$col['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL'
    );
}

echo \"\n✅ Total de colunas: \" . count(\$columns) . \"\n\";
"
```

### Teste 3: Verificar índices
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"📊 Índices da tabela admin_audit_logs:\n\n\";

\$stmt = \$pdo->query(\"
    SELECT indexname 
    FROM pg_indexes 
    WHERE tablename = 'admin_audit_logs'
    ORDER BY indexname
\");

\$indexes = \$stmt->fetchAll(PDO::FETCH_COLUMN);

foreach (\$indexes as \$idx) {
    echo \"✅ \" . \$idx . \"\n\";
}

echo \"\n✅ Total de índices: \" . count(\$indexes) . \"\n\";

// Validar índices esperados
\$expected = ['admin_audit_logs_pkey', 'idx_audit_action_type', 'idx_audit_admin_id', 'idx_audit_created_at', 'idx_audit_user_id'];
\$missing = array_diff(\$expected, \$indexes);

if (empty(\$missing)) {
    echo \"✅ Todos índices esperados foram criados\n\";
} else {
    echo \"⚠️  Índices faltando: \" . implode(', ', \$missing) . \"\n\";
}
"
```

### Teste 4: Verificar campos adicionados em outras tabelas
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"📊 Verificando campos adicionados:\n\n\";

// Verificar trial_extended_days em subscriptions
\$stmt = \$pdo->query(\"
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_name = 'subscriptions' 
    AND column_name = 'trial_extended_days'
\");

if (\$stmt->fetchColumn() > 0) {
    echo \"✅ Campo trial_extended_days existe em subscriptions\n\";
} else {
    echo \"❌ Campo trial_extended_days NÃO existe em subscriptions\n\";
}

// Verificar deleted_at em coupons
\$stmt = \$pdo->query(\"
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_name = 'coupons' 
    AND column_name = 'deleted_at'
\");

if (\$stmt->fetchColumn() > 0) {
    echo \"✅ Campo deleted_at existe em coupons\n\";
} else {
    echo \"❌ Campo deleted_at NÃO existe em coupons\n\";
}
"
```

---

## 📝 TESTE DE INSERÇÃO DE LOG

### Inserir log de teste via PHP
```bash
php -r "
require 'vendor/autoload.php';

echo \"🧪 Testando inserção de log...\n\n\";

try {
    \App\Services\AuditLogService::logUserAction([
        'user_id' => 1,
        'user_email' => 'teste@operebem.com',
        'action_type' => 'test_action',
        'entity_type' => 'test',
        'entity_id' => 1,
        'description' => 'Log de teste automatizado',
        'changes' => [
            'test' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    echo \"✅ Log inserido com sucesso!\n\n\";
    
    // Buscar log inserido
    \$app = \App\Core\Application::getInstance();
    \App\Core\Database::init(\$app->config('database'));
    \$pdo = \App\Core\Database::connection();
    
    \$stmt = \$pdo->query(\"
        SELECT * FROM admin_audit_logs 
        WHERE action_type = 'test_action' 
        ORDER BY created_at DESC 
        LIMIT 1
    \");
    
    \$log = \$stmt->fetch(PDO::FETCH_ASSOC);
    
    if (\$log) {
        echo \"📄 Log recuperado:\n\";
        echo \"   ID: \" . \$log['id'] . \"\n\";
        echo \"   Actor: \" . \$log['actor_type'] . \"\n\";
        echo \"   Action: \" . \$log['action_type'] . \"\n\";
        echo \"   Description: \" . \$log['description'] . \"\n\";
        echo \"   Created: \" . \$log['created_at'] . \"\n\n\";
        
        // Deletar log de teste
        \$pdo->exec(\"DELETE FROM admin_audit_logs WHERE action_type = 'test_action'\");
        echo \"✅ Log de teste removido\n\";
    }
    
} catch (Exception \$e) {
    echo \"❌ Erro: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
```

---

## 📧 TESTE DE EMAIL

### Testar envio de email
```bash
php -r "
require 'vendor/autoload.php';

echo \"📧 Testando envio de email...\n\n\";

\$emailTo = 'SEU_EMAIL@AQUI.COM';  // TROCAR PELO SEU EMAIL

if (\$emailTo === 'SEU_EMAIL@AQUI.COM') {
    echo \"⚠️  ATENÇÃO: Trocar variável \\\$emailTo pelo seu email real!\n\";
    exit(1);
}

try {
    \$emailService = new \App\Services\EmailService();
    
    \$result = \$emailService->sendAdminNewPassword(
        'Usuário Teste',
        'SenhaTeste123',
        \$emailTo
    );
    
    if (\$result) {
        echo \"✅ Email enviado com sucesso para \$emailTo\n\";
        echo \"📬 Verifique sua caixa de entrada (e spam)\n\";
    } else {
        echo \"❌ Falha ao enviar email\n\";
        echo \"   Verificar configurações SMTP no .env\n\";
    }
    
} catch (Exception \$e) {
    echo \"❌ Erro ao enviar email: \" . \$e->getMessage() . \"\n\";
    echo \"\n🔧 Dicas:\n\";
    echo \"   1. Verificar MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD no .env\n\";
    echo \"   2. Verificar se porta SMTP não está bloqueada no firewall\n\";
    echo \"   3. Testar credenciais em cliente de email\n\";
}
"
```

### Verificar configuração de email
```bash
php -r "
echo \"📧 Configuração de Email (.env):\n\n\";

\$vars = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];

\$missing = [];
foreach (\$vars as \$var) {
    \$value = getenv(\$var);
    if (empty(\$value)) {
        echo \"❌ \$var: NÃO DEFINIDO\n\";
        \$missing[] = \$var;
    } else {
        if (\$var === 'MAIL_PASSWORD') {
            echo \"✅ \$var: ******* (oculto)\n\";
        } else {
            echo \"✅ \$var: \$value\n\";
        }
    }
}

echo \"\n\";

if (empty(\$missing)) {
    echo \"✅ Todas variáveis de email configuradas\n\";
} else {
    echo \"⚠️  Variáveis faltando: \" . implode(', ', \$missing) . \"\n\";
    echo \"   Adicione essas variáveis no arquivo .env\n\";
}
"
```

---

## 🔍 QUERIES RÁPIDAS DE VALIDAÇÃO

### Ver últimos logs
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"📊 Últimos 10 logs:\n\n\";

\$stmt = \$pdo->query(\"
    SELECT 
        id,
        actor_type,
        COALESCE(admin_email, user_email) as email,
        action_type,
        LEFT(description, 50) as description,
        created_at
    FROM admin_audit_logs
    ORDER BY created_at DESC
    LIMIT 10
\");

\$logs = \$stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty(\$logs)) {
    echo \"⚠️  Nenhum log encontrado\n\";
    echo \"   Execute alguns testes para gerar logs\n\";
} else {
    printf(\"%-5s %-8s %-30s %-25s %s\n\", 'ID', 'Actor', 'Email', 'Action', 'Created');
    echo str_repeat('-', 100) . \"\n\";
    
    foreach (\$logs as \$log) {
        printf(\"%-5s %-8s %-30s %-25s %s\n\",
            \$log['id'],
            \$log['actor_type'],
            substr(\$log['email'], 0, 30),
            substr(\$log['action_type'], 0, 25),
            \$log['created_at']
        );
    }
    
    echo \"\n✅ Total de logs: \" . count(\$logs) . \"\n\";
}
"
```

### Estatísticas de logs
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"📊 Estatísticas de Logs:\n\n\";

// Total
\$total = \$pdo->query('SELECT COUNT(*) FROM admin_audit_logs')->fetchColumn();
echo \"Total de logs: \$total\n\n\";

// Por tipo
\$stmt = \$pdo->query(\"
    SELECT 
        action_type,
        COUNT(*) as total
    FROM admin_audit_logs
    GROUP BY action_type
    ORDER BY total DESC
\");

echo \"Logs por tipo de ação:\n\";
while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
    printf(\"  %-30s %d\n\", \$row['action_type'], \$row['total']);
}

// Por actor
echo \"\nLogs por tipo de ator:\n\";
\$stmt = \$pdo->query(\"
    SELECT 
        actor_type,
        COUNT(*) as total
    FROM admin_audit_logs
    GROUP BY actor_type
    ORDER BY total DESC
\");

while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
    printf(\"  %-10s %d\n\", \$row['actor_type'], \$row['total']);
}

// Últimas 24h
\$recent = \$pdo->query(\"
    SELECT COUNT(*) 
    FROM admin_audit_logs 
    WHERE created_at > NOW() - INTERVAL '24 hours'
\")->fetchColumn();

echo \"\nLogs nas últimas 24h: \$recent\n\";
"
```

---

## 🧹 LIMPEZA (APENAS PARA TESTES)

### Deletar logs de teste
```bash
php -r "
require 'vendor/autoload.php';

\$app = \App\Core\Application::getInstance();
\App\Core\Database::init(\$app->config('database'));
\$pdo = \App\Core\Database::connection();

echo \"🧹 Deletando logs de teste...\n\n\";

\$stmt = \$pdo->exec(\"
    DELETE FROM admin_audit_logs 
    WHERE action_type LIKE 'test%' 
    OR description LIKE '%teste%' 
    OR description LIKE '%test%'
\");

echo \"✅ Deletados \$stmt logs de teste\n\";
"
```

---

## 🐛 DEBUG E TROUBLESHOOTING

### Verificar sintaxe dos arquivos PHP
```bash
echo "Verificando sintaxe dos arquivos modificados..."
php -l src/Controllers/Admin/SubscriptionAdminController.php
php -l src/Controllers/AdminSecureController.php
php -l src/Controllers/ProfileController.php
php -l src/Services/AuditLogService.php
echo "✅ Verificação concluída"
```

### Ver logs de erro do PHP
```bash
# Windows
type storage\logs\php-error.log | findstr /i "error"

# Linux/Mac
tail -f storage/logs/php-error.log
```

### Verificar permissões de diretórios
```bash
# Linux/Mac
ls -la storage/logs/
ls -la public/uploads/avatars/

# Windows
dir storage\logs\
dir public\uploads\avatars\
```

### Limpar cache (se houver)
```bash
# Se o projeto tiver sistema de cache
php -r "
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo \"✅ OPcache limpo\n\";
}

if (file_exists('storage/cache')) {
    // Limpar arquivos de cache
    \$files = glob('storage/cache/*');
    foreach (\$files as \$file) {
        if (is_file(\$file)) {
            unlink(\$file);
        }
    }
    echo \"✅ Cache de arquivos limpo\n\";
}
"
```

---

## 📦 BACKUP E RESTORE

### Fazer backup da tabela antes de testar
```bash
# PostgreSQL dump apenas da tabela
pg_dump -U usuario -d banco -t admin_audit_logs > backup_audit_logs.sql

# Ou via psql
psql -U usuario -d banco -c "COPY admin_audit_logs TO 'C:/temp/audit_logs_backup.csv' CSV HEADER"
```

### Restaurar backup (se necessário)
```bash
# Restaurar de dump SQL
psql -U usuario -d banco < backup_audit_logs.sql

# Ou via CSV
psql -U usuario -d banco -c "COPY admin_audit_logs FROM 'C:/temp/audit_logs_backup.csv' CSV HEADER"
```

---

## ✅ SCRIPT COMPLETO DE VALIDAÇÃO

### Executar todos os testes de uma vez
```bash
php << 'EOF'
<?php
require 'vendor/autoload.php';

echo "🔍 VALIDAÇÃO COMPLETA DO SISTEMA DE AUDITORIA\n";
echo str_repeat('=', 60) . "\n\n";

$errors = 0;
$warnings = 0;

// Test 1: Conexão com banco
echo "1. Testando conexão com banco de dados...\n";
try {
    $app = \App\Core\Application::getInstance();
    \App\Core\Database::init($app->config('database'));
    $pdo = \App\Core\Database::connection();
    echo "   ✅ Conexão OK\n\n";
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 2: Migration executada
echo "2. Verificando migration 027...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM migrations WHERE filename = '027_create_admin_audit_logs.sql'");
if ($stmt->fetchColumn() > 0) {
    echo "   ✅ Migration registrada\n\n";
} else {
    echo "   ❌ Migration NÃO foi executada\n\n";
    $errors++;
}

// Test 3: Tabela existe
echo "3. Verificando tabela admin_audit_logs...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'admin_audit_logs'");
if ($stmt->fetchColumn() > 0) {
    echo "   ✅ Tabela existe\n\n";
} else {
    echo "   ❌ Tabela NÃO existe\n\n";
    $errors++;
}

// Test 4: Índices
echo "4. Verificando índices...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM pg_indexes WHERE tablename = 'admin_audit_logs'");
$indexCount = $stmt->fetchColumn();
echo "   ✅ $indexCount índices criados\n\n";

// Test 5: Campo trial_extended_days
echo "5. Verificando campo trial_extended_days...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'subscriptions' AND column_name = 'trial_extended_days'");
if ($stmt->fetchColumn() > 0) {
    echo "   ✅ Campo existe\n\n";
} else {
    echo "   ❌ Campo NÃO existe\n\n";
    $errors++;
}

// Test 6: Campo deleted_at
echo "6. Verificando campo deleted_at...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'coupons' AND column_name = 'deleted_at'");
if ($stmt->fetchColumn() > 0) {
    echo "   ✅ Campo existe\n\n";
} else {
    echo "   ❌ Campo NÃO existe\n\n";
    $errors++;
}

// Test 7: Configuração de email
echo "7. Verificando configuração de email...\n";
$mailVars = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
$mailOk = true;
foreach ($mailVars as $var) {
    if (empty(getenv($var))) {
        echo "   ⚠️  $var não definido\n";
        $warnings++;
        $mailOk = false;
    }
}
if ($mailOk) {
    echo "   ✅ Configuração OK\n\n";
} else {
    echo "\n";
}

// Test 8: Teste de inserção
echo "8. Testando inserção de log...\n";
try {
    \App\Services\AuditLogService::logUserAction([
        'user_id' => 1,
        'user_email' => 'validacao@test.com',
        'action_type' => 'validation_test',
        'description' => 'Teste de validação automática'
    ]);
    echo "   ✅ Inserção OK\n";
    
    // Verificar e deletar
    $pdo->exec("DELETE FROM admin_audit_logs WHERE action_type = 'validation_test'");
    echo "   ✅ Log de teste removido\n\n";
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Resumo
echo str_repeat('=', 60) . "\n";
echo "RESUMO DA VALIDAÇÃO\n";
echo str_repeat('=', 60) . "\n\n";

if ($errors === 0 && $warnings === 0) {
    echo "✅ TODOS OS TESTES PASSARAM!\n";
    echo "   Sistema de auditoria está funcionando corretamente.\n\n";
    exit(0);
} elseif ($errors === 0) {
    echo "⚠️  VALIDAÇÃO CONCLUÍDA COM AVISOS\n";
    echo "   Erros: $errors\n";
    echo "   Avisos: $warnings\n\n";
    exit(0);
} else {
    echo "❌ VALIDAÇÃO FALHOU\n";
    echo "   Erros: $errors\n";
    echo "   Avisos: $warnings\n\n";
    exit(1);
}
EOF
```

---

**Use esses comandos para executar e validar a migração rapidamente! ⚡**
