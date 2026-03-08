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

    // Buscar usuário ID 4 (Mateus)
    $userId = 4;
    
    echo "🔍 Buscando dados do usuário ID {$userId}..." . PHP_EOL;
    $stmt = $pdo->prepare("
        SELECT id, name, email, xp, streak, level, last_login_at, last_xp_earned_at, created_at
        FROM users
        WHERE id = :id AND deleted_at IS NULL
    ");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ Usuário não encontrado!" . PHP_EOL;
        exit(1);
    }

    echo "✅ Dados do usuário:" . PHP_EOL;
    echo "   ID: {$user['id']}" . PHP_EOL;
    echo "   Nome: {$user['name']}" . PHP_EOL;
    echo "   Email: {$user['email']}" . PHP_EOL;
    echo "   XP: {$user['xp']}" . PHP_EOL;
    echo "   Streak: {$user['streak']}" . PHP_EOL;
    echo "   Level: {$user['level']}" . PHP_EOL;
    echo "   Last Login: " . ($user['last_login_at'] ?? 'never') . PHP_EOL;
    echo "   Last XP Earned: " . ($user['last_xp_earned_at'] ?? 'never') . PHP_EOL;
    echo "   Membro desde: {$user['created_at']}" . PHP_EOL;
    echo PHP_EOL;

    // Buscar histórico de XP
    echo "🔍 Histórico de XP do usuário..." . PHP_EOL;
    $stmt = $pdo->prepare("
        SELECT id, amount, source, source_id, description, created_at
        FROM xp_history
        WHERE user_id = :id
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute(['id' => $userId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($history)) {
        echo "⚠️  Nenhum histórico de XP encontrado." . PHP_EOL;
    } else {
        echo "✅ Últimas 10 transações:" . PHP_EOL;
        foreach ($history as $h) {
            echo sprintf(
                "   [%s] %+d XP | %s | %s" . PHP_EOL,
                $h['created_at'],
                $h['amount'],
                $h['source'],
                $h['description'] ?? ''
            );
        }
    }
    echo PHP_EOL;

    // Testar query do AuthService
    echo "🔍 Testando query do AuthService (SELECT *)..." . PHP_EOL;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute(['id' => $userId]);
    $userFull = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userFull) {
        $hasXP = array_key_exists('xp', $userFull);
        $hasStreak = array_key_exists('streak', $userFull);
        $hasLevel = array_key_exists('level', $userFull);
        
        echo "✅ Query retornou " . count($userFull) . " colunas" . PHP_EOL;
        echo "   - Coluna 'xp': " . ($hasXP ? "✅ SIM (valor: {$userFull['xp']})" : "❌ NÃO") . PHP_EOL;
        echo "   - Coluna 'streak': " . ($hasStreak ? "✅ SIM (valor: {$userFull['streak']})" : "❌ NÃO") . PHP_EOL;
        echo "   - Coluna 'level': " . ($hasLevel ? "✅ SIM (valor: {$userFull['level']})" : "❌ NÃO") . PHP_EOL;
    }

    echo PHP_EOL . "✅ Debug concluído!" . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, '❌ Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
