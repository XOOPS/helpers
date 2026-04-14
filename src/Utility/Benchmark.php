<?php

declare(strict_types=1);

/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright    2000-2026 XOOPS Project (https://xoops.org/)
 * @license      GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author       XOOPS Development Team
 */

namespace Xoops\Helpers\Utility;

/**
 * Simple timing and memory measurement helpers.
 *
 * Provides lightweight profiling for operations during
 * development and debugging.
 *
 * Usage:
 *   $result = Benchmark::measure(function () {
 *       // expensive operation
 *       return computeResult();
 *   });
 *   // ['result' => ..., 'time_ms' => 42.5, 'memory_bytes' => 1024, 'memory_peak_bytes' => 2048]
 */
final class Benchmark
{
    /**
     * Measure execution time and memory usage of a callback.
     *
     * @return array{result: mixed, time_ms: float, memory_bytes: int, memory_peak_bytes: int}
     */
    public static function measure(callable $callback): array
    {
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);
        $startTime = hrtime(true);

        $result = $callback();

        $endTime = hrtime(true);
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        return [
            'result' => $result,
            'time_ms' => ($endTime - $startTime) / 1_000_000,
            'memory_bytes' => max(0, $memoryAfter - $memoryBefore),
            'memory_peak_bytes' => max(0, $peakAfter - $peakBefore),
        ];
    }

    /**
     * Measure only the execution time of a callback in milliseconds.
     *
     * @return array{result: mixed, time_ms: float}
     */
    public static function time(callable $callback): array
    {
        $start = hrtime(true);
        $result = $callback();
        $end = hrtime(true);

        return [
            'result' => $result,
            'time_ms' => ($end - $start) / 1_000_000,
        ];
    }

    /**
     * Run a callback multiple times and return average execution time.
     *
     * @return array{avg_ms: float, min_ms: float, max_ms: float, iterations: int}
     */
    public static function average(callable $callback, int $iterations = 100): array
    {
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $callback();
            $end = hrtime(true);

            $times[] = ($end - $start) / 1_000_000;
        }

        return [
            'avg_ms' => array_sum($times) / count($times),
            'min_ms' => min($times),
            'max_ms' => max($times),
            'iterations' => $iterations,
        ];
    }
}
