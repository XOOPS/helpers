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

namespace Xoops\Helpers\Tests\Unit\Utility;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xoops\Helpers\Utility\Retry;

final class RetryTest extends TestCase
{
    // ── retry() ─────────────────────────────────────────────

    public function testRetrySucceedsOnFirstAttempt(): void
    {
        $result = Retry::retry(3, fn(int $attempt) => 'ok_' . $attempt);
        self::assertSame('ok_1', $result);
    }

    public function testRetrySucceedsOnSubsequentAttempt(): void
    {
        $attempts = 0;

        $result = Retry::retry(3, function () use (&$attempts): string {
            $attempts++;
            if ($attempts < 3) {
                throw new RuntimeException('not yet');
            }
            return 'success';
        });

        self::assertSame('success', $result);
        self::assertSame(3, $attempts);
    }

    public function testRetryPassesAttemptNumberToCallback(): void
    {
        $received = [];

        Retry::retry(3, function (int $attempt) use (&$received): string {
            $received[] = $attempt;
            if ($attempt < 3) {
                throw new RuntimeException('retry');
            }
            return 'done';
        });

        self::assertSame([1, 2, 3], $received);
    }

    public function testRetryThrowsLastExceptionAfterAllAttemptsFail(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attempt 3');

        $attempts = 0;

        Retry::retry(3, function () use (&$attempts): never {
            $attempts++;
            throw new RuntimeException("attempt {$attempts}");
        });
    }

    public function testRetryWithWhenPredicateDoesNotRetryNonMatchingExceptions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Retry::retry(
            times: 3,
            callback: fn() => throw new InvalidArgumentException('no retry'),
            sleepMs: 0,
            when: fn(\Throwable $e): bool => $e instanceof RuntimeException, // only retry RuntimeException
        );
    }

    public function testRetryWithWhenPredicateRetriesMatchingExceptions(): void
    {
        $attempts = 0;

        $result = Retry::retry(
            times: 3,
            callback: function () use (&$attempts): string {
                $attempts++;
                if ($attempts < 3) {
                    throw new RuntimeException('retry me');
                }
                return 'done';
            },
            sleepMs: 0,
            when: fn(\Throwable $e): bool => $e instanceof RuntimeException,
        );

        self::assertSame('done', $result);
        self::assertSame(3, $attempts);
    }

    public function testRetryWithCallableSleepMs(): void
    {
        $sleepArgs = [];

        $attempts = 0;
        Retry::retry(
            times: 3,
            callback: function () use (&$attempts): string {
                $attempts++;
                if ($attempts < 3) {
                    throw new RuntimeException('retry');
                }
                return 'ok';
            },
            sleepMs: function (int $attempt) use (&$sleepArgs): int {
                $sleepArgs[] = $attempt;
                return 0; // 0 ms so the test doesn't slow down
            },
        );

        // sleepMs is called between attempts (after attempt 1 and 2)
        self::assertSame([1, 2], $sleepArgs);
    }

    // ── rescue() ────────────────────────────────────────────

    public function testRescueReturnsCallbackResultOnSuccess(): void
    {
        $result = Retry::rescue(fn() => 42);
        self::assertSame(42, $result);
    }

    public function testRescueReturnsNullDefaultOnException(): void
    {
        $result = Retry::rescue(fn() => throw new RuntimeException('oops'));
        self::assertNull($result);
    }

    public function testRescueReturnsScalarDefaultOnException(): void
    {
        $result = Retry::rescue(fn() => throw new RuntimeException('oops'), 'fallback');
        self::assertSame('fallback', $result);
    }

    public function testRescueResolvesFallbackClosure(): void
    {
        $result = Retry::rescue(
            fn() => throw new RuntimeException('oops'),
            fn() => 'computed_fallback',
        );

        self::assertSame('computed_fallback', $result);
    }

    public function testRescueWithWhenPredicateRethrowsNonMatchingException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Retry::rescue(
            callback: fn() => throw new InvalidArgumentException('not rescued'),
            default: 'nope',
            when: fn(\Throwable $e): bool => $e instanceof RuntimeException,
        );
    }

    public function testRescueWithWhenPredicateCatchesMatchingException(): void
    {
        $result = Retry::rescue(
            callback: fn() => throw new RuntimeException('rescued'),
            default: 'caught',
            when: fn(\Throwable $e): bool => $e instanceof RuntimeException,
        );

        self::assertSame('caught', $result);
    }
}
