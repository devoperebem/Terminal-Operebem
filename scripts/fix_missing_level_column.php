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

    echo "✅ Conectado ao banco de dados {$database}" . PHP_EOL;

    // Verificar se coluna level existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'users' AND column_name = 'level'
        ) as exists
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists['exists']) {
        echo "✅ Coluna 'level' já existe. Nada a fazer." . PHP_EOL;
        exit(0);
    }

    echo "⚠️  Coluna 'level' não existe. Adicionando..." . PHP_EOL;

    // Adicionar coluna level
    $pdo->exec("ALTER TABLE users ADD COLUMN level INTEGER DEFAULT 1 NOT NULL");
    echo "✅ Coluna 'level' adicionada com sucesso!" . PHP_EOL;

    // Adicionar índice
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_level ON users(level DESC)");
    echo "✅ Índice criado!" . PHP_EOL;

    // Adicionar comentário
    $pdo->exec("COMMENT ON COLUMN users.level IS 'Nível calculado baseado em XP'");
    echo "✅ Comentário adicionado!" . PHP_EOL;

    // Verificar se last_xp_earned_at existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'users' AND column_name = 'last_xp_earned_at'
        ) as exists
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists['exists']) {
        echo "⚠️  Coluna 'last_xp_earned_at' não existe. Adicionando..." . PHP_EOL;
        $pdo->exec("ALTER TABLE users ADD COLUMN last_xp_earned_at TIMESTAMP NULL");
        $pdo->exec("COMMENT ON COLUMN users.last_xp_earned_at IS 'Última vez que ganhou XP (qualquer fonte)'");
        echo "✅ Coluna 'last_xp_earned_at' adicionada!" . PHP_EOL;
    }

    // Recalcular níveis de todos os usuários
    echo PHP_EOL . "🔄 Recalculando níveis de todos os usuários..." . PHP_EOL;
    $stmt = $pdo->query("SELECT id, xp FROM users WHERE deleted_at IS NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($users as $user) {
        $xp = (int)($user['xp'] ?? 0);
        $level = max(1, (int)floor(sqrt($xp / 10)));
        
        $stmt = $pdo->prepare("UPDATE users SET level = :level WHERE id = :id");
        $stmt->execute(['level' => $level, 'id' => $user['id']]);
        $updated++;
    }

    echo "✅ Níveis recalculados para {$updated} usuários!" . PHP_EOL;
    echo PHP_EOL . "✅ Correção concluída com sucesso!" . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, '❌ Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
