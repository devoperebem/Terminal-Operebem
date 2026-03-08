<?php

namespace App\Services;

class PortalPricingConfigService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__, 2) . '/.config/portal_pricing_plans.json';
    }

    public function all(): array
    {
        $raw = $this->readRaw();
        $plans = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $plans[] = $this->normalizeRecord($item);
        }

        usort($plans, static function (array $a, array $b): int {
            return ((int)($a['position'] ?? 0)) <=> ((int)($b['position'] ?? 0));
        });

        return $plans;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $plan) {
            if ((string)($plan['id'] ?? '') === $id) {
                return $plan;
            }
        }
        return null;
    }

    public function create(array $input): array
    {
        $plans = $this->all();
        $nextPosition = 1;
        foreach ($plans as $plan) {
            $nextPosition = max($nextPosition, (int)($plan['position'] ?? 0) + 1);
        }

        $record = $this->normalizeRecord(array_merge($input, [
            'id' => bin2hex(random_bytes(8)),
            'position' => (int)($input['position'] ?? $nextPosition),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        $plans[] = $record;
        $this->writeAll($plans);
        return $record;
    }

    public function update(string $id, array $input): ?array
    {
        $plans = $this->all();
        $updated = null;
        foreach ($plans as $idx => $plan) {
            if ((string)($plan['id'] ?? '') !== $id) {
                continue;
            }

            $merged = array_merge($plan, $input, [
                'id' => $id,
                'created_at' => (string)($plan['created_at'] ?? date('Y-m-d H:i:s')),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $plans[$idx] = $this->normalizeRecord($merged);
            $updated = $plans[$idx];
            break;
        }

        if ($updated === null) {
            return null;
        }

        $this->writeAll($plans);
        return $updated;
    }

    public function delete(string $id): bool
    {
        $plans = $this->all();
        $filtered = array_values(array_filter($plans, static function (array $plan) use ($id): bool {
            return (string)($plan['id'] ?? '') !== $id;
        }));

        if (count($filtered) === count($plans)) {
            return false;
        }

        $this->writeAll($filtered);
        return true;
    }

    public function buildSyncPlans(): array
    {
        $sync = [];
        foreach ($this->all() as $plan) {
            $sync[] = [
                'name' => (string)$plan['name'],
                'slug' => (string)$plan['slug'],
                'price_display' => (string)$plan['price_display'],
                'price_subtitle' => (string)$plan['price_subtitle'],
                'description' => (string)$plan['description'],
                'features' => array_values($plan['features']),
                'cta_label' => (string)$plan['cta_label'],
                'cta_url' => (string)$plan['cta_url'],
                'is_highlighted' => (bool)$plan['is_highlighted'],
                'badge_text' => (string)$plan['badge_text'],
            ];
        }
        return $sync;
    }

    private function normalizeRecord(array $item): array
    {
        $name = trim((string)($item['name'] ?? ''));
        $slug = trim((string)($item['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($name !== '' ? $name : 'plan');
        }

        $features = $item['features'] ?? [];
        if (is_string($features)) {
            $features = preg_split('/\r\n|\r|\n/', $features) ?: [];
        }
        if (!is_array($features)) {
            $features = [];
        }
        $features = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $features), static fn($v) => $v !== ''));

        return [
            'id' => (string)($item['id'] ?? ''),
            'name' => $name,
            'slug' => $slug,
            'price_display' => trim((string)($item['price_display'] ?? '')),
            'price_subtitle' => trim((string)($item['price_subtitle'] ?? '')),
            'description' => trim((string)($item['description'] ?? '')),
            'features' => $features,
            'cta_label' => trim((string)($item['cta_label'] ?? 'Assinar')),
            'cta_url' => trim((string)($item['cta_url'] ?? '')),
            'is_highlighted' => !empty($item['is_highlighted']),
            'badge_text' => trim((string)($item['badge_text'] ?? '')),
            'position' => max(1, (int)($item['position'] ?? 1)),
            'created_at' => (string)($item['created_at'] ?? date('Y-m-d H:i:s')),
            'updated_at' => (string)($item['updated_at'] ?? date('Y-m-d H:i:s')),
        ];
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'plan';
    }

    private function readRaw(): array
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

    private function writeAll(array $plans): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode(array_values($plans), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new \RuntimeException('Falha ao serializar planos de pricing');
        }
        file_put_contents($this->filePath, $json . PHP_EOL);
    }
}
