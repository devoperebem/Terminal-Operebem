<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\FearGreedService;

class FearGreedServiceTest extends TestCase
{
    private function makeTmpCacheDir(string $suffix): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fg_cache_' . $suffix . '_' . uniqid();
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        return $dir;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testSummarySuccessAndCache(): void
    {
        $calls = 0;
        $http = function(string $url, array $headers, int $timeout, int $connectTimeout) use (&$calls) {
            $calls++;
            $this->assertStringContainsString('/summary', $url);
            return [200, json_encode([
                'success' => true,
                'data' => [
                    'score' => 50,
                    'rating' => 'neutral',
                    'timestamp' => '2025-10-10T00:00:00Z'
                ]
            ])];
        };
        $cacheDir = $this->makeTmpCacheDir('summary');
        try {
            $svc = new FearGreedService([
                'api_key' => 'FG_TEST',
                'base_url' => 'https://mock.local/v1/cnn-fear-greed',
                'http_handler' => $http,
                'cache_dir' => $cacheDir,
                'cache_ttl' => 300,
            ]);
            $res1 = $svc->getSummary();
            $this->assertTrue($res1['success'] ?? false);
            $this->assertSame(50, $res1['data']['score'] ?? null);
            $this->assertSame(1, $calls);

            // Segunda chamada deve vir do cache
            $res2 = $svc->getSummary();
            $this->assertTrue($res2['success'] ?? false);
            $this->assertSame(50, $res2['data']['score'] ?? null);
            $this->assertSame(1, $calls, 'Segundo GET deve usar cache e não chamar http');
        } finally {
            $this->rrmdir($cacheDir);
        }
    }

    public function testHttpErrorBubblesUp(): void
    {
        $http = function(string $url, array $headers, int $timeout, int $connectTimeout) {
            return [500, json_encode(['error' => true, 'message' => 'Internal error'])];
        };
        $cacheDir = $this->makeTmpCacheDir('error');
        try {
            $svc = new FearGreedService([
                'api_key' => 'FG_TEST',
                'base_url' => 'https://mock.local/v1/cnn-fear-greed',
                'http_handler' => $http,
                'cache_dir' => $cacheDir,
            ]);
            $res = $svc->getCurrent();
            $this->assertFalse($res['success'] ?? true);
            $this->assertSame(500, $res['status'] ?? null);
        } finally {
            $this->rrmdir($cacheDir);
        }
    }

    public function testInvalidDateValidation(): void
    {
        $svc = new FearGreedService([
            'api_key' => 'FG_TEST',
            'base_url' => 'https://mock.local/v1/cnn-fear-greed',
            'http_handler' => function(){ $this->fail('HTTP should not be called for invalid date'); },
            'cache_dir' => $this->makeTmpCacheDir('invalid'),
        ]);
        $res = $svc->getByDate('2025-13-01');
        $this->assertFalse($res['success'] ?? true);
        $this->assertStringContainsString('Data inválida', $res['message'] ?? '');
    }

    public function testIndicatorWithDate(): void
    {
        $http = function(string $url) {
            $this->assertStringContainsString('/indicator/market-volatility-vix/2025-10-12', $url);
            return [200, json_encode(['success' => true, 'data' => ['indicator' => 'market-volatility-vix']])];
        };
        $svc = new FearGreedService([
            'api_key' => 'FG_TEST',
            'base_url' => 'https://mock.local/v1/cnn-fear-greed',
            'http_handler' => $http,
            'cache_dir' => $this->makeTmpCacheDir('indicator'),
        ]);
        $res = $svc->getIndicator('market-volatility-vix', '2025-10-12');
        $this->assertTrue($res['success'] ?? false);
        $this->assertSame('market-volatility-vix', $res['data']['indicator'] ?? null);
    }

    public function testHistoricalQueryString(): void
    {
        $http = function(string $url) {
            $this->assertStringContainsString('/historical', $url);
            $this->assertStringContainsString('start_date=2025-10-01', $url);
            $this->assertStringContainsString('end_date=2025-10-10', $url);
            return [200, json_encode(['success' => true, 'data' => ['count' => 2, 'data' => []]])];
        };
        $svc = new FearGreedService([
            'api_key' => 'FG_TEST',
            'base_url' => 'https://mock.local/v1/cnn-fear-greed',
            'http_handler' => $http,
            'cache_dir' => $this->makeTmpCacheDir('historical'),
        ]);
        $res = $svc->getHistorical('2025-10-01', '2025-10-10');
        $this->assertTrue($res['success'] ?? false);
        $this->assertSame(2, $res['data']['count'] ?? null);
    }
}
