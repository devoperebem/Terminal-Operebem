<?php

namespace App\Services;

use App\Core\Application;

class PortalSyncService
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)($_ENV['PORTAL_ALUNO_BASE_URL'] ?? 'https://aluno.operebem.com.br'), '/');
        $this->secret = (string)($_ENV['TERMINAL_SYNC_SECRET'] ?? '');
    }

    public function syncPricing(array $plans, array $metadata = []): array
    {
        return $this->post('/api/terminal/sync-pricing', [
            'plans' => array_values($plans),
            'metadata' => $metadata,
        ]);
    }

    public function syncMaterials(array $materials, array $metadata = []): array
    {
        $payload = [
            'materials' => array_values($materials),
        ];
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }
        return $this->post('/api/terminal/sync-materials', $payload);
    }

    private function post(string $endpoint, array $payload): array
    {
        if ($this->secret === '') {
            return [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => 'TERMINAL_SYNC_SECRET nao configurado',
            ];
        }

        $url = $this->baseUrl . $endpoint;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            return [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => 'Falha ao serializar payload JSON',
            ];
        }

        $signature = hash_hmac('sha256', $body, $this->secret);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Sync-Signature: ' . $signature,
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== '') {
            try {
                Application::getInstance()->logger()->warning('Portal sync cURL error', [
                    'endpoint' => $endpoint,
                    'error' => $error,
                ]);
            } catch (\Throwable $__) {
            }
            return [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => $error,
            ];
        }

        if (!is_string($response)) {
            $response = '';
        }

        if ($httpCode !== 200) {
            try {
                Application::getInstance()->logger()->warning('Portal sync HTTP falha', [
                    'endpoint' => $endpoint,
                    'status' => $httpCode,
                    'response' => mb_substr($response, 0, 1000),
                ]);
            } catch (\Throwable $__) {
            }
        }

        return [
            'success' => $httpCode === 200,
            'status' => $httpCode,
            'response' => $response,
            'error' => $httpCode === 200 ? null : 'HTTP ' . $httpCode,
        ];
    }
}
