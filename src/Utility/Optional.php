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
 * Null-safe property and method access wrapper.
 *
 * Wraps a value and proxies property access and method calls.
 * Returns null if the underlying value is null or the
 * property/method doesn't exist.
 *
 * Usage:
 *   $name = Value::optional($user)->name;       // null if $user is null
 *   $upper = Value::optional($user)->getName();  // null if $user is null
 */
final readonly class Optional
{
    public function __construct(
        private mixed $value,
    ) {}

    public function __get(string $name): mixed
    {
        if (!is_object($this->value)) {
            return null;
        }

        return $this->value->{$name} ?? null;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!is_object($this->value)) {
            return null;
        }

        if (!is_callable([$this->value, $method])) {
            return null;
        }

        return $this->value->{$method}(...$arguments);
    }

    public function __isset(string $name): bool
    {
        if (!is_object($this->value)) {
            return false;
        }

        return isset($this->value->{$name});
    }

    /**
     * Get the underlying value.
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
