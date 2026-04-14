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

use Throwable;

/**
 * Retry and error recovery helpers.
 *
 * Provides retry-with-backoff logic for flaky operations
 * and rescue/fallback for graceful error handling.
 */
final class Retry
{
    /**
     * Retry an operation up to N times with optional backoff.
     *
     * @param int               $times    Maximum attempts
     * @param callable          $callback Operation to attempt; receives attempt number (1-based)
     * @param int|callable      $sleepMs  Milliseconds to wait between attempts, or a callable(int $attempt): int
     * @param callable|null     $when     Optional predicate: retry only if this returns true for the exception
     * @return mixed The callback's return value on success
     *
     * @throws Throwable The last exception if all attempts fail
     */
    public static function retry(int $times, callable $callback, int|callable $sleepMs = 0, ?callable $when = null): mixed
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return $callback($attempts);
            } catch (Throwable $e) {
                if ($attempts >= $times) {
                    throw $e;
                }

                if ($when !== null && !$when($e)) {
                    throw $e;
                }

                $delay = is_callable($sleepMs) ? $sleepMs($attempts) : $sleepMs;

                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }
        }
    }

    /**
     * Execute a callback and return a default value on failure.
     *
     * @param callable      $callback Operation to attempt
     * @param mixed         $default  Default value or Closure to return on failure
     * @param callable|null $when     Optional predicate: rescue only if this returns true for the exception
     * @return mixed The callback's return value on success, or the default on failure
     *
     * @throws Throwable If $when returns false for the exception
     */
    public static function rescue(callable $callback, mixed $default = null, ?callable $when = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if ($when !== null && !$when($e)) {
                throw $e;
            }

            return Value::value($default);
        }
    }
}
