<?php

namespace App\Services;

class MediaOriginService
{
    private string $baseUrl;
    private string $uploadPath;
    private string $uploadToken;
    private string $downloadBaseUrl;
    private string $signingKey;
    private int $signingTtl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)($_ENV['MEDIA_ORIGIN_BASE_URL'] ?? ''), '/');
        $this->uploadPath = '/' . ltrim((string)($_ENV['MEDIA_ORIGIN_UPLOAD_PATH'] ?? '/api/upload.php'), '/');
        $this->uploadToken = trim((string)($_ENV['MEDIA_ORIGIN_UPLOAD_TOKEN'] ?? ''));
        $this->downloadBaseUrl = rtrim((string)($_ENV['MEDIA_ORIGIN_DOWNLOAD_BASE_URL'] ?? ''), '/');
        $this->signingKey = trim((string)($_ENV['MEDIA_ORIGIN_SIGNING_KEY'] ?? ''));
        $this->signingTtl = max(60, (int)($_ENV['MEDIA_ORIGIN_SIGNING_TTL'] ?? 600));
        $this->timeout = max(5, (int)($_ENV['MEDIA_ORIGIN_TIMEOUT'] ?? 30));
    }

    public function isUploadConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->uploadToken !== '';
    }

    public function isSignedDownloadConfigured(): bool
    {
        return $this->downloadBaseUrl !== '' && $this->signingKey !== '';
    }

    public function upload(string $localPath, string $originalName, bool $isFree = false): array
    {
        if (!$this->isUploadConfigured()) {
            return [
                'success' => false,
                'error' => 'Media origin Hostinger nao configurado',
                'file_url' => null,
                'storage_path' => null,
                'status' => 0,
            ];
        }

        if (!is_file($localPath)) {
            return [
                'success' => false,
                'error' => 'Arquivo local nao encontrado para upload no media origin',
                'file_url' => null,
                'storage_path' => null,
                'status' => 0,
            ];
        }

        $safeOriginal = trim($originalName);
        if ($safeOriginal === '') {
            $safeOriginal = basename($localPath);
        }

        $extension = strtolower((string)pathinfo($safeOriginal, PATHINFO_EXTENSION));
        $baseName = (string)pathinfo($safeOriginal, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $baseName);
        $safeBase = trim((string)$safeBase, '-');
        if ($safeBase === '') {
            $safeBase = 'material';
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Throwable $__) {
            $suffix = substr(sha1((string)microtime(true)), 0, 8);
        }

        $finalName = date('YmdHis') . '-' . $safeBase . '-' . $suffix;
        if ($extension !== '') {
            $finalName .= '.' . $extension;
        }

        $directory = 'materials/' . date('Y/m');
        $storagePath = trim($directory . '/' . $finalName, '/');

        $endpoint = $this->baseUrl . $this->uploadPath;
        $postFields = [
            'directory' => $directory,
            'filename' => $finalName,
            'storage_path' => $storagePath,
            'visibility' => $isFree ? 'public' : 'restricted',
            'file' => new \CURLFile($localPath, (string)(mime_content_type($localPath) ?: 'application/octet-stream'), $safeOriginal),
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->uploadToken,
            ],
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response)) {
            $response = '';
        }

        if ($curlError !== '') {
            return [
                'success' => false,
                'error' => 'Erro cURL no media origin: ' . $curlError,
                'file_url' => null,
                'storage_path' => null,
                'status' => 0,
                'response' => '',
            ];
        }

        $decoded = json_decode($response, true);
        $data = is_array($decoded) ? $decoded : [];

        $fileUrl = (string)($data['file_url'] ?? $data['url'] ?? '');
        $returnedPath = (string)($data['storage_path'] ?? $data['path'] ?? '');
        if ($returnedPath === '') {
            $returnedPath = $storagePath;
        }

        $isSuccess = $status >= 200 && $status < 300;
        if (array_key_exists('success', $data)) {
            $isSuccess = $isSuccess && !empty($data['success']);
        }
        if ($fileUrl === '') {
            $isSuccess = false;
        }

        if (!$isSuccess) {
            $sample = $response === '' ? '' : mb_substr($response, 0, 400);
            return [
                'success' => false,
                'error' => (string)($data['error'] ?? ('Upload media origin falhou (HTTP ' . $status . ')')),
                'file_url' => null,
                'storage_path' => null,
                'status' => $status,
                'response' => $sample,
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'file_url' => $fileUrl,
            'storage_path' => trim($returnedPath, '/'),
            'status' => $status,
            'response' => '',
        ];
    }

    public function signDownloadUrl(string $storagePath, ?int $expiresAt = null): string
    {
        $storagePath = trim($storagePath, '/');
        if ($storagePath === '' || !$this->isSignedDownloadConfigured()) {
            return '';
        }

        $exp = $expiresAt ?? (time() + $this->signingTtl);
        if ($exp <= time()) {
            $exp = time() + $this->signingTtl;
        }

        $message = $storagePath . '|' . $exp;
        $signature = hash_hmac('sha256', $message, $this->signingKey);

        return $this->downloadBaseUrl
            . '?path=' . rawurlencode($storagePath)
            . '&exp=' . $exp
            . '&sig=' . $signature;
    }
}
