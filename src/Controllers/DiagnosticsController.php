<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\EmailService;

class DiagnosticsController extends BaseController
{
    public function testMail(): void
    {
        $email = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
        $variant = isset($_GET['variant']) ? strtolower(trim((string)$_GET['variant'])) : 'v1';
        $all = isset($_GET['all']) && in_array(strtolower((string)$_GET['all']), ['1','true','yes'], true);
        $preview = isset($_GET['preview']) && in_array(strtolower((string)$_GET['preview']), ['1','true','yes'], true);

        $baseUrl = Application::getInstance()->config('app.url');
        $resetUrl = rtrim($baseUrl, '/') . '/reset-password?token=' . urlencode(bin2hex(random_bytes(8)));

        if ($preview) {
            header('Content-Type: text/html; charset=UTF-8');
            $svc = new EmailService();
            if ($variant === 'v2') { echo $this->renderVariantHtml($svc, 'v2', $resetUrl); return; }
            if ($variant === 'v3') { echo $this->renderVariantHtml($svc, 'v3', $resetUrl); return; }
            echo $this->renderVariantHtml($svc, 'v1', $resetUrl); return;
        }

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Parâmetro email inválido']);
        }

        $svc = new EmailService();
        if ($all) {
            $ok1 = $svc->sendPasswordResetVariant($email, 'Teste Operebem', $resetUrl, 'v1');
            $ok2 = $svc->sendPasswordResetVariant($email, 'Teste Operebem', $resetUrl, 'v2');
            $ok3 = $svc->sendPasswordResetVariant($email, 'Teste Operebem', $resetUrl, 'v3');
            $this->json(['success' => ($ok1 || $ok2 || $ok3), 'results' => ['v1' => $ok1, 'v2' => $ok2, 'v3' => $ok3]]);
        }

        $ok = $svc->sendPasswordResetVariant($email, 'Teste Operebem', $resetUrl, $variant);
        $this->json(['success' => $ok, 'variant' => $variant]);
    }

    private function renderVariantHtml(EmailService $svc, string $variant, string $resetUrl): string
    {
        $name = 'Pré-visualização';
        if ($variant === 'v2') {
            $html = (new \ReflectionClass($svc))->getMethod('getPasswordResetTemplateV2');
            $html->setAccessible(true);
            return $html->invoke($svc, $name, $resetUrl);
        }
        if ($variant === 'v3') {
            $html = (new \ReflectionClass($svc))->getMethod('getPasswordResetTemplateV3');
            $html->setAccessible(true);
            return $html->invoke($svc, $name, $resetUrl);
        }
        $html = (new \ReflectionClass($svc))->getMethod('getPasswordResetTemplateV1');
        $html->setAccessible(true);
        return $html->invoke($svc, $name, $resetUrl);
    }
}
