<?php

declare(strict_types=1);

/**
 * @copyright    2000-2026 XOOPS Project (https://xoops.org/)
 * @license      GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author       XOOPS Development Team
 */

namespace Xoops\Helpers\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Benchmark;

final class BenchmarkTest extends TestCase
{
    public function testAverageThrowsForZeroIterations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Benchmark::average(static fn () => null, 0);
    }

    public function testAverageThrowsForNegativeIterations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Benchmark::average(static fn () => null, -5);
    }

    public function testAverageReturnsStatsForValidIterations(): void
    {
        $calls  = 0;
        $result = Benchmark::average(static function () use (&$calls): void {
            ++$calls;
        }, 3);

        self::assertSame(3, $calls);
        self::assertSame(3, $result['iterations']);
        self::assertArrayHasKey('avg_ms', $result);
        self::assertArrayHasKey('min_ms', $result);
        self::assertArrayHasKey('max_ms', $result);
        self::assertGreaterThanOrEqual($result['min_ms'], $result['max_ms']);
        self::assertGreaterThanOrEqual(0.0, $result['avg_ms']);
    }
}
