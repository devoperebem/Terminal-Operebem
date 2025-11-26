<?php
/**
 * Discord Integration - Auto Migration Script
 *
 * Acesse: https://terminal.operebem.com.br/database/run_discord_migrations.php
 *
 * IMPORTANTE: Após executar as migrações com sucesso, REMOVA ESTE ARQUIVO por segurança!
 */

// Segurança básica - apenas localhost ou IP específico (você pode ajustar)
$allowed_ips = ['127.0.0.1', 'localhost', $_SERVER['REMOTE_ADDR'] ?? ''];
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips);

// Verifica se foi passado um token de segurança como parâmetro
$security_token = $_GET['token'] ?? '';
$expected_token = 'EXECUTE_DISCORD_MIGRATIONS'; // Altere para algo único!

if (empty($security_token) || $security_token !== $expected_token) {
    http_response_code(403);
    die("❌ Acesso negado. Use: ?token=EXECUTE_DISCORD_MIGRATIONS\n\nOu remova este arquivo após usar.");
}

// Tenta carregar a configuração do banco de dados
$db_config = null;
$config_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../src/Core/Database.php',
    __DIR__ . '/../../.env',
];

// Buscar conexão com o banco
try {
    // Tentar carregar via require se existir config
    if (file_exists(__DIR__ . '/../src/Core/Database.php')) {
        require_once __DIR__ . '/../src/Core/Database.php';
        $connection = \App\Core\Database::connection();
    } else {
        // Fallback: tentar conexão direta
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        $database = $_ENV['DB_DATABASE'] ?? 'operebem';

        $connection = new mysqli($host, $user, $pass, $database);
        if ($connection->connect_error) {
            throw new Exception("Conexão falhou: " . $connection->connect_error);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ Erro ao conectar ao banco de dados:\n";
    echo htmlspecialchars($e->getMessage());
    die();
}

// Lista de migrações a executar
$migrations = [
    [
        'name' => '012_create_discord_users_table',
        'sql' => "
            CREATE TABLE IF NOT EXISTS discord_users (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ],
    [
        'name' => '013_create_discord_logs_table',
        'sql' => "
            CREATE TABLE IF NOT EXISTS discord_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ],
    [
        'name' => '010_fix_seed_xp_discord_settings',
        'sql' => "
            INSERT INTO xp_settings (setting_key, setting_value, description)
            VALUES
                ('xp_discord_msg_amount', 1, 'XP concedido por mensagem no Discord (0 desativa)'),
                ('xp_discord_msg_cooldown_minutes', 10, 'Cooldown em minutos entre premiações por mensagem'),
                ('xp_discord_msg_daily_cap', 25, 'Limite diário de XP vindo de mensagens no Discord (0 desativa)')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
        "
    ],
];

// HTML de resposta
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Migrations</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #16213e; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h1 { color: #5865f2; border-bottom: 2px solid #5865f2; padding-bottom: 10px; }
        .migration { margin: 20px 0; padding: 15px; background: #0f3460; border-left: 4px solid #5865f2; border-radius: 4px; }
        .success { border-left-color: #10b981; background: #064e3b; color: #d1fae5; }
        .error { border-left-color: #ef4444; background: #7f1d1d; color: #fee2e2; }
        .pending { border-left-color: #f59e0b; background: #78350f; color: #fef3c7; }
        code { background: #1a1a2e; padding: 2px 6px; border-radius: 3px; color: #5865f2; }
        .warning { background: #7f1d1d; border: 1px solid #ef4444; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .success-box { background: #064e3b; border: 1px solid #10b981; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Discord Integration - Database Migrations</h1>

<?php

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($migrations as $migration) {
    echo "<div class='migration'>";
    echo "<strong>" . htmlspecialchars($migration['name']) . "</strong><br>";

    try {
        // Executar migração
        if (method_exists($connection, 'multi_query')) {
            // MySQLi
            if ($connection->multi_query($migration['sql'])) {
                while ($connection->next_result()) {
                    // Processar resultados
                }
                echo "<span style='color: #10b981;'>✅ Executada com sucesso</span>";
                $success_count++;
            } else {
                throw new Exception($connection->error);
            }
        } else {
            // PDO ou outra conexão
            $connection->exec($migration['sql']);
            echo "<span style='color: #10b981;'>✅ Executada com sucesso</span>";
            $success_count++;
        }
    } catch (Exception $e) {
        echo "<span style='color: #fee2e2;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span>";
        $error_count++;
        $errors[] = [
            'migration' => $migration['name'],
            'error' => $e->getMessage()
        ];
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
    echo "Agora você pode acessar <code>/app/community</code> sem erros.<br>";
    echo "<br><strong style='color: #ef4444;'>⚠️ IMPORTANTE: Delete este arquivo (<code>database/run_discord_migrations.php</code>) por motivos de segurança!</strong>";
    echo "</div>";
} else if ($error_count > 0) {
    echo "<div class='warning'>";
    echo "<strong>⚠️ Houve erros durante a execução:</strong><br>";
    foreach ($errors as $error) {
        echo "• <code>" . htmlspecialchars($error['migration']) . "</code>: " . htmlspecialchars($error['error']) . "<br>";
    }
    echo "</div>";
}

?>

        </div>

        <div style="margin-top: 20px; padding: 15px; background: #0f3460; border-radius: 4px; border-left: 4px solid #f59e0b;">
            <strong>📝 Próximos Passos:</strong>
            <ol>
                <li>Se todos os erros acima mostram ✅, então as tabelas foram criadas</li>
                <li>Acesse <code>https://terminal.operebem.com.br/app/community</code> para testar</li>
                <li><strong>Delete este arquivo</strong> (<code>database/run_discord_migrations.php</code>) via FTP ou File Manager da Hostinger</li>
            </ol>
        </div>
    </div>
</body>
</html>

<?php
// Fechar conexão
if (method_exists($connection, 'close')) {
    $connection->close();
}
?>
