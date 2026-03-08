<?php

namespace App\Services;

use App\Core\Application;

class EmailService
{
    private function isConfigured(): bool
    {
        $host = trim((string)($_ENV['MAIL_HOST'] ?? ''));
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        return $host !== '' && $from !== '';
    }

    private function phpmailerAvailable(): bool
    {
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    private function sendMail(string $to, string $subject, string $html, string $text, string $toName = ''): bool
    {
        try {
            if (!$this->phpmailerAvailable()) {
                try { Application::getInstance()->logger()->error('Email send failed: PHPMailer not available'); } catch (\Throwable $__) {}
                return false;
            }
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string)($_ENV['MAIL_HOST'] ?? '');
            $mail->Port = (int)($_ENV['MAIL_PORT'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string)($_ENV['MAIL_USERNAME'] ?? '');
            $mail->Password = (string)($_ENV['MAIL_PASSWORD'] ?? '');
            $sec = strtolower((string)($_ENV['MAIL_ENCRYPTION'] ?? 'tls'));
            if ($sec === 'ssl' || $sec === 'smtps') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $from = (string)($_ENV['MAIL_FROM'] ?? '');
            $fromName = (string)($_ENV['MAIL_FROM_NAME'] ?? 'OpereBem');
            if ($from) { $mail->setFrom($from, $fromName); }
            $mail->addAddress($to, ($toName !== '' ? $toName : $to));
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
            return true;
        } catch (\Throwable $t) {
            try { Application::getInstance()->logger()->error('Email send failed (PHPMailer): '.$t->getMessage()); } catch (\Throwable $__) {}
            return false;
        }
    }

    public function sendVerificationCode(string $toName, string $code, string $toEmail, string $context = 'admin_2fa'): bool
    {
        // Compatibility: some callers pass (email, code, name). Detect and swap.
        if (filter_var($toName, FILTER_VALIDATE_EMAIL) && !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $tmp = $toEmail; $toEmail = $toName; $toName = $tmp ?: 'Usuário';
        }

        $context = strtolower(trim($context));
        $baseUrl = (string)(Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $footer = '&copy; ' . date('Y') . ' OpereBem';

        if (in_array($context, ['user_register', 'user_signup', 'cadastro'], true)) {
            $subject = 'Confirme seu cadastro - Terminal Operebem';
            $text = "Olá $toName,\n\nUse o código $code para confirmar seu cadastro no Terminal Operebem. Ele expira em 30 minutos.\n\nSe você não iniciou este cadastro, ignore este email.";
            $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
              . '<body style="margin:0;padding:0;background:#f6f8fb;">'
              . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f6f8fb;">'
              . '  <tr><td align="center" style="padding:24px;">'
              . '    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);overflow:hidden;">'
              . '      <tr><td style="padding:24px 24px 8px 24px; text-align:center;">'
              . '        <img src="'.$baseUrl.'/assets/images/favicon.png" alt="OpereBem" width="40" height="40" style="border-radius:8px;display:block;margin:0 auto 12px;" />'
              . '        <div style="font:600 18px/1.2 \'Inter\', Arial, sans-serif;color:#111827;">Terminal Operebem</div>'
              . '        <div style="font:400 14px/1.4 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:4px;">Confirmação de cadastro</div>'
              . '      </td></tr>'
              . '      <tr><td style="padding:0 24px 8px 24px;"><hr style="border:none;height:1px;background:#eef2f7;"></td></tr>'
              . '      <tr><td style="padding:0 24px 24px 24px;">'
              . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;">Olá <strong>'.$safeName.'</strong>,</div>'
              . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;margin-top:8px;">Use o código abaixo para concluir seu cadastro. Ele expira em <strong>30 minutos</strong>.</div>'
              . '        <div style="margin:16px 0 20px; text-align:center;">'
              . '          <div style="display:inline-block;background:#0b1220;color:#ffffff;border-radius:10px;padding:14px 20px;font:700 28px/1.2 \'SFMono-Regular\', Menlo, Consolas, \'Liberation Mono\', monospace;letter-spacing:4px;">'
              .              $safeCode
              . '          </div>'
              . '        </div>'
              . '        <div style="font:400 12px/1.6 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:12px;">Se você não iniciou este cadastro, ignore este email.</div>'
              . '      </td></tr>'
              . '    </table>'
              . '    <div style="font:400 11px/1.4 \'Inter\', Arial, sans-serif;color:#9ca3af;margin-top:12px;">'.$footer.'</div>'
              . '  </td></tr>'
              . '</table>'
              . '</body></html>';
        } else {
            $subject = 'Seu código 2FA (Secure Admin)';
            $text = "Olá $toName,\n\nSeu código de verificação é: $code\nEle expira em 5 minutos.\n\nSe você não solicitou, ignore este email.";
            $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
              . '<body style="margin:0;padding:0;background:#f6f8fb;">'
              . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f6f8fb;">'
              . '  <tr><td align="center" style="padding:24px;">'
              . '    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);overflow:hidden;">'
              . '      <tr><td style="padding:24px 24px 8px 24px; text-align:center;">'
              . '        <img src="'.$baseUrl.'/assets/images/favicon.png" alt="OpereBem" width="40" height="40" style="border-radius:8px;display:block;margin:0 auto 12px;" />'
              . '        <div style="font:600 18px/1.2 \'Inter\', Arial, sans-serif;color:#111827;">Secure Admin</div>'
              . '        <div style="font:400 14px/1.4 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:4px;">Código de verificação (2FA)</div>'
              . '      </td></tr>'
              . '      <tr><td style="padding:0 24px 8px 24px;"><hr style="border:none;height:1px;background:#eef2f7;"></td></tr>'
              . '      <tr><td style="padding:0 24px 24px 24px;">'
              . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;">Olá <strong>'.$safeName.'</strong>,</div>'
              . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;margin-top:8px;">Use o código abaixo para concluir seu login. Ele expira em <strong>5 minutos</strong>.</div>'
              . '        <div style="margin:16px 0 20px; text-align:center;">'
              . '          <div style="display:inline-block;background:#0b1220;color:#ffffff;border-radius:10px;padding:14px 20px;font:700 28px/1.2 \'SFMono-Regular\', Menlo, Consolas, \'Liberation Mono\', monospace;letter-spacing:4px;">'
              .              $safeCode
              . '          </div>'
              . '        </div>'
              . '        <div style="font:400 12px/1.6 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:12px;">Se não foi você, ignore este email.</div>'
              . '      </td></tr>'
              . '    </table>'
              . '    <div style="font:400 11px/1.4 \'Inter\', Arial, sans-serif;color:#9ca3af;margin-top:12px;">'.$footer.'</div>'
              . '  </td></tr>'
              . '</table>'
              . '</body></html>';
        }

        try {
            $ms = new MailService();
            if ($ms->send($toEmail, $subject, $html)) { return true; }
        } catch (\Throwable $__) { /* fallback abaixo */ }
        return $this->sendMail($toEmail, $subject, $html, $text, $toName);
    }

    public function sendAdminNewPassword(string $username, string $newPassword, string $toEmail): bool
    {
        $subject = 'Sua nova senha (Secure Admin)';
        $text = "Olá $username,\n\nSua nova senha temporária é:\n$newPassword\n\nPor segurança, altere-a após o primeiro login.";
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        try {
            $ms = new MailService();
            if ($ms->send($toEmail, $subject, $html)) { return true; }
        } catch (\Throwable $__) { /* fallback abaixo */ }
        return $this->sendMail($toEmail, $subject, $html, $text);
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        $subject = 'Redefinição de Senha - Terminal Operebem';
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $footer = '&copy; ' . date('Y') . ' OpereBem';
        $text = "Olá $toName,\n\nRecebemos um pedido para redefinir sua senha no Terminal Operebem. Se foi você, acesse o link abaixo para continuar:\n$resetUrl\n\nSe você não solicitou, ignore este email.";
        $baseUrl = (string)(Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
          . '<body style="margin:0;padding:0;background:#f6f8fb;">'
          . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f6f8fb;">'
          . '  <tr><td align="center" style="padding:24px;">'
          . '    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);overflow:hidden;">'
          . '      <tr><td style="padding:24px 24px 8px 24px; text-align:center;">'
          . '        <img src="'.$baseUrl.'/assets/images/favicon.png" alt="OpereBem" width="40" height="40" style="border-radius:8px;display:block;margin:0 auto 12px;" />'
          . '        <div style="font:600 18px/1.2 \'Inter\', Arial, sans-serif;color:#111827;">Recuperação de Senha</div>'
          . '        <div style="font:400 14px/1.4 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:4px;">Terminal Operebem</div>'
          . '      </td></tr>'
          . '      <tr><td style="padding:0 24px 8px 24px;"><hr style="border:none;height:1px;background:#eef2f7;"></td></tr>'
          . '      <tr><td style="padding:0 24px 24px 24px;">'
          . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;">Olá <strong>'.$safeName.'</strong>,</div>'
          . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;margin-top:8px;">Recebemos um pedido para redefinir sua senha. Se foi você, utilize o link abaixo para continuar:</div>'
          . '        <div style="margin:14px 0 18px;">'
          . '          <a href="'.$safeUrl.'" style="color:#2563eb;text-decoration:underline;word-break:break-all;">'.$safeUrl.'</a>'
          . '        </div>'
          . '        <div style="font:400 12px/1.6 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:12px;">Se você não solicitou, ignore este email.</div>'
          . '      </td></tr>'
          . '    </table>'
          . '    <div style="font:400 11px/1.4 \'Inter\', Arial, sans-serif;color:#9ca3af;margin-top:12px;">'.$footer.'</div>'
          . '  </td></tr>'
          . '</table>'
          . '</body></html>';
        try {
            $ms = new MailService();
            if ($ms->send($toEmail, $subject, $html)) { return true; }
        } catch (\Throwable $__) { /* fallback abaixo */ }
        return $this->sendMail($toEmail, $subject, $html, $text, $toName);
    }
    public function sendPaymentFailedEmail(string $toEmail, string $toName, string $planName, string $amount): bool
    {
        $subject = 'Falha no pagamento da assinatura - Terminal Operebem';
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safePlan = htmlspecialchars($planName, ENT_QUOTES, 'UTF-8');
        $baseUrl = (string)(Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $text = "Olá $toName,\n\nHouve uma falha ao processar o pagamento da sua assinatura ($planName - $amount). Por favor, verifique seus dados de pagamento para evitar interrupção do serviço.";
        
        $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
          . '<body style="margin:0;padding:0;background:#f6f8fb;">'
          . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f6f8fb;">'
          . '  <tr><td align="center" style="padding:24px;">'
          . '    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);overflow:hidden;">'
          . '      <tr><td style="padding:24px 24px 8px 24px; text-align:center;">'
          . '        <img src="'.$baseUrl.'/assets/images/favicon.png" alt="OpereBem" width="40" height="40" style="border-radius:8px;display:block;margin:0 auto 12px;" />'
          . '        <div style="font:600 18px/1.2 \'Inter\', Arial, sans-serif;color:#dc2626;">Falha no Pagamento</div>'
          . '        <div style="font:400 14px/1.4 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:4px;">Terminal Operebem</div>'
          . '      </td></tr>'
          . '      <tr><td style="padding:0 24px 8px 24px;"><hr style="border:none;height:1px;background:#eef2f7;"></td></tr>'
          . '      <tr><td style="padding:0 24px 24px 24px;">'
          . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;">Olá <strong>'.$safeName.'</strong>,</div>'
          . '        <div style="font:400 14px/1.6 \'Inter\', Arial, sans-serif;color:#374151;margin-top:8px;">Tentamos processar o pagamento da sua assinatura <strong>'.$safePlan.'</strong> no valor de <strong>'.$amount.'</strong>, mas ocorreu uma falha.</div>'
          . '        <div style="margin:20px 0; text-align:center;">'
          . '          <a href="'.$baseUrl.'/app/profile" style="display:inline-block;background:#dc2626;color:#ffffff;border-radius:6px;padding:12px 20px;font-weight:600;text-decoration:none;">Atualizar Pagamento</a>'
          . '        </div>'
          . '        <div style="font:400 12px/1.6 \'Inter\', Arial, sans-serif;color:#6b7280;margin-top:12px;">Se você já atualizou seus dados, ignore este email. Faremos uma nova tentativa em breve.</div>'
          . '      </td></tr>'
          . '    </table>'
          . '    <div style="font:400 11px/1.4 \'Inter\', Arial, sans-serif;color:#9ca3af;margin-top:12px;">&copy; '.date('Y').' OpereBem</div>'
          . '  </td></tr>'
          . '</table>'
          . '</body></html>';

        try {
            $ms = new MailService(); // Tentar usar o MailService legado se existir
            if ($ms->send($toEmail, $subject, $html)) { return true; }
        } catch (\Throwable $__) { }
        
        return $this->sendMail($toEmail, $subject, $html, $text, $toName);
    }
}
