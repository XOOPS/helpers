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

use Throwable;

/**
 * Conditional exception throwing helpers.
 *
 * Provides guard-clause style exception throwing that reads
 * more clearly than if/throw blocks in validation code.
 *
 * Usage:
 *   ThrowHelper::throwIf($id < 1, \InvalidArgumentException::class, 'ID must be positive');
 *   ThrowHelper::throwUnless($user->isAdmin(), \RuntimeException::class, 'Access denied');
 */
final class ThrowHelper
{
    /**
     * Throw an exception if the condition is true.
     *
     * @param bool   $condition      Condition to evaluate
     * @param string $exceptionClass Fully qualified exception class name
     * @param mixed  ...$args        Constructor arguments for the exception
     *
     * @throws Throwable
     */
    public static function throwIf(bool $condition, string $exceptionClass, mixed ...$args): void
    {
        if ($condition) {
            throw new $exceptionClass(...$args);
        }
    }

    /**
     * Throw an exception unless the condition is true.
     *
     * @param bool   $condition      Condition to evaluate
     * @param string $exceptionClass Fully qualified exception class name
     * @param mixed  ...$args        Constructor arguments for the exception
     *
     * @throws Throwable
     */
    public static function throwUnless(bool $condition, string $exceptionClass, mixed ...$args): void
    {
        if (!$condition) {
            throw new $exceptionClass(...$args);
        }
    }
}
