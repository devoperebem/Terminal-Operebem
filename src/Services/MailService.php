<?php

namespace App\Services;

use App\Core\Application;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = Application::getInstance()->config('mail') ?? [];
    }

    private function makeMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mailerCfg = $this->config['mailers']['smtp'] ?? [];
        $fromCfg = $this->config['from'] ?? ['address' => 'noreply@example.com', 'name' => 'App'];

        // SMTP
        $mail->isSMTP();
        $mail->Host = $mailerCfg['host'] ?? 'localhost';
        $mail->Port = (int)($mailerCfg['port'] ?? 25);
        $enc = $mailerCfg['encryption'] ?? '';
        if ($enc === 'tls' || $enc === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->SMTPAuth = !empty($mailerCfg['username']);
        if ($mail->SMTPAuth) {
            $mail->Username = $mailerCfg['username'];
            $mail->Password = $mailerCfg['password'] ?? '';
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromCfg['address'] ?? 'noreply@example.com', $fromCfg['name'] ?? 'App');
        $mail->isHTML(true);
        return $mail;
    }

    public function send(string $to, string $subject, string $html, array $options = []): bool
    {
        try {
            $mail = $this->makeMailer();
            $mail->addAddress($to);

            // CC / BCC
            if (!empty($options['cc'])) {
                foreach ((array)$options['cc'] as $cc) $mail->addCC($cc);
            }
            if (!empty($options['bcc'])) {
                foreach ((array)$options['bcc'] as $bcc) $mail->addBCC($bcc);
            }

            // Reply-To
            if (!empty($options['reply_to'])) {
                $rt = $options['reply_to'];
                $mail->addReplyTo($rt['email'] ?? $rt[0] ?? '', $rt['name'] ?? ($rt[1] ?? ''));
            }

            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
            return true;
        } catch (MailException $e) {
            Application::getInstance()->logger()->error('Erro ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    public function sendSupportTicket(array $ticket): bool
    {
        $to = 'suporte@operebem.com.br';
        $subject = 'Novo ticket de suporte - ' . ($ticket['subject'] ?? 'Sem assunto');
        $html = '<h2>Novo ticket de suporte</h2>' .
            '<p><b>Nome:</b> ' . htmlspecialchars($ticket['name'] ?? '') . '</p>' .
            '<p><b>Email:</b> ' . htmlspecialchars($ticket['email'] ?? '') . '</p>' .
            '<p><b>CPF:</b> ' . htmlspecialchars($ticket['cpf'] ?? '') . '</p>' .
            '<p><b>Assunto:</b> ' . htmlspecialchars($ticket['subject'] ?? '') . '</p>' .
            '<p><b>Mensagem:</b><br>' . nl2br(htmlspecialchars($ticket['message'] ?? '')) . '</p>' .
            '<p><b>User ID:</b> ' . htmlspecialchars((string)($ticket['user_id'] ?? '')) . '</p>' .
            '<p><b>Origem:</b> ' . htmlspecialchars($ticket['origin'] ?? 'web') . '</p>';
        $options = [
            'bcc' => ['patrick@operebem.com.br', 'mateus@operebem.com.br'],
            'reply_to' => ['email' => $ticket['email'] ?? 'noreply@operebem.com.br', 'name' => $ticket['name'] ?? 'Usuário']
        ];
        return $this->send($to, $subject, $html, $options);
    }
}
