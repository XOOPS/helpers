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

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Xoops\Helpers\Contracts\DateTimeProviderInterface;
use Xoops\Helpers\Provider\SystemDateTimeProvider;

/**
 * Date and time utility helpers.
 *
 * Provides date range generation, validation, arithmetic,
 * and comparison methods. Uses DateTimeImmutable throughout.
 *
 * The DateTime source is injectable via setProvider() for testing.
 */
final class Date
{
    private static ?DateTimeProviderInterface $provider = null;

    /**
     * Inject a custom DateTime provider (useful for testing).
     */
    public static function setProvider(DateTimeProviderInterface $provider): void
    {
        self::$provider = $provider;
    }

    /**
     * Reset the provider to the system default.
     */
    public static function resetProvider(): void
    {
        self::$provider = null;
    }

    /**
     * Get the current DateTimeImmutable instance.
     */
    public static function now(): DateTimeImmutable
    {
        return self::getProvider()->now();
    }

    /**
     * Generate a range of dates between start and end (inclusive).
     *
     * @param string      $start  Start date string
     * @param string      $end    End date string
     * @param string      $step   DateInterval spec (default: "P1D" = 1 day)
     * @param string|null $format Output format, or null for DateTimeImmutable objects
     * @return array<string|DateTimeImmutable>
     */
    public static function range(string $start, string $end, string $step = 'P1D', ?string $format = 'Y-m-d'): array
    {
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);
        $interval = new DateInterval($step);

        $period = new DatePeriod($startDate, $interval, $endDate->add(new DateInterval('P1D')));
        $result = [];

        foreach ($period as $date) {
            $result[] = $format !== null ? $date->format($format) : $date;
        }

        return $result;
    }

    /**
     * Calculate the difference between two dates.
     */
    public static function diff(string $start, string $end): DateInterval
    {
        return (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end));
    }

    /**
     * Validate a date string against a format.
     */
    public static function isValid(string $date, string $format = 'Y-m-d'): bool
    {
        $parsed = DateTimeImmutable::createFromFormat($format, $date);

        return $parsed !== false && $parsed->format($format) === $date;
    }

    /**
     * Add days to a date and return formatted result.
     */
    public static function addDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $modifier = ($days >= 0 ? '+' : '') . $days . ' days';

        return (new DateTimeImmutable($date))->modify($modifier)->format($format);
    }

    /**
     * Subtract days from a date and return formatted result.
     */
    public static function subDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        return self::addDays($date, -$days, $format);
    }

    /**
     * Check if a date falls on a weekend (Saturday or Sunday).
     */
    public static function isWeekend(string $date): bool
    {
        $dayOfWeek = (int) (new DateTimeImmutable($date))->format('N');

        return $dayOfWeek >= 6;
    }

    /**
     * Check if a date is today.
     */
    public static function isToday(string $date): bool
    {
        return (new DateTimeImmutable($date))->format('Y-m-d') === self::now()->format('Y-m-d');
    }

    /**
     * Check if a date is in the past.
     */
    public static function isPast(string $date): bool
    {
        return new DateTimeImmutable($date) < self::now();
    }

    /**
     * Check if a date is in the future.
     */
    public static function isFuture(string $date): bool
    {
        return new DateTimeImmutable($date) > self::now();
    }

    /**
     * Reformat a date string from one format to another.
     */
    public static function reformat(string $date, string $fromFormat, string $toFormat): string
    {
        $parsed = DateTimeImmutable::createFromFormat($fromFormat, $date);

        if ($parsed === false) {
            return $date;
        }

        return $parsed->format($toFormat);
    }

    /**
     * Get the age in years from a birth date.
     */
    public static function age(string $birthDate): int
    {
        return (int) (new DateTimeImmutable($birthDate))->diff(self::now())->y;
    }

    private static function getProvider(): DateTimeProviderInterface
    {
        return self::$provider ??= new SystemDateTimeProvider();
    }
}
