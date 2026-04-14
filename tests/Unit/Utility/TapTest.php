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

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Tap;

final class TapTest extends TestCase
{
    public function testTapReturnsOriginalValue(): void
    {
        $result = Tap::tap('hello', fn(string $v) => strtoupper($v));
        self::assertSame('hello', $result);
    }

    public function testTapCallbackIsInvoked(): void
    {
        $called = false;

        Tap::tap('trigger', function () use (&$called): void {
            $called = true;
        });

        self::assertTrue($called);
    }

    public function testTapPassesValueToCallback(): void
    {
        $received = null;

        Tap::tap(42, function (int $v) use (&$received): void {
            $received = $v;
        });

        self::assertSame(42, $received);
    }

    public function testTapWithObjectDoesNotMutateReference(): void
    {
        $obj = new \stdClass();
        $obj->name = 'original';

        $result = Tap::tap($obj, function (\stdClass $o): void {
            // callback inspects but does not reassign the variable
            $_ = $o->name;
        });

        self::assertSame($obj, $result);
        self::assertSame('original', $result->name);
    }

    public function testTapSideEffectDoesNotAlterReturnedScalar(): void
    {
        $log = [];

        $value = Tap::tap(99, function (int $v) use (&$log): void {
            $log[] = "saw {$v}";
        });

        self::assertSame(99, $value);
        self::assertSame(['saw 99'], $log);
    }

    public function testTapWithArrayValue(): void
    {
        $result = Tap::tap([1, 2, 3], fn(array $a) => count($a));
        self::assertSame([1, 2, 3], $result);
    }

    public function testTapWithNullValue(): void
    {
        $callbackRan = false;

        $result = Tap::tap(null, function () use (&$callbackRan): void {
            $callbackRan = true;
        });

        self::assertNull($result);
        self::assertTrue($callbackRan);
    }
}
