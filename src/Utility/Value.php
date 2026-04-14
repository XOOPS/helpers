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

use Closure;
use Countable;

/**
 * Value resolution and inspection helpers.
 *
 * Provides tools for resolving lazy values, checking emptiness,
 * null-safe access, and memoization.
 */
final class Value
{
    /**
     * Resolve a value — if it's a Closure, call it; otherwise return it.
     */
    public static function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Determine if a value is "blank" (null, empty string, empty countable).
     *
     * Numeric values and booleans are never blank.
     */
    public static function blank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }

    /**
     * Determine if a value is "filled" (not blank).
     */
    public static function filled(mixed $value): bool
    {
        return !self::blank($value);
    }

    /**
     * Wrap a value in an Optional for null-safe property and method access.
     */
    public static function optional(mixed $value): Optional
    {
        return new Optional($value);
    }

    /**
     * Execute a callback once and cache the result for subsequent calls.
     *
     * The callback is identified by its object identity (spl_object_id),
     * so the same Closure instance will return the cached result.
     */
    public static function once(Closure $callback): mixed
    {
        /** @var array<int, mixed> $cache */
        static $cache = [];

        $key = spl_object_id($callback);

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = $callback();
        }

        return $cache[$key];
    }

    /**
     * Return a MissingValue sentinel for absence detection.
     *
     * Use with Arr::get() to distinguish "key not found"
     * from "key exists but value is null".
     */
    public static function missing(): MissingValue
    {
        static $instance = null;

        return $instance ??= new MissingValue();
    }
}
