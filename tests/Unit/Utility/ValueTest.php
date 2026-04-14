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
use Xoops\Helpers\Utility\MissingValue;
use Xoops\Helpers\Utility\Optional;
use Xoops\Helpers\Utility\Value;

final class ValueTest extends TestCase
{
    public function testValueReturnsRawValue(): void
    {
        self::assertSame(42, Value::value(42));
    }

    public function testValueResolvesClosure(): void
    {
        self::assertSame(42, Value::value(fn() => 42));
    }

    public function testValuePassesArgsToClosure(): void
    {
        $result = Value::value(fn($a, $b) => $a + $b, 3, 4);
        self::assertSame(7, $result);
    }

    public function testBlank(): void
    {
        self::assertTrue(Value::blank(null));
        self::assertTrue(Value::blank(''));
        self::assertTrue(Value::blank('  '));
        self::assertTrue(Value::blank([]));

        self::assertFalse(Value::blank(0));
        self::assertFalse(Value::blank(false));
        self::assertFalse(Value::blank('text'));
        self::assertFalse(Value::blank([1]));
    }

    public function testFilled(): void
    {
        self::assertTrue(Value::filled('text'));
        self::assertTrue(Value::filled(0));
        self::assertFalse(Value::filled(null));
        self::assertFalse(Value::filled(''));
    }

    public function testOptionalReturnsOptionalInstance(): void
    {
        $opt = Value::optional(null);
        self::assertInstanceOf(Optional::class, $opt);
    }

    public function testOptionalPropertyAccessOnNull(): void
    {
        self::assertNull(Value::optional(null)->name);
    }

    public function testOptionalPropertyAccessOnObject(): void
    {
        $obj = (object) ['name' => 'XOOPS'];
        self::assertSame('XOOPS', Value::optional($obj)->name);
    }

    public function testOptionalMethodCallOnNull(): void
    {
        self::assertNull(Value::optional(null)->someMethod());
    }

    public function testOptionalMethodCallOnNonObjectReturnsNull(): void
    {
        self::assertNull(Value::optional([])->someMethod());
        self::assertNull(Value::optional(123)->someMethod());
    }

    public function testMissingReturnsSentinel(): void
    {
        self::assertInstanceOf(MissingValue::class, Value::missing());
    }

    public function testMissingIsSingleton(): void
    {
        self::assertSame(Value::missing(), Value::missing());
    }

    public function testOnce(): void
    {
        $counter = 0;
        $callback = function () use (&$counter) {
            $counter++;
            return 'result';
        };

        self::assertSame('result', Value::once($callback));
        self::assertSame('result', Value::once($callback));
        self::assertSame(1, $counter);
    }
}
