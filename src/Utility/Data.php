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

use stdClass;

/**
 * Data conversion helpers.
 *
 * Provides conversions between arrays, objects, and
 * query string representations.
 */
final class Data
{
    /**
     * Recursively convert a value to an associative array.
     *
     * @return array<string, mixed>|mixed
     */
    public static function toArray(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $k => $v) {
                $result[$k] = self::toArray($v);
            }

            return $result;
        }

        if (is_object($value)) {
            $result = [];

            foreach (get_object_vars($value) as $k => $v) {
                $result[$k] = self::toArray($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * Recursively convert a value to a stdClass object.
     */
    public static function toObject(mixed $value): object
    {
        if (is_object($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return (object) ['value' => $value];
        }

        $obj = new stdClass();

        foreach ($value as $k => $v) {
            $obj->{$k} = is_array($v) ? self::toObject($v) : $v;
        }

        return $obj;
    }

    /**
     * Build a URL query string from an array.
     *
     * @param array<string, mixed> $data Query parameters
     */
    public static function toQueryString(array $data): string
    {
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Parse a query string into an associative array.
     *
     * @return array<string, string>
     */
    public static function fromQueryString(string $queryString): array
    {
        $result = [];
        parse_str($queryString, $result);

        return $result;
    }
}
