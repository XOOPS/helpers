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

use NumberFormatter;

/**
 * Number formatting and conversion helpers.
 *
 * Provides locale-aware formatting via intl extension with
 * graceful fallback to basic PHP formatting.
 */
final class Number
{
    /**
     * Format a number with locale-aware separators.
     *
     * @param int|float   $number   Number to format
     * @param int         $decimals Decimal places
     * @param string|null $locale   ICU locale (requires intl extension)
     */
    public static function format(int|float $number, int $decimals = 0, ?string $locale = null): string
    {
        if ($locale !== null && extension_loaded('intl')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
            $result = $formatter->format($number);

            return $result !== false ? $result : number_format($number, $decimals);
        }

        return number_format($number, $decimals);
    }

    /**
     * Format bytes into a human-readable file size string.
     *
     * @param int|float $bytes     Number of bytes
     * @param int       $precision Decimal precision
     */
    public static function fileSize(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $bytes = max(0, $bytes);

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return sprintf('%.' . $precision . 'f %s', $bytes / (1024 ** $power), $units[$power]);
    }

    /**
     * Format a number for human readability with abbreviations.
     *
     * Examples: 1500 => "1.5K", 1000000 => "1M", 2500000000 => "2.5B"
     *
     * @param int|float $number   Number to abbreviate
     * @param int       $decimals Decimal places for abbreviated form
     */
    public static function forHumans(int|float $number, int $decimals = 1): string
    {
        $abbreviations = [
            12 => 'T',
            9  => 'B',
            6  => 'M',
            3  => 'K',
            0  => '',
        ];

        foreach ($abbreviations as $exponent => $suffix) {
            if (abs($number) >= 10 ** $exponent) {
                $display = $number / (10 ** $exponent);

                return number_format($display, $exponent > 0 ? $decimals : 0) . $suffix;
            }
        }

        return number_format($number, $decimals);
    }

    /**
     * Format a number as a percentage string.
     *
     * @param int|float   $number   The percentage value (e.g. 75 for 75%)
     * @param int         $decimals Decimal places
     * @param string|null $locale   ICU locale (requires intl extension)
     */
    public static function percentage(int|float $number, int $decimals = 0, ?string $locale = null): string
    {
        if ($locale !== null && extension_loaded('intl')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::PERCENT);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
            $result = $formatter->format($number / 100);

            return $result !== false ? $result : number_format($number, $decimals) . '%';
        }

        return number_format($number, $decimals) . '%';
    }

    /**
     * Format an integer as an ordinal string (1st, 2nd, 3rd, etc.).
     *
     * @param int         $number Number to format
     * @param string|null $locale ICU locale (requires intl extension)
     */
    public static function ordinal(int $number, ?string $locale = null): string
    {
        if ($locale !== null && extension_loaded('intl')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::ORDINAL);
            $result = $formatter->format($number);

            return $result !== false ? $result : $number . self::ordinalSuffix($number);
        }

        return $number . self::ordinalSuffix($number);
    }

    /**
     * Format a number as currency.
     *
     * @param int|float $amount       Amount to format
     * @param string    $currencyCode ISO 4217 currency code (e.g. "USD")
     * @param string    $locale       ICU locale
     */
    public static function currency(int|float $amount, string $currencyCode = 'USD', string $locale = 'en_US'): string
    {
        if (extension_loaded('intl')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $result = $formatter->formatCurrency($amount, $currencyCode);

            return $result !== false ? $result : number_format($amount, 2);
        }

        return number_format($amount, 2);
    }

    /**
     * Clamp a number between a minimum and maximum value.
     */
    public static function clamp(int|float $number, int|float $min, int|float $max): int|float
    {
        return max($min, min($max, $number));
    }

    /**
     * Get the English ordinal suffix for a number.
     */
    private static function ordinalSuffix(int $number): string
    {
        $abs = abs($number);

        if (($abs % 100) >= 11 && ($abs % 100) <= 13) {
            return 'th';
        }

        return match ($abs % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}
