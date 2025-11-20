<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Application;
use App\Services\MailService;
use Carbon\Carbon;

class SupportController extends BaseController
{
    public function index(): void
    {
        // Apenas renderiza a página de suporte
        // Recuperar e limpar mensagem de erro da sessão (se houver)
        $errMsg = $_SESSION['support_error'] ?? '';
        unset($_SESSION['support_error']);

        // Autopreencher CPF se usuário estiver logado
        $logged = $this->authService->getCurrentUser();
        $prefillCpf = '';
        if (!empty($logged['cpf'])) {
            $prefillCpf = $logged['cpf'];
        } elseif (!empty($_GET['cpf'])) {
            $prefillCpf = (string)$_GET['cpf'];
        }

        $this->view('support/index', [
            'title' => 'Central de Suporte',
            'ok' => isset($_GET['ok']) ? (int)$_GET['ok'] : 0,
            'cpf' => $prefillCpf,
            'err' => $_GET['err'] ?? '',
            'errMsg' => $errMsg,
        ]);
    }

    public function submitTicket(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }

        $this->ensureTable();

        $trap = trim($_POST['website'] ?? '');
        if ($trap !== '') {
            Application::getInstance()->logger()->warning('Support form honeypot triggered', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
            header('Location: /support?ok=1');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', (string)($_POST['cpf'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $origin = 'web';

        $user = $this->authService->getCurrentUser();
        $userId = $user['id'] ?? null;
        // Preencher automaticamente nome/email do usuário logado se não enviados
        if ($userId) {
            if ($name === '' && !empty($user['name'])) $name = $user['name'];
            if (($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) && !empty($user['email'])) $email = $user['email'];
            if ($cpf === '' && !empty($user['cpf'])) $cpf = preg_replace('/[^0-9]/', '', (string)$user['cpf']);
        }

        $errors = [];
        if ($name === '') $errors['name'] = 'Informe seu nome';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Informe um email válido';
        $allowedCategories = ['conta','planos','dados','bugs','sugestoes','outros'];
        if ($category === '' || !in_array($category, $allowedCategories, true)) $errors['category'] = 'Selecione uma categoria';
        if ($subject === '') $errors['subject'] = 'Informe um assunto';
        if ($message === '') $errors['message'] = 'Descreva sua solicitação';
        if ($cpf !== '' && !$this->isValidCpf($cpf)) $errors['cpf'] = 'CPF inválido';
        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.', 'errors' => $errors], 422);
            } else {
                $_SESSION['support_error'] = 'Preencha todos os campos obrigatórios.';
                header('Location: /support?err=1');
                exit;
            }
        }

        try {
            $now = Carbon::now()->toDateTimeString();
            $ticketId = Database::insert('support_tickets', [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf,
                'category' => $category ?: 'outros',
                'subject' => $subject,
                'message' => $message,
                'status' => 'open',
                'origin' => $origin,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Enviar e-mails
            $mailer = new MailService();
            $mailer->sendSupportTicket([
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf,
                'category' => $category ?: 'outros',
                'subject' => $subject,
                'message' => $message,
                'user_id' => $userId,
                'origin' => $origin,
                'ticket_id' => $ticketId,
            ]);

            Application::getInstance()->logger()->info('Support ticket created', ['ticket_id' => $ticketId, 'user_id' => $userId, 'category' => $category ?: 'outros']);

            // Redirecionar para evitar reenvio
            header('Location: /support?ok=1');
            exit;
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro ao criar ticket: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Erro interno ao enviar ticket.'], 500);
            } else {
                $_SESSION['support_error'] = 'Erro interno ao enviar ticket.';
                header('Location: /support?err=1');
                exit;
            }
        }
    }

    public function adminIndex(): void
    {
        $this->ensureTable();
        $tickets = Database::fetchAll('SELECT * FROM support_tickets ORDER BY created_at DESC');
        $openTickets = array_values(array_filter($tickets, fn($t) => $t['status'] === 'open'));
        $closedTickets = array_values(array_filter($tickets, fn($t) => $t['status'] !== 'open'));

        $messagesByTicket = [];
        $all = $tickets;
        if ($all) {
            $ids = implode(',', array_map('intval', array_column($all, 'id')));
            if ($ids !== '') {
                $msgs = Database::fetchAll("SELECT * FROM support_messages WHERE ticket_id IN ($ids) ORDER BY created_at ASC");
                foreach ($msgs as $m) { $messagesByTicket[$m['ticket_id']][] = $m; }
            }
        }

        // Flash messages
        $flashOk = $_SESSION['admin_support_ok'] ?? '';
        $flashErr = $_SESSION['admin_support_error'] ?? '';
        unset($_SESSION['admin_support_ok'], $_SESSION['admin_support_error']);

        $this->view('support/admin2', [
            'title' => 'Admin - Tickets de Suporte',
            'tickets' => $tickets,
            'openTickets' => $openTickets,
            'closedTickets' => $closedTickets,
            'messages' => $messagesByTicket,
            'flashOk' => $flashOk,
            'flashErr' => $flashErr,
        ]);
    }

    private function ensureTable(): void
    {
        try {
            // Tabela de tickets - PostgreSQL syntax
            $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(255) NOT NULL,
                cpf VARCHAR(20) NULL,
                category VARCHAR(30) DEFAULT 'outros',
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'open',
                origin VARCHAR(50) DEFAULT 'web',
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL
            );";
            Database::query($sql);
            
            // Sincronizar sequence com o maior ID existente (fix para duplicate key)
            $maxId = Database::fetch("SELECT MAX(id) as max_id FROM support_tickets");
            if ($maxId && $maxId['max_id']) {
                $nextId = (int)$maxId['max_id'] + 1;
                Database::query("SELECT setval('support_tickets_id_seq', $nextId, false);");
            }
            
            // Criar índices se não existirem
            Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_user_id ON support_tickets(user_id);");
            Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_email ON support_tickets(email);");
            Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status);");
            
            // Verificar se coluna category existe (para compatibilidade com versões antigas)
            $colCheck = Database::fetch(
                "SELECT column_name FROM information_schema.columns WHERE table_name = 'support_tickets' AND column_name = 'category'"
            );
            if (!$colCheck) {
                Database::query("ALTER TABLE support_tickets ADD COLUMN category VARCHAR(30) DEFAULT 'outros'");
            }

            // Tabela de mensagens dos tickets - PostgreSQL syntax
            $sqlMsg = "CREATE TABLE IF NOT EXISTS support_messages (
                id SERIAL PRIMARY KEY,
                ticket_id INTEGER NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_id INTEGER NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL
            );";
            Database::query($sqlMsg);
            
            // Sincronizar sequence de support_messages
            $maxMsgId = Database::fetch("SELECT MAX(id) as max_id FROM support_messages");
            if ($maxMsgId && $maxMsgId['max_id']) {
                $nextMsgId = (int)$maxMsgId['max_id'] + 1;
                Database::query("SELECT setval('support_messages_id_seq', $nextMsgId, false);");
            }
            
            // Criar índice para ticket_id
            Database::query("CREATE INDEX IF NOT EXISTS idx_support_messages_ticket_id ON support_messages(ticket_id);");
            
            // Adicionar constraint de validação para status (similar ao ENUM do MySQL)
            Database::query(
                "DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'support_tickets_status_check'
                    ) THEN
                        ALTER TABLE support_tickets ADD CONSTRAINT support_tickets_status_check 
                        CHECK (status IN ('open', 'closed'));
                    END IF;
                END $$;"
            );
            
            // Adicionar constraint de validação para sender_type
            Database::query(
                "DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'support_messages_sender_type_check'
                    ) THEN
                        ALTER TABLE support_messages ADD CONSTRAINT support_messages_sender_type_check 
                        CHECK (sender_type IN ('user', 'admin', 'system'));
                    END IF;
                END $$;"
            );
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro ao criar tabelas de suporte: ' . $e->getMessage());
            throw $e;
        }
    }

    // Lista os tickets do usuário logado e permite responder
    public function myTickets(): void
    {
        $this->ensureTable();
        $user = $this->authService->getCurrentUser();
        if (!$user) { $this->redirect('/'); }
        $userId = (int)$user['id'];
        $tickets = Database::fetchAll('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
        $messagesByTicket = [];
        if ($tickets) {
            $ids = implode(',', array_map('intval', array_column($tickets, 'id')));
            $msgs = Database::fetchAll("SELECT * FROM support_messages WHERE ticket_id IN ($ids) ORDER BY created_at ASC");
            foreach ($msgs as $m) { $messagesByTicket[$m['ticket_id']][] = $m; }
        }
        $flashOk = $_SESSION['support_ok'] ?? 0;
        unset($_SESSION['support_ok']);
        $this->view('support/my', [
            'title' => 'Meus Tickets',
            'tickets' => $tickets,
            'messages' => $messagesByTicket,
            'ok' => $flashOk,
        ]);
    }

    // Usuário responde a um ticket próprio
    public function userReply(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }
        $this->ensureTable();
        $user = $this->authService->getCurrentUser();
        if (!$user) { $this->json(['success' => false, 'message' => 'Não autenticado'], 401); }
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($ticketId <= 0 || $message === '') {
            $this->json(['success' => false, 'message' => 'Mensagem obrigatória'], 422);
        }
        $ticket = Database::fetch('SELECT * FROM support_tickets WHERE id = ? AND user_id = ?', [$ticketId, (int)$user['id']]);
        if (!$ticket) { $this->json(['success' => false, 'message' => 'Ticket não encontrado'], 404); }
        $now = Carbon::now()->toDateTimeString();
        Database::insert('support_messages', [
            'ticket_id' => $ticketId,
            'sender_type' => 'user',
            'sender_id' => (int)$user['id'],
            'message' => $message,
            'created_at' => $now,
        ]);
        Database::update('support_tickets', ['updated_at' => $now], ['id' => $ticketId]);
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $_SESSION['support_ok'] = 1;
            $this->redirect('/app/support#ticket-' . $ticketId);
        }
    }

    // Admin cria ticket para usuário específico ou contato avulso
    public function adminCreate(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }
        $this->ensureTable();
        $userId = (int)($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', (string)($_POST['cpf'] ?? ''));
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($subject === '' || $message === '') {
            $this->json(['success' => false, 'message' => 'Assunto e mensagem são obrigatórios'], 422);
        }
        if ($userId > 0) {
            $u = Database::fetch('SELECT id, name, email, cpf FROM users WHERE id = ?', [$userId]);
            if ($u) { $name = $name ?: $u['name']; $email = $email ?: $u['email']; $cpf = $cpf ?: ($u['cpf'] ?? ''); }
        }
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Nome e e-mail válidos são obrigatórios'], 422);
        }
        $now = Carbon::now()->toDateTimeString();
        $ticketId = Database::insert('support_tickets', [
            'user_id' => $userId ?: null,
            'name' => $name,
            'email' => $email,
            'cpf' => $cpf,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'origin' => 'admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Database::insert('support_messages', [
            'ticket_id' => $ticketId,
            'sender_type' => 'admin',
            'sender_id' => $_SESSION['admin_id'] ?? null,
            'message' => $message,
            'created_at' => $now,
        ]);
        // Enviar e-mail de notificação igual ao público
        $mailer = new MailService();
        $mailer->sendSupportTicket([
            'name' => $name,
            'email' => $email,
            'cpf' => $cpf,
            'subject' => $subject,
            'message' => $message,
            'user_id' => $userId ?: null,
            'origin' => 'admin',
            'ticket_id' => $ticketId,
        ]);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'ticket_id' => $ticketId]);
        } else {
            $_SESSION['admin_support_ok'] = 'Ticket #' . $ticketId . ' criado com sucesso';
            $this->redirect('/secure/adm/tickets#ticket-' . $ticketId);
        }
    }

    // Admin responde a um ticket existente
    public function adminReply(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }
        $this->ensureTable();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($ticketId <= 0 || $message === '') {
            $this->json(['success' => false, 'message' => 'Mensagem obrigatória'], 422);
        }
        $ticket = Database::fetch('SELECT id FROM support_tickets WHERE id = ?', [$ticketId]);
        if (!$ticket) { $this->json(['success' => false, 'message' => 'Ticket não encontrado'], 404); }
        $now = Carbon::now()->toDateTimeString();
        Database::insert('support_messages', [
            'ticket_id' => $ticketId,
            'sender_type' => 'admin',
            'sender_id' => $_SESSION['admin_id'] ?? null,
            'message' => $message,
            'created_at' => $now,
        ]);
        Database::update('support_tickets', ['updated_at' => $now], ['id' => $ticketId]);
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $_SESSION['admin_support_ok'] = 'Resposta enviada no ticket #' . $ticketId;
            $this->redirect('/secure/adm/tickets#ticket-' . $ticketId);
        }
    }

    // Admin fecha um ticket
    public function adminClose(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }
        $this->ensureTable();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        if ($ticketId <= 0) { $this->json(['success' => false, 'message' => 'Ticket inválido'], 422); }
        $now = Carbon::now()->toDateTimeString();
        Database::update('support_tickets', ['status' => 'closed', 'updated_at' => $now], ['id' => $ticketId]);
        Database::insert('support_messages', [
            'ticket_id' => $ticketId,
            'sender_type' => 'system',
            'sender_id' => null,
            'message' => 'Ticket encerrado pelo administrador.',
            'created_at' => $now,
        ]);
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $_SESSION['admin_support_ok'] = 'Ticket #' . $ticketId . ' encerrado';
            $this->redirect('/secure/adm/tickets#ticket-' . $ticketId);
        }
    }

    // Admin reabre um ticket
    public function adminReopen(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF inválido'], 422);
        }
        $this->ensureTable();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        if ($ticketId <= 0) { $this->json(['success' => false, 'message' => 'Ticket inválido'], 422); }
        $now = Carbon::now()->toDateTimeString();
        Database::update('support_tickets', ['status' => 'open', 'updated_at' => $now], ['id' => $ticketId]);
        Database::insert('support_messages', [
            'ticket_id' => $ticketId,
            'sender_type' => 'system',
            'sender_id' => null,
            'message' => 'Ticket reaberto pelo administrador.',
            'created_at' => $now,
        ]);
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $_SESSION['admin_support_ok'] = 'Ticket #' . $ticketId . ' reaberto';
            $this->redirect('/secure/adm/tickets#ticket-' . $ticketId);
        }
    }

    private function isAjax(): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        $sum = 0;
        for ($i = 0, $w = 10; $i < 9; $i++, $w--) { $sum += (int)$cpf[$i] * $w; }
        $r = $sum % 11; $d1 = ($r < 2) ? 0 : 11 - $r;
        if ((int)$cpf[9] !== $d1) return false;
        $sum = 0;
        for ($i = 0, $w = 11; $i < 10; $i++, $w--) { $sum += (int)$cpf[$i] * $w; }
        $r = $sum % 11; $d2 = ($r < 2) ? 0 : 11 - $r;
        return (int)$cpf[10] === $d2;
    }
}
