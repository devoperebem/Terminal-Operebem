<?php

namespace App\Services;

use App\Core\Application;

class CdnService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim($_ENV['CDN_BASE_URL'] ?? '', '/');
        $this->apiKey = $_ENV['CDN_API_KEY'] ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    public function getStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        try {
            $response = $this->request('GET', '/status');
            return ['success' => true, 'data' => $response];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function listFiles(?string $category = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        try {
            $endpoint = '/api/list.php';
            if ($category) {
                $endpoint .= '?category=' . urlencode($category);
            }
            $response = $this->request('GET', $endpoint);
            return ['success' => true, 'data' => $response];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function upload(string $filePath, string $category, string $originalName): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . '/api/upload.php',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . $this->apiKey,
                ],
                CURLOPT_POSTFIELDS => [
                    'category' => $category,
                    'file' => new \CURLFile($filePath, mime_content_type($filePath), $originalName),
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'error' => 'cURL error: ' . $error];
            }

            $data = json_decode($response, true);
            if ($httpCode !== 200 || empty($data['success'])) {
                return ['success' => false, 'error' => $data['error'] ?? 'Upload failed'];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete(string $category, string $filename): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . '/api/delete.php?category=' . urlencode($category) . '&filename=' . urlencode($filename),
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . $this->apiKey,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);
            if ($httpCode !== 200 || empty($data['success'])) {
                return ['success' => false, 'error' => $data['error'] ?? 'Delete failed'];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateToken(string $filePath, int $expiryMinutes = 30, bool $reusable = false, int $maxUses = 1): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        try {
            $params = [
                'file' => $filePath,
                'expiry' => $expiryMinutes,
            ];
            if ($reusable) {
                $params['reusable'] = 'true';
                $params['max_uses'] = $maxUses;
            }

            $response = $this->request('GET', '/api/token.php?' . http_build_query($params));
            return ['success' => true, 'data' => $response];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function cleanup(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'CDN not configured'];
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . '/api/cleanup.php',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . $this->apiKey,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);
            if ($httpCode !== 200 || empty($data['success'])) {
                return ['success' => false, 'error' => $data['error'] ?? 'Cleanup failed'];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function buildPublicUrl(string $category, string $filename): string
    {
        return $this->baseUrl . '/public/' . $category . '/' . $filename;
    }

    private function request(string $method, string $endpoint): array
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;

        $headers = ['X-API-Key: ' . $this->apiKey];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        $data = json_decode($response, true);
        if ($httpCode >= 400) {
            throw new \RuntimeException($data['error'] ?? 'HTTP ' . $httpCode);
        }

        return $data ?? [];
    }
}
