<?php
/**
 * Discord Integration - Auto Migration Script
 *
 * Acesse: https://terminal.operebem.com.br/run_migrations.php?token=discord123
 *
 * IMPORTANTE: Delete este arquivo após usar!
 */

// Token simples (altere se necessário)
$valid_token = 'discord123';
$provided_token = $_GET['token'] ?? $_POST['token'] ?? '';

// Mostrar formulário se não tiver token
if (empty($provided_token)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discord Migrations</title>
        <style>
            body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
            .container { max-width: 600px; margin: 50px auto; background: #16213e; padding: 30px; border-radius: 8px; }
            h1 { color: #5865f2; }
            form { margin: 20px 0; }
            input { padding: 10px; width: 100%; margin: 10px 0; border: 1px solid #5865f2; border-radius: 4px; background: #0f3460; color: #eee; }
            button { background: #5865f2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
            button:hover { background: #4752c4; }
            .warning { background: #7f1d1d; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🗄️ Execute as Migrações Discord</h1>
            <p>Para executar as migrações, envie o token de segurança:</p>

            <form method="GET">
                <input type="text" name="token" placeholder="Digite o token" required>
                <button type="submit">Executar Migrações</button>
            </form>

            <div class="warning">
                <strong>⚠️ Aviso:</strong> Após executar as migrações com sucesso, delete este arquivo (<code>run_migrations.php</code>) por segurança!
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Validar token
if ($provided_token !== $valid_token) {
    http_response_code(403);
    die("❌ Token inválido!");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Migrations - Executando</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #16213e; border-radius: 8px; padding: 30px; }
        h1 { color: #5865f2; border-bottom: 2px solid #5865f2; padding-bottom: 10px; }
        .migration { margin: 20px 0; padding: 15px; background: #0f3460; border-left: 4px solid #5865f2; border-radius: 4px; }
        .success { border-left-color: #10b981; background: #064e3b; }
        .error { border-left-color: #ef4444; background: #7f1d1d; }
        code { background: #1a1a2e; padding: 2px 6px; border-radius: 3px; color: #5865f2; }
        .success-box { background: #064e3b; border: 1px solid #10b981; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .warning { background: #7f1d1d; border: 1px solid #ef4444; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Executando Migrações Discord</h1>
        <p style="color: #5865f2; margin: 10px 0;"><strong>Banco de dados detectado:</strong> <code><?php echo htmlspecialchars($db_type); ?></code></p>

<?php

$success_count = 0;
$error_count = 0;
$errors = [];

// Tentar carregar via Application ou Database class
$connection = null;
try {
    require_once __DIR__ . '/src/Core/Application.php';
    $app = \App\Core\Application::getInstance();
    $connection = \App\Core\Database::connection();
} catch (Exception $e1) {
    // Se falhar, tentar conexão direta via .env
    try {
        $env_file = __DIR__ . '/.env';
        if (file_exists($env_file)) {
            $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $env_vars = [];
            foreach ($env_lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    [$key, $value] = explode('=', $line, 2);
                    $env_vars[trim($key)] = trim($value, '\'"');
                }
            }
            $_ENV = array_merge($_ENV, $env_vars);
        }

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        $db = $_ENV['DB_DATABASE'] ?? 'operebem';
        $port = $_ENV['DB_PORT'] ?? 3306;

        $connection = new mysqli($host, $user, $pass, $db, $port);
        if ($connection->connect_error) {
            throw new Exception("MySQLi: " . $connection->connect_error);
        }
        $connection->set_charset("utf8mb4");
    } catch (Exception $e2) {
        echo "<div class='error'><strong>❌ Erro ao conectar ao banco:</strong><br>";
        echo "Tentativa 1: " . htmlspecialchars($e1->getMessage()) . "<br>";
        echo "Tentativa 2: " . htmlspecialchars($e2->getMessage()) . "<br>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
}

// Detectar o tipo de banco de dados
$db_driver = null;
$db_type = 'unknown';

// Tentar detectar através da classe Database
try {
    if (method_exists($connection, 'getAttribute')) {
        $driver_name = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (strpos($driver_name, 'pgsql') !== false) {
            $db_driver = 'pgsql';
            $db_type = 'PostgreSQL';
        } elseif (strpos($driver_name, 'mysql') !== false) {
            $db_driver = 'mysql';
            $db_type = 'MySQL';
        }
    }
} catch (Exception $e) {
    // Fallback: tentar by hostname or config
    $db_driver = 'pgsql'; // Padrão para Hostinger é PostgreSQL
    $db_type = 'PostgreSQL (assumido)';
}

// Migrações a executar - versão PostgreSQL
if ($db_driver === 'pgsql' || $db_driver === null) {
    $migrations = [
        [
            'name' => 'discord_users (PostgreSQL)',
            'sql' => "CREATE TABLE IF NOT EXISTS discord_users (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL UNIQUE,
                discord_id VARCHAR(255) NULL,
                discord_username VARCHAR(255) NULL,
                discord_avatar VARCHAR(500) NULL,
                verification_code VARCHAR(32) UNIQUE NOT NULL,
                is_verified BOOLEAN DEFAULT FALSE,
                verified_at TIMESTAMP NULL,
                last_sync_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_discord_id ON discord_users(discord_id);
            CREATE INDEX IF NOT EXISTS idx_user_id ON discord_users(user_id);
            CREATE INDEX IF NOT EXISTS idx_is_verified ON discord_users(is_verified);
            CREATE INDEX IF NOT EXISTS idx_verification_code ON discord_users(verification_code);"
        ],
        [
            'name' => 'discord_logs (PostgreSQL)',
            'sql' => "CREATE TABLE IF NOT EXISTS discord_logs (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                action VARCHAR(50) NOT NULL,
                details JSONB NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_user_id_logs ON discord_logs(user_id);
            CREATE INDEX IF NOT EXISTS idx_action ON discord_logs(action);
            CREATE INDEX IF NOT EXISTS idx_created_at ON discord_logs(created_at);"
        ],
        [
            'name' => 'xp_discord_settings (PostgreSQL)',
            'sql' => "INSERT INTO xp_settings (setting_key, setting_value, description)
                VALUES
                    ('xp_discord_msg_amount', '1', 'XP concedido por mensagem no Discord (0 desativa)'),
                    ('xp_discord_msg_cooldown_minutes', '10', 'Cooldown em minutos entre premiações por mensagem'),
                    ('xp_discord_msg_daily_cap', '25', 'Limite diário de XP vindo de mensagens no Discord (0 desativa)')
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value;"
        ],
    ];
} else {
    // Migrações MySQL
    $migrations = [
        [
            'name' => 'discord_users (MySQL)',
            'sql' => "CREATE TABLE IF NOT EXISTS discord_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                discord_id VARCHAR(255) NULL,
                discord_username VARCHAR(255) NULL,
                discord_avatar VARCHAR(500) NULL,
                verification_code VARCHAR(32) UNIQUE NOT NULL,
                is_verified BOOLEAN DEFAULT FALSE,
                verified_at TIMESTAMP NULL,
                last_sync_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_discord_id (discord_id),
                INDEX idx_user_id (user_id),
                INDEX idx_is_verified (is_verified),
                INDEX idx_verification_code (verification_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ],
        [
            'name' => 'discord_logs (MySQL)',
            'sql' => "CREATE TABLE IF NOT EXISTS discord_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ],
        [
            'name' => 'xp_discord_settings (MySQL)',
            'sql' => "INSERT INTO xp_settings (setting_key, setting_value, description)
                VALUES
                    ('xp_discord_msg_amount', '1', 'XP concedido por mensagem no Discord (0 desativa)'),
                    ('xp_discord_msg_cooldown_minutes', '10', 'Cooldown em minutos entre premiações por mensagem'),
                    ('xp_discord_msg_daily_cap', '25', 'Limite diário de XP vindo de mensagens no Discord (0 desativa)')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        ],
    ];
}

// Executar migrações
foreach ($migrations as $migration) {
    echo "<div class='migration'>";
    echo "<strong>" . htmlspecialchars($migration['name']) . "</strong><br>";

    try {
        if (method_exists($connection, 'multi_query')) {
            // MySQLi
            if ($connection->multi_query($migration['sql'])) {
                while ($connection->next_result()) {}
                echo "<span style='color: #10b981;'>✅ OK</span>";
                $success_count++;
            } else {
                throw new Exception($connection->error);
            }
        } else {
            // PDO
            $connection->exec($migration['sql']);
            echo "<span style='color: #10b981;'>✅ OK</span>";
            $success_count++;
        }
    } catch (Exception $e) {
        echo "<span style='color: #fee2e2;'>❌ " . htmlspecialchars($e->getMessage()) . "</span>";
        $error_count++;
        $errors[] = ['name' => $migration['name'], 'error' => $e->getMessage()];
    }
    echo "</div>";
}

?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #5865f2;">
            <h2>📊 Resultado</h2>
            <p><strong>✅ Sucesso:</strong> <?php echo $success_count; ?></p>
            <p><strong>❌ Erros:</strong> <?php echo $error_count; ?></p>

<?php

if ($error_count === 0 && $success_count > 0) {
    echo "<div class='success-box'>";
    echo "<strong>🎉 Todas as migrações foram executadas com sucesso!</strong><br>";
    echo "Agora teste em: <code>https://terminal.operebem.com.br/app/community</code><br>";
    echo "<br><strong style='color: #ef4444;'>⚠️ DELETE este arquivo por segurança!</strong>";
    echo "</div>";
} else if ($error_count > 0) {
    echo "<div class='warning'>";
    echo "<strong>⚠️ Alguns erros ocorreram:</strong><br>";
    foreach ($errors as $error) {
        echo "• <code>" . htmlspecialchars($error['name']) . "</code>: " . htmlspecialchars($error['error']) . "<br>";
    }
    echo "</div>";
}

?>

        </div>
    </div>
</body>
</html>

<?php
if (method_exists($connection, 'close')) {
    $connection->close();
}
?>
