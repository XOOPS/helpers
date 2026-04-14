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
 * Tap helper for side-effect operations in value chains.
 *
 * Calls a callback with a value, then returns the original value unchanged.
 * Useful for logging, debugging, or triggering side effects
 * within a fluent chain.
 *
 * Usage:
 *   $user = Tap::tap($user, fn($u) => $logger->info("Created user {$u->id}"));
 */
final class Tap
{
    /**
     * Call a callback with the value and return the value unchanged.
     */
    public static function tap(mixed $value, callable $callback): mixed
    {
        $callback($value);

        return $value;
    }
}
