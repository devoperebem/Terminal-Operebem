<?php

namespace App\Services;

class PortalSyncAuditService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__, 2) . '/.config/portal_sync_audit.json';
    }

    public function append(array $entry): void
    {
        $all = $this->readAll();
        $all[] = [
            'at' => date('c'),
            'endpoint' => (string)($entry['endpoint'] ?? ''),
            'status' => (int)($entry['status'] ?? 0),
            'success' => !empty($entry['success']),
            'payload_hash' => (string)($entry['payload_hash'] ?? ''),
            'error' => (string)($entry['error'] ?? ''),
            'response_sample' => (string)($entry['response_sample'] ?? ''),
        ];

        $max = 500;
        if (count($all) > $max) {
            $all = array_slice($all, -$max);
        }

        $this->writeAll($all);
    }

    public function latest(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $all = $this->readAll();
        if (empty($all)) {
            return [];
        }

        $slice = array_slice($all, -$limit);
        return array_reverse($slice);
    }

    private function readAll(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeAll(array $entries): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }

        file_put_contents($this->filePath, $json . PHP_EOL);
    }
}
