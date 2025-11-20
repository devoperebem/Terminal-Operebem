<?php

namespace App\Controllers;
use App\Core\Application;

class NewsController extends BaseController
{
    public function index(): void
    {
        // Usar proxy interno por padrão, com logging via Monolog
        $apiUrl = '/api/news';
        $apiKey = '';
        $refreshMs = (int)($_ENV['NEWS_REFRESH_INTERVAL_MS'] ?? 30000);
        $this->view('app/news/index', [
            'title' => 'Notícias',
            'newsApiUrl' => $apiUrl,
            'newsApiKey' => $apiKey,
            'newsRefreshMs' => $refreshMs,
        ]);
    }

    public function noticias(): void
    {
        $logger = Application::getInstance()->logger();
        $base = rtrim((string)($_ENV['NEWS_API_URL'] ?? ''), '/');
        $key = (string)($_ENV['NEWS_API_KEY'] ?? '');
        if (empty($base)) {
            try { $logger->warning('[NEWS] NEWS_API_URL ausente'); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'NEWS_API_URL ausente']);
        }
        $url = $base . '/noticias';
        $start = microtime(true);
        try {
            $ch = curl_init($url);
            // Construir headers para evitar bloqueio por ausência de Origin/UA
            $headers = ['Accept: application/json'];
            $scheme = Application::getInstance()->isHttps() ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'terminal.operebem.local';
            $origin = $_SERVER['HTTP_ORIGIN'] ?? ($scheme . '://' . $host);
            $referer = ($scheme . '://' . $host) . '/app/news';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'TerminalOperebem/1.0 (+'.$host.') PHP-curl';
            $headers[] = 'Origin: ' . $origin;
            $headers[] = 'Referer: ' . $referer;
            $headers[] = 'User-Agent: ' . $ua;
            if (!empty($key)) { $headers[] = 'X-API-Key: ' . $key; }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($resp === false) {
                try { $logger->error('[NEWS] Falha ao requisitar API', ['error' => $err]); } catch (\Throwable $t) {}
                $this->json(['success' => false, 'message' => 'Falha ao requisitar API de notícias']);
            }
            $data = json_decode($resp, true);
            if (!is_array($data)) {
                try { $logger->error('[NEWS] Resposta inválida da API', ['code' => $code]); } catch (\Throwable $t) {}
                $this->json(['success' => false, 'message' => 'Resposta inválida da API de notícias']);
            }
            $count = isset($data['noticias']) && is_array($data['noticias']) ? count($data['noticias']) : 0;
            $dur = round((microtime(true) - $start) * 1000);
            try { $logger->info('[NEWS] Coleta de notícias', ['status' => $code, 'itens' => $count, 'ms' => $dur]); } catch (\Throwable $t) {}
            $this->json($data);
        } catch (\Throwable $e) {
            try { $logger->error('[NEWS] Exceção no proxy', ['msg' => $e->getMessage()]); } catch (\Throwable $t) {}
            $this->json(['success' => false, 'message' => 'Erro interno ao coletar notícias']);
        }
    }
}
