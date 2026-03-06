<?php

namespace App\Services;

use App\Core\Application;

class PortalSyncService
{
    private string $baseUrl;
    private string $secret;
    private PortalSyncAuditService $auditService;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)($_ENV['PORTAL_ALUNO_BASE_URL'] ?? 'https://aluno.operebem.com.br'), '/');
        $this->secret = (string)($_ENV['TERMINAL_SYNC_SECRET'] ?? '');
        $this->auditService = new PortalSyncAuditService();
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

    public function checkPricingPublicEndpoint(): array
    {
        $endpoint = '/api/terminal/pricing';
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response)) {
            $response = '';
        }

        $result = [
            'success' => $error === '' && $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'response' => $response,
            'error' => $error !== '' ? $error : ($httpCode >= 200 && $httpCode < 300 ? null : 'HTTP ' . $httpCode),
        ];

        $this->recordAudit($endpoint, '', $result);
        return $result;
    }

    private function post(string $endpoint, array $payload): array
    {
        if ($this->secret === '') {
            $result = [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => 'TERMINAL_SYNC_SECRET nao configurado',
            ];
            $this->recordAudit($endpoint, '', $result);
            return $result;
        }

        $url = $this->baseUrl . $endpoint;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            $result = [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => 'Falha ao serializar payload JSON',
            ];
            $this->recordAudit($endpoint, '', $result);
            return $result;
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
            $result = [
                'success' => false,
                'status' => 0,
                'response' => '',
                'error' => $error,
            ];
            $this->recordAudit($endpoint, $body, $result);
            return $result;
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

        $result = [
            'success' => $httpCode === 200,
            'status' => $httpCode,
            'response' => $response,
            'error' => $httpCode === 200 ? null : 'HTTP ' . $httpCode,
        ];

        $this->recordAudit($endpoint, $body, $result);
        return $result;
    }

    private function recordAudit(string $endpoint, string $body, array $result): void
    {
        try {
            $this->auditService->append([
                'endpoint' => $endpoint,
                'status' => (int)($result['status'] ?? 0),
                'success' => !empty($result['success']),
                'payload_hash' => $body !== '' ? hash('sha256', $body) : '',
                'error' => (string)($result['error'] ?? ''),
                'response_sample' => mb_substr((string)($result['response'] ?? ''), 0, 500),
            ]);
        } catch (\Throwable $__) {
        }
    }
}
