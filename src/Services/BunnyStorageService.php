<?php

namespace App\Services;

class BunnyStorageService
{
    private string $storageZone;
    private string $accessKey;
    private string $region;
    private string $publicBaseUrl;

    public function __construct()
    {
        $this->storageZone = trim((string)($_ENV['BUNNY_STORAGE_ZONE'] ?? ''));
        $this->accessKey = trim((string)($_ENV['BUNNY_STORAGE_ACCESS_KEY'] ?? ''));
        $this->region = trim((string)($_ENV['BUNNY_STORAGE_REGION'] ?? ''));

        $defaultPublic = trim((string)($_ENV['BUNNY_CDN_HOSTNAME'] ?? ''));
        if ($defaultPublic !== '' && !preg_match('#^https?://#i', $defaultPublic)) {
            $defaultPublic = 'https://' . $defaultPublic;
        }
        $this->publicBaseUrl = rtrim((string)($_ENV['BUNNY_STORAGE_PUBLIC_BASE_URL'] ?? $defaultPublic), '/');
    }

    public function isConfigured(): bool
    {
        return $this->storageZone !== '' && $this->accessKey !== '' && $this->publicBaseUrl !== '';
    }

    public function upload(string $localPath, string $remotePath): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'url' => null,
                'error' => 'Bunny Storage nao configurado',
            ];
        }

        if (!is_file($localPath)) {
            return [
                'success' => false,
                'url' => null,
                'error' => 'Arquivo local nao encontrado para upload',
            ];
        }

        $safeRemotePath = ltrim(str_replace('\\', '/', $remotePath), '/');
        $endpoint = $this->buildStorageEndpoint($safeRemotePath);

        $body = file_get_contents($localPath);
        if (!is_string($body)) {
            return [
                'success' => false,
                'url' => null,
                'error' => 'Falha ao ler arquivo local para upload',
            ];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'AccessKey: ' . $this->accessKey,
                'Content-Type: application/octet-stream',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            return [
                'success' => false,
                'url' => null,
                'error' => 'Erro cURL no upload Bunny: ' . $curlError,
            ];
        }

        if ($status < 200 || $status >= 300) {
            $sample = is_string($response) ? mb_substr($response, 0, 300) : '';
            return [
                'success' => false,
                'url' => null,
                'error' => 'Upload Bunny falhou (HTTP ' . $status . '). ' . $sample,
            ];
        }

        return [
            'success' => true,
            'url' => $this->publicBaseUrl . '/' . $safeRemotePath,
            'error' => null,
        ];
    }

    private function buildStorageEndpoint(string $remotePath): string
    {
        $base = 'https://storage.bunnycdn.com';
        if ($this->region !== '') {
            $base = 'https://' . $this->region . '.storage.bunnycdn.com';
        }

        return $base . '/' . rawurlencode($this->storageZone) . '/' . str_replace('%2F', '/', rawurlencode($remotePath));
    }
}
