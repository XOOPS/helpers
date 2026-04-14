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

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Contracts\DateTimeProviderInterface;
use Xoops\Helpers\Utility\Date;

final class DateTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::resetProvider();
    }

    public function testNow(): void
    {
        $now = Date::now();
        self::assertInstanceOf(DateTimeImmutable::class, $now);
    }

    public function testNowWithCustomProvider(): void
    {
        $fixed = new DateTimeImmutable('2025-06-15 12:00:00');
        Date::setProvider(new class ($fixed) implements DateTimeProviderInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        });

        self::assertSame('2025-06-15', Date::now()->format('Y-m-d'));
    }

    public function testRange(): void
    {
        $range = Date::range('2025-01-01', '2025-01-05');
        self::assertSame(['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04', '2025-01-05'], $range);
    }

    public function testDiff(): void
    {
        $diff = Date::diff('2025-01-01', '2025-01-10');
        self::assertSame(9, $diff->days);
    }

    public function testIsValid(): void
    {
        self::assertTrue(Date::isValid('2025-12-31'));
        self::assertFalse(Date::isValid('2025-13-01'));
        self::assertFalse(Date::isValid('not-a-date'));
    }

    public function testAddDays(): void
    {
        self::assertSame('2025-01-06', Date::addDays('2025-01-01', 5));
    }

    public function testSubDays(): void
    {
        self::assertSame('2024-12-27', Date::subDays('2025-01-01', 5));
    }

    public function testIsWeekend(): void
    {
        self::assertTrue(Date::isWeekend('2025-03-15'));  // Saturday
        self::assertTrue(Date::isWeekend('2025-03-16'));  // Sunday
        self::assertFalse(Date::isWeekend('2025-03-17')); // Monday
    }

    public function testIsTodayWithFixedProvider(): void
    {
        $today = new DateTimeImmutable('2025-06-15 12:00:00');
        Date::setProvider(new class ($today) implements DateTimeProviderInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        });

        self::assertTrue(Date::isToday('2025-06-15'));
        self::assertFalse(Date::isToday('2025-06-14'));
    }

    public function testIsPastAndFuture(): void
    {
        $fixed = new DateTimeImmutable('2025-06-15 12:00:00');
        Date::setProvider(new class ($fixed) implements DateTimeProviderInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        });

        self::assertTrue(Date::isPast('2025-06-14'));
        self::assertFalse(Date::isPast('2025-06-16'));
        self::assertTrue(Date::isFuture('2025-06-16'));
        self::assertFalse(Date::isFuture('2025-06-14'));
    }

    public function testReformat(): void
    {
        self::assertSame('15/06/2025', Date::reformat('2025-06-15', 'Y-m-d', 'd/m/Y'));
    }

    public function testAge(): void
    {
        $fixed = new DateTimeImmutable('2025-06-15 12:00:00');
        Date::setProvider(new class ($fixed) implements DateTimeProviderInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        });

        self::assertSame(25, Date::age('2000-01-01'));
    }
}
