<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrmLogicTest extends TestCase
{
    private function getMomentumScore(float $last, float $high, float $low, bool $isRiskOn): float
    {
        $range = $high - $low;
        if ($range == 0.0) {
            return 50.0;
        }
        $pos = ($last - $low) / $range; // 0..1
        $pct = $isRiskOn ? ($pos * 100.0) : ((1.0 - $pos) * 100.0);
        if ($pct < 0) $pct = 0; if ($pct > 100) $pct = 100;
        return round($pct, 2);
    }

    public function testRiskOnAtHighIs100(): void
    {
        $this->assertSame(100.0, $this->getMomentumScore(110, 110, 100, true));
    }

    public function testRiskOnAtLowIs0(): void
    {
        $this->assertSame(0.0, $this->getMomentumScore(100, 110, 100, true));
    }

    public function testRiskOffAtLowIs100(): void
    {
        $this->assertSame(100.0, $this->getMomentumScore(100, 110, 100, false));
    }

    public function testRiskOffAtHighIs0(): void
    {
        $this->assertSame(0.0, $this->getMomentumScore(110, 110, 100, false));
    }

    public function testZeroRangeReturnsNeutral50(): void
    {
        $this->assertSame(50.0, $this->getMomentumScore(100, 100, 100, true));
        $this->assertSame(50.0, $this->getMomentumScore(100, 100, 100, false));
    }

    public function testAverageOrm(): void
    {
        $assets = [
            ['last'=>110,'high'=>110,'low'=>100,'on'=>true],   // 100
            ['last'=>105,'high'=>110,'low'=>100,'on'=>true],   // 50
            ['last'=>100,'high'=>110,'low'=>100,'on'=>true],   // 0
            ['last'=>110,'high'=>110,'low'=>100,'on'=>false],  // 0
            ['last'=>105,'high'=>110,'low'=>100,'on'=>false],  // 50
            ['last'=>100,'high'=>110,'low'=>100,'on'=>false],  // 100
        ];
        $sum = 0.0; $n = 0;
        foreach ($assets as $a) {
            $sum += $this->getMomentumScore((float)$a['last'], (float)$a['high'], (float)$a['low'], (bool)$a['on']);
            $n++;
        }
        $avg = round($sum / $n, 2);
        $this->assertSame(50.0, $avg);
    }
}
