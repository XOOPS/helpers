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

/**
 * Value transformation helpers.
 *
 * Provides conditional transformation and predicate-based
 * branching for clean, readable value processing.
 */
final class Transform
{
    /**
     * Transform a value if it is "filled" (not blank).
     *
     * Returns $default if the value is blank.
     *
     * @param mixed    $value    Value to check
     * @param callable $callback Transformation to apply if filled
     * @param mixed    $default  Default value or Closure if blank
     */
    public static function transform(mixed $value, callable $callback, mixed $default = null): mixed
    {
        if (Value::blank($value)) {
            return Value::value($default);
        }

        return $callback($value);
    }

    /**
     * Apply a callback or return a fallback based on a predicate.
     *
     * @param mixed         $value     Value to test and pass to callbacks
     * @param callable      $predicate Predicate function
     * @param callable      $callback  Applied when predicate returns true
     * @param mixed         $else      Default value or Closure when predicate returns false
     */
    public static function when(mixed $value, callable $predicate, callable $callback, mixed $else = null): mixed
    {
        return $predicate($value) ? $callback($value) : Value::value($else);
    }
}
