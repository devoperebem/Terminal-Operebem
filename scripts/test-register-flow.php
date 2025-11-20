<?php
// CLI script: Simula o fluxo completo de cadastro e remove o usuário de teste
// Uso: php scripts/test-register-flow.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;
use App\Controllers\AuthController;

// Exceção para capturar respostas JSON dos controllers sem encerrar o script
class ControllerResponse extends \Exception {
    public array $payload; public int $status;
    public function __construct(array $payload, int $status = 200) {
        parent::__construct('controller_response');
        $this->payload = $payload; $this->status = $status;
    }
}

// Subclasse para interceptar json()/redirect()
class TestAuthController extends AuthController {
    protected function json(array $data, int $statusCode = 200): void {
        throw new ControllerResponse($data, $statusCode);
    }
    protected function redirect(string $url): void {
        throw new ControllerResponse(['redirect' => $url], 302);
    }
}

function callController(TestAuthController $ctrl, string $method, array $post = []): array {
    // Preparar POST + CSRF
    $_POST = $post;
    if (!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    try {
        $ctrl->$method();
        // Se não lançou, algo errado (controller normalmente encerra com json/redirect)
        return ['success' => false, 'message' => 'No response thrown by controller'];
    } catch (ControllerResponse $res) {
        return $res->payload + ['__status' => $res->status];
    }
}

function cleanupUserByEmail(string $email): void {
    try { App\Core\Database::delete('remember_tokens', ['user_id' => 0]); } catch (\Throwable $e) {}
    try {
        $u = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($u && isset($u['id'])) {
            $uid = (int)$u['id'];
            Database::delete('remember_tokens', ['user_id' => $uid]);
            Database::delete('email_verifications', ['user_id' => $uid]);
            Database::delete('users', ['id' => $uid]);
        }
    } catch (\Throwable $e) {}
}

// Bootstrap aplicação/DB
$app = Application::getInstance();
Database::init($app->config('database'));

// Sessão
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$testId = (string)random_int(10000, 99999);
$email = "test+{$testId}@example.com"; // destinatário inexistente para evitar envio real
$cpfDigits = '12345678901'; // CPF dummy válido format-wise
$telefone = '+5511912345678';
$countryCode = '+55';
$countryIso = 'BR';
$senha = 'Aa12345678';
$codigo = '123456';

// Limpar qualquer usuário anterior com o mesmo email
cleanupUserByEmail($email);

$ctrl = new TestAuthController();

$results = [];

// Passo 1: adicionar telefone
$results['adicionarTelefone'] = callController($ctrl, 'adicionarTelefone', [
    'telefone' => $telefone,
    'country_code' => $countryCode,
    'country_iso' => $countryIso,
]);
if (!($results['adicionarTelefone']['success'] ?? false)) {
    echo json_encode(['ok' => false, 'step' => 'adicionarTelefone', 'response' => $results['adicionarTelefone']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// Simular Passo 2 (email) sem enviar email: preencher sessão diretamente
if (!isset($_SESSION['registro_temp']) || !is_array($_SESSION['registro_temp'])) { $_SESSION['registro_temp'] = []; }
$_SESSION['registro_temp']['email'] = $email;
$_SESSION['registro_temp']['verification_code'] = $codigo;
$_SESSION['registro_temp']['code_expires_at'] = time() + 1800;
$_SESSION['registro_temp']['step'] = 2;

// Passo 2 (alternativo): override-dados para não chamar API externa de CPF
$results['overrideDados'] = callController($ctrl, 'overrideDados', [
    'nome' => 'Teste Automático',
    'genero' => 'Masculino',
    'data_nascimento' => '1990-01-01',
    'aceite_termos' => '1',
]);
if (!($results['overrideDados']['success'] ?? false)) {
    echo json_encode(['ok' => false, 'step' => 'overrideDados', 'response' => $results['overrideDados']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// Completar dados obrigatórios na sessão: CPF (já que overrideDados não define)
$_SESSION['registro_temp']['cpf'] = $cpfDigits;

// Passo 2 formal: confirmar dados (aceite políticas)
$results['confirmarDados'] = callController($ctrl, 'confirmarDados', [ 'aceite_politicas' => '1' ]);
if (!($results['confirmarDados']['success'] ?? false)) {
    echo json_encode(['ok' => false, 'step' => 'confirmarDados', 'response' => $results['confirmarDados']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// Passo 3: criar senha
$results['criarSenha'] = callController($ctrl, 'criarSenha', [ 'password' => $senha, 'password_confirmation' => $senha ]);
if (!($results['criarSenha']['success'] ?? false)) {
    echo json_encode(['ok' => false, 'step' => 'criarSenha', 'response' => $results['criarSenha']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// Passo 4: verificar código
$results['verificarCodigo'] = callController($ctrl, 'verificarCodigo', [ 'codigo' => $codigo ]);
if (!($results['verificarCodigo']['success'] ?? false)) {
    echo json_encode(['ok' => false, 'step' => 'verificarCodigo', 'response' => $results['verificarCodigo']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// Verificar se o usuário foi criado e depois apagar
$user = Database::fetch('SELECT id, email FROM users WHERE email = ?', [$email]);
$created = (bool)$user;

// Limpeza
if ($created) { cleanupUserByEmail($email); }

echo json_encode([
    'ok' => true,
    'created' => $created,
    'email' => $email,
    'flow' => [
        'adicionarTelefone' => $results['adicionarTelefone'],
        'overrideDados' => $results['overrideDados'],
        'confirmarDados' => $results['confirmarDados'],
        'criarSenha' => $results['criarSenha'],
        'verificarCodigo' => $results['verificarCodigo'],
    ]
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
