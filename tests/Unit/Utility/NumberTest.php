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
use Xoops\Helpers\Utility\Number;

final class NumberTest extends TestCase
{
    public function testFormat(): void
    {
        self::assertSame('1,234', Number::format(1234));
        self::assertSame('1,234.57', Number::format(1234.5678, 2));
    }

    public function testFileSize(): void
    {
        self::assertSame('0.00 B', Number::fileSize(0));
        self::assertSame('1.00 KB', Number::fileSize(1024));
        self::assertSame('1.50 MB', Number::fileSize(1572864));
        self::assertSame('2.00 GB', Number::fileSize(2147483648));
    }

    public function testForHumans(): void
    {
        self::assertSame('1.5K', Number::forHumans(1500));
        self::assertSame('2.3M', Number::forHumans(2300000));
        self::assertSame('1.0B', Number::forHumans(1000000000));
    }

    public function testPercentage(): void
    {
        self::assertSame('75%', Number::percentage(75));
        self::assertSame('99.5%', Number::percentage(99.5, 1));
    }

    public function testOrdinal(): void
    {
        self::assertSame('1st', Number::ordinal(1));
        self::assertSame('2nd', Number::ordinal(2));
        self::assertSame('3rd', Number::ordinal(3));
        self::assertSame('4th', Number::ordinal(4));
        self::assertSame('11th', Number::ordinal(11));
        self::assertSame('12th', Number::ordinal(12));
        self::assertSame('13th', Number::ordinal(13));
        self::assertSame('21st', Number::ordinal(21));
    }

    public function testClamp(): void
    {
        self::assertSame(5, Number::clamp(5, 1, 10));
        self::assertSame(1, Number::clamp(-5, 1, 10));
        self::assertSame(10, Number::clamp(15, 1, 10));
    }
}
