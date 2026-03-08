<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$basePath = dirname(__DIR__);

// Load environment variables
$dotenv = Dotenv::createMutable($basePath);
$dotenv->safeLoad();

$localEnv = $basePath . '/.env.local';
if (is_file($localEnv)) {
    try {
        Dotenv::createMutable($basePath, '.env.local')->safeLoad();
    } catch (Throwable $t) {
        // ignore
    }
}

function envOrFail(string $key): string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        throw new RuntimeException("Variável de ambiente {$key} não definida");
    }
    return $value;
}

try {
    $host = envOrFail('DB_HOST');
    $port = envOrFail('DB_PORT');
    $database = envOrFail('DB_DATABASE');
    $username = envOrFail('DB_USERNAME');
    $password = envOrFail('DB_PASSWORD');

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Conectado ao banco de dados {$database}" . PHP_EOL . PHP_EOL;

    // Verificar colunas da tabela users
    echo "🔍 Verificando schema da tabela 'users'..." . PHP_EOL;
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'users'
        AND column_name IN ('xp', 'streak', 'level', 'last_xp_earned_at', 'last_login_at')
        ORDER BY column_name
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($columns)) {
        echo "❌ ERRO: Colunas de gamificação NÃO ENCONTRADAS!" . PHP_EOL;
        echo "   As migrations não foram aplicadas corretamente." . PHP_EOL;
        exit(1);
    }

    echo "✅ Colunas encontradas:" . PHP_EOL;
    foreach ($columns as $col) {
        echo "   - {$col['column_name']} ({$col['data_type']}) - Default: " . ($col['column_default'] ?? 'NULL') . PHP_EOL;
    }
    echo PHP_EOL;

    // Verificar tabela xp_history
    echo "🔍 Verificando tabela 'xp_history'..." . PHP_EOL;
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_name = 'xp_history'
        ) as exists
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists['exists']) {
        echo "❌ ERRO: Tabela 'xp_history' NÃO EXISTE!" . PHP_EOL;
        exit(1);
    }

    echo "✅ Tabela 'xp_history' existe" . PHP_EOL . PHP_EOL;

    // Verificar dados de um usuário
    echo "🔍 Verificando dados de gamificação dos usuários..." . PHP_EOL;
    $stmt = $pdo->query("
        SELECT id, name, email, xp, streak, level, last_login_at, last_xp_earned_at
        FROM users
        WHERE deleted_at IS NULL
        ORDER BY id
        LIMIT 5
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "⚠️  Nenhum usuário encontrado no banco." . PHP_EOL;
    } else {
        echo "✅ Primeiros 5 usuários:" . PHP_EOL;
        foreach ($users as $user) {
            echo sprintf(
                "   ID: %d | %s | XP: %d | Streak: %d | Level: %d | Last Login: %s" . PHP_EOL,
                $user['id'],
                $user['name'],
                $user['xp'] ?? 0,
                $user['streak'] ?? 0,
                $user['level'] ?? 1,
                $user['last_login_at'] ?? 'never'
            );
        }
    }
    echo PHP_EOL;

    // Verificar histórico de XP
    echo "🔍 Verificando histórico de XP..." . PHP_EOL;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM xp_history");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Total de transações de XP: " . $count['count'] . PHP_EOL;

    if ($count['count'] > 0) {
        $stmt = $pdo->query("
            SELECT h.*, u.name as user_name
            FROM xp_history h
            JOIN users u ON u.id = h.user_id
            ORDER BY h.created_at DESC
            LIMIT 5
        ");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Últimas 5 transações:" . PHP_EOL;
        foreach ($history as $h) {
            echo sprintf(
                "   - %s | %s | %+d XP | %s | %s" . PHP_EOL,
                $h['created_at'],
                $h['user_name'],
                $h['amount'],
                $h['source'],
                $h['description'] ?? ''
            );
        }
    }

    echo PHP_EOL . "✅ Schema de gamificação está correto!" . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, '❌ Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
