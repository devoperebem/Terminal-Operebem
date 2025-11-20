<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\FearGreedController;
use App\Services\FearGreedService;

class FakeFearGreedService extends FearGreedService
{
    private array $responses;
    public function __construct(array $responses)
    {
        // Não chamar parent::__construct para evitar side-effects
        $this->responses = $responses;
    }
    public function getCurrent(): array { return $this->responses['current'] ?? ['success'=>true,'data'=>[]]; }
    public function getSummary(?string $date = null): array { return $this->responses['summary'] ?? ['success'=>true,'data'=>[]]; }
    public function getByDate(string $date): array { return $this->responses['byDate'] ?? ['success'=>true,'data'=>['date'=>$date]]; }
    public function getHistorical(string $startDate, string $endDate, ?int $limit = null): array { return $this->responses['historical'] ?? ['success'=>true,'data'=>['start_date'=>$startDate,'end_date'=>$endDate,'limit'=>$limit]]; }
    public function getIndicator(string $indicator, ?string $date = null): array { return $this->responses['indicator'] ?? ['success'=>true,'data'=>['indicator'=>$indicator,'date'=>$date]]; }
}

class FearGreedControllerTest extends TestCase
{
    private function capture(callable $fn): string
    {
        ob_start();
        try {
            $fn();
            return (string)ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function testCurrentOutputsJson(): void
    {
        $service = new FakeFearGreedService(['current' => ['success'=>true,'data'=>['ok'=>1]]]);
        $controller = new FearGreedController($service);
        $out = $this->capture(fn() => $controller->current());
        $data = json_decode($out, true);
        $this->assertTrue($data['success'] ?? false);
        $this->assertSame(1, $data['data']['ok'] ?? null);
    }

    public function testSummaryWithAndWithoutDate(): void
    {
        $service = new FakeFearGreedService(['summary' => ['success'=>true,'data'=>['score'=>55]]]);
        $controller = new FearGreedController($service);
        $out = $this->capture(fn() => $controller->summary());
        $data = json_decode($out, true);
        $this->assertSame(55, $data['data']['score'] ?? null);

        $out2 = $this->capture(fn() => $controller->summary(['date' => '2025-10-12']));
        $data2 = json_decode($out2, true);
        $this->assertSame(55, $data2['data']['score'] ?? null);
    }

    public function testByDate(): void
    {
        $service = new FakeFearGreedService(['byDate' => ['success'=>true,'data'=>['d'=>'2025-10-12']]]);
        $controller = new FearGreedController($service);
        $out = $this->capture(fn() => $controller->byDate(['date' => '2025-10-12']));
        $data = json_decode($out, true);
        $this->assertTrue($data['success'] ?? false);
        $this->assertSame('2025-10-12', $data['data']['d'] ?? null);
    }

    public function testHistoricalReadsQueryParams(): void
    {
        $service = new FakeFearGreedService(['historical' => ['success'=>true,'data'=>['count'=>1]]]);
        $controller = new FearGreedController($service);
        $old = $_GET;
        $_GET = ['start_date' => '2025-10-01', 'end_date' => '2025-10-10'];
        try {
            $out = $this->capture(fn() => $controller->historical());
            $data = json_decode($out, true);
            $this->assertTrue($data['success'] ?? false);
            $this->assertSame(1, $data['data']['count'] ?? null);
        } finally {
            $_GET = $old;
        }
    }

    public function testIndicatorWithAndWithoutDate(): void
    {
        $service = new FakeFearGreedService(['indicator' => ['success'=>true,'data'=>['indicator'=>'market-volatility-vix','date'=>'2025-10-12']]]);
        $controller = new FearGreedController($service);

        $out1 = $this->capture(fn() => $controller->indicator(['indicator' => 'market-volatility-vix']));
        $d1 = json_decode($out1, true);
        $this->assertTrue($d1['success'] ?? false);

        $out2 = $this->capture(fn() => $controller->indicator(['indicator' => 'market-volatility-vix', 'date' => '2025-10-12']));
        $d2 = json_decode($out2, true);
        $this->assertTrue($d2['success'] ?? false);
        $this->assertSame('market-volatility-vix', $d2['data']['indicator'] ?? null);
        $this->assertSame('2025-10-12', $d2['data']['date'] ?? null);
    }
}
