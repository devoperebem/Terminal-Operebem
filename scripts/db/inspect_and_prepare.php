<?php
// scripts/db/inspect_and_prepare.php
// CLI script: inspeciona o banco e prepara tabelas auxiliares do captcha com segurança

declare(strict_types=1);

// Carregar autoload e .env
$base = dirname(__DIR__);
$root = dirname($base);
require_once $root . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Carregar envs (priorizar .env.local)
$envFiles = [];
if (file_exists($root.'/.env.local')) { $envFiles[] = '.env.local'; }
if (file_exists($root.'/.env')) { $envFiles[] = '.env'; }
if ($envFiles) {
    Dotenv::createImmutable($root, $envFiles)->safeLoad();
}

// Helper: parse simples de .env para captar DB_*
$parseDbEnv = function(string $path): array {
    $out = [];
    if (!is_file($path)) return $out;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq+1));
        // Remover aspas simples ou duplas ao redor
        if (strlen($val) >= 2) {
            $first = $val[0];
            $last = substr($val, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $val = substr($val, 1, -1);
            }
        }
        if (strpos($key, 'DB_') === 0) {
            $out[$key] = $val;
        }
    }
    return $out;
};

// Conectar usando variáveis do .env (priorizar $_ENV/$_SERVER) com fallback de parse manual
$host = $_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? getenv('DB_HOST'));
$port = $_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? getenv('DB_PORT'));
$db   = $_ENV['DB_DATABASE'] ?? ($_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE'));
$user = $_ENV['DB_USERNAME'] ?? ($_SERVER['DB_USERNAME'] ?? getenv('DB_USERNAME'));
$pass = $_ENV['DB_PASSWORD'] ?? ($_SERVER['DB_PASSWORD'] ?? getenv('DB_PASSWORD'));

if (!$host || !$db || !$user) {
    $manual = [];
    if (file_exists($root.'/.env.local')) { $manual = array_merge($manual, $parseDbEnv($root.'/.env.local')); }
    if (file_exists($root.'/.env')) { $manual = array_merge($manual, $parseDbEnv($root.'/.env')); }
    $host = $host ?: ($manual['DB_HOST'] ?? '');
    $port = $port ?: ($manual['DB_PORT'] ?? '3306');
    $db   = $db   ?: ($manual['DB_DATABASE'] ?? '');
    $user = $user ?: ($manual['DB_USERNAME'] ?? '');
    $pass = $pass ?: ($manual['DB_PASSWORD'] ?? '');
}

if (!$host || !$db || !$user) {
    fwrite(STDERR, "[ERR] Variáveis do .env ausentes (DB_*). Verificado host='{$host}', db='{$db}', user='{$user}'.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERR] Falha na conexão: ".$e->getMessage()."\n");
    exit(2);
}

// Parâmetros
$apply = in_array('--apply', $argv, true) || in_array('-a', $argv, true);

// 1) Inventário de tabelas
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$schema = [];
foreach ($tables as $t) {
    $cols = $pdo->query('SHOW COLUMNS FROM `'.str_replace('`','',$t).'`')->fetchAll();
    $schema[$t] = $cols;
}

echo "===== Banco: {$db} @ {$host}:{$port} =====\n";
echo "Tabelas (".count($tables)."):\n";
foreach ($tables as $t) echo " - {$t}\n";
echo "\n";

// 2) Preparar tabela captcha_events
$sqlCreateCaptchaEvents = <<<SQL
CREATE TABLE IF NOT EXISTS captcha_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event VARCHAR(64) NOT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  origin VARCHAR(255) NULL,
  referer VARCHAR(255) NULL,
  payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (event), INDEX (ip), INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

if ($apply) {
    $pdo->exec($sqlCreateCaptchaEvents);
    echo "[OK] Tabela captcha_events criada/atualizada.\n";
} else {
    echo "[DRY-RUN] Criaria tabela captcha_events. Use --apply para executar.\n";
}

// 3) Opcional: habilitar logging via .env
if ($apply) {
    echo "\nPara ativar logs no BD sem scripts adicionais, adicione ao .env:\n";
    echo "  CAPTCHA_LOG_DB=true\n\n";
}

echo "Concluído.\n";
