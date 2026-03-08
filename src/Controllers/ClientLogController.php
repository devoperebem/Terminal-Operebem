<?php

namespace App\Controllers;

use App\Core\Application;

class ClientLogController extends BaseController
{
    public function ingest(): void
    {
        // SameOrigin middleware deve proteger esta rota; aceita apenas JSON simples
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) { $this->json(['success'=>false], 400); }
        $tag = (string)($data['tag'] ?? 'client');
        $level = strtolower((string)($data['level'] ?? 'info'));
        $message = (string)($data['message'] ?? '');
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $context['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        try {
            $logger = Application::getInstance()->logger();
            switch ($level) {
                case 'debug': $logger->debug($tag . ': ' . $message, $context); break;
                case 'warning': $logger->warning($tag . ': ' . $message, $context); break;
                case 'error': $logger->error($tag . ': ' . $message, $context); break;
                default: $logger->info($tag . ': ' . $message, $context); break;
            }
        } catch (\Throwable $__) { /* ignore */ }
        $this->json(['success'=>true]);
    }
}
